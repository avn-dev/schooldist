{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}

    <div data-help-key="description">
        {$step->getHelpText('description', '')}
    </div>

    <div class="pull-right">
        <a href="{$wizard->routeStep($step, 'step.save', ['action' => 'new_school'])}" class="btn bg-maroon">
            {$wizard->translate('Ja')}
        </a>
        <a href="{$wizard->routeStep($step, 'step.save', ['action' => 'next'])}" class="btn btn-primary">
            {$wizard->translate('Nein')}
        </a>
    </div>

{/block}
