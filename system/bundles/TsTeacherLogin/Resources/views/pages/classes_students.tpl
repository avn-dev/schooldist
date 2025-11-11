<form onsubmit="return false">
    <div class="box">
        <div class="box-body table-responsive no-padding">

            <!-- Table -->
            <table class="table table-hover table-striped" style="table-layout: fixed;" id="timetable_details_table">
                <thead>
                    <tr>
                        <th style="width: 25px;"><span><i class="fa fa-sort-numeric-asc"></i></span></th>
                        <th style="width: auto;">{'Personal information'|L10N}</th>
                        <th style="width: 60px;">{'Status'|L10N}</th>
                        <th style="width: 65px;">{'Level'|L10N}</th>
                        <th style="width: 70px;">{'Group'|L10N}</th>
                        <th style="width: 90px;">{'Raum'|L10N}</th>
                        <th style="width: 180px;">{'Course'|L10N}</th>
                        <th style="width: 200px;">{'Course comment'|L10N}</th>
                        <th style="width: 30px;"><i class="fa fa-history"></i></th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $aStudents as $iKey => $oStudent}
                        <tr class="students-modal-row">
                            <td>{$iKey+1}</td>
                            <td><span data-toggle="tooltip" title="{$oStudent->getTooltipValue()}">{$oStudent->getMainColumnPayload()}</span></td>
                            <td><span data-toggle="tooltip" title="{'Course status'|L10N}({$oStudent->getCourseState()}): {$aStates[$oStudent->getCourseState()]}<br>{'Booking status'|L10N}({$oStudent->getInquiryState()}): {$aStates[$oStudent->getInquiryState()]}">{$oStudent->getCourseState()}/{$oStudent->getInquiryState()}</span></td>
                            <td>{$oStudent->getLevel()}</td>
                            <td>{$oStudent->getGroup()}</td>
                            <td>{$oStudent->getAllocation()->getRoom()->name}</td>
                            <td>{$oStudent->getCourse()}<br>{$oStudent->getStartDate()} - {$oStudent->getEndDate()}</td>
                            <td>{$oStudent->getCourseComment()}</td>
                            <td class="students-info-icon" data-id="{$oStudent->getInquiryId()}"><i class="fa fa-info-circle"></i></td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <br>
            <div class="alert alert-danger alert-dismissible" id="errorAlert" style="display: none;">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fa fa-exclamation"></i> {'Your changes could not be saved!'|L10N}
            </div>
            <div class="alert alert-success alert-dismissible" id="successAlert" style="display: none;">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fa fa-check"></i> {'Your changes have been saved successfully!'|L10N}
            </div>

            {if $oSchool->teacherlogin_show_internal_class_comment && $oBlock->getClass()->internal_comment}
                <div class="col-xs-12">
                    <div class="form-group">
                        <label>{'Internal comment'|L10N}</label>
                        <div>
                            {$oBlock->getClass()->internal_comment|nl2br}
                        </div>
                    </div>
                </div>
            {/if}
            <div class="col-xs-12">
                <div class="form-group">
                    <label>{'Content'|L10N}</label>
                    <textarea id="block-description" name="description" class="form-control"></textarea>
                </div>
            </div>
        </div>
        <!-- /.box-body -->
    </div>

    <div class="box box-default collapsed-box" id="description-box">
        <div class="box-header">
            <h3 class="box-title">{'Content overview (%d entries)'|L10N|sprintf:count($aDescriptions)}</h3>

            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
                </button>
            </div>
            <!-- /.box-tools -->
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="box-comments">
                {foreach $aDescriptions as $aDescription}
                    <div class="box-comment">
                        <small>
                            {'Week'|L10N} {$aDescription['week_num']}:
                            {if !empty($aDescription['block_date'])}
                                {$aLocaleDays[reset($aDescription['block_days'])]} {$aDescription['block_date']}
                            {else}
                                {$aDescription['week_from']} - {$aDescription['week_until']}
                            {/if}
                        </small>
                        <br>
                        {$aDescription['description']}
                    </div>
                {/foreach}
            </div>
        </div>
        <!-- /.box-body -->
    </div>

    {if
        \TcExternalApps\Service\AppService::hasApp(\TsTeacherLogin\Handler\ExternalApp::APP_NAME) &&
        $oBlock->isEditableByTeacher($oTeacher)
    }
        <div class="box box-default collapsed-box" id="class-edit-box">
            <div class="box-header">
                <h3 class="box-title">
                    {if $disabled}
                        <i class="fas fa-lock"></i>
                    {/if}
                    {'Klasse bearbeiten'|L10N|sprintf:count($aDescriptions)}
                </h3>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                <!-- /.box-tools -->
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                {include file="system/bundles/TsTeacherLogin/Resources/views/pages/timetable/class_fields.tpl"}
            </div>
            <!-- /.box-body -->
        </div>
    {/if}
</form>

{foreach $aStudents as $oStudent}
    <div id="modal_content_student_{$oStudent->getInquiryId()}" style="display: none;">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{$oStudent->getName()}</h4>
        </div>
        <div class="modal-body" >

            {foreach $oStudent->getPreviousAllocations() as $sWeek => $aWeekAllocations}
                {$bFirst = $aWeekAllocations@first}
                <div class="box box-default  {if !$bFirst}collapsed-box{/if} collapsible-box-student-{$oStudent->getInquiryId()}">
                    <div class="box-header">
                        <h3 class="box-title">{'Week %1$d: %2$s - %3$s'|L10N|sprintf:$aWeekAllocations['week_num']:$aWeekAllocations['week_from']:$aWeekAllocations['week_until']}</h3>

                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-{($bFirst) ? 'minus' : 'plus'}"></i>
                            </button>
                        </div>
                        <!-- /.box-tools -->
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover table-striped student-details" style="table-layout: fixed;">
                            <thead>
                            <tr>
                                <th style="width: 80px;">{'Course'|L10N}</th>
                                <th style="width: 100px;">{'Class'|L10N}</th>
                                <th style="width: 85px;">{'Room'|L10N}</th>
                                <th style="width: 100px;">{'Days'|L10N}</th>
                                <th style="width: 105px;">{'Time'|L10N}</th>
                                <th style="width: 80px;">{'Teacher'|L10N}</th>
                                <th style="width: 100px;">{'Level'|L10N}</th>
                                <th style="width: auto;">{'Content'|L10N}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach $aWeekAllocations['allocations'] as $aAllocation}
                                <tr>
                                    <td>{$aAllocation['course_short']}</td>
                                    <td>{$aAllocation['class_name']}</td>
                                    <td>{$aAllocation['classroom']}</td>
                                    <td>{$aAllocation['days']}</td>
                                    <td>{$aAllocation['block_from']} - {$aAllocation['block_until']}</td>
                                    <td>{$aAllocation['teacher_name']}</td>
                                    <td>{$aAllocation['block_level']}</td>
                                    <td>{$aAllocation['block_description']}</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- Table -->
            {/foreach}

            {$oPlacementTest = $oStudent->getPlacementTestResult()}

            {if !empty($oPlacementTest)}

                {$aLevel = $oPlacementTest->getLevel()->getData()}

                <h3>{'Placement test result'|L10N}:</h3>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover table-striped student-details" style="table-layout: fixed;">
                        <thead>
                            <tr>
                                <th style="width: 150px;">{'Internal level'|L10N}</th>
                                <th style="width: 120px;">{'Mark'|L10N}</th>
                                <th style="width: 120px;">{'Score'|L10N}</th>
                                <th style="width: auto;">{'Comment'|L10N}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{$aLevel['name_short']}</td>
                                <td>{$oPlacementTest->mark}</td>
                                <td>{$oPlacementTest->score}</td>
                                <td>{$oPlacementTest->comment}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
{/foreach}
