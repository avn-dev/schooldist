{extends file="system/bundles/Gui2/Resources/views/page.tpl"}

{block name="system_head"}
	<link rel="stylesheet" href="/assets/ts-reporting/css/reporting_legacy.css">
{/block}

{block name="system_footer"}
	<script>
		var iCurrentWeek = {$iCurrentWeek};
	    var aTranslations = {$aTranslationsJson};

		function initPage() {
			initOverviewPage();
		}
	</script>
	<script src="/admin/extensions/thebing/tuition/js/report.js?v={\System::d('version')}"></script>
	<script src="/admin/extensions/thebing/tuition/js/own_overview.js?v={\System::d('version')}"></script>
{/block}

{block name="html"}
<div class="divHeader clearfix">
	<div class="divHeaderSeparator"><div class="header-line"></div></div>
	<div class="divToolbar form-inline" style="width: 100%; height: 36px;">
		<div class="grow">
			<div class="elements-container">

				{* Erste Zeile *}

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="report_id">
						<option value="0">-- {$aTranslations.overview} --</option>
						{foreach from=$aReports item='sReport' key='iReportID'}
							<option value="{$iReportID}">{$sReport}</option>
						{/foreach}
					</select>
				</div>

				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>

				<!--<div id="week_filter">-->
					<div class="guiBarFilter">
						<div class="divToolbarLabel">{$aTranslations.courseweek}</div>
						<select class="txt form-control input-sm" id="week">
							{foreach from=$aWeeks item='sWeek' key='iWeekStart'}
								<option value="{$iWeekStart}" {if $iWeekStart == $iCurrentWeek}selected="selected"{/if}>{$sWeek}</option>
							{/foreach}
						</select>
					</div>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon">
							<i class="fa fa-backward" title="{$aTranslations.last_week}" onclick="changeFilterWeek('last');"></i>
						</div>
					</div>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon">
							<i class="fa fa-stop" title="{$aTranslations.current_week}" onclick="changeFilterWeek('current');"></i>
						</div>
					</div>
					<div class="guiBarElement guiBarLink">
						<div class="divToolbarIcon">
							<i class="fa fa-forward" title="{$aTranslations.next_week}" onclick="changeFilterWeek('next');"></i>
						</div>
					</div>
				<!--</div>-->

				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>

				<div class="guiBarElement guiBarLink" onclick="getExport('pdf');">
					<div class="divToolbarIcon">
						<i class="fa fa-colored fa-file-pdf-o"></i>
					</div>
					<div class="divToolbarLabel">{$aTranslations.pdf}</div>
				</div>

				<div class="guiBarElement guiBarLink" onclick="getExport('csv');">
					<div class="divToolbarIcon">
						<i class="fa fa-colored fa-file-excel-o"></i>
					</div>
					<div class="divToolbarLabel">{$aTranslations.csv}</div>
				</div>

				<div class="divToolbarIcon">
					<img style="height:16px; width:1px;" alt="" src="/admin/media/spacer.gif">
					<img style="display:none;" alt="" src="/admin/media/indicator.gif" id="loading_indicator">
				</div>

				<div style="flex-basis: 100%;height: 0;"></div>

				{* Zweite Zeile *}

				<!--<label class="divToolbarLabelGroup" style="visibility: hidden">{$aTranslations.filter}</label>-->

				<div class="guiBarFilter">
					<input type="text" id="search" class="txt form-control input-sm" placeholder="{$aTranslations.search}">
				</div>

				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>


				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="course_category_id">
						<option value="0">-- {$aTranslations.course_category} --</option>
						{foreach from=$aCourseCategories item=sCourseCategory key=iCourseCategoryId}
							<option value="{$iCourseCategoryId}">{$sCourseCategory}</option>
						{/foreach}
					</select>
				</div>

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="course_id">
						<option value="0">-- {$aTranslations.course} --</option>
						{foreach from=$aCourses item=sCourse key=iCourseId}
							<option value="{$iCourseId}">{$sCourse}</option>
						{/foreach}
					</select>
				</div>

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="teacher_id">
						<option value="0">-- {$aTranslations.teacher} --</option>
						{foreach from=$aTeachers item=sTeacher key=iTeacherId}
							<option value="{$iTeacherId}">{$sTeacher}</option>
						{/foreach}
					</select>
				</div>

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="inbox_id">
						<option value="0">-- {$aTranslations.inbox} --</option>
						{foreach from=$aInbox item=sInbox key=iInboxId}
							<option value="{$iInboxId}">{$sInbox}</option>
						{/foreach}
					</select>
				</div>

				<div style="flex-basis: 100%;height: 0;"></div>

				{* Dritte Zeile *}

				<!--<label class="divToolbarLabelGroup" style="visibility: hidden">{$aTranslations.filter}</label>-->

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="weekday">
						<option value="0">-- {$aTranslations.weekday} --</option>
						{foreach from=$aWeekDays item=sWeekDay key=iWeekDay}
							<option value="{$iWeekDay}">{$sWeekDay}</option>
						{/foreach}
					</select>
				</div>

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="tuition_template">
						<option value="0">-- {$aTranslations.course_time} --</option>
						{foreach from=$aTuitionTemplates item=sTuitionTemplate key=iTuitionTemplate}
							<option value="{$iTuitionTemplate}">{$sTuitionTemplate}</option>
						{/foreach}
					</select>
				</div>

				{foreach from=$aStateFilters item=sStateFilter}
					<div class="guiBarFilter">
						<select class="txt form-control input-sm" id="state_{$sStateFilter}">
							<option value="all">-- {assign var=sTransKey value="state_$sStateFilter"}{$aTranslations[$sTransKey]} --</option>
							{if $sStateFilter == 'course'}{assign var='aStates' value=$aCourseStates}{/if}
							{foreach from=$aStates item='sState' key='iKeyState'}
								<option value="{$iKeyState}">{$sState}</option>
							{/foreach}
						</select>
					</div>
				{/foreach}

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="class_color">
						<option value="0">-- {$aTranslations.class_color} --</option>
						{foreach from=$aColors item=sColor key=iColor}
							<option value="{$iColor}">{$sColor}</option>
						{/foreach}
					</select>
				</div>

				<div class="divToolbarSeparator"> <span class="hidden">::</span> </div>

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="course_start_from">
						<option value="0">-- {$aTranslations.course_start_from} --</option>
						{foreach from=$courseTimes item=courseTime key=courseTimeId}
							<option value="{$courseTimeId}">{$courseTime}</option>
						{/foreach}
					</select>
				</div>

				<div class="guiBarFilter">
					<select class="txt form-control input-sm" id="course_start_until">
						<option value="0">-- {$aTranslations.course_start_until} --</option>
						{foreach from=$courseTimes item=courseTime key=courseTimeId}
							<option value="{$courseTimeId}">{$courseTime}</option>
						{/foreach}
					</select>
				</div>

                <div class="guiBarFilter">
                    <select name="courselanguage_id" id="courselanguage_id" class="form-control input-sm">
                        <option value="0">-- {$aTranslations.label_course_language} --</option>
                        {foreach from=$levelGroups item=levelGroup key=levelGroupId}
                            <option value="{$levelGroupId}">
                                {$levelGroup}
                            </option>
                        {/foreach}
                    </select>
                </div>

			</div>
		</div>
		<div class="flex-none"></div>
	</div>
</div>

<div id="blocksScroller" style="overflow-y:scroll; overflow-x:auto; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
	<div id="blocksContainer" style=""></div>
</div>

<div class="divFooter" id="divFooter">
	<div class="divToolbar" style="width:100%;height:36px;">
		<div class="guiBarElement">
			<div class="divToolbarHtml">
				{$sLegendHtml}
			</div>
		</div>
	</div>
	<div class="divCleaner"></div>
</div>

{/block}