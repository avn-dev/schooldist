{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Timetable'|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1>{'Timetable'|L10N}</h1>
        <div class="breadcrumb" style="top: 7px;">
            {if
                \TcExternalApps\Service\AppService::hasApp(\TsTeacherLogin\Handler\ExternalApp::APP_NAME) &&
                $oTeacher->hasAccessRight(\Ext_Thebing_Teacher::ACCESS_CLASS_SCHEDULING)
            }
                <button
                    id="newClass"
                    type="button"
                    class="btn btn-sm btn-primary"
                >
                    {'Neu Klasse anlegen'|L10N}
                </button>
            {/if}
        </div>
    </div>
    <div class="content">
        <div class="box">
            <div class="box-body">
                <div id="calendar"></div>
            </div>
            <!-- /.box-body -->
        </div>

        <div class="modal fade" id="timetableModal" tabindex="-1" role="dialog" aria-labelledby="timetableModalLabel">
            <div class="modal-dialog modal-lg" role="dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="timetableModalLabel"></h4>
                    </div>
                    <div id="timetableModalBody" class="modal-body" >

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{'Cancel'|L10N}</button>
                        <button type="button" id="timetableModalBtn" class="btn btn-primary">{'Save changes'|L10N} <i class="fa fa-spinner fa-pulse"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="modal-student" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="dialog">
                <div class="modal-content">

                </div>
            </div>
        </div>

    </div>
{/block}

{block name="footer_js"}
    <script>
        {if !empty($aViewPeriod)}
            var oViewPeriod = {$aViewPeriod|json_encode};
        {else}
            var oViewPeriod = null;
        {/if}
		new TsTeacherLogin.Timetable('{$sInterfaceLanguage}', '{$sDateFormat}', '{$sShortDateFormat}', {$aClassTimes|json_encode}, oViewPeriod);
    </script>
{/block}
