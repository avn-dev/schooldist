
<script type="text/javascript">
	var iDisplayType = {$iDisplayType};
</script>

{literal}
<script type="text/javascript">
	Event.observe(window, 'load', function()
	{
		var oScroller		= $('scroller');
		var oBody			= document.getElementsByTagName("BODY")[0];
		var oH1				= $('headerTitle');
		var iBodyHeight		= oBody.getHeight();
		var iScrollerHeight = oScroller.getHeight();
		var iH1Height		= oH1.getHeight();
		var dProcent		= parseInt((iBodyHeight-iH1Height) / iBodyHeight * 100);

		oScroller.style.height = dProcent+'%';
	});

	// onchange event kommt nicht in Event.observe rein, weil es zu viele checkboxxen gibt
	function updateRelation(bChecked, iDimensionX, iDimensionY){

		var handle;
		if(bChecked){
			handle = 'add';
		}else{
			handle = 'remove';
		}

		new Ajax.Request('/admin/extensions/thebing/admin/statistic_relation.ajax.php', {
		  method: 'post',
		  parameters: { handle: handle, x_id: iDimensionX, y_id: iDimensionY, type: iDisplayType }
		});
	}
</script>
{/literal}

<div class="divHeader" style="padding-bottom:0;">
	<h1 id="headerTitle">{$sDisplayTitle}</h1>
	<div class="divCleaner"></div>
</div>

<div id="scroller" style="height:100%; overflow-y:scroll;">
	<table class="table guiTableHead" style="table-layout:auto; border-collapse:separate;">
		<thead>
			<tr>
				{if 1 == $iDisplayType}
					<th />
				{/if}
				{foreach from=$aHeaderRows item='sThead' key='iKey' name='head'}
					<th {if $iDisplayType == 2 && !$smarty.foreach.head.first}style="width:140px;"{/if}>{$sThead}</th>
				{/foreach}
			</tr>
		</thead>
		<tbody class="guiTableTBody">
			{assign var=iStartPos value=1}
			{foreach from=$aRelations item='aInfos' key='sTitle'}
				<tr class="">
					<th class="guiBodyColumn">{$sTitle}</th>
					{assign var=i value=1}
					{foreach from=$aTrBuild item='sXDimensionTitle' key='iXDimensionId'}
						<td class="guiBodyColumn" {if $iDisplayType == 2 && !$smarty.foreach.head.first}style="width:140px;"{/if}>
							{if 2==$iDisplayType || $i>$iStartPos}
								{if $iXDimensionId|in_array:$aInfos.data}
									<input type="checkbox" value="1" checked="checked" onchange="updateRelation(this.checked, {$iXDimensionId}, {$aInfos.dimension_y});" />
								{else}
									<input type="checkbox" value="1" onchange="updateRelation(this.checked, {$iXDimensionId}, {$aInfos.dimension_y});" />
								{/if}
							{/if}
						</td>
						{assign var=i value=$i+1}
					{/foreach}
				</tr>
				{assign var=iStartPos value=$iStartPos+1}
			{/foreach}
			</tbody>
	</table>
</div>