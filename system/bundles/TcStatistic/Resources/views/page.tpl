{extends file="system/bundles/Gui2/Resources/views/page.tpl"}

{block name="system_head"}
	<link rel="stylesheet" href="/assets/tc-statistic/statistic.css" />
{/block}

{block name="system_footer"}
	<script src="/assets/tc-statistic/statistic.js?v={\System::d('version')}"></script>
	<script>
		function initPage() {
			var oStatistic = new Thebing.Statistic('{$sBundle}', '{$sDateFormat}');
			oStatistic.initialize();
		}
	</script>
{/block}

{block name="html"}
	<div class="bg-white pb-2 rounded-b-md">
<div class="divHeader clearfix">
	<div class="divHeaderSeparator"><div class="header-line"></div></div>
	<div class="divToolbar form-inline" style="width: 100%; height: 36px;">
		<!--<label class="divToolbarLabelGroup">{$aTranslations.filter}</label>-->
		<div class="grow">
			<div class="elements-container">
				<div class="guiBarFilter">
					<div class="divToolbarLabel">{$aTranslations.from}</div>
					<div class="input-group input-group-sm date">
						<div class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</div>
						<input type="text" style="width:80px;" name="filter_date_from" class="form-control input-sm calendar_input" autocomplete="off" data-filter="date">
					</div>
					<div class="divToolbarLabel">{$aTranslations.until}</div>
					<div class="input-group input-group-sm date">
						<div class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</div>
						<input type="text" style="width:80px;" name="filter_date_until" class="form-control input-sm calendar_input" autocomplete="off" data-filter="date">
					</div>
				</div>
				{if !empty($aDateFilterBasedOnOptions)}
					{if count($aDateFilterBasedOnOptions) == 1}
						<div class="guiBarElement">
							<div class="divToolbarHtml">
								<span>{$aTranslations.based_on} {reset($aDateFilterBasedOnOptions)}</span>
							</div>
						</div>
					{else}
						<div class="guiBarFilter">
							<div class="divToolbarLabel">{$aTranslations.based_on}</div>
							<select name="filter_date_based_on" data-filter="select" class="form-control input-sm">
								{foreach $aDateFilterBasedOnOptions as $sKey => $sLabel}
									<option value="{$sKey}">{$sLabel}</option>
								{/foreach}
							</select>
						</div>
					{/if}
				{/if}
				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>
				<div id="button_refresh" class="guiBarElement guiBarLink">
					<div class="divToolbarIcon">
						<i class="fa fa-colored fa-refresh"></i>
					</div>
					<div class="divToolbarLabel">{$aTranslations.refresh}</div>
				</div>
				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>
				<label class="divToolbarLabelGroup">{$aTranslations.export}</label>
				<div id="button_export_excel" class="guiBarElement guiBarLink">
					<div class="divToolbarIcon">
						<i class="fa fa-colored fa-file-excel-o"></i>
					</div>
					<div class="divToolbarLabel">Excel</div>
				</div>
				<div class="divToolbarToggleIcon" id="filter_toggle">
					{if !$bFiltersShown}
						<span class="divToolbarToggleLabel" data-toggle-translation="{$aTranslations.hide_more_options}">{$aTranslations.show_more_options}</span>
					{else}
						<span class="divToolbarToggleLabel" data-toggle-translation="{$aTranslations.show_more_options}">{$aTranslations.hide_more_options}</span>
					{/if}
					<i class="fa fa-angle-{(!$bFiltersShown) ? 'down' : 'up'} toolbarToggleIcon"></i>
				</div>
				<div class="guiBarElement">
					<div class="divToolbarIcon">
						<i id="loading_indicator" class="fa fa-spinner fa-pulse" style="display: none;"></i>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="additional_filters" class="infoBox statistic_margin" style="{if !$bFiltersShown}display: none;{/if}">
	<h3 style="border-bottom: 1px solid #CCC;">{$aTranslations.filter}</h3>
	<div class="GUIDialogContentPadding">
		{foreach $aFilters as $oFilter}
			{include file=$sFilterTemplatePath}
		{/foreach}
	</div>
</div>
<div id="statistic_content" class="statistic_margin"></div>
{if !empty($aInfoBoxItems)}
	<div class="GUIDialogNotification alert alert-info statistic_margin">
		<h4>
			<i class="icon fa fa-info"></i> {$aTranslations.hints}
		</h4>
		<br />
		<ul>
			{foreach $aInfoBoxItems as $sInfoBoxItem}
				<li>{$sInfoBoxItem}</li>
			{/foreach}
		</ul>
	</div>
{/if}
	</div>
{/block}
