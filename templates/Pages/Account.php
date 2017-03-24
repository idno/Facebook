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
                    $login_url = $facebook->getAuthURL();
                }
                $t = \Idno\Core\site()->template();
                $body = $t->__(array('login_url' => $login_url))->draw('account/facebook');
                $t->__(array('title' => 'Facebook', 'body' => $body))->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                if (($this->getInput('remove'))) {
                    $user = \Idno\Core\site()->session()->currentUser();
                    $user->facebook = array();
                    $user->save();
                    \Idno\Core\site()->session()->addMessage('Your Facebook settings have been removed from your account.');
                }
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/facebook/');
            }

        }

    }