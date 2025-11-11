{extends file="system/bundles/Admin/Resources/views/emails/email.tpl"}

{block name="content"}
    {'<h2 style="font-size: 20px;">Hallo %1$s %2$s,</h2><br><p>wir haben eine Anfrage für ein neues Passwort zu Ihrem Konto "%3$s" erhalten. Bitte klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:<br><br><a target="_blank" href="%4$s" class="link2" style="color:#ffffff">Passwort zurücksetzen</a><br><br>Falls Sie nicht ein neues Passwort angefordert haben, versucht möglicherweise eine andere Person, auf das Konto "%3$s" zuzugreifen. Geben Sie diesen Link nicht weiter.<br><br>Mit freundlichen Grüßen<br>%5$s</p>'|L10N:'Framework'|sprintf:$sFirstname:$sLastname:$sEmail:$sForgotPasswordLink:$sProjectName}
{/block}