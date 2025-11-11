{extends file="system/bundles/AdminTools/Resources/views/layout.tpl"}

{block name="heading"}

{/block}

{block name="content"}

    {$colors = array_unique(Factory::executeStatic('Util', 'getColor'))}
    {$colorkeys = array_keys($colors)}
    <div class="box box-default">
        <div class="box-body table-responsive no-padding">

            <table class="table table-striped table-hover">
                <tr>
                    <th style="width:100px;">{'Number'|L10N}</th>
                    <th style="width:150px;">{'Name'|L10N}</th>
                    <th style="width:150px;">{'Colorcode'|L10N}</th>
                    <th style="width:500px;">{'Textcolor'|L10N}</th>
                    <th style="width:auto;">{'Backgroundcolor'|L10N}</th>
                    <th style="width:250px;"></th>
                </tr>
                {$i = 1}
                {foreach array_combine($colors, $colorkeys) as $color => $colorkey}
                    <tr>
                        <td>{$i}</td>
                        <td>{$colorkey}</td>
                        <td>{$color}</td>
                        <td bgcolor="white"><font color="{$color}">{'This is an Example Text'|L10N}</font></td>
                        <td bgcolor="{$color}">{'This is an Example Text'|L10N}</td>
                    </tr>
                    {$i = $i+1}
               {/foreach}
            </table>
        </div>
    </div>
{/block}
