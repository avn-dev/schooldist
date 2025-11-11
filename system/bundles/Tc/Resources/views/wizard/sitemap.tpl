{function renderBlock}

    <li class="{($node['active']) ? 'active' : ''}">

        {if $node['type'] === \Tc\Service\Wizard\Structure::SEPARATOR}

            <hr/>

        {else}

            <div class="node bg-success {($node['active']) ? 'btn btn-primary' : ''}{($node['disabled']) ? ' disabled' : ''}"        >
                {if !empty($node['icon'])}
                    <i class="{$node['icon']} icon {($node['active']) ? 'system-color' : ''}">
                        {if $wizard->isEnabled('progress_icons') && $node['checked']}
                            <i class="fas fa-check-circle checked {($node['active']) ? 'system-color' : ''}"></i>
                        {/if}
                    </i>

                {elseif $wizard->isEnabled('progress_icons') && $node['checked']}
                    <i class="fa fa-check icon {($node['active']) ? 'system-color' : ''}"></i>
                {/if}

                {if $node['disabled']}
                    {$node['title']}
                {else}
                    <a href="{$wizard->routeStep($node['step'])}">{$node['title']}</a>
                {/if}

                {if !$node['active'] && !empty($node['elements'])}
                    <span class="label label-default">
                    <i class="fa fa-folder-plus"></i>
                    {count($node['elements'])}
                </span>
                {/if}
            </div>

            {if !empty($node['elements'])}
                <ul>
                    {foreach $node['elements'] as $subElement}
                        {renderBlock node=$subElement}
                    {/foreach}
                </ul>
            {/if}

        {/if}
    </li>
{/function}

<div class="sitemap">
    <ul>
        {foreach $structure as $element}
            {renderBlock node=$element}
        {/foreach}
    </ul>
</div>
