<#subscribe#> 
<form method="post" action="#page:PHP_SELF#" class="form-horizontal"> 
	<input type="hidden" name="newsletter_action" value="save">
	
	<div class="form-group">
		<label for="ne_email" class="col-sm-2 control-label">E-Mail</label>
		<div class="col-sm-10">
		  <input type="email" class="form-control" id="ne_email" name="ne_email" placeholder="E-Mail" value="<#ne_email#>" required>
		</div>
	</div>
	
	<p>Dürfen wir Sie persönlich begrüßen?</p>
	
	<div class="form-group">
		<label for="inputEmail3" class="col-sm-2 control-label">Anrede</label>
		<div class="col-sm-10">
			<select name="ne_sex" class="form-control">
               <option value="0" <#ne_sex_0#>>bitte wählen
               <option value="2" <#ne_sex_2#>>Frau
               <option value="1" <#ne_sex_1#>>Herr
           </select>
		</div>
	</div>
	<div class="form-group">
		<label for="ne_firstname" class="col-sm-2 control-label">Vorname</label>
		<div class="col-sm-10">
		  <input type="text" class="form-control" id="ne_firstname" name="ne_firstname" placeholder="Vorname" value="<#ne_firstname#>">
		</div>
	</div>
	<div class="form-group">
		<label for="ne_name" class="col-sm-2 control-label">Nachname</label>
		<div class="col-sm-10">
		  <input type="text" class="form-control" id="ne_name" name="ne_name" placeholder="Nachname" value="<#ne_name#>">
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="radio">
				<label>
				  <input type="radio" name="newsletter_task" id="newsletter_task1" value="subscribe" checked>
				  Anmelden
				</label>
			</div>
			<div class="radio">
				<label>
				  <input type="radio" name="newsletter_task" id="newsletter_task2" value="unsubscribe">
				  Abmelden
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="newsletter_acceptterms" id="newsletter_acceptterms" value="1"> Die <a href="/information/datenschutz.html" target="_blank">Datenschutzbestimmungen</a> sind mir bekannt und ich akzeptiere diese.
				</label>
			</div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
		  <button type="submit" class="btn btn-primary">Absenden</button>
		</div>
	</div>
	</form> 
<#/subscribe#>

<#unsubscribe#> 
   <div class="alert alert-success" role="alert">Vielen Dank!<br>Sie wurden erfolgreich abgemeldet.</div>
<#/unsubscribe#> 

<#save#>
   <#messages#>
       <#success#>
           <div class="alert alert-success" role="alert">Vielen Dank! Die Eintragung war erfolgreich.</div>
       <#/success#>
       <#success_deactivation#>
       		<div class="alert alert-success" role="alert">Schade, dass Sie gehen.<br>Wir schicken keine Werbe-Mails mehr an <#email#>. Die Änderungen treten umgehend in Kraft</div>
	   <#/success_deactivation#>
	   <#deactivation_success#>
       		<div class="alert alert-success" role="alert">Schade, dass Sie gehen.<br>Wir schicken keine Werbe-Mails mehr an <#email#>. Die Änderungen treten umgehend in Kraft</div>
	   <#/deactivation_success#>
	   <#deactivation_failure#>
			<div class="alert alert-danger" role="alert">Bei ihrer Deaktivierung ist ein Fehler aufgetreten.</div>
   	   <#/deactivation_failure#>
	   <#activation_success#>
       		<div class="alert alert-success" role="alert">Sie haben sich erfolgreich für unseren Newsletter angemeldet. Ab sofort erhalten Sie wissenswerte Informationen über unsere Look Sprachreisen.</div>
	   <#/activation_success#>
	   <#activation_failure#>
			<div class="alert alert-danger" role="alert">Bei ihrer Aktivierung ist ein Fehler aufgetreten.</div>
   	   <#/activation_failure#>
	   <#wrongemail#>
           <div class="alert alert-danger" role="alert">Bitte geben Sie eine gültige E-Mailadresse ein.</div>
       <#/wrongemail#>
       <#exists#>
           <div class="alert alert-danger" role="alert">Diese E-Mailadresse ist bereits eingetragen.</div>
       <#/exists#>
	   <#existsnot#>
           <div class="alert alert-danger" role="alert">Diese E-Mailadresse ist nicht eingetragen.</div>
       <#/existsnot#>
	   <#mustacceptterms#>
           <div class="alert alert-danger" role="alert">Bitte akzeptieren Sie die Bedingungen für das Abonnement unseres Newsletters.</div>
       <#/mustacceptterms#>
   <#/messages#>
   <#form#>
<#/save#>