{extends file="system/bundles/Admin/Resources/views/emails/email.tpl"}

{block name="content"}
    {'<h2 style="font-size: 20px;">


								  Hallo %1$s %2$s,</h2>
                              <br>
                              <p>
								  für das Systemupdate gibt es noch offene Checks. Bitte führen Sie die aus um das Systemupdate abzuschließen.
<br>
Mit freundlichen Grüßen<br>
%3$s
                              </p>'|L10N:'Framework'|sprintf:$sFirstname:$sLastname:$sProjectName}
{/block}