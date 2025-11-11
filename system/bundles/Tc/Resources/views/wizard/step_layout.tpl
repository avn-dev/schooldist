{extends file="system/bundles/Tc/Resources/views/wizard/layout.tpl"}

{block name="sidebar"}
    <nav class="open">

        {$sitemap->render($step)}

    </nav>

{/block}

{block name="main-class"}steps{/block}

{*{block name="breadcrumbs"}
    <ol class="breadcrumb">
        {foreach $step->getTitles() as $breadcrumb}
            <li>{$breadcrumb}</li>
        {/foreach}
    </ol>
{/block}*}

{block name="box-title"}{$title}{/block}

{block name="box-content"}

    <form id="stepForm" action="{$saveUrl}" method="post" class="form-horizontal" autocomplete="off" {block name="form-attributes"}{/block}>
        <input type="hidden" id="action" name="action" value="save" />
        <div class="box-body">
            {block name="step-content"}{/block}
        </div>

        <div class="box-footer">
            {block name="box-footer"}{/block}
        </div>
    </form>
{/block}

{block name="box-footer"}

    {if $step && !$wizard->isIndexStep($step)}
        <a href="{$backUrl}" class="btn btn-default">
            {$wizard->translate('Zurück')}
        </a>
    {/if}

    {if $wizard->isEnabled('stop_and_continue')}
        <button type="button" class="btn btn-success" onclick="$('#action').val('save_exit');$('#stepForm').submit();" data-toggle="tooltip" title="{$wizard->translate('Sie können zu einem späteren Zeitpunkt fortfahren')}">
            {$wizard->translate('Speichern & Schließen')}
        </button>
    {/if}

    <div class="pull-right">
        {block name="step-buttons"}{/block}
    </div>
{/block}

{block name="footer-additional-js"}
    {if $helpTextMode}
        <script>
            var helpKey = "{$step->getHelpTextKey()}";
            {literal}
            $(function() {
                $('*[data-help-key]').css({cursor: 'pointer'})
                    .click(function () {
                        editHelpText(helpKey, $(this).data('help-key'));
                    })
                $('div[data-help-key]').css({backgroundColor: '#EEE', minHeight: '20px'})
            });
            {/literal}
        </script>
    {/if}
{/block}
