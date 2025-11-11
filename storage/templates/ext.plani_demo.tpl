{if $sFlag == 'form'}
Herzlich willkommen!<br />
Hier k&ouml;nnen Sie Ihren kostenlosen Demozugang f&uuml;r webDynamics CMS anfordern.</p>
<ol>
    <li>Bitte f&uuml;llen Sie dieses Formular vollst&auml;ndig aus und klicken auf &quot;Formular abschicken&quot;.</li>
    <li>Sie erhalten nach dem Versenden eine E-Mail mit Ihren Benutzernamen und Ihrem pers&ouml;nlichen Passwort.</li>
    <li>Folgen Sie dem Link in der E-Mail und loggen Sie sich mit den &uuml;bermittelten Daten ein.</li>
</ol>
(Felder, die mit * gekennzeichnet sind, m&uuml;ssen ausgef&uuml;llt werden.)
<br /><br />

{if $sErrorMessage != ''}
	<div style="color:red;">{$sErrorMessage}</div><br /><br />
{/if}

<form method="post" action="">
		<div><input type="hidden" name="task" value="get_demo_data" /></div>

		<fieldset>
		<div>
			<label for="firstname">Vorname *</label>
			<input class="txt" type="text" id="firstname" name="firstname" value="{$aCustomer.firstname}" />
			<br />
		</div>
		<div>
			<label for="lastname">Nachname *</label>
			<input class="txt" type="text" id="lastname" name="lastname" value="{$aCustomer.lastname}" />
			<br />
		</div>
		<div>
			<label for="email">E-Mail *</label>
			<input class="txt" type="text" id="email" name="email" value="{$aCustomer.email}" />
			<br />
		</div>
		<div>
			<label for="company">Firma</label>
			<input class="txt" type="text" id="company" name="company" value="{$aCustomer.company}" />
			<br />
		</div>
		<div>
			<label for="street">Straße</label>
			<input class="txt" type="text" id="street" name="street" value="{$aCustomer.street}" />
			<br />
		</div>
		<div>
			<label for="zip">PLZ</label>
			<input class="txt" type="text" id="zip" name="zip" value="{$aCustomer.zip}" />
			<br />
		</div>
		<div>
			<label for="city">Ort</label>
			<input class="txt" type="text" id="city" name="city" value="{$aCustomer.city}" />
			<br />
		</div>
		<div>
			<label for="phone">Telefon</label>
			<input class="txt" type="text" id="phone" name="phone" value="{$aCustomer.phone}" />
			<br />
		</div>
		<div style="width:310px;">
			<input type="image" class="btnRight" src="/media/2008/form_send.jpg" value="Formular absenden" />
		</div>
		</fieldset>
	</form>
{else if $sFlag == 'confirmation'}
	<div>
		Vielen Dank für Ihr Interesse an webDynamics CMS!<br/>
        Sie erhalten in Kürze eine E-Mail mit Ihren Zugangsdaten.<br />
		<br />
		Ihr plan-i Team

	</div>
    {literal}
    <!-- Google Code for Demozugang anfordern Conversion Page -->
<script language="JavaScript" type="text/javascript">
<!--
var google_conversion_id = 1071923847;
var google_conversion_language = "de";
var google_conversion_format = "1";
var google_conversion_color = "ffffff";
var google_conversion_label = "KG1gCOLgaRCHhZH_Aw";
if (5.0) {
  var google_conversion_value = 5.0;
}
//-->
</script>
<script language="JavaScript" src="http://www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<img height="1" width="1" border="0" src="http://www.googleadservices.com/pagead/conversion/1071923847/?value=5.0&amp;label=KG1gCOLgaRCHhZH_Aw&amp;script=0"/>
</noscript>
{/literal}
{/if}