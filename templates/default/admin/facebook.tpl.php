<div class="row">

    <div class="span10 offset1">
	            <?=$this->draw('admin/menu')?>
        <h1>Facebook configuration</h1>

    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <form action="<?=\Idno\Core\site()->config()->getURL()?>admin/facebook/" class="form-horizontal" method="post">
            <div class="control-group">
                <div class="controls-config">
                    <p>
                        To begin using Facebook, <a href="https://developers.facebook.com/apps" target="_blank">create a new application in
                            the Facebook apps portal</a>.</p>
                    <p>
                        Mark the Platform as <strong>Website</strong>, and use <strong><?=\Idno\Core\site()->config()->url?></strong>
                        as the site URL. Be sure to also include an email address. Then, click <em>Status &amp; Review</em>, and make the app public.
                    </p>
                    <p>
                        Once you've finished, fill in the details below. You can then <a href="<?=\Idno\Core\site()->config()->getURL()?>account/facebook/">connect your Facebook account</a>.
                    </p>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">App ID</label>
                <div class="controls">
                    <input type="text" id="name" placeholder="App ID" class="span6" name="appId" value="<?=htmlspecialchars(\Idno\Core\site()->config()->facebook['appId'])?>" >
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="name">App secret</label>
                <div class="controls">
                    <input type="text" id="name" placeholder="App secret" class="span6" name="secret" value="<?=htmlspecialchars(\Idno\Core\site()->config()->facebook['secret'])?>" >
                </div>
            </div>
            
                        
                      <div class="control-group">
	          <p>
                        After the Facebook application is configured, you must connect under account Settings.
                    </p>

          </div> 
            <div class="control-group">
                <div class="controls-save">
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
            </div>
            <?= \Idno\Core\site()->actions()->signForm('/admin/facebook/')?>
        </form>
    </div>
</div>
