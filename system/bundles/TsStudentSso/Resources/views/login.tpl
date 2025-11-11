	
        {foreach $session->getFlashBag()->get('error') as $sMessage}
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <i class="icon fa fa-exclamation"></i> {$sMessage}
            </div>
        {/foreach}
		
		<form method="post" action="{route name="TsStudentSso.execute_login"}">
			<input type="hidden" name="loginmodul" value="1" />
			<input type="hidden" name="table_number" value="77" />
			
			<input type="hidden" id="SAMLRequest" name="SAMLRequest" value="{$SAMLRequest|escape}" />
			<input type="hidden" id="RelayState" name="RelayState" value="{$RelayState|escape}" />
			
			<fieldset>
			
				<div class="divFormElement">
					<label for="username">{'Username'|L10N}</label>
					<input type="text" id="username" name="customer_login_1" value="" />
				</div>
				
				<div class="divFormElement">
					<label for="password">{'Password'|L10N}</label>
					<input type="password" id="password" name="customer_login_3" value="" />
				</div>
			
			</fieldset>
		
			<div class="divFormButton">
				<input type="submit" class="inputBtn" value="{'Login'|L10N}" />
			</div>
			
		</form>