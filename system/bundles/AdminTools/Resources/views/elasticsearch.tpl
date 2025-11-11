{extends file="system/bundles/AdminTools/Resources/views/layout.tpl"}
{block name="title"}Elasticsearch{/block}
{block name="heading"}Elasticsearch{/block}

{block name="content"}
<div style="padding: 30px; width: 1200px;">

    <h2>All indexes</h2>
    <table class="table" cellpadding="4" cellspacing="0" style="width: 100%;">
        <tr><th>Index</th><th>Types</th><th>Count</th><th>Stack</th></tr>
        {foreach from=$aIndicesData.indices key=sIndexName item=aIndex}
            {if isset($aPossibleIndices[$sIndexName])}
                <tr>
                    <td>{$aPossibleIndices[$sIndexName]} ({$sIndexName})</td>
                    <td>{$aTypesPerIndex[$sIndexName]|default:''}</td>
                    <td align="right">{$aStats[$sIndexName]._all.total.docs.count|default:0}</td>
                    <td align="right">{$stackCount[$sIndexName]|default:0}</td>
                </tr>
            {/if}
        {/foreach}
    </table>

    <br><br>

    <form method="post" action="/admin/tools/elasticsearch">
        <table class="table" cellpadding="4" cellspacing="0" style="width: 100%;">
            <tr>
                <th>Index</th>
                <td>
                    <select name="index" onchange="this.form.submit();">
                        {foreach from=$aPossibleIndices key=sIndexName item=sIndex}
                            <option value="{$sIndexName}" {if $index == $sIndexName}selected{/if}>{$sIndex}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            {if $index}
                <tr>
                    <th>Type</th>
                    <td>
                        <select name="type" onchange="this.form.submit();">
                            {foreach from=$aIndicesTypes[$index] key=sType item=sTypeVal}
                                <option value="{$sType}" {if $type == $sType}selected{/if}>{$sTypeVal}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
            {/if}
            <tr><th>Search</th><td><input type="text" name="search" value="{$search|escape}"></td></tr>
            <tr><th>Show mapping</th><td><input type="checkbox" name="show_mapping" value="1" {if $showMapping}checked{/if}></td></tr>
            {if $oResultSet}
                <tr><th>Results</th><td>{$oResultSet->getTotalHits()}</td></tr>
            {/if}
        </table>
        <input type="submit" value="Submit">
    </form>

    {if $oResultSet}
        <h2>Results</h2>
        <pre>{$oResultSet->getResults()|print_r|escape}</pre>

        {if $showMapping}
            <h2>Mapping</h2>
            <pre>{$aMapping|print_r|escape}</pre>
        {/if}
    {/if}

</div>
{/block}