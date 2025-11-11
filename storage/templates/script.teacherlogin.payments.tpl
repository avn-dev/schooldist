
			<h2>#page:_LANG['Payments']#</h2>

			{if $bShowPayments}
				<table class="tblInquiries" style="table-layout: fixed;">
					<tr>
						<th style="width:auto;">#page:_LANG['Lastname']#, #page:_LANG['Firstname']#</th>
						<th style="width:200px;">#page:_LANG['Accommodation']#</th>
						<th style="width:120px;">#page:_LANG['Date']#</th>
						<th style="width:100px;">#page:_LANG['Nights']#</th>
						<th style="width:100px;">#page:_LANG['Payment method']#</th>
						<th style="width:80px;">#page:_LANG['Amount']#</th>
					</tr>
					{foreach from=$aPayments item=aPayment}
						<tr>
							<td>{$aPayment.lastname}, {$aPayment.firstname}</td>
							<td>
								{$aPayment.accommodation}
								{$aPayment.room} / {$aPayment.meal}
							</td>
							<td>{$aPayment.created|date_format:"%x %X"}</td>
							<td>{$aPayment.nights}</td>
							<td>{$aPayment.payment_method}</td>
							<td style="text-align: right;">{$aPayment.amount|number_format:"2":",":"."}</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="6">#page:_LANG['No payments found!']#</td>
						</tr>
					{/foreach}
				</table>
			{else}
				<p>#page:_LANG['No access!']#</p>
			{/if}