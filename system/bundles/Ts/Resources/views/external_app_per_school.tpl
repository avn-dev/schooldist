<form class="form-horizontal" method="post" action="{route name="TcExternalApps.save" sAppKey=$sAppKey}" autocomplete="off" enctype="multipart/form-data">
	<div class="box-body">

		<div class="box-group" id="accordion">
			
            {foreach $aSchools as $oSchool}
				<div class="panel box box-default">
					<div class="box-header with-border">
					  <h4 class="box-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse_{$oSchool->getId()}">
						  {$oSchool->getName()}
						</a>
					  </h4>
					</div>
					<div id="collapse_{$oSchool->getId()}" class="panel-collapse collapse {if $oSchool@first}in{/if}">
						<div class="box-body">
							{foreach $aAttributes as $sAttribute => $aAttribute}

								{$mValue = $aAttribute['value'][$oSchool->getId()]}
								
								{if $aAttribute['type'] === 'select' || $aAttribute['type'] === 'select_multiple'}
									{if isset($aAttribute['options_per_school'])}
										{$aOptions = $aAttribute['options_per_school'][$oSchool->getId()]}
									{else}
										{$aOptions = $aAttribute['options']}
									{/if}
								{/if}
								
								<div class="form-group">
									<label for="{$oSchool->getId()}_{$sAttribute}" class="col-sm-2 control-label">{$aAttribute['label']}</label>
									<div class="col-sm-10">
										{if $aAttribute['type'] === 'input'}
											<input type="text" name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}" value="{$mValue}">
                                        {elseif $aAttribute['type'] === 'number'}
											<input type="number" step="{$aAttribute['step']}" name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}" value="{$mValue}">
                                         {elseif $aAttribute['type'] === 'date'}
											<input type="date" name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}" value="{$mValue}">
                                        {elseif $aAttribute['type'] === 'html'}
											<textarea type="text" name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control tinymce" id="{$oSchool->getId()}_{$sAttribute}">{$mValue}</textarea>
                                        {elseif $aAttribute['type'] === 'password'}
											<!-- https://gist.github.com/runspired/b9fdf1fa74fc9fb4554418dea35718fe -->
											<input type="password" name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}" value="{$mValue}" autocomplete="new-password">
										{elseif $aAttribute['type'] === 'checkbox'}
											<input type="hidden" name="config[{$oSchool->getId()}][{$sAttribute}]" id="{$oSchool->getId()}_{$sAttribute}" value="0">
											<input type="checkbox" name="config[{$oSchool->getId()}][{$sAttribute}]" id="{$oSchool->getId()}_{$sAttribute}" value="1" {if $mValue == 1}checked{/if}>
                                        {elseif $aAttribute['type'] === 'select'}
											<select name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}">
												{foreach $aOptions as $sKey => $sLabel}
													<option value="{$sKey}" {if $mValue == $sKey}selected{/if}>{$sLabel}</option>
												{/foreach}
											</select>
                                        {elseif $aAttribute['type'] === 'select_multiple'}
											<select name="config[{$oSchool->getId()}][{$sAttribute}][]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}" multiple>
                                                {foreach $aOptions as $sKey => $sLabel}
													<option value="{$sKey}" {if $sKey|in_array:$mValue}selected{/if}>{$sLabel}</option>
                                                {/foreach}
											</select>
										{elseif $aAttribute['type'] === 'upload'}
											<input type="file" name="config[{$oSchool->getId()}][{$sAttribute}]" class="form-control" id="{$oSchool->getId()}_{$sAttribute}" value="{$mValue}">
										{else}
											Unknown
										{/if}
                                        {if $aAttribute['description']}
											<p class="help-block">{$aAttribute['description']}</p>
										{/if}
									</div>
								</div>
							{/foreach}
						</div>
					</div>
				</div>  
			{/foreach}
               
		</div>

	</div>
	<!-- /.box-body -->
	<div class="box-footer">
		<a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
		<button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
	</div>
	<!-- /.box-footer -->
</form>
<script type="text/javascript" src="/tinymce/resource/basic/tinymce.min.js"></script>
<script>
	tinyMCE.init({
		selector: '.tinymce',
		menubar: false,
		plugins: 'advlist autolink code lists link',
		toolbar: 'undo redo | styleselect | bold italic underline link | alignleft aligncenter alignright | forecolor | bullist numlist | preview code fullscreen',
	});
</script>