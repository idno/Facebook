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
                            if (!empty($message) && substr($message,0,1) != '@') {
                                $facebookAPI->api('/me/feed', 'POST',
                                    array(
                                        'message' => strip_tags($object->getDescription())
                                    ));
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
                            $facebookAPI->api('/me/feed', 'POST',
                                array(
                                    'link' => $object->getURL(),
                                    'message' => $object->getTitle()
                                ));
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