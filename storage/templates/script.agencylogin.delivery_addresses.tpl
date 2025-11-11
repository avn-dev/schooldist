                <div class="inbox">
		<h2>#page:_LANG['Delivery addresses']#</h2>
		
		{if $sTask == 'add'}

			{if $aErrors}
				<p class="pError">#page:_LANG['Please fill out all required fields.']#</p>
			{/if}

			<form method="post" action="?">
				<input type="hidden" name="view" value="delivery_addresses" />
				<input type="hidden" name="task" value="save" />
				<input type="hidden" name="address_id" value="{$oAddress->id}" />

				<fieldset class="fieldsetDetail">
				
					<div class="divFormElement{if $aErrors.shortcut} divError{/if}">
						<label for="shortcut">#page:_LANG['Shortcut']# *</label>
						<div class="divValue">
							<input type="text" id="shortcut" name="shortcut" value="{$oAddress->shortcut|escape}" />
						</div>
					</div>
				
					<div class="divFormElement{if $aErrors.company} divError{/if}">
						<label for="company">#page:_LANG['Company']# *</label>
						<div class="divValue">
							<input type="text" id="company" name="company" value="{$oAddress->company|escape}" />
						</div>
					</div>
				
					<div class="divFormElement">
						<label for="contact">#page:_LANG['Contact']#</label>
						<div class="divValue">
							<input type="text" id="contact" name="contact" value="{$oAddress->contact|escape}" />
						</div>
					</div>
				
					<div class="divFormElement">
						<label for="street">#page:_LANG['Street']#</label>
						<div class="divValue">
							<input type="text" id="street" name="street" value="{$oAddress->street|escape}" />
						</div>
					</div>
				
					<div class="divFormElement">
						<label for="zip">#page:_LANG['ZIP']#</label>
						<div class="divValue">
							<input type="text" id="zip" name="zip" value="{$oAddress->zip|escape}" />
						</div>
					</div>
				
					<div class="divFormElement">
						<label for="city">#page:_LANG['City']#</label>
						<div class="divValue">
							<input type="text" id="city" name="city" value="{$oAddress->city|escape}" />
						</div>
					</div>
				
					<div class="divFormElement">
						<label for="country">#page:_LANG['Country']#</label>
						<div class="divValue">
							<select id="country" name="country">
							{html_options options=$aCountries selected=$oAddress->country}
							</select>
						</div>
					</div>
				
					<div class="divFormElement">
						<label for="phone">#page:_LANG['Phone']#</label>
						<div class="divValue">
							<input type="text" id="phone" name="phone" value="{$oAddress->phone|escape}" />
						</div>
					</div>
				
				</fieldset>
			
				<div class="divFormButton">
					<input type="submit" class="inputBtn" value="#page:_LANG['Save address']#" />
					<input type="button" class="inputBtn" value="#page:_LANG['Delete address']#" onclick="go('?view=delivery_addresses&task=delete&address_id={$oAddress->id}'); return false;" />
				</div>
	
			</form>
			
			<div class="divFormButton">
				<input type="button" class="inputBtn" value="#page:_LANG['Back']#" onclick="go('?view=delivery_addresses'); return false;" />
			</div>
		
		{else}
		
			<table class="tblInquiries">
				<tr>
					<th style="width:100px;">#page:_LANG['Shortcut']#</th>
					<th style="width:auto;">#page:_LANG['Company']#</th>
					<th style="width:120px;">#page:_LANG['Contact']#</th>
					<th style="width:120px;">#page:_LANG['Street']#</th>
					<th style="width:60px;">#page:_LANG['ZIP']#</th>
					<th style="width:90px;">#page:_LANG['City']#</th>
					<th style="width:90px;">#page:_LANG['Country']#</th>
					<th style="width:90px;">#page:_LANG['Phone']#</th>
				</tr>
				{foreach from=$aAddresses item=aAddress}
				<tr ondblclick="openAddress('{$aAddress.id}');" style="cursor: pointer;">
					<td>{$aAddress.shortcut}</td>
					<td>{$aAddress.company}</td>
					<td>{$aAddress.contact}</td>
					<td>{$aAddress.street}</td>
					<td>{$aAddress.zip}</td>
					<td>{$aAddress.city}</td>
					<td>{$aAddress.country}</td>
					<td>{$aAddress.phone}</td>
				</tr>
				{/foreach}
			</table>
			
			<div class="divFormButton">
				<input type="button" class="inputBtn" value="#page:_LANG['Add address']#" onclick="go('?view=delivery_addresses&task=add'); return false;" />
			</div>
		{/if}
                </div>