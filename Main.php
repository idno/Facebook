<?php

    namespace IdnoPlugins\Facebook {

        class Main extends \Idno\Common\Plugin
        {

            function registerPages()
            {
                // Register the callback URL
                \Idno\Core\site()->addPageHandler('facebook/callback', '\IdnoPlugins\Facebook\Pages\Callback');
                // Register admin settings
                \Idno\Core\site()->addPageHandler('admin/facebook', '\IdnoPlugins\Facebook\Pages\Admin');
                // Register settings page
                \Idno\Core\site()->addPageHandler('account/facebook', '\IdnoPlugins\Facebook\Pages\Account');

                /** Template extensions */
                // Add menu items to account & administration screens
                \Idno\Core\site()->template()->extendTemplate('admin/menu/items', 'admin/facebook/menu');
                \Idno\Core\site()->template()->extendTemplate('account/menu/items', 'account/facebook/menu');
                \Idno\Core\site()->template()->extendTemplate('onboarding/connect/networks', 'onboarding/connect/facebook');
            }

            function registerEventHooks()
            {

                \Idno\Core\site()->syndication()->registerService('facebook', function () {
                    return $this->hasFacebook();
                }, ['note', 'article', 'image', 'media','rsvp']);

                // Push "notes" to Facebook
                \Idno\Core\site()->addEventHook('post/note/facebook', function (\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasFacebook()) {
                        if ($facebookAPI = $this->connect()) {
                            $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                            $message = preg_replace('/<[^\>]*>/', '', $object->getDescription()); //strip_tags($object->getDescription());

                            // Obey the IndieWeb reference setting
                            if (!substr_count($message, \Idno\Core\site()->config()->host) && \Idno\Core\site()->config()->indieweb_reference) {
                                $message .= "\n\n(" . $object->getShortURL(true, false) . ")";
                            }

                            if (!empty($message) && substr($message, 0, 1) != '@') {
                                $params = array(
                                    'message' => $message,
                                    'actions' => array(
                                        'name' => 'See Original',
                                        'link' => $object->getURL()
                                    )
                                );
                                if (preg_match('/(?<!=)(?<!["\'])((ht|f)tps?:\/\/[^\s\r\n\t<>"\'\(\)]+)/i', $message, $matches)) {
                                    $params['link'] = $matches[0]; // Set the first discovered link as the match
                                }
                                try {
                                    $result = $facebookAPI->api('/me/feed', 'POST', $params);
                                    if (!empty($result['id'])) {
                                        $result['id'] = str_replace('_', '/posts/', $result['id']);
                                        $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id']);
                                        $object->save();
                                    }
                                } catch (\Exception $e) {
                                    error_log('There was a problem posting to Facebook: ' . $e->getMessage());
                                    \Idno\Core\site()->session()->addMessage('There was a problem posting to Facebook: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                });

                $article_function = function (\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasFacebook()) {
                        if ($facebookAPI = $this->connect()) {
                            $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                            $result = $facebookAPI->api('/me/feed', 'POST',
                                array(
                                    'link'    => $object->getURL(),
                                    'message' => $object->getTitle(),
                                    'actions' => array(
                                        'name' => 'See Original',
                                        'link' => $object->getURL()
                                    )
                                ));
                            if (!empty($result['id'])) {
                                $result['id'] = str_replace('_', '/posts/', $result['id']);
                                $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id']);
                                $object->save();
                            }
                        }
                    }
                };

                // Push "articles" and "rsvps" to Facebook
                \Idno\Core\site()->addEventHook('post/rsvp/facebook', $article_function);
                \Idno\Core\site()->addEventHook('post/article/facebook', $article_function);

                // Push "media" to Facebook
                \Idno\Core\site()->addEventHook('post/media/facebook', function (\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasFacebook()) {
                        if ($facebookAPI = $this->connect()) {
                            $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                            $result = $facebookAPI->api('/me/feed', 'POST',
                                array(
                                    'link'    => $object->getURL(),
                                    'message' => $object->getTitle(),
                                    'actions' => array(
                                        'name' => 'See Original',
                                        'link' => $object->getURL()
                                    )
                                ));
                            if (!empty($result['id'])) {
                                $result['id'] = str_replace('_', '/posts/', $result['id']);
                                $object->setPosseLink('facebook', 'https://facebook.com/' . $result['id']);
                                $object->save();
                            }
                        }
                    }
                });

                // Push "images" to Facebook
                \Idno\Core\site()->addEventHook('post/image/facebook', function (\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($attachments = $object->getAttachments()) {
                        foreach ($attachments as $attachment) {
                            if ($this->hasFacebook()) {
                                if ($facebookAPI = $this->connect()) {
                                    $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                                    $message = strip_tags($object->getDescription());
                                    $message .= "\n\nOriginal: " . $object->getURL();
                                    try {
                                        $facebookAPI->setFileUploadSupport(true);
                                        $response = $facebookAPI->api(
                                            '/me/photos/',
                                            'post',
                                            array(
                                                'message' => $message,
                                                'url'     => $attachment['url'],
                                                'actions' => array(
                                                    'name' => 'See Original',
                                                    'link' => $object->getURL()
                                                )
                                            )
                                        );
                                        if (!empty($response['id'])) {
                                            $result['id'] = str_replace('_', '/photos/', $response['id']);
                                            $object->setPosseLink('facebook', 'https://facebook.com/' . $response['id']);
                                            $object->save();
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

            /**
             * Retrieve the URL to authenticate with Facebook
             * @return string
             */
            function getAuthURL()
            {
                $facebook = $this;
                //if (!$facebook->hasFacebook()) {
                if ($facebookAPI = $facebook->connect()) {
                    $login_url = $facebookAPI->getLoginUrl(array(
                        'scope'        => 'publish_actions,publish_stream,offline_access',
                        'redirect_uri' => \Idno\Core\site()->config()->url . 'facebook/callback',
                        'cancel_url'   => \Idno\Core\site()->config()->url . 'account/facebook/',
                    ));
                }
                //} else {
                //    $login_url = '';
                //}
                return $login_url;
            }

            /**
             * Connect to Facebook
             * @return bool|\Facebook
             */
            function connect()
            {
                if (!empty(\Idno\Core\site()->config()->facebook)) {
                    require_once(dirname(__FILE__) . '/external/facebook-php-sdk/src/facebook.php');
                    $facebook = new \Facebook([
                        'appId'  => \Idno\Core\site()->config()->facebook['appId'],
                        'secret' => \Idno\Core\site()->config()->facebook['secret'],
                        'cookie' => true
                    ]);

                    return $facebook;
                }

                return false;
            }

            /**
             * Can the current user use Twitter?
             * @return bool
             */
            function hasFacebook()
            {
                return \Idno\Core\site()->session()->currentUser()->facebook;
            }

        }

    }
