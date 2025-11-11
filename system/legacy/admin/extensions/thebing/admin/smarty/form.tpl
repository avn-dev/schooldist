


<!--<div class="alert alert-info alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h4><i class="icon fa fa-info"></i> {$aTranslations.info}</h4>
	{$aTranslations.info_data}
</div>-->
	
{if !empty($aErrors)}
	<div class="alert alert-danger">
		<h4><i class="icon fa fa-ban"></i> {$aTranslations.info}</h4>
		{$aErrors|join:'<br>'}
	</div>
{/if}

<div id="form_content" {if $iFormID <= 0}style="display:none;"{/if} data-block-dependency="{$aBlockDependency}">
	<div id="form_blocks" class="infoBox floatLeft">
		{foreach from=$aBlocks item=aBlock}
			<div id="block_{$aBlock.key}" class="filter_col{if $aBlock.disabled} filter_col_disabled{/if}" {if !empty($aBlock['fixed'])}data-fixed="{$aBlock['fixed']}"{/if}>
				<span class="spanTitle">{$aBlock.title}</span>
			</div>
		{/foreach}
	</div>
	<div id="form_pages_content">
		<div id="form_pages_tabs">
			{foreach from=$aPages item=aPage name='tabs'}
				<div class="form_pages_tab {if $smarty.foreach.tabs.first}form_pages_tab_active{/if}" id="pages_tab_{$aPage.id}" data-type="{$aPage.type}">
					<span class="form_tab_title">{$aPage.title}</span>
					<span class="form_tab_icons">
						<i id="edit_page_{$aPage.id}" class="fa {$sEditIconPath}" alt="{$aTranslations.edit}" title="{$aTranslations.edit_page}"></i>&nbsp;
						<i id="remove_page_{$aPage.id}" class="fa {$sRemoveIconPath}" alt="{$aTranslations.remove}" title="{$aTranslations.remove_page}"></i>
					</span>
				</div>
			{/foreach}
			
			<div class="form_pages_tab" style="padding-bottom:4px;">
				<i style="margin-left:0;" id="add_page_icon" class="fa {$sAddIconPath}" alt="{$aTranslations.add_page}" title="{$aTranslations.add_page}"></i>
			</div>
		</div>

		<div class="divCleaner"></div>
		<div id="form_pages_contents">
			{foreach from=$aPages item=aPage name='contents'}
				<div class="form_pages_content sortable" id="form_pages_content_{$aPage.id}" {if !$smarty.foreach.contents.first}style="display:none;"{/if}>
					{foreach from=$aContents[$aPage.id][0] item=aCodes}
						{foreach from=$aCodes item=sCode}
							{$sCode}
						{/foreach}
					{/foreach}
				</div>
			{/foreach}
		</div>
		<div class="divCleaner"></div>
	</div>
	<div class="divCleaner"></div>
</div>

<div id="dom_test_div">
	
</div>