{if !empty($globalActions)}
    <div class="pull-right" style="margin-bottom: 10px;">
        {foreach $globalActions as $action}
            <a href="{$action['link']}" class="btn {$action['color']}"">
            <i class="{$action['icon']}"></i>
            {$action['label']}
            </a>
        {/foreach}
    </div>
{/if}

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            {foreach $columns as $column}
                <th>{$column['label']}</th>
            {/foreach}
            {if !empty($actions)}
                <th class="actions" style="width: 200px;"></th>
            {/if}
        </tr>
    </thead>
    <tbody>
    {foreach $rows as $rowIndex => $row}
        <tr>
            {foreach $columns as $columnIndex => $column}
                <td style="{$column['config']['style']}">
                    {$row[$columnIndex]}
                </td>
            {/foreach}
            {if !empty($actions)}
                <td>
                    {foreach $row['actions'] as $action}

                        {assign var=confirm value=""}
                        {if $action['confirm']}
                            {assign var=confirm value="onclick=\"return confirm('%s')\""|replace:"%s":$action['confirm']}
                        {/if}

                        <a href="{$action['link']}" class="btn btn-sm {$action['color']}" data-toggle="tooltip" title="{$action['label']}" {$confirm}>
                            <i class="{$action['icon']}"></i>
                        </a>
                    {/foreach}
                </td>
            {/if}
        </tr>
    {/foreach}
    </tbody>
</table>

{if empty($rows)}
    <p style="text-align: center;padding: 20px;">{$wizard->translate('Keine Einträge vorhanden')}</p>
{/if}
