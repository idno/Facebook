<?php

    namespace IdnoPlugins\Facebook {

        use Idno\Core\Idno;
        use Idno\Core\Webservice;

        class Main extends \Idno\Common\Plugin
        {

            public $endpoint = 'me';

            function registerPages()
            {
                // Deauth URL
                Idno::site()->addPageHandler('facebook/deauth', '\IdnoPlugins\Facebook\Pages\Deauth');
                // Register the callback URL
                Idno::site()->addPageHandler('facebook/callback', '\IdnoPlugins\Facebook\Pages\Callback');
                // Register admin settings
                Idno::site()->addPageHandler('admin/facebook', '\IdnoPlugins\Facebook\Pages\Admin');
                // Register settings page
                Idno::site()->addPageHandler('account/facebook', '\IdnoPlugins\Facebook\Pages\Account');

                /** Template extensions */
                // Add menu items to account & administration screens
                Idno::site()->template()->extendTemplate('admin/menu/items', 'admin/facebook/menu');
                Idno::site()->template()->extendTemplate('account/menu/items', 'account/facebook/menu');
                Idno::site()->template()->extendTemplate('onboarding/connect/networks', 'onboarding/connect/facebook');
            }

            function registerEventHooks()
            {

                Idno::site()->syndication()->registerService('facebook', function () {
                    return $this->hasFacebook();
                }, array('note', 'article', 'image', 'media','rsvp', 'bookmark'));

                Idno::site()->addEventHook('user/auth/success', function (\Idno\Core\Event $event) {
                    if ($this->hasFacebook()) {
                        if (is_array(Idno::site()->session()->currentUser()->facebook)) {
                            foreach(Idno::site()->session()->currentUser()->facebook as $username => $details) {
                                if ($username != 'access_token') {
                                    if (empty($details['expiry']) || ($details['expiry'] > time())) {
                                        Idno::site()->syndication()->registerServiceAccount('facebook', $username, $details['name']);
                                    }
                                } else {
                                    Idno::site()->syndication()->registerServiceAccount('facebook', $username, 'Facebook');
                                }
                            }
                        }
                    }
                });

                $notes_function = function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    if ($this->hasFacebook()) {
                        $object      = $eventdata['object'];
                        $facebookAPI = $this->getFacebookAPI($eventdata['syndication_account']);
                        $name        = $this->getDisplayName($eventdata['syndication_account']);
                        if (!empty($facebookAPI)) {
                            $message = preg_replace('/<[^\>]*>/', '', $object->getDescription()); //strip_tags($object->getDescription());

                            $message = html_entity_decode($message);

                            // Obey the IndieWeb reference setting
                            if (!substr_count($message, Idno::site()->config()->host) && Idno::site()->config()->indieweb_reference) {
                                $message .= "\n\n(" . $object->getShortURL(true, false) . ")";
                            }

                            if (!empty($message) && substr($message, 0, 1) != '@') {
                                $params = array(
                                    'message' => $message,
                                );
                                if (preg_match('/(?<!=)(?<!["\'])((ht|f)tps?:\/\/[^\s\r\n\t<>"\'\(\)]+)/i', $message, $matches)) {
                                    $params['link'] = $matches[0]; // Set the first discovered link as the match
                                    $params['message'] = str_replace($params['link'],'',$params['message']);
                                    foreach(['youtube.com','youtu.be','vimeo.com'] as $video_domain) {
                                        if (substr_count(strtolower($params['link']), $video_domain)) {
                                            unset($params['actions']);  // Facebook doesn't like "actions" to co-exist with video links
                                        }
                                    }
                                }
                                try {
                                    $this->warmFacebookCache($object->getURL());
                                    $result = $facebookAPI->api('/'.$this->endpoint.'/feed', 'POST', $params);
                                    if (!empty($result['id'])) {
                                        $result['id'] = str_replace('_', '/posts/', $result['id']);
                                        $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id'], $name, "", $name);
                                        $object->save();
                                    } else {
                                        error_log("Nothing was posted to Facebook: " . var_export($result,true));
                                    }
                                } catch (\Exception $e) {
                                    error_log('There was a problem posting to Facebook: ' . $e->getMessage());
                                    Idno::site()->session()->addMessage('There was a problem posting to Facebook: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                };

                // Push "notes" to Facebook
                Idno::site()->addEventHook('post/note/facebook', $notes_function);
                Idno::site()->addEventHook('post/bookmark/facebook', $notes_function);

                // Push "articles" to Facebook
                Idno::site()->addEventHook('post/article/facebook', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    if ($this->hasFacebook()) {
                        $object      = $eventdata['object'];
                        $facebookAPI = $this->getFacebookAPI($eventdata['syndication_account']);
                        $name        = $this->getDisplayName($eventdata['syndication_account']);
                        if (!empty($facebookAPI)) {
                            try {
                                $this->warmFacebookCache($object->getURL());
                                $result = $facebookAPI->api('/'.$this->endpoint.'/feed', 'POST',
                                    array(
                                        'link'    => $object->getURL(),
                                        'message' => $message = html_entity_decode($object->getTitle()),
                                    ));
                                if (!empty($result['id'])) {
                                    $result['id'] = str_replace('_', '/posts/', $result['id']);
                                    $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id'], $name, "", $name);
                                    $object->save();
                                } else {
                                    error_log("Nothing was posted to Facebook: " . var_export($result,true));
                                }
                            } catch (\Exception $e) {
                                error_log('There was a problem posting to Facebook: ' . $e->getMessage());
                                Idno::site()->session()->addMessage('There was a problem posting to Facebook: ' . $e->getMessage());
                            }
                        }
                    }
                });

                // Push RSVPs to Facebook
                Idno::site()->addEventHook('post/rsvp/facebook', function (\Idno\Core\Event $event) {
                    Idno::site()->logging()->debug("publishing RSVP to Facebook");

                    $eventdata   = $event->data();
                    $object      = $eventdata['object'];
                    $facebookAPI = $this->getFacebookAPI($eventdata['syndication_account']);
                    $name        = $this->getDisplayName($eventdata['syndication_account']);
                    if ($facebookAPI) {

                        $eventRegex = '#https?://(?:www\.|m\.)?facebook.com/events/(\d+)/?#';
                        $eventUrl   = $this->possePostDiscovery($object->inreplyto, $eventRegex);

                        if ($eventUrl && preg_match($eventRegex, $eventUrl, $matches)) {
                            $eventId = $matches[1];

                            $endpoint = false;
                            if ($object->rsvp === 'yes') {
                                $endpoint = "/$eventId/attending";
                            } else if ($object->rsvp === 'no') {
                                $endpoint = "/$eventId/declined";
                            } else if ($object->rsvp === 'maybe') {
                                $endpoint = "/$eventId/maybe";
                            }

                            if ($endpoint) {
                                Idno::site()->logging()->debug("publishing rsvp to Facebook on $endpoint");
                                $response = $facebookAPI->api($endpoint, 'POST');
                                Idno::site()->logging()->debug("publish response from Facebook", ['response' => $response]);
                            }
                        }
                    }
                });

                // Push "media" to Facebook
                Idno::site()->addEventHook('post/media/facebook', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    if ($this->hasFacebook()) {
                        $object      = $eventdata['object'];
                        $facebookAPI = $this->getFacebookAPI($eventdata['syndication_account']);
                        $name        = $this->getDisplayName($eventdata['syndication_account']);
                        if (!empty($facebookAPI)) {
                            try {
                                $result = $facebookAPI->api('/'.$this->endpoint.'/feed', 'POST',
                                    array(
                                        'link'    => $object->getURL(),
                                        'message' => $message = html_entity_decode($object->getTitle()),
                                    ));
                                if (!empty($result['id'])) {
                                    $result['id'] = str_replace('_', '/posts/', $result['id']);
                                    $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id'], $name, "", $name);
                                    $object->save();
                                } else {
                                    error_log("Nothing was posted to Facebook: " . var_export($result,true));
                                }
                            } catch (\Exception $e) {
                                error_log('There was a problem posting to Facebook: ' . $e->getMessage());
                                Idno::site()->session()->addMessage('There was a problem posting to Facebook: ' . $e->getMessage());
                            }
                        }
                    }
                });

                // Push "images" to Facebook
                Idno::site()->addEventHook('post/image/facebook', function (\Idno\Core\Event $event) {
                    $eventdata = $event->data();
                    $object = $eventdata['object'];
                    if ($attachments = $object->getAttachments()) {
                        foreach ($attachments as $attachment) {
                            if ($this->hasFacebook()) {
                                $facebookAPI = $this->getFacebookAPI($eventdata['syndication_account']);
                                $name        = $this->getDisplayName($eventdata['syndication_account']);
                                if (!empty($facebookAPI)) {
                                    $message = strip_tags($object->getTitle()) . "\n\n" . strip_tags($object->getDescription());
                                    $message = html_entity_decode($message);
                                    // Strip out "Untitled"
                                    $message = str_replace("Untitled\n\n",'',$message);
                                    $message .= "\n\nOriginal: " . $object->getURL();
                                    try {
                                        //$facebookAPI->setFileUploadSupport(true);
                                        $response = $facebookAPI->api(
                                            '/'.$this->endpoint.'/photos/',
                                            'post',
                                            array(
                                                'message' => $message,
                                                'url'     => $attachment['url'],
                                            )
                                        );
                                        if (!empty($response['id'])) {
                                            $result['id'] = str_replace('_', '/photos/', $response['id']);
                                            $object->setPosseLink('facebook', 'https://facebook.com/' . $response['id'], $name, "", $name);
                                            $object->save();
                                        } else {
                                            error_log("Nothing was posted to Facebook: " . var_export($result,true));
                                        }
                                    } catch (\FacebookApiException $e) {
                                        error_log('Could not post image to Facebook: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                });
            }


            private function getFacebookAPI($syndicationAccount)
            {
                if ($this->hasFacebook()) {
                    if (!empty($syndicationAccount)) {
                        return $this->connect($syndicationAccount);
                    }
                    return  $this->connect();
                }
                return false;
            }

            private function getDisplayName($syndicationAccount)
            {
                if (!empty($syndicationAccount)
                        && !empty(Idno::site()->session()->currentUser()->facebook[$syndicationAccount]['name'])) {
                    return Idno::site()->session()->currentUser()->facebook[$syndicationAccount]['name'];
                }
                return 'Facebook';
            }

            /**
             * Given an array of original URLs and a permalink regex,
             * looks for silo-specific syndication URLs. If the
             * original is a silo url, that url is returned; otherwise
             * we fetch the source and attempt to look for
             * rel-syndication and u-syndication URLs.
             *
             * TODO merge this with Webmention::addSyndicatedReplyTargets?
             *
             * @param array $originals the original URLs to fetch and
             *  search for syndication links
             * @param string $regex the regex that matches syndication
             *  links for this source
             * @return a string matching the regex or false
             */
            private function possePostDiscovery($originals, $regex)
            {
                $originals = (array) $originals;

                foreach ($originals as $original) {
                    if (preg_match($regex, $original)) {
                        Idno::site()->logging()->debug("Found a matching url in the original list: $original");
                        return $original;
                    }
                }

                foreach ($originals as $original) {
                    Idno::site()->logging()->debug("Fetching $original to look for syndication links");
                    $resp   = Webservice::get($original);
                    if ($resp['response'] >= 200 && $resp['response'] < 300) {
                        $d = (new \Mf2\Parser($resp['content'], $original))->parse();
                        $urls = [];
                        if (!empty($d['rels']['syndication'])) {
                            $urls = array_merge($urls, $d['rels']['syndication']);
                        }
                        if (!empty($d['items'])) {
                            foreach ($d['items'] as $item) {
                                if ((in_array('h-entry', $item['type']) || in_array('h-event', $item['type']))
                                        && !empty($item['properties']['syndication'])) {
                                    $urls = array_merge($urls, $item['properties']['syndication']);
                                }
                            }
                        }
                        foreach ($urls as $url) {
                            if (preg_match($regex, $url)) {
                                Idno::site()->logging()->debug("Discovered a matching url in the syndication links $url");
                                return $url;
                            }
                        }
                    } else {
                        Idno::site()->logging()->warning("Failed to fetch $original: status={$resp['response']}, error={$resp['error']}");
                    }
                }

                return false;
            }

            /**
             * Retrieve the URL to authenticate with Facebook
             * @return string
             */
            function getAuthURL()
            {
                $facebook = $this;
                if ($facebookAPI = $facebook->connect()) {
                    return $facebookAPI->getLoginUrl();
                }
                return '';
            }

            /**
             * Connect to Facebook
             * @return bool|FacebookAPI
             */
            function connect($account_id = '')
            {
                if (!empty(Idno::site()->config()->facebook)) {

                    require_once(dirname(__FILE__) . '/external/facebook-sdk/autoload.php');
                    \Facebook\FacebookSession::setDefaultApplication(
                        Idno::site()->config()->facebook['appId'],
                        Idno::site()->config()->facebook['secret']
                    );

                    $facebookAPI = new FacebookAPI();
                    if (!empty($account_id)) {
                        if (!empty(Idno::site()->session()->currentUser()->facebook[$account_id])) {
                            if ($account_id == 'Facebook' || $account_id == 'access_token') {
                                $facebookAPI->setAccessToken(Idno::site()->session()->currentUser()->facebook['access_token']);
                            } else {
                                $facebookAPI->setAccessToken(Idno::site()->session()->currentUser()->facebook[$account_id]['access_token']);
                                $this->endpoint = $account_id;
                            }
                            return $facebookAPI;
                        } else {
                            if ($account_id == 'Facebook' && !empty(Idno::site()->session()->currentUser()->facebook['access_token'])) {
                                $facebookAPI->setAccessToken(Idno::site()->session()->currentUser()->facebook['access_token']);
                            }
                        }
                    } else {
                        if (!empty(Idno::site()->session()->currentUser()->facebook['access_token'])) {
                            $facebookAPI->setAccessToken(Idno::site()->session()->currentUser()->facebook['access_token']);
                        }
                        return $facebookAPI;    // This needs to return even if we haven't set the user token yet, for the auth callback
                    }

                }

                return false;
            }

            /**
             * Facebook is silly and needs to have its cache warmed up before you post.
             * @param $url
             */
            function warmFacebookCache($url)
            {
                $client = new Webservice();
                $result = $client->post('https://graph.facebook.com/',['id' => $url, 'scrape' => 'true']);
                error_log('Facebook cache result: ' . json_encode($result));
            }

            /**
             * Can the current user use Twitter?
             * @return bool
             */
            function hasFacebook()
            {
                if (!Idno::site()->session()->currentUser()) {
                    return false;
                }
                return Idno::site()->session()->currentUser()->facebook;
            }

        }

    }
