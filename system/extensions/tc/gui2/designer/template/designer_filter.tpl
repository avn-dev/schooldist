{$sHint}
<div id="filter_content" {if $oDesign->id <= 0}style="display:none;"{/if}>
	<div class="accordion floatLeft">
		<h3>
			<a href="#" tabindex="-1">{$sFixElementTitle}</a>
		</h3>
		<div class="accordionContent">
			<div class="elementBox">
				{foreach from=$oDesigner->getFixElements() item=oElement}
					<div id="{$oElement->generateDesignerID()}" class="filter_element_list fix_elements">
						<span class="spanTitle">{$oElement->getName()}</span>
					</div>
				{/foreach}
			</div>
		</div>
		<h3>
			<a href="#" tabindex="-1">{$sDynamicElementTitle}</a>
		</h3>
		<div class="accordionContent">
			<div class="elementBox">
				{foreach from=$oDesigner->getDynamicElements() item=oElement}
					<div id="{$oElement->generateDesignerID()}" class="filter_element_list">
						<span class="spanTitle">{$oElement->getName()}</span>
					</div>
				{/foreach}
			</div>
		</div>
	</div>
	<div id="form_pages_filter">
		<h3>{$sFilterRowsTitel}</h3>
		<table class="table" id="form_pages_filter_rows">
			{foreach from=$oDesign->getJoinedObjectChilds('filterrows') item=oRow name='tabs'}
				<tr class="form_pages_filter_row" id="form_pages_filter_row_{$oRow->id}">
					<th>
						<span class="form_tab_title">{$oRow->getName()}</span>
						<br/>
						<span class="form_tab_icons">
							<i id="edit_filter_row_{$oRow->id}" class="edit_filter_row fa {$sEditIconPath}"></i>
							<i id="remove_filter_row_{$oRow->id}" class="remove_filter_row fa {$sRemoveIconPath}"></i>
							<span class="divCleaner"></span>
						</span>
					</th>
					<td>
						<div class="filter_row_content" id="filter_row_{$oRow->id}">
							{foreach from=$oRow->getMainElements() item=oElement}
								{$oElement->generateDesignerHtml()}
							{/foreach}
							<span class="divCleaner"></span>
						</div>
					</td>
				</tr>	
			{/foreach}
		</table>
		<img style="margin-left:0;" id="add_filter_row_icon" src="{$sAddIconPath}" />
		<div class="divCleaner"></div>
	</div>
	<div class="divCleaner"></div>
</div>

<div id="dom_test_div">
	
</div>