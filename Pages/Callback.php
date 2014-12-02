<?php

    /**
     * Facebook pages
     */

    namespace IdnoPlugins\Facebook\Pages {

        /**
         * Default class to serve the Facebook callback
         */
        class Callback extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($facebook = \Idno\Core\site()->plugins()->get('Facebook')) {
                    if ($facebookAPI = $facebook->connect()) {
                        if ($session = $facebookAPI->getSessionOnLogin()) {
                            $user = \Idno\Core\site()->session()->currentUser();
                            $access_token = $session->getToken();
                            $user->facebook = array('access_token' => $access_token);
                            $user->save();
                            \Idno\Core\site()->session()->addMessage('Your Facebook account was connected.');
                        }
                    }
                }
                if (!empty($_SESSION['onboarding_passthrough'])) {
                    unset($_SESSION['onboarding_passthrough']);
                    $this->forward(\Idno\Core\site()->config()->getURL() . 'begin/connect-forwarder');
                }
                $this->forward('/account/facebook/');
            }

        }

    }