{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Communication'|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1>{'Communication'|L10N}</h1>
    </div>
    <div class="content">
        <div class="box">
            <form action="{route name='TsTeacherLogin.teacher_communication'}" method="post" id="communication-form" {if $iBlockId > 0 && $sMessageType != 'sms' && $sMessageType != 'app'}class="dropzone"{/if} enctype="multipart/form-data">
                <div class="box-body">
                    <div class="fc-toolbar fc-header-toolbar row">
                        <div class="col-md-3 text-left" style="padding-left: 0">
                            <div class="fc-button-group">
                                <button type="button" class="btn btn-sm btn-default" id="prev-week">
                                    <span class="fa fa-arrow-left"></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="next-week">
                                    <span class="fa fa-arrow-right"></span>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-default" id="current-week" {if $bIsCurrentWeek}disabled{/if}>{'Current week'|L10N}</button>
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
                        <i class="icon fa fa-exclamation"></i><b>{'Errors have occurred:'|L10N}</b>
                        <ul>

                        </ul>
                    </div>
                    <div class="form-group">
                        <select class="form-control" id="block-select" name="block_id">
                            {'Select course'|L10N}
                            {foreach $aBlocksForSelect as $sKey => $sLabel}
                                <option value="{$sKey}" {if $sKey == $iBlockId}selected{/if}>{$sLabel}</option>
                            {/foreach}
                        </select>
                    </div>

                    {if $iBlockId > 0}
                    <div id="communicationFormDiv" >
                        <div class="form-group">
                            <label>{'Message via'|L10N}</label>
                            <select class="form-control" id="message-type-select" name="message_type">
                                <option value="email" {if $sMessageType == 'email'}selected{/if}>{'E-Mail'|L10N}</option>
                                <option value="sms" {if $sMessageType == 'sms'}selected{/if}>{'SMS'|L10N}</option>
                                <option value="app" {if $sMessageType == 'app'}selected{/if}>{'Schüler-App'|L10N}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>{'Recipient'|L10N}</label>
                            <select multiple="multiple" size="5" class="form-control" id="students" name="students[]">
                                {html_options options=$aStudents selected=$aSelectedStudents}
                            </select>
                        </div>
                        {if $sMessageType != 'sms'}
                        <div class="form-group" id="emailSubjectDiv">
                            <label>{'Subject'|L10N}</label>
                            <input type="text" name="subject" class="form-control" required value="{$oRequest->get('subject')|escape}">
                        </div>
                        {/if}
                        <div class="form-group">
                            <label>{'Message'|L10N}</label>
                            <textarea class="form-control" name="message" required rows="6">{$sMessage|escape}</textarea>
							<span class="help-block">{'Placeholder:'|L10N} {literal}{className}, {classContent}{/literal}</span>
                        </div>
                        {if $sMessageType != 'sms' && $sMessageType != 'app'}
                            <div id="dropzone-previews" class="dropzone-previews">
                                <div class="dz-message">
                                {'Click here or drag the file inside.'|L10N}
                                </div>
                            </div>
                        {/if}
                    </div>
                    {/if}
                </div>
                <!-- /.box-body -->
                <div class="box-footer">
                    <button type="button" id="submit-btn" class="btn btn-primary pull-right">{'Submit'|L10N} <i class="fa fa-spinner fa-pulse"></i></button>
                </div>
            </form>
        </div>
    </div>
{/block}

{block name="footer_js"}
    <!-- Dropzone -->
    <script src="/assets/dropzone/dropzone.min.js?v={\System::d('version')}"></script>
    <script>

		var sFormAction = "{route name='TsTeacherLogin.teacher_communication_submit'}";

		//Initialize Select2 Elements
        new TsTeacherLogin.Communication(sFormAction);
    </script>

{/block}