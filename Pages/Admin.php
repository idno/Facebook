<?php

    /**
     * Facebook pages
     */

    namespace IdnoPlugins\Facebook\Pages {

        /**
         * Default class to serve Facebook settings in administration
         */
        class Admin extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t = \Idno\Core\site()->template();
                $body = $t->draw('admin/facebook');
                $t->__(array('title' => 'Facebook', 'body' => $body))->drawPage();
            }

            function postContent() {
                $this->adminGatekeeper(); // Admins only
                $appId = $this->getInput('appId');
                $secret = $this->getInput('secret');
                \Idno\Core\site()->config->config['facebook'] = array(
                    'appId' => $appId,
                    'secret' => $secret
                );
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Your Facebook application details were saved.');
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/facebook/');
            }

        }

    }