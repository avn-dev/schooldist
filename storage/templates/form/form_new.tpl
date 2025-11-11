{function name=printBlockHeadline oSchool=null sLanguage=null oBlock=null}
	<h{$oBlock->getHeadlineLevel()} class="block-headline" {$oBlock->getTitleDataAttributes($oSchool, $sLanguage)}>{$oBlock->getTitle($sLanguage)|default:"{$oBlock->getDefaultTitle()}"|escape:"htmlall"}</h{$oBlock->getHeadlineLevel()}>
{/function}

{function name=printBlockText oSchool=null sLanguage=null oBlock=null}
	<div class="block-content block-text">
		<p {$oBlock->getTitleDataAttributes($oSchool, $sLanguage)}>{$oBlock->getBlockText($sLanguage)|default:"{$oBlock->getDefaultTitle()|escape:"htmlall"}"}</p>
	</div>
{/function}

{function name=printBlockInput oSchool=null sLanguage=null oBlock=null}
	<div class="block-message" data-validateable="block-message"></div>
	<div class="block-content block-input-{$oBlock->getInputBlockType()|default:"default"|escape:"htmlall"}">
		{if !$oBlock->isShowingLabelAsPlaceholder()}
			<div class="block-content-title" {$oBlock->getTitleDataAttributes($oSchool, $sLanguage)}>{$oBlock->getTitle($sLanguage)|default:"{$oBlock->getDefaultTitle()}"|escape:"htmlall"}<span class="block-content-title-required">{if $oBlock->isRequired()} *{/if}</span></div>
		{/if}
		{if $oBlock->hasInfoMessage()}
			<div class="block-content-info-message">
				<img src="?get_file=info_icon.png" alt="?" />
				<span>{$oBlock->getInfoMessage($sLanguage)|escape:"htmlall"}</span>
			</div>
		{/if}
		<div class="block-content-text {if $oBlock->hasInfoMessage()}block-content-text-with-info-message{/if}">
			{if $oBlock->getInputBlockType() == "text"}
				<input type="{$oBlock->getInputTextType()}" name="{$oBlock->getInputBlockName()|escape:"htmlall"}" value="" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)} />
			{elseif $oBlock->getInputBlockType() == "textarea"}
				<textarea name="{$oBlock->getInputBlockName()|escape:"htmlall"}" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)}></textarea>
			{elseif $oBlock->getInputBlockType() == "select"}
				<select name="{$oBlock->getInputBlockName()|escape:"htmlall"}" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)}></select>
			{elseif $oBlock->getInputBlockType() == "multiselect"}
				<select multiple size="3" name="{$oBlock->getInputBlockName()|escape:"htmlall"}[]" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)}></select>
			{elseif $oBlock->getInputBlockType() == "checkbox"}
				<input type="checkbox" name="{$oBlock->getInputBlockName()|escape:"htmlall"}" value="1" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)} />
			{elseif $oBlock->getInputBlockType() == "yesno"}
				<div class="block-content-radio-group">
					<div>
						<span class="block-content-radio-group-label">{$aTranslations['yes']}</span>
						<input type="radio" name="{$oBlock->getInputBlockName()|escape:"htmlall"}" value="yes" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)} />
					</div>
					<div>
						<span class="block-content-radio-group-label">{$aTranslations['no']}</span>
						<input type="radio" name="{$oBlock->getInputBlockName()|escape:"htmlall"}" value="no" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)} />
					</div>
				</div>
			{elseif $oBlock->getInputBlockType() == "date"}
				<input type="text" class="datepicker" name="{$oBlock->getInputBlockName()|escape:"htmlall"}" value="" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)} />
			{elseif $oBlock->getInputBlockType() == "upload"}
				<input type="file" name="{$oBlock->getInputBlockName()|escape:"htmlall"}" value="" {$oBlock->getInputDataAttributes($oSchool, $sLanguage)}>
			{elseif $oBlock->getInputBlockType() == "download"}
				<a href="?get_file={$oBlock->getInputBlockName()|escape:"htmlall"}" target="_blank">{$oBlock->getInfoMessage($sLanguage)|default:"Download file"|escape:"htmlall"}</a>
			{else}
				Input-Block-Type "{$oBlock->getInputBlockType()|escape:"htmlall"}"
			{/if}
		</div>
		<div style="clear: both;"></div>
	</div>
{/function}

{function name=printSpecialInput oSchool=null sLanguage=null oBlock=null}
	{if $oBlock->getSubtype() == "duplicator_controls"}
		<div {$oBlock->getTitleDataAttributes($oSchool, $sLanguage)} class="block-special-duplicator-controls">
			<input class="duplicator-controls-add" type="button" value="{$oBlock->getDuplicateAddButtonText($oSchool, $sLanguage)|default:"+"|escape:"htmlall"}" data-duplicateable="control-add" />
			<input class="duplicator-controls-remove" type="button" value="{$oBlock->getDuplicateRemoveButtonText($oSchool, $sLanguage)|default:"-"|escape:"htmlall"}" data-duplicateable="control-remove" />
		</div>
	{else}
		Special-Block-Type "{$oBlock->getSubtype()|escape:"htmlall"}"
	{/if}
{/function}

{function name=printBlock oSchool=null sLanguage=null oBlock=null}
	{if $oBlock->isAvailable($oSchool, $sLanguage)}
		<div class="{$oBlock->getCssClass()|escape:"htmlall"}" {$oBlock->getBlockDataAttributes($oSchool, $sLanguage)}>
			{if $oBlock->hasAreas()}
				{assign "aAreas" $oBlock->getAreaWidths()}
				{assign "sAreaClass" "block-multiple-areas block-multiple-areas-single"}
				{assign "sMultiAreaClass" ""}
				{if $aAreas|@count gt 1}
					{assign "sAreaClass" "block-multiple-areas block-multiple-areas-multiple"}
					{assign "sMultiAreaClass" "block-multiple-areas-multiple-first"}
				{/if}
				{foreach $aAreas as $iArea => $iWidth}
					{assign "mWidth" "$iWidth%"}
					{if $iWidth < 1}
						{assign "mWidth" "auto"}
					{/if}
					<div style="margin: 0; padding: 0; float: left; width: {$mWidth|escape:"htmlall"};" class="block-area-{$oBlock->getAreaType()|default:"default"|escape:"htmlall"}">
						<div class="{$sAreaClass|escape:"htmlall"} {$sMultiAreaClass|escape:"htmlall"}">
							{if $oBlock->getAreaType() == "prices"}
								<div class="block-area-overlay"><span><img src="?get_file=form_loading.gif" /></span></div>
								<div class="block-prices-print">
									<img src="?get_file=printer.png" alt="" />
								</div>
							{/if}
							{foreach $oBlock->getChildBlocksForArea($iArea) as $oChildBlock}
								{printBlock oSchool=$oSchool sLanguage=$sLanguage oBlock=$oChildBlock}
							{/foreach}
						</div>
					</div>
					{if $sMultiAreaClass == "block-multiple-areas-multiple-first"}
						{assign "sMultiAreaClass" "block-multiple-areas-multiple-following"}
					{/if}
				{/foreach}
				<div style="clear: both;"></div>
			{elseif $oBlock->isHeadlineBlock()}
				{printBlockHeadline oSchool=$oSchool sLanguage=$sLanguage oBlock=$oBlock}
			{elseif $oBlock->isTextBlock()}
				{printBlockText oSchool=$oSchool sLanguage=$sLanguage oBlock=$oBlock}
			{elseif $oBlock->isInputBlock()}
				{printBlockInput oSchool=$oSchool sLanguage=$sLanguage oBlock=$oBlock}
			{elseif $oBlock->isSpecialBlock()}
				{printSpecialInput oSchool=$oSchool sLanguage=$sLanguage oBlock=$oBlock}
			{else}
				<p>{$oBlock->getTitle($sLanguage)|default:"{$oBlock->getDefaultTitle()}"|escape:"htmlall"}</p>
			{/if}
		</div>
	{/if}
{/function}

{function name=printPage oSchool=null sLanguage=null oPage=null}
	<div class="page-container" style="display: {$pageDisplay|escape:"htmlall"};" {$oPage->getPageDataAttributes($oSchool, $sLanguage)}>
		<div class="page-loading-overlay"><span><img src="?get_file=form_loading.gif" /></span></div>
		<div class="pages-list">
			{foreach $oPage->getPreviousPages() as $oPreviousPage}
				<h1 class="page-previous">{$oPreviousPage->getTitle($sLanguage)|default:"{$oPreviousPage->getDefaultTitle()}"|escape:"htmlall"}</h1>
			{/foreach}
			<h1 class="page-current">{$oPage->getTitle($sLanguage)|default:"{$oPage->getDefaultTitle()}"|escape:"htmlall"}</h1>
			{foreach $oPage->getFollowingPages() as $oFollowingPage}
				<h1 class="page-following">{$oFollowingPage->getTitle($sLanguage)|default:"{$oFollowingPage->getDefaultTitle()}"|escape:"htmlall"}</h1>
			{/foreach}
		</div>
		<div class="page-content">
			<div class="form-message" data-validateable="form-message"></div>
			{foreach $oPage->getBlocks() as $oBlock}
				{printBlock oSchool=$oSchool sLanguage=$sLanguage oBlock=$oBlock}
			{/foreach}
			<div class="page-navigation">
				{if !$oPage->isFirstPage()}
					<input class="page-navigation-back" type="button" {$oPage->getPreviousPageButtonDataAttributres()} value="{$oPage->getPreviousPageButtonText($sLanguage)|default:"Back"|escape:"htmlall"}" />
				{/if}
				{if !$oPage->isLastPage()}
					<input class="page-navigation-next" type="button" {$oPage->getNextPageButtonDataAttributres()} value="{$oPage->getNextPageButtonText($sLanguage)|default:"Next"|escape:"htmlall"}" />
				{/if}
				{if $oPage->isLastPage()}
					<input class="page-navigation-submit" type="button" {$oPage->getSubmitButtonDataAttributres()} value="{$oPage->getSubmitButtonText($sLanguage)|default:"Submit"|escape:"htmlall"}" />
				{/if}
			</div>
		</div>
	</div>
{/function}

{function name=printSuccessPage oForm=null oSchool=null sLanguage=null}
	<div class="page-container" style="display: {$pageDisplay|escape:"htmlall"};" {$oForm->getSuccessPageDataAttributes($oSchool, $sLanguage)}>
		<div class="pages-list">
			{foreach $oForm->getPages() as $oPreviousPage}
				<h1 class="page-previous">{$oPreviousPage->getTitle($sLanguage)|default:"{$oPage->getDefaultTitle()}"|escape:"htmlall"}</h1>
			{/foreach}
			<h1 class="page-current">{$oForm->getSuccessPageTitle($sLanguage)|default:"Success"|escape:"htmlall"}</h1>
		</div>
		<div class="page-content">
			<div class="form-message" data-validateable="form-message"></div>
			<p class="success-message">Success!</p>{* Meldung wird per AJAX ausgetauscht*}
			<div class="page-navigation page-navigation-submit-reset">
				<input class="page-navigation-back" type="button" data-form-navigation="submit-reset" value="Reset" />
			</div>
		</div>
	</div>
{/function}

{function name=printForm oForm=null oSchool=null sLanguage=null}
	<form class="form {$sFormIdentifier} {$sFormType}" method="POST" target="_self" enctype="multipart/form-data" {$oForm->getFormDataAttributes($oSchool, $sLanguage)}>
		{assign "pageDisplay" "block"}
		{foreach $oForm->getPages() as $oPage}
			{printPage oSchool=$oSchool sLanguage=$sLanguage oPage=$oPage}
			{assign "pageDisplay" "none"}
		{/foreach}
		{printSuccessPage oForm=$oForm oSchool=$oSchool sLanguage=$sLanguage}
	</form>
{/function}

{if $oForm->isIncludingDefaultCSS()}
	{include file="form_new.css.tpl"}
{/if}
{$oForm->getIndividualCSS()}
{block name="header_css"}{/block}

{if $sView === 'payment_return'}
	{$sMessage}
{else}
	{printForm oForm=$oForm oSchool=$oSchool sLanguage=$sLanguage}
{/if}

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css" />
<script>var {$sJQueryName} = jQuery.noConflict(true);</script>
<script type="text/javascript" src="?get_file=form_object.js"></script>
<script type="text/javascript">{$sOnloadJsEvent}</script>
<script type="text/javascript">
	{$sJQueryName}().ready(function() {
		{* Monkey-Patch, da Ordinal-Suffix an beliebiger Stelle stehen kann (alle anderen nutzen onSelect-Hook) *}
		var formatDate = {$sJQueryName}.datepicker.__proto__.formatDate;
		{$sJQueryName}.datepicker.__proto__.formatDate = function(format, date, settings) {
			format = format.replace('O', function() {
				var s = ['th','st','nd','rd'], v = date.getDate() % 100;
				return "'" + (s[(v-20)%10]||s[v]||s[0]) + "'";
			});
			return formatDate(format, date, settings);
		};
		{$sJQueryName}('form.{$sFormIdentifier} .datepicker').datepicker({
			dateFormat: "{$sDatepickerFormat}",
			changeMonth: true,
			changeYear: true,
			firstDay: 1,
			yearRange: "1900:+5"
		});
		{$sJQueryName}('form.{$sFormIdentifier} .block-prices-print img').bind('click', function() {
			window.print();
		});
		{block name="footer_js"}{/block}
	});
</script>
