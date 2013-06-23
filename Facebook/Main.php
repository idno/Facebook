<?php

    namespace IdnoPlugins\Facebook {

        class Main extends \Idno\Common\Plugin {

            function registerPages() {
                // Register the callback URL
                    \Idno\Core\site()->addPageHandler('facebook/callback','\IdnoPlugins\Facebook\Pages\Callback');
                // Register admin settings
                    \Idno\Core\site()->addPageHandler('admin/facebook','\IdnoPlugins\Facebook\Pages\Admin');
                // Register settings page
                    \Idno\Core\site()->addPageHandler('account/facebook','\IdnoPlugins\Facebook\Pages\Account');

                /** Template extensions */
                // Add menu items to account & administration screens
                    \Idno\Core\site()->template()->extendTemplate('admin/menu/items','admin/facebook/menu');
                    \Idno\Core\site()->template()->extendTemplate('account/menu/items','account/facebook/menu');
            }

            function registerEventHooks() {
                // Push "notes" to Facebook
                \Idno\Core\site()->addEventHook('post/note',function(\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasFacebook()) {
                        if ($facebookAPI = $this->connect()) {
                            $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                            $message = strip_tags($object->getDescription());
                            $message .= "\n\n" . $object->getURL();
                            if (!empty($message) && substr($message,0,1) != '@') {
                                $params = array(
                                    'message' => $message
                                );
                                if (preg_match('/(?<!=)(?<!["\'])((ht|f)tps?:\/\/[^\s\r\n\t<>"\'\(\)]+)/i',$message,$matches)) {
                                    $params['link'] = $matches[0];  // Set the first discovered link as the match
                                }
                                try {
                                    $result = $facebookAPI->api('/me/feed', 'POST', $params);
                                    if (!empty($result['id'])) {
										$object->setPosseLink('facebook','https://facebook.com/' . $result['id']);
										$object->save();
									}
                                } catch (\Exception $e) {
                                    \Idno\Core\site()->session()->addMessage('There was a problem posting to Facebook: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                });

                // Push "articles" to Facebook
                \Idno\Core\site()->addEventHook('post/article',function(\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($this->hasFacebook()) {
                        if ($facebookAPI = $this->connect()) {
                            $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                            $result = $facebookAPI->api('/me/feed', 'POST',
                                array(
                                    'link' => $object->getURL(),
                                    'message' => $object->getTitle()
                                ));
                            if (!empty($result['id'])) {
								$object->setPosseLink('facebook','https://facebook.com/' . $response['id']);
								$object->save();
							}
                        }
                    }
                });

                // Push "images" to Facebook
                \Idno\Core\site()->addEventHook('post/image',function(\Idno\Core\Event $event) {
                    $object = $event->data()['object'];
                    if ($attachments = $object->getAttachments()) {
                        foreach($attachments as $attachment) {
                            if ($this->hasFacebook()) {
                                if ($facebookAPI = $this->connect()) {
                                    $facebookAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->facebook['access_token']);
                                    $message = strip_tags($object->getDescription());
									$message .= "\n\n" . $object->getURL();
                                    try {
                                        $facebookAPI->setFileUploadSupport(true);
                                        $response = $facebookAPI->api(
                                            '/me/photos/',
                                            'post',
                                            array(
                                                'message' => $message,
                                                'url' => $attachment['url']
                                            )
                                        );
                                        if (!empty($response['id'])) {
                                        	$object->setPosseLink('facebook','https://facebook.com/' . $response['id']);
                                        	$object->save();
                                        }
                                    }
                                    catch (\FacebookApiException $e) {
                                        error_log('Could not post image to Facebook: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                });
            }

            /**
             * Connect to Facebook
             * @return bool|\Facebook
             */
            function connect() {
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
            function hasFacebook() {
                if (\Idno\Core\site()->session()->currentUser()->facebook) {
                    return true;
                }
                return false;
            }

        }

    }
