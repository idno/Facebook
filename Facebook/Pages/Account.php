<?php

    /**
     * Facebook pages
     */

    namespace IdnoPlugins\Facebook\Pages {

        /**
         * Default class to serve Facebook-related account settings
         */
        class Account extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($facebook = \Idno\Core\site()->plugins()->get('Facebook')) {
                    if (!$facebook->hasFacebook()) {
                        if ($facebookAPI = $facebook->connect()) {
                            $login_url = $facebookAPI->getLoginUrl(array(
                                'scope' => 'publish_stream,offline_access',
                                'redirect_uri' => \Idno\Core\site()->config()->url . 'facebook/callback',
                                'cancel_url' => \Idno\Core\site()->config()->url . 'account/facebook/',
                            ));
                        }
                    } else {
                        $login_url = '';
                    }
                }
                $t = \Idno\Core\site()->template();
                $body = $t->__(['login_url' => $login_url])->draw('account/facebook');
                $t->__(['title' => 'Facebook', 'body' => $body])->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                if (($this->getInput('remove'))) {
                    $user = \Idno\Core\site()->session()->currentUser();
                    $user->facebook = [];
                    $user->save();
                    \Idno\Core\site()->session()->addMessage('Your Facebook settings have been removed from your account.');
                }
                $this->forward('/account/facebook/');
            }

        }

    }