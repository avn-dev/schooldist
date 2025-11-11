<form role="form" class="form-horizontal" action="{route name='TsTeacherLogin.teacher_reportcards_modal_save'}" method="post">
    <h3 style="margin-top: 0;">{'Transcript details'|L10N}</h3>
    <br>
    <div class="form-group">
        <label class="col-md-2">{'Student'|L10N}</label>
        <div class="col-md-10">
            <select class="form-control input-sm" disabled>
                <option value="{$oContact->id}">{$oContact->getName()}</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-2">{'Examination'|L10N}</label>
        <div class="col-md-4">
            <select class="form-control input-sm" disabled>
                <option value="{$oExaminationTemplate->id}">{$oExaminationTemplate->title}</option>
            </select>
        </div>
        <label class="col-md-2">{'Course'|L10N}</label>
        <div class="col-md-4">
            <select class="form-control input-sm" name="course">
                {html_options options=$aCourses selected=$sSelectedCourse}
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-2">{'Examination date'|L10N}</label>
        <div class="col-md-4">
            <div class="input-group date">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control input-sm pull-right" id="datepicker" disabled>
            </div>
        </div>
        <label class="col-md-2">{'Date range'|L10N}</label>
        <div class="col-md-4">
            <div class="input-group">
                <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                </div>
                <input type="text" class="form-control input-sm pull-right" id="daterange" disabled>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-2">{'Level'|L10N}</label>
        <div class="col-md-4">
            <select class="form-control input-sm" name="level">
                {html_options options=$aLevels selected=$iSelectedLevel}
            </select>
        </div>
        <label class="col-md-2">{'Score'|L10N}</label>
        <div class="col-md-4">
            <div class="input-group">
                <input type="text" class="txt form-control input-sm" name="score" id="score" value="{$aVersionData['score']|escape}">
                <div class="input-group-btn">
                    <button type="button" title="{'Calculate score'|L10N}" id="calculate-score-btn" class="btn btn-default btn-sm">
                        <i class="fa fa-calculator"></i> {'Calculate score'|L10N}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <h3>{'Transcript subject'|L10N}</h3>
    {foreach $aCategories as $sCategoryName => $aCategoryEntities}
        <h4>{$sCategoryName}</h4>
        <br>
        {foreach $aCategoryEntities as $aCategoryEntity}
            <div class="form-group">
                <label class="col-md-2">{$aCategoryEntity['title']}</label>
                <div class="col-md-10">
                    {if $aCategoryEntity['model_class']->getInput() == 'select'}
                        <select class="form-control input-sm" name="sections[{$aCategoryEntity['id']}]">
                            <option value="0"> --- </option>
                            {foreach $aCategoryEntity['section_select_options'] as $oOption}
                                <option value="{$oOption->id}" {if $oOption->id == $aCategoryEntity['section_value']}selected{/if}>{$oOption->title}</option>
                            {/foreach}
                        </select>
                    {elseif $aCategoryEntity['model_class']->getInput() == 'textarea'}
                        <textarea class="form-control" name="sections[{$aCategoryEntity['id']}]">{$aCategoryEntity['section_value']|escape}</textarea>
                    {elseif $aCategoryEntity['model_class']->getInput() == 'checkbox'}
                        <input type="checkbox" value="1" {if $aCategoryEntity['section_value'] == 1}checked{/if} name="sections[{$aCategoryEntity['id']}]">
                    {else}
                        <input class="form-control" name="sections[{$aCategoryEntity['id']}]" value="{$aCategoryEntity['section_value']|escape}">
                    {/if}
                </div>
            </div>
        {/foreach}
    {/foreach}
    <h4>{'Conclusion'|L10N}</h4>
    <br>
    <div class="form-group">
        <label class="col-md-2">{'Comment'|L10N}</label>
        <div class="col-md-10">
            <textarea class="form-control" name="sections_comment">{$aVersionData['comment_sections']|escape}</textarea>
        </div>
    </div>
    <h3>{'Other'|L10N}</h3>
    <br>
    <div class="form-group">
        <label class="col-md-2">{'Passed'|L10N}</label>
        <div class="col-md-10">
            <input type="checkbox" value="1" {if $aVersionData['passed'] == 1}checked{/if} data-passed-checkbox={$aVersionData['passed']} name="passed">
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-2">{'Final grade'|L10N}</label>
        <div class="col-md-10">
            <input type="text" class="form-control input-sm" name="grade" value="{$aVersionData['grade']|escape}">
        </div>
    </div>
    <div class="form-group">
        <label class="col-md-2">{'Note'|L10N}</label>
        <div class="col-md-10">
            <textarea class="form-control" name="comment">{$aVersionData['comment']|escape}</textarea>
        </div>
    </div>
</form>

<div class="alert alert-danger alert-dismissible" id="errorAlert" style="display: none;">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <i class="icon fa fa-exclamation"></i> {'Your changes could not be saved!'|L10N}
</div>
<div class="alert alert-success alert-dismissible" id="successAlert" style="display: none;">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <i class="icon fa fa-check"></i> {'Your changes have been saved successfully!'|L10N}
</div>
