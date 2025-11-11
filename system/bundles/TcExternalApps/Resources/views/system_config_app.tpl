<form class="form-horizontal" method="post" action="{route name="TcExternalApps.save" sAppKey=$sAppKey}">
	<div class="box-body">

		{foreach $aFields as $aField}
			{if $aField['type'] && $aField['type'] === 'headline'}
				<h4>{$aField['title']}</h4>
			{else}
			<div class="form-group">
				<label for="{$aField['key']}" class="col-sm-2 control-label">{$aField['title']}</label>
				<div class="col-sm-10">
					{if $aField['type'] && $aField['type'] === 'select'}
						<select name="config[{$aField['key']}]" class="form-control" id="{$aField['key']}">
							{html_options selected=$aField['value'] options=$aField['options']}
						</select>
					{elseif $aField['type'] && $aField['type'] === 'multiple_select'}
						<select name="config[{$aField['key']}][]" class="form-control" multiple id="{$aField['key']}">
							{html_options selected=$aField['value'] options=$aField['options']}
						</select>
					{elseif $aField['type'] && $aField['type'] === 'textarea'}
						<textarea name="config[{$aField['key']}]" class="form-control" id="{$aField['key']}" placeholder="{$aField['placeholder']}">{$aField['value']}</textarea>
					{elseif $aField['type'] && $aField['type'] === 'checkbox'}
						<input
							type="checkbox"
							name="config[{$aField['key']}]"
							id="{$aField['key']}"
							value="1"
							{if $aField['value']}checked{/if}
						>
					{else}
						<input type="text" name="config[{$aField['key']}]" class="form-control" id="{$aField['key']}" placeholder="{$aField['placeholder']}" value="{$aField['value']}">
					{/if}
				</div>
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

