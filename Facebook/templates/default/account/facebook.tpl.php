<div class="row">

    <div class="span10 offset1">
        <h1>Facebook</h1>
        <?=$this->draw('account/menu')?>
    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <form action="/account/facebook/" class="form-horizontal" method="post">
            <?php
                if (empty(\Idno\Core\site()->session()->currentUser()->facebook)) {
            ?>
                    <div class="control-group">
                        <div class="controls">
                            <p>
                                If you have a Facebook account, you may connect it here. Public content that you
                                post to this site will be automatically cross-posted to your Facebook wall.
                            </p>
                            <p>
                                <a href="<?=$vars['login_url']?>" class="btn btn-large btn-success">Click here to connect Facebook to your account</a>
                            </p>
                        </div>
                    </div>
                <?php

                } else {

                    ?>
                    <div class="control-group">
                        <div class="controls">
                            <p>
                                Your account is currently connected to Facebook. Public content that you post here
                                will be shared with your Facebook account.
                            </p>
                            <p>
                                <input type="hidden" name="remove" value="1" />
                                <button type="submit" class="btn-primary">Click here to remove Facebook from your account.</button>
                            </p>
                        </div>
                    </div>

                <?php

                }
            ?>
            <?= \Idno\Core\site()->actions()->signForm('/account/facebook/')?>
        </form>
    </div>
</div>
