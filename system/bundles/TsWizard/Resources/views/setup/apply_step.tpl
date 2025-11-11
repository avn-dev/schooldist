{extends file="system/bundles/Tc/Resources/views/wizard/table_step.tpl"}

{block name="table-content-before"}

    {if $helpTextMode}
        <p>Platzhalter für Schule: <code>%school</code></p>
    {/if}

    <div data-help-key="description">
        {$step->getHelpText('description', '')|replace:"%school":"<code>$schoolName</code>"}
    </div>
    
{/block}
