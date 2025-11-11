{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Attendance'|L10N}{/block}

{block name="header_css"}
	<link rel="stylesheet" href="{route name='TsTeacherLogin.teacher_resources' sFile= 'css/summernote.min.css'}">
{/block}

{block name="content"}
	
	<style>
		.student-dummy {
			font-size: 180px;
			color: #ddd;
		}
	</style>
	
    <div class="content-header">
        <h1 class="header-tooltip">
            {'Attendance'|L10N}
            <i class="fa fa-info-circle" data-placement="bottom" data-toggle="tooltip" title="{'<h4>Usage of attendance:</h4><ul><li>Select the main checkbox of the student if the student attended the whole day</li><li>Deselect the checkbox of the student or drag the slider handle to zero if the student was absent</li><li>Drag the slider handle to the required time to mark the student as partly present</li><ul><li>The selected value will set the time the student attended the class</li><li>Selecting 150 of 180 minutes would mean that the student missed 30 minutes of the class</li></ul><li>Click the "Mark as present" icon to mark all students as fully present</li><li>Always save your changes after adjusting the details</li></ul>'|L10N|escape}"></i>
        </h1>
        <!--<div class="breadcrumb" style="top: 7px;">
            <a href="{route name='TsTeacherLogin.teacher_attendance_code'}" class="btn btn-sm btn-primary">{'QR-Code'|L10N}</a>
        </div>-->
    </div>
    <div class="content">
        <div class="box">
            <form id="saveAttendanceForm" action="{route name='TsTeacherLogin.teacher_attendance_save'}" method="post">
				{if $aDays}
                <input type="hidden" name="days" value="{$aDays|array_keys|join:','}">
				{/if}

                <div class="box-body">

                    <div class="fc-toolbar fc-header-toolbar">
                        <div class="fc-left">
                            <div class="fc-button-group">
                                <button type="button" class="btn btn-sm btn-default" id="prev-week" {if !$bPreviousWeek}disabled{/if}>
                                    <span class="fa fa-arrow-left"></span>
                                </button>

                                <button type="button" class="btn btn-sm btn-default" id="next-week" {if !$bNextWeek}disabled{/if}>
                                    <span class="fa fa-arrow-right"></span>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-default" id="current-week" {if $bIsCurrentWeek == true}disabled{/if}>{'Current week'|L10N}</button>
                        </div>
                        <div class="fc-center">
                            <h2>{$sWeekFrom} – {$sWeekUntil}</h2>
                        </div>
                        <div class="fc-right">
                            {if $sViewType == 'extended'}
                                <button type="button" class="btn btn-sm btn-default" id="simple-view" data-view="simple">{'Simple view'|L10N}</button>
                                <button type="button" class="btn btn-sm btn-default" id="weekly-view" data-view="weekly" {if $bWeekly == true}disabled{/if}>{'Weekly'|L10N}</button>
                                <button type="button" class="btn btn-sm btn-default" id="daily-view" data-view="daily" {if $bDaily == true}disabled{/if}>{'Daily'|L10N}</button>
                            {else}
                                <button type="button" class="btn btn-sm btn-default" id="extended-view" data-view="extended">{'Extended view'|L10N}</button>
                            {/if}
                            <input type="hidden" value="{$sViewType|escape}" name="view_type" id="view-type">
                            <input type="hidden" value="{$sPeriod|escape}" name="period" id="period">
                        </div>
                        <div class="fc-clear"></div>
                    </div>
                    <input type="hidden" value="{$sBackendWeekFrom}" name="week" id="week-date">

                    {foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-exclamation"></i> {$sMessage}
                        </div>
                    {/foreach}

                    {foreach $oSession->getFlashBag()->get('success', array()) as $sMessage}
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <i class="icon fa fa-check"></i> {$sMessage}
                    </div>
                    {/foreach}

                    {if !empty($teachers)}
                        <div class="form-group">
                            <select class="form-control" id="teacher-select" name="teacher">
                                {html_options options=$teachers selected=$selectedTeacherId}
                            </select>
                        </div>
                    {/if}

                    {if !empty($aBlocks)}
                        <div class="form-group">
                            <select class="form-control" id="block-select" name="block">
                               {html_options options=$aBlocks selected=$sBlockKey}
                            </select>
                        </div>
                        {if !empty($aStudents)}
                            {if $sViewType == 'extended'}
                                <div class="row">
                                    {foreach $aStudents as $oStudentProxy}
										
										{$allocation = $oStudentProxy->getAllocation()}
										{$course = $allocation->getProgramService()->getService()}
										{$room = $allocation->getRoom()}
										
                                        <div class="col-xs-12 col-sm-12 col-md-6 col-lg-4 attendance-student">
                                            <div class="box box-primary box-solid">
                                                <div class="box-body">
                                                    <div class="box-tools pull-right">
                                                        {if count($aDays) > 1 || !$oBlock->getUnit((int)key($aDays))->isCancelled()}
                                                            <button type="button" class="btn btn-box-tool attendance-edit-btn" data-id="{$oStudentProxy->getInquiryId()}">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        {/if}
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-xs-4 col-sm-5 col-md-5 col-lg-5">
															{if $aPhotos[$oStudentProxy->getInquiryId()]}
                                                            <img class="img-responsive" src="{route name='TsTeacherLogin.teacher_storage' sFile=$aPhotos[$oStudentProxy->getInquiryId()]}">
															{else}
																<i class="fa fa-user student-dummy"></i>
															{/if}															
                                                        </div>
                                                        <div class="col-xs-8 col-sm-7 col-md-7 col-lg-7">
                                                            <div class="checkbox">
                                                                <label class="student-name">
                                                                    <input type="checkbox" {if $aAttendanceValueExists[$oStudentProxy->getInquiryId()] == true}checked{/if} value="1" class="attendant-checkbox" data-id="{$oStudentProxy->getInquiryId()}" name="attendant[{$oStudentProxy->getInquiryId()}]" id="attendant-{$oStudentProxy->getInquiryId()}">
                                                                    {$oStudentProxy->getName()}
                                                                    <br>
                                                                    <small><span data-toggle="tooltip" title="{$oStudentProxy->getTooltipValue(true)}">({$oStudentProxy->getMainColumnPayload(false, true)})</span></small>
                                                                </label>
                                                            </div>
                                                            <ul class="products-list product-list-in-box">
                                                            {foreach $aDays as $iDay => $sDay}
                                                                {if !$oBlock->getUnit($iDay)->isCancelled()}
                                                                    <li class="item">
                                                                        {if $bWeekly == true}
                                                                            <label for="attendance-{$oStudentProxy->getInquiryId()}-{$iDay}">{$sDay}</label>
                                                                        {/if}
                                                                        {* TODO Logik sollte im Controller stattfinden *}
                                                                        <input type="text" class="rangeslider" id="attendance-{$oStudentProxy->getInquiryId()}-{$iDay}" name="attendance[{$oStudentProxy->getInquiryId()}][{$iDay}]" data-id="{$oStudentProxy->getInquiryId()}-{$iDay}" value="{if $aStudentsAttendanceTime[{$oStudentProxy->getInquiryId()|cat:'_'|cat:$iDay}] == $fLessonDuration}{$fLessonDuration}{else}{$aStudentsAttendanceTime[{$oStudentProxy->getInquiryId()|cat:'_'|cat:$iDay}]}{/if}" {if !$aAttendanceValueExists[$oStudentProxy->getInquiryId()]}disabled{/if}>
                                                                        {if
                                                                            $oTeacher->access_right_excused_absence != 0 ||
                                                                            $aExcused[$oStudentProxy->getInquiryId()][$iDay] ||
                                                                            $aAbsenceReason[$oStudentProxy->getInquiryId()][$iDay]
                                                                        }
                                                                            {* Bereich nur anzeigen, wenn das Recht vorhanden ist oder wenn etwas eingetragen wurde, dann aber readonly (disabled) *}
																			<div class="checkbox-container-excused">
																				<div class="checkbox">
																					<label>
																						<input type="hidden" value="0" name="excused[{$oStudentProxy->getInquiryId()}][{$iDay}]">
																						<input type="checkbox" value="1" name="excused[{$oStudentProxy->getInquiryId()}][{$iDay}]" {if $aExcused[$oStudentProxy->getInquiryId()][$iDay]}checked{/if} {if $oTeacher->access_right_excused_absence == 0}disabled{/if}> {'Excused absence'|L10N}
																					</label>
																				</div>
																				{if !empty($aAbsenceReasons)}
																					{$selectedAbsenceReason=$aAbsenceReason[$oStudentProxy->getInquiryId()][$iDay]}
                                                                                    <div class="form-group form-inline">
                                                                                        {if empty($selectedAbsenceReason) || isset($aAbsenceReasons[$selectedAbsenceReason])}
                                                                                        <label for="absence_reason" class="no-bold">{'Absence reason'|L10N}</label>
                                                                                        <select class="form-control input-sm" id="absence_reason" name="absence_reason[{$oStudentProxy->getInquiryId()}][{$iDay}]" {if $oTeacher->access_right_excused_absence == 0}disabled{/if}>
                                                                                             {html_options options=$aAbsenceReasons selected=$selectedAbsenceReason}
                                                                                        </select>
                                                                                        {else}
                                                                                            {'Absence reason'|L10N}: {$aAbsenceReasonsAll[$selectedAbsenceReason]}
                                                                                        {/if}
                                                                                    </div>
																				{/if}
																			</div>
                                                                        {/if}
																		<div class="checkbox-container-online">
																		{if
																			$course->isHybrid() &&
																			$room->isOffline()
																		}
																			<div class="checkbox">
																				<label>
																					<input type="hidden" value="0" name="online[{$oStudentProxy->getInquiryId()}][{$iDay}]">
																					<input type="checkbox" value="1" name="online[{$oStudentProxy->getInquiryId()}][{$iDay}]" {if $aOnline[$oStudentProxy->getInquiryId()][$iDay]}checked{/if}> {'Participated online'|L10N}
																				</label>
																			</div>
																		{elseif $course->canBeOnline()}
																			<input type="hidden" value="1" name="online[{$oStudentProxy->getInquiryId()}][{$iDay}]">
																		{else}
																			<input type="hidden" value="0" name="online[{$oStudentProxy->getInquiryId()}][{$iDay}]">
                                                                        {/if}
																		</div>
                                                                    </li>
                                                                {/if}
                                                            {/foreach}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- /.box-body -->

                                                <div class="box-footer" id="attendance-edit-form-{$oStudentProxy->getInquiryId()}" style="{if $bExpandFieldsBlock == true}display: block;{else}display: none;{/if}">
                                                    <div class="form-group row">
                                                        <label class="col-lg-5 col-sm-5">{'Total attendance'|L10N}</label>
                                                        <div class="col-lg-7 col-sm-7">
                                                            <span>
                                                                {Ext_Thebing_Format::Number($aTotalAttendance[$oStudentProxy->getInquiryId()], null, null, false, 2)}%
                                                            </span>
                                                        </div>
                                                    </div>
                                                    {foreach $aFlexFields as $oFlexField}
                                                        {if $oFlexField['type'] == '0'}
                                                            <div class="form-group row" style="-ms-word-wrap: ;word-wrap: break-word;">
                                                                <label class="col-lg-5 col-sm-5" for="flex-{$oFlexField['id']}-{$oStudentProxy->getInquiryId()}">{$oFlexField['title']|L10N|escape}</label>
                                                                <div class="col-lg-7 col-sm-7">
                                                                    <input type="text" class="form-control input-sm" id="flex-{$oFlexField['id']}-{$oStudentProxy->getInquiryId()}" name="flex[{$oFlexField['id']}][{$oStudentProxy->getInquiryId()}]" value="{$aFlexFieldsValues[$oFlexField['id']][$oStudentProxy->getInquiryId()]}">
                                                                </div>
                                                            </div>
                                                        {elseif $oFlexField['type'] == '2'}
                                                            <div class="form-group row" style="-ms-word-wrap: ;word-wrap: break-word;">
                                                                <label style="display: inline-block;" class="col-lg-5 col-sm-5 col-xs-4" for="flex-{$oFlexField['id']}-{$oStudentProxy->getInquiryId()}">{$oFlexField['title']|L10N}</label>
                                                                <input type="hidden" value="0" name="flex[{$oFlexField['id']}][{$oStudentProxy->getInquiryId()}]">
                                                                <div class="col-lg-7 col-sm-7 col-xs-8">
                                                                    <input type="checkbox" value="1" {if $aFlexFieldsValues[$oFlexField['id']][$oStudentProxy->getInquiryId()] == 1} checked {/if} name="flex[{$oFlexField['id']}][{$oStudentProxy->getInquiryId()}]" id="flex-{$oFlexField['id']}-{$oStudentProxy->getInquiryId()}">
                                                                </div>
                                                            </div>
                                                        {elseif $oFlexField['type'] == '5'}
                                                            <div class="form-group row" style="-ms-word-wrap: ;word-wrap: break-word;">
                                                                <label class="col-lg-5 col-sm-5">{$oFlexField['title']|L10N}</label>
                                                                <div class="col-lg-7 col-sm-7">
                                                                    <select class="form-control input-sm" name="flex[{$oFlexField['id']}][{$oStudentProxy->getInquiryId()}]" id="flex-{$oFlexField['id']}-{$oStudentProxy->getInquiryId()}">
                                                                        {html_options options=$aFlexFieldsSelectOptions[$oFlexField['id']] selected=$aFlexFieldsValues[$oFlexField['id']][$oStudentProxy->getInquiryId()]}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        {/if}
                                                    {/foreach}
                                                    <div class="form-group row">
                                                        <label class="col-lg-5 col-sm-5" for="score-{$oStudentProxy->getInquiryId()}">{'Score'|L10N}</label>
                                                        <div class="col-lg-7 col-sm-7">
                                                            <input type="text" class="form-control input-sm" id="score-{$oStudentProxy->getInquiryId()}" name="score[{$oStudentProxy->getInquiryId()}]" value="{$aScores[$oStudentProxy->getInquiryId()]}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <label class="col-lg-5 col-sm-5" for="comment-{$oStudentProxy->getInquiryId()}">{'Comment'|L10N}</label>
                                                        <div class="col-lg-7 col-sm-7">
                                                            <textarea class="form-control input-sm" id="comment-{$oStudentProxy->getInquiryId()}" name="comment[{$oStudentProxy->getInquiryId()}]">{$aComments[$oStudentProxy->getInquiryId()]}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            {else}
                                {foreach $aStudents as $oStudentProxy}
                                    <div class="checkbox">
                                        <label class="student-name student-name-simple">
                                            <input type="checkbox" {if $aPresentStudents[$oStudentProxy->getInquiryId()] == true}checked{/if} value="1" class="attendant-checkbox" data-id="{$oStudentProxy->getInquiryId()}" name="attendant[{$oStudentProxy->getInquiryId()}]" id="attendant-{$oStudentProxy->getInquiryId()}">
                                            {$oStudentProxy->getName()}
                                            <br>

                                            <small>({$oStudentProxy->getCustomerNumber()}{if $showCourseCommentsInAttendance[$oStudentProxy->getId()]}, {$showCourseCommentsInAttendance[$oStudentProxy->getId()]}{/if})</small>
                                        </label>
                                    </div>
                                {/foreach}
                            {/if}
                        {else}
                            <div class="alert alert-info alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                <i class="icon fa fa-exclamation"></i> {'No students have been assigned to this block yet!'|L10N}
                            </div>
                        {/if}
						
                    {else}
                        <div class="alert alert-info alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <i class="icon fa fa-exclamation"></i> {'There are no available blocks in this week!'|L10N}
                        </div>
                    {/if}
					
					{if $aDays && count($aDays) == 1 && !$oBlock->getUnit((int)key($aDays))->isCancelled()}
                        <h3>{'Session comment'|L10N}</h3>
                        <textarea class="summernote" name="daily_comment[{(int)key($aDays)}]">{$oBlock->getUnit((int)key($aDays))->comment|escape:"html"}</textarea>
					{/if}
					
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    {if !empty($aStudents)}
                        {if $aDays && count($aDays) == 1}
                            {if $oBlock->getUnit((int)key($aDays))->isCancelled()}
                                {* TODO Wieder aktivierbar? *}
                                <div class="alert alert-danger">
                                    {'This lesson will not take place.'|L10N}
                                </div>
                            {elseif !$oBlock->getUnit((int)key($aDays))->hasTakenPlace() && empty($aAttendanceValueExists)}
                                <button type="button" id="block-state" class="btn btn-danger" data-block="{$oBlock->id}" data-day="{(int)key($aDays)}">
                                    <i class="fa fa-times"></i>
                                    {'Lesson do not take place'|L10N}
                                </button>
                            {/if}
                        {/if}
                        {if count($aDays) > 1 || !$oBlock->getUnit((int)key($aDays))->isCancelled()}
                            <button type="submit" id="submit-btn" class="btn btn-primary pull-right">{'Save'|L10N}</button>
                            <button type="button" id="check-all-btn" class="btn btn-default">{'Mark all as present'|L10N}</button>
                            {if $sViewType == 'extended'}
                                <button type="button" class="btn btn-default" id="hide_fields_btn" {if $bExpandFieldsBlock == false}style="display: none"{/if}>{'Collapse all fields'|L10N}</button>
                                <button type="button" class="btn btn-default" id="show_fields_btn" {if $bExpandFieldsBlock == true}style="display: none"{/if}>{'Expand all fields'|L10N}</button>
                            {/if}
                        {/if}
                    {/if}
                </div>
            </form>
        </div>
			
		{if
            $teacherCanAddStudents &&
            (
                count($aDays) > 1 ||
                !$oBlock->getUnit((int)key($aDays))->isCancelled()
            )
        }
        <div class="box">
            <form action="{route name='TsTeacherLogin.teacher_attendance_add_students'}" method="post">
				<input type="hidden" value="{$sBackendWeekFrom}" name="week" id="week-date">
				<input type="hidden" name="block" value="{$sBlockKey|escape}">

                <div class="box-body">

					<h3>{'Add students'|L10N}</h3>

					{foreach $potentialStudents as $potentialStudentProxy}
						<div class="checkbox">
							<label class="student-name student-name-simple">
								<input type="checkbox" value="{$potentialStudentProxy->getProgramServiceId()}" class="add-student" data-id="{$potentialStudentProxy->getId()}" name="potential-student[{$potentialStudentProxy->getId()}]" id="potential-student-{$potentialStudentProxy->getId()}">
								{$potentialStudentProxy->getName()}
								<br>
								<small>({$potentialStudentProxy->getCustomerNumber()})</small>
							</label>
						</div>
					{/foreach}
					
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="submit" id="submit-btn" class="btn btn-primary pull-right">{'Save'|L10N}</button>
                </div>
            </form>
        </div>
		{/if}
    </div>

    <div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="attendanceModalLabel">
        <div class="modal-dialog" role="dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="attendanceModalLabel"></h4>
                </div>
                <div id="attendanceModalBody" class="modal-body" >

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{'Cancel'|L10N}</button>
                    <button type="button" id="attendanceModalBtn" class="btn btn-primary">
                        {'Save changes'|L10N}
                        <i class="fa fa-spinner fa-pulse" style="display: none"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

{/block}

{block name="footer_js"}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.2.0/js/ion.rangeSlider.min.js"></script>
    <script src="{route name='TsTeacherLogin.teacher_resources' sFile= 'js/summernote.min.js'}"></script>
    <script src="{route name='TsTeacherLogin.teacher_resources' sFile= 'js/lang/summernote-de-DE.min.js'}"></script>
    <script>
        {if !empty($fLessonDuration)}
            var fLessonDuration = {$fLessonDuration};
        {else}
            var fLessonDuration = 0.0;
        {/if}
        new TsTeacherLogin.Attendance(fLessonDuration, '{route name='TsTeacherLogin.teacher_attendance'}');
        TsTeacherLogin.unloadConfirmOnChangeMessage = '{'Es gibt nicht gespeicherte Daten! Wirklich die Seite verlassen?'|L10N|escape}';
		$(document).ready(function() {
            $('#saveAttendanceForm').submit(function(e) {
                TsTeacherLogin.setChange(false);
            }).change(function(e) {
				if (
					e.target !== $('#block-select')[0] &&
					e.target !== $('#teacher-select')[0]
                ) {
					TsTeacherLogin.setChange();
				}
			});
			$('.summernote').summernote({
				callbacks: {
					onChange: function(contents, $editable) {
						TsTeacherLogin.setChange();
					}
				},
				toolbar: [
					['style', ['style']],
					['font', ['bold', 'underline', 'clear']],
					['color', ['color']],
					['para', ['ul', 'ol', 'paragraph']],
					['table', ['table']],
					['insert', ['link']],
					['view', ['fullscreen']],
				  ]
			});
		});
		
    </script>
{/block}
