<h2>{'wgm.github.common'|devblocks_translate}</h2>
{if !$extensions.oauth}
<b>The oauth extension is not installed.</b>
{else}

<form action="javascript:;" method="post" id="frmSetupGitHub" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="github">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Authentication</legend>
	
	<b>Consumer key:</b><br>
	<input type="text" name="consumer_key" value="{$params.consumer_key}" size="64"><br>
	<br>
	<b>Consumer secret:</b><br>
	<input type="text" name="consumer_secret" value="{$params.consumer_secret}" size="64"><br>
	<br>
	<div class="status"></div>

	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

</form>

<form action="{devblocks_url}ajax.php{/devblocks_url}" method="post" id="frmAuthGitHub" style="display: {if $params.consumer_key && $params.consumer_secret}block{else}none{/if}">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="github">
<input type="hidden" name="action" value="auth">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
<fieldset>
	<legend>GitHub Authorization</legend>
	<input type="submit" class="submit" value="Sign in with GitHub">
</fieldset>
</form>
{if !empty($params.users)}
<fieldset>
	<legend>Authorized Users</legend>
	<ul>
	{foreach $params.users as $user}
	<li>{$user.login}</li>
	{/foreach}
	</ul>
</fieldset>
{/if}
<script type="text/javascript">
$('#frmSetupGitHub BUTTON.submit')
	.click(function(e) {
		genericAjaxPost('frmSetupGitHub','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmSetupGitHub div.status',$o.error);
				$('#frmAuthGitHub').fadeOut();
			} else {
				Devblocks.showSuccess('#frmSetupGitHub div.status',$o.message);
				$('#frmAuthGitHub').fadeIn();
			}
		});
	})
;
</script>
{/if}