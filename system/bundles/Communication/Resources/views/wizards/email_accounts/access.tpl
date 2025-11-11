{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}
    <div data-help-key="description">
        {$step->getHelpText('description', '')}
    </div>

    {$html}
{/block}

{block name="step-buttons"}
    <button type="submit" class="btn btn-primary">{$wizard->translate('Speichern')}</button>
{/block}
