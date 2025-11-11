
            <div class="inbox">
            <h2>#page:_LANG['Inbox']#</h2>

		{if $sTask == 'detail' || $sTask == 'add'}
				
			{if $oInquiry->id > 0}
				<p>
					Booking: {$oInquiry->document_number}<br/>
					Date:  {$oInquiry->created|date_format:"%x"}
				</p>
			{/if}
			
			{if $sTask == 'add'}
			<form method="post" name="booking_form" action="?">
			<input type="hidden" name="view" value="inbox" />
			<input type="hidden" name="task" value="add" />
			<input type="hidden" id="act" name="act" value="save" />
			<input type="hidden" name="inquiry_id" value="{$oInquiry->id}" />
			<input type="hidden" id="delete_id" name="delete_id" value="" />
			{/if}

			<fieldset class="fieldsetDetail">
			
			{include file="script.form_fields.tpl"}
			
			</fieldset>
						
			{if $sTask == 'add'}
				<div class="divFormButton">
					<input type="submit" class="inputBtn" value="#page:_LANG['Save booking']#" />
					<input type="button" class="inputBtn" value="#page:_LANG['Release booking']#" onclick="go('?view=inbox&task=release&inquiry_id={$oInquiry->id}'); return false;" />
				</div>
				
				</form>
			{/if}
			
			{if $sTask != 'add' && $oInquiry->active == 0 && $oInquiry->is_agency_inquiry == 1}
				<div class="divFormButton">
					<input type="button" class="inputBtn" value="#page:_LANG['Edit booking']#" onclick="go('?view=inbox&task=add&inquiry_id={$oInquiry->id}'); return false;" />
				</div>
			{/if}
			
			<div class="divFormButton">
				<input type="button" class="inputBtn" value="#page:_LANG['Back']#" onclick="go('?view=inbox'); return false;" />
			</div>
		
		{else}
		
		<form method="post" name="booking_list" action="?">
			<input type="hidden" name="view" value="inbox" />
			<input type="hidden" id="task" name="task" value="" />

			<fieldset>

				<div class="divFormElement">
					<label for="search">#page:_LANG['Search']#</label>
					<input type="text" id="search" name="search" value="{$sSearch|escape}" />
				</div>

				<div class="divFormElement">
					<label for="from">#page:_LANG['Period']#</label>
					<input type="text" class="w80" id="filter_from" name="filter_from" value="{$iFrom|date_format:"%x"}" />
					#page:_LANG['to']#
					<input type="text" class="w80" id="filter_until" name="filter_until" value="{$iUntil|date_format:"%x"}" />
				</div>
			
				<div class="divFormElement">
					<label for="filter">#page:_LANG['Filter']#</label>
					<select id="filter" name="filter">
						{html_options options=$aFilterOptions selected=$sFilter}
					</select>
				</div>
			
			</fieldset>
		
			<div class="divFormButton">
				<input type="submit" class="inputBtn" value="#page:_LANG['Update list']#" />
			</div>
	
			<div class="divPagination">
				<div class="divLeft">
					<a href="?view=inbox&offset={$aPagination.offset_back}">#page:_LANG['back']#</a>
				</div>
				<div class="divCenter">
					#page:_LANG['Bookings']# {$aPagination.from+1} #page:_LANG['to']# {$aPagination.to} #page:_LANG['of']# {$aPagination.total}
				</div>
				<div class="divRight">
					<a href="?view=inbox&offset={$aPagination.offset_forward}">#page:_LANG['forward']#</a>
				</div>
			</div>
		
			<table class="tblInquiries">
				<tr>
					<th style="width:20px;">&nbsp;</th>
					<th style="width:100px;">#page:_LANG['Invoice no.']#</th>
					<th style="width:auto;">#page:_LANG['Name']#</th>
					<th style="width:160px;">#page:_LANG['School']#</th>
					<th style="width:80px;">#page:_LANG['Paid']#</th>
					<th style="width:80px;">#page:_LANG['Total']#</th>
					<th style="width:80px;">#page:_LANG['Course start']#</th>
					<th style="width:40px;">#page:_LANG['Net']#</th>
					<th style="width:40px;">#page:_LANG['Gross']#</th>
					<th style="width:40px;">#page:_LANG['LoA']#</th>
					<th style="width:80px;">#page:_LANG['Acco.']#</th>
				</tr>
				{foreach from=$aInquiries item=aInquiry}
					<tr ondblclick="openDetails('{$aInquiry.id}');" style="cursor: pointer;" {if $aInquiry.active == 0}class="trHighlight"{/if}>
						<td style="text-align: center;">
						{if $aInquiry.confirmed == 0 || $aInquiry.active == 0}
							<input type="checkbox" name="inquiries[]" value="{$aInquiry.id}" />
						{else}
							&nbsp;
						{/if}
						</td>
						<td>{$aInquiry.document_number}</td>
						<td>{$aInquiry.ext_1}, {$aInquiry.ext_2}</td>
						<td>{$aInquiry.school}</td>
						<td style="text-align: right;">{$aInquiry.paid|number_format:2:",":"."} {$aInquiry.currency}</td>
						<td style="text-align: right;">{$aInquiry.amount|number_format:2:",":"."} {$aInquiry.currency}</td>
						<td>{$aInquiry.course_start|date_format:"%x"}</td>
						<td style="text-align: center;">
							{if $aInquiry.idInvoiceNet > 0}
								<a href="?task=get_document&amp;type=get_inquiry_document&amp;document_id={$aInquiry.idInvoiceNet}&amp;inquiry_id={$aInquiry.id}" onclick="window.open(this.href); return false;"><img src="?task=get_image&amp;image=page_white_acrobat.png" alt="#page:_LANG['Net invoice']#" /></a>
							{/if}
						</td>
						<td style="text-align: center;">
							{if $aInquiry.idInvoice > 0}
								<a href="?task=get_document&amp;type=get_inquiry_document&amp;document_id={$aInquiry.idInvoice}&amp;inquiry_id={$aInquiry.id}" onclick="window.open(this.href); return false;"><img src="?task=get_image&amp;image=page_white_acrobat.png" alt="#page:_LANG['Gross invoice']#" /></a>
							{/if}
						</td>
						<td style="text-align: center;">
							{if $aInquiry.idLoa > 0}
								<a href="?task=get_document&amp;type=get_inquiry_document&amp;document_id={$aInquiry.idLoa}&amp;inquiry_id={$aInquiry.id}" onclick="window.open(this.href); return false;"><img src="?task=get_image&amp;image=page_white_acrobat.png" alt="#page:_LANG['Letter of acceptance']#" /></a>
							{/if}
						</td>
						<td style="text-align: center;">
							{foreach from=$aInquiry.family_documents item=sDocument key=sPath}
								<a href="?task=get_document&amp;type=get_family_document&amp;path={$sPath}&amp;inquiry_id={$aInquiry.id}" onclick="window.open(this.href); return false;" title="{$sDocument}"><img src="?task=get_image&amp;image=page_white_acrobat.png" alt="{$sDocument}" /></a>
							{/foreach}
							{if $aInquiry.family_image}
								<a href="?task=get_document&amp;type=get_family_image&amp;inquiry_id={$aInquiry.id}" onclick="window.open(this.href); return false;" title="{$aInquiry.family_image.title}"><img src="?task=get_image&amp;image=page_white_acrobat.png" alt="{$aInquiry.family_image.title}" /></a>
							{/if}
						</td>
					</tr>
				{foreachelse}
					<tr>
						<td colspan="11">#page:_LANG['No inquiries found!']#</td>
					</tr>
				{/foreach}
			</table>
			
			<div class="divFormButton">
				<input type="button" class="inputBtn" value="#page:_LANG['Add booking']#" onclick="go('?view=inbox&task=add'); return false;" />
				<input type="button" class="inputBtn" value="#page:_LANG['Confirm marked bookings']#" onclick="processMarkedBookings('confirm'); return false;" />
				<input type="button" class="inputBtn" value="#page:_LANG['Release booking']#" onclick="processMarkedBookings('release'); return false;" />
			</div>
			
		</form>
		
		{/if}
                </div>