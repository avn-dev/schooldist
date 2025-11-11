{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}

    {if $helpTextMode}
        <p>Platzhalter für Schule: <code>%school</code></p>
    {/if}

    <div data-help-key="description">
        {$step->getHelpText('description', '')|replace:"%school":"<code>$schoolName</code>"}
    </div>
{/block}

{block name="step-buttons"}
    <button type="submit" class="btn btn-primary">{$wizard->translate('Starten')}</button>
{/block}