<?php

    if ($facebook = \Idno\Core\site()->plugins()->get('Facebook')) {
        if (empty(\Idno\Core\site()->session()->currentUser()->facebook)) {
            $login_url = $facebook->getAuthURL();
        } else {
            $login_url = \Idno\Core\site()->config()->getURL() . 'facebook/deauth';
        }
    }

?>
<div class="social">
    <a href="<?=$login_url?>" class="connect fb <?php

        if (!empty(\Idno\Core\site()->session()->currentUser()->facebook)) { echo 'connected'; }

    ?>" target="_top">Facebook<?php if (!empty(\Idno\Core\site()->session()->currentUser()->facebook)) { echo ' - connected!'; } ?></a><br>
    <label class="control-label">Share pictures, updates, and posts to Facebook.</label>
</div>