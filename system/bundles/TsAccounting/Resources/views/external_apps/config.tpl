<form class="form-horizontal" method="post" action="{route name="TcExternalApps.save" sAppKey=$sAppKey}">
	<div class="box-body">

		<div class="box-group" id="accordion">
			{if $sAppKey == 'verifactu'}
			<div class="panel box box-default">
				<div class="box-header with-border">
					<h4 class="box-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse_0">
							{'Verifactu Allgemein'|L10N}
						</a>
					</h4>
				</div>
				<div id="collapse_0" class="panel-collapse collapse in">
					<div class="box-body">
                        {foreach $configElements as $configElement}
							{$key= $configElement['key']}
							<div class="form-group">
								<label for="{$key}" class="col-sm-2 control-label">{$configElement['title']}</label>
								<div class="col-sm-10">
									{if $configElement['type'] === 'input'}
										<input type="text" name="config[{$key}]" class="form-control" id="{$key}" value="{$configElement['value']}">
									{elseif $configElement['type'] === 'checkbox'}
										<input type="checkbox" name="config[{$key}]" id="{$key}" value="0">
										<input type="checkbox" name="config[{$key}]" id="{$key}" value="1" {if $configElement['value'] == 1}checked{/if}>
									{elseif $configElement['type'] === 'select'}
										<select name="config[{$key}]" class="form-control" id="{$key}">
											{foreach $configElement['options'] as $option => $optionValue}
												<option value="{$option}" {if $configElement['value'] === $option}selected{/if}>{$optionValue}</option>
											{/foreach}
										</select>
                                    {elseif $configElement['type'] === 'textarea'}
										<textarea name="config[{$key}]" class="form-control" id="{$key}">{$configElement['value']}</textarea>
                                    {else}
										Unknown
									{/if}
								</div>
							</div>
                        {/foreach}
						<div class="form-group">
							<div class="col-sm-2"></div>
							<div class="col-sm-10">
								<a href="https://fidelo.com/DECLARACIÓN RESPONSABLE FIDELO SOFTWARE GmbH.pdf" target="_blank">
									<button type="button">{'DECLARACIÓN RESPONSABLE'|L10N}</button>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
			{/if}
			
            {foreach $aCompanies as $oCompany}
				<div class="panel box box-default">
					<div class="box-header with-border">
					  <h4 class="box-title">
						<a data-toggle="collapse" data-parent="#accordion" href="#collapse_{$oCompany->getId()}">
						  {$oCompany->getName()}
						</a>
					  </h4>
					</div>
					<div id="collapse_{$oCompany->getId()}" class="panel-collapse collapse {if $oCompany@first && $sAppKey !== 'verifactu'}in{/if}">
						<div class="box-body">
							{foreach $aAttributes as $sAttribute => $aAttribute}

								{$mValue= $oCompany->__get($sAttribute)}

								<div class="form-group">
									<label for="{$oCompany->getId()}_{$sAttribute}" class="col-sm-2 control-label">{$aAttribute['label']}</label>
									<div class="col-sm-10">
										{if $aAttribute['type'] === 'input'}
											<input type="text" name="config[{$oCompany->getId()}][{$sAttribute}]" class="form-control" id="{$oCompany->getId()}_{$sAttribute}" value="{$mValue}">
										{elseif $aAttribute['type'] === 'checkbox'}
											<input type="hidden" name="config[{$oCompany->getId()}][{$sAttribute}]" id="{$oCompany->getId()}_{$sAttribute}" value="0">
											<input type="checkbox" name="config[{$oCompany->getId()}][{$sAttribute}]" id="{$oCompany->getId()}_{$sAttribute}" value="1" {if $mValue == 1}checked{/if}>
										{elseif $aAttribute['type'] === 'select'}
											<select name="config[{$oCompany->getId()}][{$sAttribute}]" class="form-control" id="{$oCompany->getId()}_{$sAttribute}">
												{foreach $aAttribute['options'] as $sOption => $sOptionValue}
													<option value="{$sOption}" {if $mValue === $sOption}selected{/if}>{$sOptionValue}</option>
												{/foreach}
											</select>
										{else}
											Unknown
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