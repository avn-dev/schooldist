{extends file="system/bundles/Tc/Resources/views/wizard/layout.tpl"}

{block name="main-class"}index{/block}

{block name="box-title"}{$wizard->translate('Startseite')}{/block}

{block name="box-content"}
    <div class="box-body overview">
        <div class="row" style="margin-bottom: 15px;">
            <div class="col-md-3 col-xs-12">
                <a href="{$wizard->route('start')}" class="btn btn-block btn-success btn-lg">
                    <i class="fa fa-play-circle" aria-hidden="true"></i>
                    {$wizard->translate('Starten')}
                </a>
            </div>
            {if $wizard->canContinue()}
                <div class="col-md-3">
                    <a href="{$wizard->route('continue')}" class="btn btn-block btn-success btn-lg" data-toggle="tooltip" title="" data-original-title="Saisons » Aktuelle Saisons">
                        <i class="fa fa-forward" aria-hidden="true"></i> {$wizard->translate('Fortsetzen')}
                    </a>
                </div>
            {/if}
        </div>
        <div class="row" style="margin-bottom: 15px;">
            {foreach $structure as $element}

                {if $element['disabled']}
                    {continue}
                {/if}

                {if $element['type'] === \Tc\Service\Wizard\Structure::SEPARATOR}

                    <div class="col-md-12">
                        <hr/>
                    </div>

                {else}

                    {assign var=status value=$element['process_status']}
                    {math equation="x / y * 100" x=$status[0] y=$status[1] assign=percent}

                    <div class="col-md-3 col-xs-12">
                        <div class="info-box block" style="background-color: {$element['color']}">
                            <span class="info-box-icon">
                                {*<i class="counter">{$counter}</i>*}
                                <i class="{$element['icon']}"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">
                                    <a href="{$wizard->route('start', ['stepKey' => $element['step']->getUrlKey()])}">
                                        {$element['title']}
                                    </a>
                                </span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: {$percent}%"></div>
                                </div>
                                <span class="progress-description">
                                    {$percent|round:0}% ({$status[0]} {$wizard->translate('von')} {$status[1]})
                                </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                    </div>
                {/if}
            {/foreach}
        </div>
    </div>
{/block}