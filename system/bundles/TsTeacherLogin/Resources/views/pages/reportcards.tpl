{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Report cards'|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1 class="header-tooltip">{'Report Cards'|L10N} <i class="fa fa-info-circle" data-placement="bottom" data-toggle="tooltip" title="{'<h4>Usage of report cards:</h4>
        <p>You can create and edit report cards in this area.</p>
        <p>The following entries are displayed in the list:
        <ul>
        <li>Report cards that are to be created according to the templates and whose students you teach.</li>
        <li>Manually created report cards to which you are assigned as a examiner.</li>
        </ul>'|L10N|escape}"></i>
        </h1>
    </div>
    <div class="content">
        <div class="box">
            <div class="box-body table-responsive">
                <form action="{route name='TsTeacherLogin.teacher_reportcards'}" method="get">
                    <div class="fc-toolbar fc-header-toolbar row">
                        <div class="col-md-3 text-left" style="padding-left: 0">
                            <div class="fc-button-group">
                                <button type="button" class="btn btn-sm btn-default" id="prev-week" {if !$bPreviousWeek}disabled{/if}>
                                    <span class="fa fa-arrow-left"></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="next-week" {if !$bNextWeek}disabled{/if}>
                                    <span class="fa fa-arrow-right"></span>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-default" id="current-week" {if $bIsCurrentWeek == true}disabled{/if}>{'Current week'|L10N}</button>
                            <input type="hidden" value="{$sBackendWeekFrom}" name="week" id="week-date">
                        </div>
                        <div class="col-md-6">
                            <h2><span id="week-start">{$sWeekFrom}</span> – <span id="week-end">{$sWeekUntil}</span></h2>
                        </div>
                        <div class="fc-clear"></div>
                    </div>

                    <div class="alert alert-success alert-dismissible" id="success-alert" style= "display:none;">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <i class="icon fa fa-check"></i><span></span>
                    </div>
                    <div class="alert alert-danger alert-dismissible" id="error-alert" style="display:none;">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <i class="icon fa fa-exclamation"></i><span></span>
                    </div>

                    <table class="table table-hover table-striped" id="reportcards_table">
                        <thead>
                            <tr>
                                <th style="width: auto;">{'Student'|L10N}</th>
                                <th style="width: 150px;">{'Course'|L10N}</th>
                                <th style="width: 170px;">{'Course start'|L10N}</th>
                                <th style="width: 170px;">{'Course end'|L10N}</th>
                                <th style="width: auto;">{'Transcript'|L10N}</th>
                                <th style="width: 200px;">{'Date of transcript'|L10N}</th>
                                <th style="width: 50px;">{'PDF'|L10N}</th>
                                <th style="width: 70px;">{'Send'|L10N}</th>
                            </tr>
                        </thead>
                        <tbody>
                        {foreach $aExamsData as $aExamData}
                            <tr class="reportcards_table_row" data-template-id="{$aExamData['template_id']}" data-program-service-id="{$aExamData['program_service_id']}" data-inquiry-course-id="{$aExamData['inquiry_course_id']}" data-examination-date="{$aExamData['examination_date_object']->format('Y-m-d')}" data-examination-term-id="{$aExamData['examination_term_id']}" data-examination-version-id="{$aExamData['examination_version_id']}" data-examination-id="{$aExamData['examination_id']}">
                                <td>{$aExamData['student_name']}</td>
                                <td>{$aExamData['course_name']}</td>
                                <td>{$aExamData['course_from']}</td>
                                <td>{$aExamData['course_until']}</td>
                                <td>{$aExamData['examination_name']}</td>
                                <td>{$aExamData['examination_date_formatted']}</td>
                                <td style="text-align: center;">{if $aExamData['examination_version_id'] != 0}<a target="_blank" href="{route name='TsTeacherLogin.teacher_reportcards_file' iVersionId=$aExamData['examination_version_id'] }"><i class="fa fa-file-pdf-o"></i></a>{/if}</td>
                                <td style="text-align: center;">{if $aExamData['examination_version_id'] != 0}<a {if $aExamData['student_email'] == ''} class="disabled-link" data-placement="left" data-toggle="tooltip" title="{'No email is set for this student'|L10N}" {else}data-toggle="modal" data-target="#modal_{$aExamData['examination_version_id']}"{/if}><i class="fa fa-paper-plane"></i></a>{/if}</td>
                            </tr>
							{if $aExamData['examination_version_id'] != 0}
                            <div class="modal fade" id="modal_{$aExamData['examination_version_id']}" style="display: none;">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">×</span></button>
                                            <h4 class="modal-title">{'E-Mail sending confirmation'|L10N}</h4>
                                        </div>
                                        <div class="modal-body">
                                            <p>{'Are you sure you want to email the report card to this student: (%s)?'|L10N|sprintf:$aExamData['student_name']}</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">{'Cancel'|L10N}</button>
                                            <button type="button" class="btn btn-primary email-reportcard" data-version-id="{$aExamData['examination_version_id']}" data-student-id="{$aExamData['student_id']}">{'Send email'|L10N}</button>
                                        </div>
                                    </div>
                                    <!-- /.modal-content -->
                                </div>
                                <!-- /.modal-dialog -->
                            </div>
							{/if}
                        {/foreach}
                        </tbody>
                    </table>

                </form>
                <div class="alert alert-danger alert-dismissible" id="error-unauthorized-access" style="display: none;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <i class="icon fa fa-exclamation"></i> {'Unauthorized access!'|L10N}
                </div>
            </div>
                <!-- /.box-body -->
        </div>
        <div class="modal fade" id="reportcards_modal" tabindex="-1" role="dialog" aria-labelledby="reportcards_modal_label">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="reportcards_modal_title">{'Edit report card'|L10N}</h4>
                    </div>
                    <div id="reportcards_modal_body" class="modal-body">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{'Cancel'|L10N}</button>
                        <button type="button" id="submit-btn" class="btn btn-primary">{'Save changes'|L10N} <i class="fa fa-spinner fa-pulse"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
{/block}

{block name="footer_js"}
    <script>
		$(function () {
            new TsTeacherLogin.Reportcards('{$sDaterangepickerFormat}');
		});
    </script>
{/block}
