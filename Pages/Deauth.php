<?php

    /**
     * Plugin administration
     */

    namespace IdnoPlugins\Facebook\Pages {

        class Deauth extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($twitter = \Idno\Core\site()->plugins()->get('Facebook')) {
                    if ($user = \Idno\Core\site()->session()->currentUser()) {
                        if ($account = $this->getInput('remove')) {
                            if (array_key_exists($account, $user->facebook)) {
                                unset($user->facebook[$account]);
                            } else {
                                $user->facebook = false;
                            }
                        }
                        $user->save();
                        \Idno\Core\site()->session()->refreshSessionUser($user);
                        if (!empty($user->link_callback)) {
                            $this->forward($user->link_callback); exit;
                        }
                    }
                }
                $this->forward($_SERVER['HTTP_REFERER']);
            }

            function postContent() {
                $this->getContent();
            }

        }

    }