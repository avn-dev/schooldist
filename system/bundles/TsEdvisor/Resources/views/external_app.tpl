<form class="form-horizontal" method="post" action="{route name="TcExternalApps.save" sAppKey=$appKey}">
    <div class="box-body">
        {foreach $session->getFlashBag()->get('error', []) as $message}
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fa fa-exclamation"></i> {$message}
            </div>
        {/foreach}
        <div class="box-group">
            <div class="form-group">
                <label class="col-sm-2 control-label">{'API Key'|L10N}</label>
                <div class="col-sm-10">
                    <input type="password" name="api_key" class="form-control" value="{$apiKey}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">{'Inbox'|L10N}</label>
                <div class="col-sm-10">
                    <select name="inbox" class="form-control">
                        {foreach $inboxes as $inboxId => $inboxName}
                            <option value="{$inboxId}" {if $inbox == $inboxId}selected{/if}>{$inboxName}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            {if !empty($edvisorSchools)}
                <h3>{'Schulen'|L10N}</h3>
                {foreach $schools as $school}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">{$school->ext_1}</label>
                        <div class="col-sm-10">
                            <select name="school_mapping[{$school->id}]" class="form-control">
                                <option value="0"></option>
                                {foreach $edvisorSchools as $edvisorSchoolId => $edvisorSchool}
                                    <option value="{$edvisorSchoolId}" {if $schoolMapping[$school->id] == $edvisorSchoolId}selected{/if}>{$edvisorSchool}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                {/foreach}
            {/if}
        </div>
    </div>
    <div class="box-footer">
        <a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
        <button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
        {if !empty($apiKey)}<button type="submit" class="btn btn-default pull-right" name="submit" value="connect">{'Connect webhook'|L10N}</button>{/if}
    </div>
</form>
