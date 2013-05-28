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
                        if ($fb_user = $facebookAPI->getUser()) {
                            $access_token = $facebookAPI->getAccessToken();
                            $user = \Idno\Core\site()->session()->currentUser();
                            $user->facebook = ['access_token' => $access_token];
                            $user->save();
                            \Idno\Core\site()->session()->addMessage('Your Facebook account was connected.');
                        }
                    }
                }
                $this->forward('/account/facebook/');
            }

        }

    }