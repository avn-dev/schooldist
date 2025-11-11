{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{$oSchool->name|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1>{'Dashboard'|L10N}</h1>
    </div>
    <div class="content">
        <div class="box box-primary">
            <div class="box-body">
                <p>{$sWelcomeText}</p>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
{/block}