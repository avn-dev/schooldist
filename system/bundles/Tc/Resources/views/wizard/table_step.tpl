{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}

    <div data-help-key="description" style="margin-bottom: 10px">
        {$step->getHelpText('description', '')}
    </div>

    {block name="table-content-before"}{/block}

    {$table->render($wizard)}

    {block name="table-content-after"}{/block}

{/block}

{block name="step-buttons"}
    {if !$wizard->isIndexStep($step)}
        <button type="submit" class="btn btn-primary">{$wizard->translate('Weiter')}</button>
    {/if}
{/block}