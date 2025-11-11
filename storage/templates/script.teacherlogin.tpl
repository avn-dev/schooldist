<div id="divThebingAgencyArea">

	<script>
		{literal}

		function go(sUrl) {
			document.location.href = sUrl;
		}
		
		{/literal}
	</script>

<h1>#page:_LANG['Teacher area']#</h1>

{if $bLoggedIn}
		
	<ul class="ulNavigation">
		<li {if $sView == 'start'}class="liActive"{/if}><a href="?view=courses">#page:_LANG['Courses']#</a></li>
		{if $bShowPayments}
		<li {if $sView == 'payments'}class="liActive"{/if}><a href="?view=payments">#page:_LANG['Payments']#</a></li>
		{/if}
		<li {if $sView == 'administration'}class="liActive"{/if}><a href="?view=administration">#page:_LANG['Administration']#</a></li>
		<li><a href="?logout=ok">#page:_LANG['Logout']#</a></li>
	</ul>

	<div class="divContent">

	{if $sView == 'courses'}

		{include file="script.teacherlogin.courses.tpl"}

	{elseif $sView == 'payments'}

		{include file="script.teacherlogin.payments.tpl"}

	{elseif $sView == 'administration'}
	
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
				<input type="button" class="inputBtn" value="#page:_LANG['Back']#" onclick="go('?&'); return false;" />
			</div>
			
		</form>
	
	{else}
	
		<h2>#page:_LANG['Login']#</h2>
	
		{if $sLoginfailed}
			<p class="pError">#page:_LANG['Login failed. Please check your input!']#</p>
		{/if}
	
		<form method="post" action="?">
			<input type="hidden" name="loginmodul" value="1" />
			<input type="hidden" name="table_number" value="32" />
			
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

</div>