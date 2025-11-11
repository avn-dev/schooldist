<div id="divThebingAgencyArea">

	<script>
		{literal}
		function openDetails(iInquiryId) {
			document.location.href = '?view=inbox&task=detail&inquiry_id='+iInquiryId;
		}
		function openAddress(iAddressId) {
			document.location.href = '?view=delivery_addresses&task=add&address_id='+iAddressId;
		}
		function openOrder(iOrderId) {
			document.location.href = '?view=material_orders&task=detail&order_id='+iOrderId;
		}
		function go(sUrl) {
			document.location.href = sUrl;
		}
		function processMarkedBookings(sMode) {
			
		}
		function deleteEntry(sElement, iKey) {
			document.getElementById('act').value = 'delete_'+sElement;
			document.getElementById('delete_id').value = iKey;
			document.booking_form.submit();
		}
		function addEntry(sElement, iKey) {
			document.getElementById('act').value = 'add_'+sElement;
			document.booking_form.submit();
		}
		function reload(sForm) {
			document.getElementById('act').value = 'reload';
			if(sForm) {
				document.forms[sForm].submit();
			} else {
				document.booking_form.submit();
			}
		}

		function processMarkedBookings(sAction) {
			document.getElementById('task').value = sAction;
			document.booking_list.submit();
		}

		{/literal}
	</script>

<h1>#page:_LANG['Agency area']#</h1>

{if $bLoggedIn}
		
	<ul class="ulNavigation">
		<li {if $sView == 'inbox'}class="liActive"{/if}><a href="?view=inbox">#page:_LANG['Inbox']#</a></li>
		<li {if $sView == 'material_orders'}class="liActive"{/if}><a href="?view=material_orders">#page:_LANG['Material orders']#</a></li>
		<li {if $sView == 'delivery_addresses'}class="liActive"{/if}><a href="?view=delivery_addresses">#page:_LANG['Delivery addresses']#</a></li>
		<li {if $sView == 'administration'}class="liActive"{/if}><a href="?view=administration">#page:_LANG['Administration']#</a></li>
		<li {if $sView == 'statistics'}class="liActive"{/if}><a href="?view=statistics">#page:_LANG['Statistics']#</a></li>
		<li><a href="?logout=ok">#page:_LANG['Logout']#</a></li>
	</ul>
<div style="clear:both;"></div>

	{if $sView == 'inbox'}
	
		{include file="script.agencylogin.inbox.tpl"}

	{elseif $sView == 'material_orders'}
	
		{include file="script.agencylogin.material_orders.tpl"}
	
	{elseif $sView == 'delivery_addresses'}
	
		{include file="script.agencylogin.delivery_addresses.tpl"}
	
	{elseif $sView == 'administration'}

	<div class="inbox">

		<h2>#page:_LANG['Administration']#</h2>
		
		{if $bError}
			<p class="pError">#page:_LANG['Please try again.']#</p>
		{/if}
		{if $bSuccess}
			<p class="pSuccess">#page:_LANG['Your password was changed successfully.']#</p>
		{/if}

		<form method="post" action="?">
			<input type="hidden" name="view" value="administration" />
			
			<fieldset>
			
				<div class="divFormElement">
					<label for="password">#page:_LANG['Password']#</label>
					<input type="password" id="password" name="password" value="" />
				</div>
			
				<div class="divFormElement">
					<label for="password_repeat">#page:_LANG['Password repeat']#</label>
					<input type="password" id="password_repeat" name="password_repeat" value="" />
				</div>
			
			</fieldset>
                        
			<div class="divFormButton">
				<input type="submit" class="inputBtn" value="#page:_LANG['Save password']#" />
			</div>

		</form>
	
	</div>

	{elseif $sView == 'statistics'}

            <div class="inbox">

		<h2>#page:_LANG['Statistics']#</h2>
	
			
		{foreach from=$aReports key=iSchoolId item=aSchoolData} 
			<h3>{$aSchoolData.title}</h3>

			<table class="tblInquiries">
				<tr>
					<th style="width:auto;">#page:_LANG['Year']#</th>
					<th style="width:160px;">#page:_LANG['Number of bookings']#</th>
					<th style="width:160px;">#page:_LANG['Number of weeks']#</th>
					<th style="width:160px;">#page:_LANG['Sales']#</th>
					<th style="width:160px;">#page:_LANG['Provision']#</th>
				</tr>
				{foreach from=$aSchoolData.data item=aReport key=iYear}
				<tr>
					<td>{$iYear}</td>
					<td style="text-align: right;">{$aReport.6.value}</td>
					<td style="text-align: right;">{$aReport.70.value}</td>
					<td style="text-align: right;">{$aReport.36.value|number_format:"2":".":","}</td>
					<td style="text-align: right;">{$aReport.63.value|number_format:"2":".":","}</td>
				</tr>
				{/foreach}
			</table>
		{/foreach}



                </div>

	{else}
	
		<h2>#page:_LANG['Error']#</h2>
		
		<p>#page:_LANG['You tried to access an unsupported view. Please use the navigation.']#</p>
	
	{/if}

	

{else}
	
	{if $sView == 'send_password'}
	
		<h2>#page:_LANG['Send password']#</h2>
	
		{if $bError}
			<p class="pError">#page:_LANG['This username is not in our database.']#</p>
		{/if}
		{if $bSuccess}
			<p class="pSuccess">#page:_LANG['You will recieve an e-mail with a new password.']#</p>
		{/if}
	
		<p>#page:_LANG['Please enter your username']#</p>
	
		<form method="post" action="?">
			<input type="hidden" name="view" value="send_password" />
			
			<fieldset>
			
				<div class="divFormElement">
					<label for="username">#page:_LANG['Username']#</label>
					<input type="text" id="username" name="customer_login_1" value="" />
				</div>
			
			</fieldset>
		
			<div class="divFormButton">
				<input type="submit" class="inputBtn" value="#page:_LANG['Send password']#" />
			</div>
			
			<div class="divFormButton">
				<input type="button" class="inputBtn" value="#page:_LANG['Back']#" onclick="go('?'); return false;" />
			</div>
			
		</form>
	
	{else}
	
		<h2>#page:_LANG['Login']#</h2>
	
		{if $sLoginfailed}
			<p class="pError">#page:_LANG['Login failed. Please check your input!']#</p>
		{/if}
	
		<form method="post" action="?">
			<input type="hidden" name="loginmodul" value="1" />
			<input type="hidden" name="table_number" value="13" />
			
			<fieldset>
			
				<div class="divFormElement">
					<label for="username">#page:_LANG['Username']#</label>
					<input type="text" id="username" name="customer_login_1" value="" />
				</div>
				
				<div class="divFormElement">
					<label for="password">#page:_LANG['Password']#</label>
					<input type="password" id="password" name="customer_login_3" value="" />
				</div>
			
			</fieldset>
		
			<div class="divFormButton">
				<input type="submit" class="inputBtn" value="#page:_LANG['Login']#" />
			</div>
			
			<p>
				<a href="?view=send_password">#page:_LANG['Forgot password']#</a>
			</p>
			
		</form>

	{/if}

{/if}

</div>