<h2>{'wgm.github.common'|devblocks_translate}</h2>

<form action="javascript:;" method="post" id="frmSetupGitHub" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="github">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Authentication</legend>
	
	<b>Client ID:</b><br>
	<input type="text" name="consumer_key" value="{$credentials.consumer_key}" size="64" spellcheck="false"><br>
	<br>
	<b>Client Secret:</b><br>
	<input type="password" name="consumer_secret" value="{$credentials.consumer_secret}" size="64" spellcheck="false"><br>
	<br>
	<div class="status"></div>

	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>	
</fieldset>

</form>

<script type="text/javascript">
$(function() {
	$('#frmSetupGitHub BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupGitHub','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupGitHub div.status', $o.error);
				} else {
					Devblocks.showSuccess('#frmSetupGitHub div.status', $o.message);
				}
			});
		})
	;
});
</script>