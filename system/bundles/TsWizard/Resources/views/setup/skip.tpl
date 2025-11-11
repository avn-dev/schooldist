{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}

    {if $helpTextMode}
        <p>Platzhalter für Block: <code>%block</code></p>
    {/if}

    <div data-help-key="description">
        {$step->getHelpText('description', '')|replace:"%block":"<code>$blockName</code>"}
    </div>

    <div class="pull-right">
        <a href="{$wizard->routeStep($step, 'step.save', ['action' => 'skip'])}" class="btn bg-maroon">
            {$wizard->translate('Überspringen')}
        </a>
        <a href="{$wizard->routeStep($step, 'step.save', ['action' => 'edit'])}" class="btn btn-primary">
            {$wizard->translate('%s bearbeiten')|replace:"%s":$blockName}
        </a>
    </div>

{/block}
