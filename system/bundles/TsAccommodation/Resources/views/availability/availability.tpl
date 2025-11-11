{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}

		<link rel="stylesheet" href="/admin/assets/interface/css/tailwind.css?v={\System::d('version')}"/>
		<link rel="stylesheet" href="/admin/extensions/gui2/gui2.css" />
		<link rel="stylesheet" href="/assets/ts/css/gui2.css" />

		<style>

		.stat_result {
			border: 1px solid #CCC;
			
		}

		.stat_result.margin {
			margin-bottom: 15px;
		}

		.stat_result.fixed {
			table-layout: fixed;
		}

		.stat_result th, .stat_result td {
			border: 1px solid #CCC!important;
		}

		.stat_result th {
			padding: 5px;
			background-color: #EEE;
		}

		.stat_result .noWrap {
			white-space: nowrap;
		}

		.stat_result td {
			padding: 5px;
			text-align: right;
		}

		.stat_result .tr_bg_light {
			background-color: #FFF;
		}

		.stat_result .tr_bg_dark {
			background-color: #F7F7F7;
		}

		.stat_result .border_big {
			border-right: 3px solid #CCC;
		}
		
		.stat_result .small {
			font-size: inherit;
			letter-spacing: -0.07em;
			word-spacing: 0.01em;
		}
		
		.w75 {
			width: 75px!important;
		}
		
		.totals {
			font-weight: bold;
		}
		</style>
		
{/block}

{block name="content"}

<script type="text/javascript">
	var iCurrentWeek = {$iCurrentWeek};
</script>

	<section class="content-header">
		<h1>{$aTranslations.title}</h1>
	</section>

	<section class="content">
		<div class="box">
			<div class="box-body">
				<div class="w-full h-8 inline-flex items-center">
					<label class="mb-0 p-1.5">{$aTranslations.filter}</label>
					<div class="guiBarFilter inline-flex items-center">
						<span class="divToolbarLabel p-1.5 mb-1">{$aTranslations.from}</span>
						<select class="form-control input-sm" id="from">
							{foreach from=$aWeeks item='sWeek' key='iWeekStart'}
								<option value="{$iWeekStart}" {if $iWeekStart == $iCurrentWeek}selected="selected"{/if}>{$sWeek}</option>
							{/foreach}
						</select>
					</div>

					<div class="guiBarFilter inline-flex items-center">
						<span class="divToolbarLabel p-1.5">{$aTranslations.till}</span>
						<select class="form-control input-sm" id="till">
							{foreach from=$aWeeks item='sWeek' key='iWeekEnd'}
								<option value="{$iWeekEnd}" {if $iWeekEnd == $iCurrentWeek}selected="selected"{/if}>{$sWeek}</option>
							{/foreach}
						</select>
					</div>

					<div class="divToolbarSeparator p-1.5"> :: </div>

					<div class="guiBarFilter inline-flex items-center">
						<select class="form-control input-sm" id="school">
							<option value="">-- {$aTranslations.school} --</option>
							{foreach from=$aSchools item='sSchool' key='iSchoolID'}
								<option value="{$iSchoolID}" {($iSchoolID == $iDefaultSchool) ? 'selected' : '' }>{$sSchool}</option>
							{/foreach}
						</select>
					</div>

					<div class="divToolbarSeparator p-1.5"> :: </div>

					<div class="guiBarFilter inline-flex items-center">
						<select class="form-control input-sm" id="category">
							<option value="">-- {$aTranslations.category} --</option>
							{foreach from=$aCategories item='sCategory' key='iCategoryID'}
								<option value="{$iCategoryID}">{$sCategory}</option>
							{/foreach}
							<option value="all">{$aTranslations.all}</option>
						</select>
					</div>
					<div class="divToolbarSeparator p-1.5"> :: </div>
					<div class="guiBarFilter inline-flex items-center">
						<label class="divToolbarLabelGroup mb-0 p-1.5">{$aTranslations.view}</label>
						<select class="form-control input-sm" id="view">
							{foreach from=$aTranslations.views item='sValue' key='sKey'}
								<option value="{$sKey}">{$sValue}</option>
							{/foreach}
						</select>
					</div>
					<div class="divToolbarSeparator p-1.5"> :: </div>
					<div class="guiBarFilter inline-flex items-center">
						<label class="filter_label mb-0 pr-1.5" for="export_xls">{'Export'|L10N:'Thebing » Accommodation » Availability'}</label>
						<button style="margin-left:6px" class="fa fa-file-excel-o export-icon icon form-control input-sm" name="export_xls" onclick="loadTable(true)"></button>
					</div>

					<div class="divToolbarIcon">
						<img style="height:16px; width:1px;" alt="" src="/admin/media/spacer.gif">
						<img style="display:none;" alt="" src="/admin/media/indicator.gif" id="loading_indicator">
					</div>
				</div>
				<div id="blocksScroller" style="overflow-y:scroll; overflow-x:auto; clear: both;">
					<div id="blocksContainer" style="margin:20px;"></div>
				</div>
			</div>

		</div>
	</section>

{/block}

{block name="footer"}

		<script type="text/javascript" src="/admin/ts/accommodation/resources/js/availability.js"></script>

{/block}