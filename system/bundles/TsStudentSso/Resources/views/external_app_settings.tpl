<form method="post" action="{route name="TcExternalApps.save" sAppKey=$sAppKey}" autocomplete="off">
	<div class="box-body">

		<h4>{'Identity provider settings'|L10N}</h4>
		
		<div class="alert alert-info" role="alert">{'Use the following values to set up SSO'|L10N}</div>
		
		<dl>
			<dt>{'SHA256 Certificate Fingerprint'|L10N}</dt>
			<dd>{$fingerprint}</dd>
			<dt>{'X.509 Certificate'|L10N}</dt>
			<dd><pre style="height:100px;max-width:600px;">{$cert}</pre></dd>
			<dt>{'Entity ID / Issuer URL'|L10N}</dt>
			<dd>{route name="TsStudentSso.metadata"}</dd>
			<dt>{'SAML 2.0 Endpoint'|L10N}</dt>
			<dd>{route name="TsStudentSso.sso"}</dd>
			<dt>{'SLO Endpoint'|L10N}</dt>
			<dd>{route name="TsStudentSso.slo"}</dd>
		</dl>
		
		<h4>{'Service provider settings'|L10N}</h4>
		
		{foreach $aFields as $aField}
			{if $aField['type'] && $aField['type'] === 'headline'}
				<h4>{$aField['title']}</h4>
			{else}
			<div class="form-group">
				<label for="{$aField['key']}" class="control-label">{$aField['title']}</label>
				{if $aField['type'] && $aField['type'] === 'select'}
					<select name="config[{$aField['key']}]" class="form-control" id="{$aField['key']}">
						{html_options selected=$aField['value'] options=$aField['options']}
					</select>						
				{elseif $aField['type'] && $aField['type'] === 'textarea'}
					<textarea name="config[{$aField['key']}]" class="form-control" id="{$aField['key']}" placeholder="{$aField['placeholder']}">{$aField['value']}</textarea>
				{else}
					<input type="text" name="config[{$aField['key']}]" class="form-control" id="{$aField['key']}" placeholder="{$aField['placeholder']}" value="{$aField['value']}">
				{/if}
			</div>
			{/if}
		{/foreach}


	</div>
	<!-- /.box-body -->
	<div class="box-footer">
		<a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
		<button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
	</div>
	<!-- /.box-footer -->
</form>
	