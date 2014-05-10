<div class="row">

    <div class="span10 offset1">
        <h1>Facebook</h1>
        <?=$this->draw('admin/menu')?>
    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <form action="/admin/facebook/" class="form-horizontal" method="post">
            <div class="control-group">
                <div class="controls">
                    <p>
                        To begin using Facebook, <a href="https://developers.facebook.com/apps" target="_blank">create a new application in
                            the Facebook apps portal</a>.</p>
                    <p>
                        Mark the integration method as <strong>Website with Facebook Login</strong>, and use <strong><?=\Idno\Core\site()->config()->url?></strong>
                        as the site URL.
                    </p>
		    <p>
			In order for your posts to be visible to others, you must take your application out of <a href="https://developers.facebook.com/docs/ApplicationSecurity/" target="_blank">developer mode</a>. You may also wish to configure a default post level in your application privacy settings.
		    </p>
                    <p>
                        Once you've finished, fill in the details below:
                    </p>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">App ID</label>
                <div class="controls">
                    <input type="text" id="name" placeholder="App ID" class="span4" name="appId" value="<?=htmlspecialchars(\Idno\Core\site()->config()->facebook['appId'])?>" >
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">App secret</label>
                <div class="controls">
                    <input type="text" id="name" placeholder="App secret" class="span4" name="secret" value="<?=htmlspecialchars(\Idno\Core\site()->config()->facebook['secret'])?>" >
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
            <?= \Idno\Core\site()->actions()->signForm('/admin/facebook/')?>
        </form>
    </div>
</div>
