<?php

    /**
     * Wrapper class for common Facebook functions, maintaining some compatibility with the old
     * Facebook SDK
     */

    namespace IdnoPlugins\Facebook {

        use \Facebook;

        class FacebookAPI {

            public $session = false;

            function setAccessToken($token)
            {

                try {
                    $session = new \Facebook\FacebookSession($token);
                    $this->session = $session;
                    return $session;
                } catch (\Exception $e) {
                    \Idno\Core\site()->session()->addMessage("Your Facebook session seems to have expired. You need to <a href=\"".\Idno\Core\site()->config()->getDisplayURL()."account/facebook/\">re-authenticate</a>.");
                }
                return false;

            }

            /**
             * Get the URL to authenticate with Facebook
             * @param array $params
             * @return string
             */
            function getLoginUrl($params = []) {

                $redirect_url = \Idno\Core\site()->config()->getDisplayURL() . 'facebook/callback';

                $helper = new Facebook\FacebookRedirectLoginHelper($redirect_url);
                return $helper->getLoginUrl(['public_profile','email','manage_pages']);

            }

            /**
             * Get the Facebook session on redirect
             * @return bool|Facebook\FacebookSession
             */
            function getSessionOnLogin() {

                $helper = new Facebook\FacebookRedirectLoginHelper(\Idno\Core\site()->config()->getDisplayURL() . 'facebook/callback');
                try {
                    return $helper->getSessionFromRedirect();
                } catch (\Exception $e) {
                    return false;
                }

            }

            /**
             * Make an API call
             * @param $endpoint
             * @param $verb
             * @param $params
             * @return array|bool
             */
            function api($endpoint, $verb = 'GET', $params = null) {

                if (empty($this->session)) {
                    return false;
                }
                try {
                    $response = (new Facebook\FacebookRequest($this->session, $verb, $endpoint, $params))->execute()->getGraphObject();
                    $result = array('id' => $response->getProperty('id'), 'response' => $response);
                    return $result;
                } catch (\Exception $e) {
                    \Idno\Core\site()->session()->addMessage($e->getMessage());
                    return false;
                }

            }

        }

    }