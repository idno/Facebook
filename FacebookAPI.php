<?php

    /**
     * Wrapper class for common Facebook functions, maintaining some compatibility with the old
     * Facebook SDK
     */

    namespace IdnoPlugins\Facebook {

        use \Facebook;

        class FacebookAPI {

            public $session = false; /* @var \Facebook\Facebook $session */

            function __construct()
            {
                $fb = new \Facebook\Facebook([
                    'app_id'     => \Idno\Core\Idno::site()->config()->facebook['appId'],
                    'app_secret' => \Idno\Core\Idno::site()->config()->facebook['secret'],
                    'default_graph_version' => 'v2.6',
                ]);
                $this->session = $fb;
            }

            function setAccessToken($token)
            {
                try {
                    $session = $this->session;
                    $session->setDefaultAccessToken($token);
                    $this->session = $session;
                    return $session;
                } catch (\Exception $e) {
                    var_export($token);
                    echo $e->getMessage(); exit;
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

                $session = $this->session; /* @var \Facebook\Facebook $session */
                $helper = $session->getRedirectLoginHelper();
                return $helper->getLoginUrl($redirect_url, ['publish_pages', 'public_profile','email','manage_pages','publish_actions', 'rsvp_event']);

            }

            /**
             * Get the Facebook access token on redirect
             * @return
             */
            function getTokenOnLogin() {

                $session = $this->session; /* @var \Facebook\Facebook $session */
                $helper = $session->getRedirectLoginHelper();
                try {
                    return $helper->getAccessToken();
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
                    $verb_function = strtolower($verb);
                    $session = $this->session;
                    $response = $session->$verb_function($endpoint, $params); /* @var \Facebook\FacebookResponse $response */
		    
		    try {
			if ($items = $response->getGraphNode()) {
			    $result = array('id' => $items->getField('id'), 'response' => $items);
			    return $result;
			}
		    } catch (\Exception $e) {
			
			// Ok, lets see if its a graph edge before we ditch
			if ($items = $response->getGraphEdge()) {
			    $result = array('id' => $items->getField('id'), 'response' => $items);
			    return $result;
			}
		    }
                } catch (\Exception $e) {
                    \Idno\Core\site()->logging()->error($e->getMessage());
                    return false;
                }

            }

        }

    }
