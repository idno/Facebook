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
                        /* @var \IdnoPlugins\Facebook\FacebookAPI $facebookAPI */
                        if ($session = $facebookAPI->getSessionOnLogin()) {
                            $user = \Idno\Core\site()->session()->currentUser();
                            $access_token = $session->getToken();
                            $facebookAPI->setAccessToken($access_token);
                            if ($person = $facebookAPI->api('/me','GET')) {
                                $name = $person['response']->getProperty('name');
                                $id = $person['response']->getProperty('id');
                                $user->facebook[$id] = ['id' => $id, 'access_token' => $access_token, 'name' => $name];
                                \Idno\Core\site()->syndication()->registerServiceAccount('facebook', $id, $name);
                                if (\Idno\Core\site()->config()->multipleSyndicationAccounts()) {
                                    if ($companies = $facebookAPI->api('/me/accounts','GET')) {
                                        if (!empty($companies['response'])) {
                                            foreach($companies['response']->asArray() as $company_container) {
                                                foreach($company_container as $company) {
                                                    $company = (array) $company;
                                                    if ($perms = $company['perms']) {
                                                        if (in_array('CREATE_CONTENT', $perms) && !empty($company['name'])) {
                                                            $id = $company['id'];
                                                            $name = $company['name'];
                                                            $access_token = $company['access_token'];
                                                            $user->facebook[$id] = ['id' => $id, 'access_token' => $access_token, 'name' => $name, 'page' => true];
                                                            \Idno\Core\site()->syndication()->registerServiceAccount('facebook', $id, $name);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                $user->facebook = array('access_token' => $access_token);
                            }
                            $user->save();
                        }
                    }
                }
                if (!empty($_SESSION['onboarding_passthrough'])) {
                    unset($_SESSION['onboarding_passthrough']);
                    $this->forward(\Idno\Core\site()->config()->getURL() . 'begin/connect-forwarder');
                }
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'account/facebook/');
            }

        }

    }