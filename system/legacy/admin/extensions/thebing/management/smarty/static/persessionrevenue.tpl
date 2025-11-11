{foreach from=$aCourses item='aData'}
    <table class="stat_result margin fixed">
        <colgroup>
            {foreach from=$aColumns item='aColumn'}
                <col style="width: {$aColumn.width}px">
            {/foreach}
        </colgroup>
	    <tr><th colspan="{$aColumns|@count}">{$aData[0].course_name}</th></tr>
        <tr>
            {foreach from=$aColumns item='aColumn'}
                <th style="background-color: {$aColors[$aColumn.color].color_light}">{$aColumn.title}</th>
            {/foreach}
        </tr>
        {foreach from=$aData item='aRow' name='dataForeach'}
            {assign var='sRowTag' value='td'}
            {if $smarty.foreach.dataForeach.last}
                {* Letzte Zeile ist Summenzeile *}
                {assign var="sRowTag" value='th'}
            {/if}
            <tr>
                {foreach from=$aColumns item='aColumn'}
                    <{$sRowTag} style="{if $aColumn.summable}text-align: right;{/if}">{$aRow[$aColumn.value]}</{$sRowTag}>
                {/foreach}
            </tr>
        {/foreach}
    </table>
{/foreach}