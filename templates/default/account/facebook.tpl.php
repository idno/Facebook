<div class="row">

    <div class="span10 offset1">
        <?= $this->draw('account/menu') ?>
        <h1>Facebook</h1>

    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <?php
            if (empty(\Idno\Core\site()->session()->currentUser()->facebook)) {
                ?>
                <div class="control-group">
                    <div class="controls-config">
                        <div class="row">
                            <div class="span6">
                                <p>
                                    Easily share pictures, updates, and posts to Facebook.</p>

                                <p>
                                    With Facebook connected, you can cross-post content that you publish publicly on
                                    your site.
                                </p>
                            </div>
                        </div>
                        <div class="social span4">
                            <p>
                                <a href="<?= $vars['login_url'] ?>" class="connect fb">Connect Facebook</a>
                            </p>
                        </div>
                    </div>
                </div>
            <?php

            } else {

                ?>
                <div class="control-group">
                    <div class="controls-config">
                        <div class="row">
                            <div class="span6">
                                <p>
                                    Your account is currently connected to Facebook. Public content that you publish
                                    here
                                    can be cross-posted to your Facebook account.
                                </p>
                            </div>
                        </div>
                        <?php

                            if ($accounts = \Idno\Core\site()->syndication()->getServiceAccounts('facebook')) {

                                foreach ($accounts as $id => $account) {

                                    ?>
                                    <form action="<?= \Idno\Core\site()->config()->getDisplayURL() ?>facebook/deauth"
                                          class="form-horizontal" method="post">
                                        <p>
                                            <input type="hidden" name="remove" value="<?= $account['username'] ?>"/>
                                            <button type="submit"
                                                    class="connect fb connected"><?= $account['name'] ?> (Disconnect)
                                            </button>
                                            <?= \Idno\Core\site()->actions()->signForm('/account/facebook/') ?>
                                        </p>
                                    </form>
                                <?php

                                }

                            } else {

                                ?>
                                <div class="social">
                                    <form action="<?= \Idno\Core\site()->config()->getDisplayURL() ?>facebook/deauth"
                                          class="form-horizontal" method="post">
                                        <p>
                                            <input type="hidden" name="remove" value="1"/>
                                            <button type="submit" class="connect fb connected">Disconnect Facebook
                                            </button>
                                            <?= \Idno\Core\site()->actions()->signForm('/account/facebook/') ?>
                                        </p>
                                    </form>
                                </div>
                            <?php

                            }

                        ?>
                    </div>
                </div>
                <div class="social span4">
                    <p>
                        <a href="<?= $vars['login_url'] ?>" >Connect another Facebook account</a>
                    </p>
                </div>

            <?php

            }
        ?>
    </div>
</div>
