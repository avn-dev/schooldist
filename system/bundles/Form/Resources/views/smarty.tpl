{function printInfo}
	{if $oField->getProperty('infotext')}<span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" title="{$oField->getProperty('infotext')|escape}" aria-hidden="true"></span>{/if}
{/function}
	
{function printLabel}
	{$oField->getProperty('name')}{if $oField->getProperty('check')} *{/if}
{/function}
	
{function printError}
	{if $oField->hasError()}
		<span class="help-block">
		{if $oField->getError() === 'numbers'}
			{'Bitte geben Sie nur Zahlen ein!'|L10N}
		{elseif $oField->getError() === 'email'}
			{'Dies ist keine gültige E-Mail-Adresse!'|L10N}
		{elseif $oField->getError() === 'plz'}
			{'Dies ist keine gültige Postleitzahl!'|L10N}
		{elseif $oField->getError() === 'date'}
			{'Bitte geben Sie ein Datum im Format DD.MM.YYYY ein!'|L10N}
		{elseif $oField->getError() === 'currency'}
			{'Bitte geben Sie einen Währungsbetrag an!'|L10N}
		{else}
			{'Bitte füllen Sie dieses Feld aus!'|L10N}
		{/if}
		</span>
	{/if}
{/function}

<form class="form-horizontal" id="form_{$iContentId}" method="post" action="{$sFormAction}" role="form">
	<input type="hidden" name="fo_action_{$iContentId}" value="send">
	<input type="hidden" name="fo_instance_hash" value="{$sInstanceHash}">
	<input type="hidden" name="fo_page_id" value="{$iCurrentPageId}">

	<ul class="nav nav-pills nav-justified">
		{foreach $aPages as $oPage}
		<li role="presentation" class="{if $oPage->id == $iCurrentPageId}active {/if}disabled"><a href="#">{$oPage->name}</a></li> 
		{/foreach}
		<li role="presentation" class="{if $bSuccess === true}active {/if}disabled"><a href="#">{'Bestätigung'|L10N}</a></li> 
	</ul>
	<br>

	{if $bSuccess === true}

		{if empty($sMessage)}
			{assign var=sMessage value="Ihre Anfrage wurde erfolgreich abgeschickt!"}
		{/if}
		
		<div class="alert alert-success alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>{$sMessage}</div>

		<h2>{'Zusammenfassung Ihrer Anfrage'|L10N}</h2>
		{foreach $aPages as $oPage}
			<h3>{$oPage->name}</h3>
			
			{foreach $aAllFields[$oPage->id] as $oField}
				{if $oField->getProperty('type') === 'onlytitle'}
					<h4>{$oField->getProperty('name')}{printInfo oField=$oField}</h4>
				{elseif $oField->getProperty('type') === 'onlytext'}
					<p>{$oField->getProperty('name')}{printInfo oField=$oField}</p>
				{else}
					<div class="form-group">
					  <label for="inputEmail3" class="col-sm-4 control-label">{printLabel oField=$oField}</label>
					  <div class="col-sm-8"><p class="form-control-static">{if is_array($oField->getValue())}{$oField->getValue()|implode:", "}{else}{$oField->getValue()}{/if}</p></div>
					</div>
				{/if}
			{/foreach}

		{/foreach}

	{else}
		
		{if $sMessage}
		<div class="alert alert-danger alert-dismissable"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>{$sMessage}</div>
		{/if}	
		
		{foreach $aFields as $oField}

			{if $oField->getProperty('type') === 'onlytitle'}
				
				<h3 {$oField->getConditionCheck()}>{$oField->getProperty('name')}{printInfo oField=$oField}</h3>
				
			{elseif $oField->getProperty('type') === 'onlytext'}
				
				<p {$oField->getConditionCheck()}>{$oField->getProperty('name')}{printInfo oField=$oField}</p>
			
			{else}

				<div class="form-group {$oField->getProperty('type')}{if $oField->hasError()} has-error{/if}" {$oField->getConditionCheck()}>
					
				{if $oField->getProperty('type') === 'text'}

					  <label for="field_{$oField->getProperty('id')}" class="col-sm-4 control-label">{printLabel oField=$oField}{printInfo oField=$oField}</label>
					  <div class="col-sm-8">
						<input type="{if $oField->getProperty('validation') == 'email'}email{else}text{/if}" class="form-control{if $oField->getProperty('check')} required{/if}{if $oField->getProperty('validation') == 'date'} datepicker_single{/if}" name="{$oField->getName()}" value="{$oField->getValue()|escape}" id="field_{$oField->getProperty('id')}" placeholder="{$oField->getProperty('name')}" {$oField->getConditionAction()}{if $oField->getProperty('check')} required{/if} {$oField->getProperty('options')}>
						{printError oField=$oField}
					  </div>

				{elseif $oField->getProperty('type') === 'textarea'}

					  <label for="field_{$oField->getProperty('id')}" class="col-sm-4 control-label">{printLabel oField=$oField}{printInfo oField=$oField}</label>
					  <div class="col-sm-8">
						  <textarea class="form-control{if $oField->getProperty('check')} required{/if}" name="{$oField->getName()}" id="field_{$oField->getProperty('id')}" placeholder="{$oField->getProperty('name')}" {$oField->getConditionAction()}{if $oField->getProperty('check')} required{/if}>{$oField->getValue()|escape}</textarea>
						  {printError oField=$oField}
					  </div>

				{elseif $oField->getProperty('type') === 'select' or $oField->getProperty('type') === 'reference'}

					  <label for="field_{$oField->getProperty('id')}" class="col-sm-4 control-label">{printLabel oField=$oField}{printInfo oField=$oField}</label>
					  <div class="col-sm-8">
						<select class="form-control{if $oField->getProperty('check')} required{/if}" name="{$oField->getName()}" {$oField->getConditionAction()}{if $oField->getProperty('check')} required{/if} id="field_{$oField->getProperty('id')}" {$oField->getProperty('options')}>
							{html_options values=$oField->getOptions() output=$oField->getOptions() selected=$oField->getValue()}
						</select>
						{printError oField=$oField}
					  </div>

				{elseif $oField->getProperty('type') === 'checkbox'}

					  <label class="col-sm-4 control-label">{printLabel oField=$oField}{printInfo oField=$oField}</label>
					  <div class="col-sm-8">
						  {foreach $oField->getOptions() as $sOption}
						<div class="checkbox">
						  <label>
							<input type="checkbox" name="{$oField->getName()}" value="{$sOption|escape}" {$oField->getConditionAction()} {if in_array($sOption, $oField->getValue())}checked{/if}> {$sOption}
						  </label>
						</div>
						{/foreach}
						{printError oField=$oField}
					  </div>

				{elseif $oField->getProperty('type') === 'radio'}

					  <label class="col-sm-4 control-label">{printLabel oField=$oField}{printInfo oField=$oField}</label>
					  <div class="col-sm-8">
						{foreach $oField->getOptions() as $sOption}
						<div class="radio">
						  <label>
						    <input type="radio" name="{$oField->getName()}" value="{$sOption|escape}" {$oField->getConditionAction()} {if $oField->getValue() == $sOption}checked{/if}> {$sOption}
						  </label>
						</div>
						{/foreach}
						{printError oField=$oField}
					  </div>

				{/if}
	
				</div>

			{/if}
			
		{/foreach}
	  
		{$sCaptchaHtml}
		
		<div class="form-group">
			<div class="col-sm-offset-4 col-sm-8">
				<div class="pull-right">
					{if $iFirstPageId != $iCurrentPageId}
						<a href="{$sFormAction}?fo_action_{$iContentId}=show&fo_instance_hash={$sInstanceHash}&fo_page_id={$iPreviousPageId}" class="btn btn-default">{'Zurück'|L10N}</a>
					{/if}
					{if $iLastPageId == $iCurrentPageId}
						<button type="submit" class="btn btn-primary">{'Absenden'|L10N}</button>
					{else}
						<button type="submit" class="btn btn-primary">{'Weiter'|L10N}</button>
					{/if}
				</div>
			</div>
		</div>
  
	{/if}

	{$sConditionJavaScript}
	{$sCaptchaJavaScript}
	
</form>