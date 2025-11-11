{$sHint}
<div id="form_content" {if $oDesign->id <= 0}style="display:none;"{/if}>
	<div class="accordion floatLeft">
		
		{assign var=iPanelIndex value=0}
		
		{foreach from=$oDesigner->getFixLists(true) item=aListData}
			<h3 data-panel-index="{$iPanelIndex}">
				<a href="#" tabindex="-1">
					{if $aListData.self == false}
						{$sFixElementTitle}
					{else}
						{$aListData.self->getName()}
					{/if}
				</a>
			</h3>
			<div class="accordionContent">
				<ul class="elementBox" {if !$aListData.self} id="fix_tab_elements" {/if}>
					{foreach from=$aListData.childs item=oElement}
						<li id="{$oElement->generateDesignerID()}" class="filter_col fix_elements">
							{if $oElement->getSelfFlagSrc() != ""}
								<span><img src="{$oElement->getSelfFlagSrc()}"/></span>
							{/if} 
							{if $oElement->getParentFlagSrc() != ""}
								<span><img src="{$oElement->getParentFlagSrc()}"/></span>
							{/if} 
							<span class="spanTitle">{$oElement->getName()}</span>
							{if $oElement->required == 1}
								<span class="required">*</span>
							{/if}
						</li>
					{/foreach}
				</ul>
			</div>
				
			{math equation="x + y" x=$iPanelIndex y=1 assign=iPanelIndex}	
								
		{/foreach}
		<h3 data-panel-index="{$iPanelIndex}">
			<a href="#" tabindex="-1">{$sDynamicElementTitle}</a>
		</h3>
		<div class="accordionContent">
			<ul class="elementBox">
				{foreach from=$oDesigner->getDynamicElements() item=oElement}
					<li id="{$oElement->generateDesignerID(true)}" class="filter_col">
						<span class="spanTitle">{$oElement->getName()}</span>
					</li>
				{/foreach}
			</ul>
		</div>
			
		{math equation="x + y" x=$iPanelIndex y=1 assign=iPanelIndex}	
			
		<h3 data-panel-index="{$iPanelIndex}">
			<a href="#" tabindex="-1">{$sFlexFieldsTitle}</a>
		</h3>
		<div class="accordionContent">
			<ul class="elementBox">
				{foreach from=$oDesigner->getFlexFields() item=oElement}
					<li id="{$oElement->generateDesignerID(true)}" class="filter_col">
						<span class="spanTitle">{$oElement->getName()}</span>
					</li>
				{/foreach}
			</ul>
		</div>
			
		{math equation="x + y" x=$iPanelIndex y=1 assign=iPanelIndex}	
			
	</div>
	<div id="form_pages_content">
		<div id="form_pages_tabs">
			{foreach from=$oDesign->getJoinedObjectChilds('tabs') item=oTab name='tabs'}
				<div class="form_pages_tab {if $smarty.foreach.tabs.first}form_pages_tab_active{/if}" id="pages_tab_{$oTab->id}">
					<span class="form_tab_title">{$oTab->getName()}</span>
					<span class="form_tab_icons">
						<i id="edit_tab_{$oTab->id}" class="edit_tab fa {$sEditIconPath}"></i>
						<i id="remove_tab_{$oTab->id}" class="remove_tab fa {$sRemoveIconPath}"></i>
						<span class="divCleaner"></span>
					</span>
					<div class="divCleaner"></div>
				</div>
			{/foreach}
			<span class="divCleaner"></span>
		</div>
		<div class="form_pages_tab" style="padding-bottom:4px;">
			<i id="add_page_icon" class="fa {$sAddIconPath}" style="margin-left:0;"></i>
			<div class="divCleaner"></div>
		</div>
		<div class="divCleaner"></div>
		<div id="form_pages_contents"> 
			{foreach from=$oDesign->getJoinedObjectChilds('tabs') item=oTab name='contents'}
				<ul class="form_pages_content sortable" id="pages_tab_{$oTab->id}_content" {if !$smarty.foreach.contents.first}style="display:none;"{/if}>
					{foreach from=$oTab->getMainElements() item=oElement}
						{$oElement->generateDesignerHtml()}
					{/foreach}
				</ul>
			{/foreach}
		</div>
		<div class="divCleaner"></div>
	</div>
	<div class="divCleaner"></div>
</div>

<div id="dom_test_div">
	
</div>