{extends file="system/bundles/Gui2/Resources/views/page.tpl"}

{block name="system_head"}
	<link rel="stylesheet" href="/assets/ts-reporting/css/reporting_legacy.css" />
{/block}

{block name="system_footer"}
	<script src="/admin/extensions/thebing/management/js/statistic.js?v={\System::d('version')}"></script>
	<script src="/admin/extensions/thebing/management/js/pages.js?v={\System::d('version')}"></script>

	<script>
		// Globaler Mist für pages.js
		var aGUI = {};
		var iGlobalPageID = {$iPageID};
		var aStatisticTypes = new Object();
	    var sStaticReportClass = '{$sStaticReportClass}';
	    var sDateFormat = '{$sDateFormat}';

		{foreach from=$aStatistics item='aStatistic' key='iStatisticID'}
			{if $iStatisticID && $aStatistic.type}
				aStatisticTypes[{$iStatisticID}] = {$aStatistic.type};
			{/if}
		{/foreach}

		function initPage() {
			initStatisticPage();
		}
	</script>
{/block}

{block name="html"}
<div class="divHeader clearfix">
	<div class="divHeaderSeparator"><div class="header-line"></div></div>
	<div class="divToolbar form-inline" style="width:100%; height:36px;">
		<div class="grow">
			<div class="elements-container">

				<label class="divToolbarLabelGroup">{$aTranslations.choose_statistic}</label>

				{if $iPageID == 0 && isset($aStatistics)}
					<div class="guiBarFilter">
						<!--<div class="divToolbarLabel">{$aTranslations.choose_statistic}</div>-->
						<select class="txt form-control input-sm" id="statistic" disabled>
							<option value="0"></option>
							{foreach from=$aStatistics item='aStatistic' key='iStatisticID'}
								<option value="{$iStatisticID}">{$aStatistic.title}</option>
							{/foreach}
						</select>
					</div>
				{/if}


				<div id="date_filter_separator" class="divToolbarSeparator" style="display:{if $iPageID == 0 && isset($aStatistics)}none{else}block{/if};"> <span class="hidden">::</span> </div>

				<div id="date_filter" style="display:{if $iPageID == 0 && isset($aStatistics)}none{else}block{/if};">
					<!--<label class="divToolbarLabelGroup">{$aTranslations.date}</label>-->
					<div class="guiBarFilter">
						<div class="divToolbarLabel">{$aTranslations.from}</div>
						<div class="input-group input-group-sm date">
							<div class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</div>
							<input type="text" style="width:80px;" id="from" class="form-control input-sm calendar_input" data-filter="date" autocomplete="off">
						</div>
						<div class="divToolbarLabel">{$aTranslations.until}</div>
						<div class="input-group input-group-sm date">
							<div class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</div>
							<input type="text" style="width:80px;" id="till" class="form-control input-sm calendar_input" data-filter="date" autocomplete="off">
						</div>
					</div>
				</div>

				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>

				<div class="guiBarElement guiBarLink" onclick="alert('{$aTranslations.do_not_use}'); loadTable();">
					<div class="divToolbarIcon">
						<i class="fa fa-colored fa-refresh"></i>
					</div>
					<div class="divToolbarLabel">{$aTranslations.refresh}</div>
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

<div id="blocksScroller" style="overflow-y:scroll; overflow-x:auto;">
	<div id="blocksContainer"></div>
	<div id="blocksContainerInfo">
		<div class="alert alert-danger">
			<p>{'Bitte auf eine neue Auswertung migrieren, da diese Auswertung nicht mehr gewartet wird und zukünftig entfernt werden wird.'|L10N}</p>
		</div>
		<div class="alert alert-info">
			<h3><i class="icon fa fa-info"></i> {$aTranslations.info}</h3>
			<ul>
				{foreach from=$aTranslations.infos item=sInfo key=iInfoKey}
					<li>{$sInfo}</li>
				{/foreach}
			</ul>
		</div>
	</div>
</div>
			
<div id="hidden_form"></div>
{/block}
