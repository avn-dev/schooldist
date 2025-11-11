		<table class="table table-hover table-striped table-bordered dataTable">
            <thead>
				<tr>
                  <th>{$sLabel}</th>
				  {foreach $aSchools as $iSchoolId=>$sSchool}
                  <th>{$sSchool}</th>
				  {/foreach}
                </tr>
			<thead>
			<tbody>
				{foreach $aItems as $iItemId=>$sItem}
                <tr>
					<td data-item="{$iItemId}">{$sItem}</td>
					{foreach $aSchools as $iSchoolId=>$sSchool}
						
							{if isset($aData[$iItemId][$iSchoolId])}
								<td class="bg-green">
								{$aUsers[$aData[$iItemId][$iSchoolId]]}
							{else}
								<td>
							{/if}
						</td>
					{/foreach}
                </tr>
				{/foreach}
            </tbody>
		</table>
		
		<script type="text/javascript" language="javascript">
			$('.dataTable').DataTable();
		</script>
				
				