{if $sType == "content" &&  $iColumn == 1}
	<li>
		<table class="areas" id="element_{$iElement}">
			<tr>
				<th>
					<div class="floatLeft block_title">
						{if $oElement->getSelfFlagSrc() != ""}
							<div style="float: left;">
								<img src="{$oElement->getSelfFlagSrc()}" />
							</div>
						{/if}
						<div style="float: left;">
							{if $sUnknownTitel == ""}
								{$sTitel}{if $oElement->isRequired()}*{/if}
							{else}
							<span style="color:red;">
								{$sUnknownTitel}
							</span>
							{/if}
						</div>
					</div>
					<div class="floatRight">
						<span class="form_tab_icons">
							{if $oElement->isEditable()}
								<i id="edit_element_{$iElement}" class="element_edit_img fa {$sEditIconPath}"></i>
							{/if}
							{if $oElement->isDeleteable()}
								<i id="remove_element_{$iElement}" class="element_remove_img fa {$sRemoveIconPath}"></i>
							{/if}
							<span class="divCleaner"></span>
						</span>
					</div>
					<div class="divCleaner"></div>
				</th>
			</tr>
			{if $sHint != ""}
				<tr>
					<td>
						{$sHint}
					</td>
				</tr>
			{/if}
			<tr>
			<tr>
				<td style="padding: 0">
					<ul id="form_pages_content_block_1_{$iElement}" class="form_pages_content {$sParentHash}">
						{$sContent}
					</ul>
					<div class="divCleaner"></div>
				</td>
			</tr>
		</table>
	</li>
{elseif $sType == "content"}
	<li>
		<table class="areas" id="element_{$iElement}">
			<tr>
				<th colspan="2">
					<div class="floatLeft block_title">
						{if $oElement->getSelfFlagSrc() != ""}
							<div style="float: left;">
								<img src="{$oElement->getSelfFlagSrc()}" />
							</div>
						{/if}
						<div style="float: left;">
							{if $sUnknownTitel == ""}
								{$sTitel}{if $oElement->isRequired()}*{/if}
							{else}
							<span style="color:red;">
								{$sUnknownTitel}
							</span>
							{/if}
						</div>
					</div>
					<div class="floatRight">
						<span class="form_tab_icons">
							{if $oElement->isEditable()}
								<i id="edit_element_{$iElement}" class="element_edit_img fa {$sEditIconPath}"></i>
							{/if}
							{if $oElement->isDeleteable()}
								<i id="remove_element_{$iElement}" class="element_remove_img fa {$sRemoveIconPath}"></i>
							{/if}
							<span class="divCleaner"></span>
						</span>
					</div>
					<div class="divCleaner"></div>
				</th>
			</tr>
			{if $sHint != ""}
				<tr>
					<td colspan="2">
						{$sHint}
					</td>
				</tr>
			{/if}
			<tr>
				<td style="width:50%;padding: 0">
					<ul id="form_pages_content_block_1_{$iElement}" class="sortable form_pages_content {$sParentHash}">
						{$sContent}
					</ul>
					<div class="divCleaner"></div>
				</td>
				<td style="width:50%;padding: 0">
					<ul id="form_pages_content_block_2_{$iElement}" class="sortable form_pages_content {$sParentHash}">
						{$sContent2}
					</ul>
					<div class="divCleaner"></div>
				</td>
			</tr>
		</table>
	<li>
{else}
	<li>
		<table id="element_{$iElement}" class="areas field">
			<tr>
				<th>
					{if $oElement->getParentFlagSrc() != ""}
						<div style="float: left;">
							<img src="{$oElement->getParentFlagSrc()}" />
						</div>
					{/if}
					<div class="floatLeft block_title">
						{if $sUnknownTitel == ""}
							{$sTitel}{if $oElement->isRequired()}*{/if}
						{else}
						<span style="color:red;">
							{$sUnknownTitel}
						</span>
						{/if}
					</div>
					<div class="floatRight">
						<span class="form_tab_icons">
							{if $oElement->isEditable()}
								<i id="edit_element_{$iElement}" class="element_edit_img fa {$sEditIconPath}"></i>
							{/if}
							{if $oElement->isDeleteable()}
								<i id="remove_element_{$iElement}" class="element_remove_img fa {$sRemoveIconPath}"></i>
							{/if}
							<span class="divCleaner"></span>
						</span>
					</div>
					<div class="divCleaner"></div>
				</th>
			</tr>
			<tr class="field-content">
				<td>
					{$sHint}
					<div>
						<div class="floatLeft">
							{$sLabel}
						</div>
						<div class="floatRight">
							{$sContent}
						</div>
						<div class="divCleaner"></div>
					</div>
				</td>
			</tr>
		</table>
	</li>
{/if}
