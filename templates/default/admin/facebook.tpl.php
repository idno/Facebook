<div class="row">

    <div class="col-md-10 col-md-offset-1">
	            <?=$this->draw('admin/menu')?>
        <h1>Facebook configuration</h1>

    </div>

</div>
<div class="row">
    <div class="col-md-10 col-md-offset-1">
        <form action="<?=\Idno\Core\site()->config()->getDisplayURL()?>admin/facebook/" class="form-horizontal" method="post">
            <div class="controls-group">
                <div class="controls-config">
                    <p>
                        To begin using Facebook, <a href="https://developers.facebook.com/apps" target="_blank">create a new application in
                            the Facebook apps portal</a>.</p>
                    <p>
                        Mark the Platform as <strong>Website</strong>, and use <strong><?=\Idno\Core\site()->config()->url?></strong>
                        as the site URL. Be sure to also include an email address. Then, click <em>Status &amp; Review</em>, and make the app public.
                    </p>
                    <p>
                        Once you've finished, fill in the details below. You can then <a href="<?=\Idno\Core\site()->config()->getDisplayURL()?>account/facebook/">connect your Facebook account</a>.
                    </p>
                </div>
            </div>
            
            <div class="controls-group">
                <label class="control-label" for="app-id">App ID</label><br>
                
                    <input type="text" id="app-id" placeholder="App ID" class="form-control" name="appId" value="<?=htmlspecialchars(\Idno\Core\site()->config()->facebook['appId'])?>" >

                <label class="control-label" for="app-secret">App secret</label><br>
                
                    <input type="text" id="app-secret" placeholder="App secret" class="form-control" name="secret" value="<?=htmlspecialchars(\Idno\Core\site()->config()->facebook['secret'])?>" >

            </div>
            
                        
            <div class="controls-group">
					  	<p>
                        After the Facebook application is configured, site users must authenticate their Facebook account under Settings.
						</p>

            </div> 
          	<div>

                <div class="controls-save">
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
          	</div>

            <?= \Idno\Core\site()->actions()->signForm('/admin/facebook/')?>
        </form>
    </div>
</div>
