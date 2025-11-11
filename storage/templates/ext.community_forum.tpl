<#list#> 
   
	<div class="divButtons">
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_action=post';" style="background-image: url(/media/icons/folder_add.png);">Beitrag erstellen</button>
	</div>
   
	<table class="tblForum">
		<tr>
           <th style="width: auto;">Thema</td> 
           <th style="width: 60px;">Antworten</td> 
           <th style="width: 100px;">Mitglied</td> 
           <th style="width: 100px;">Datum</td> 
		</tr>
		<#entry_loop#> 
			<#entry#> 
               <#topic_loop#> 
                   <tr class="<#rowclass#>"> 
                       <td class="font11"><a href="<#PHP_SELF#>?discussions_action=detail&amp;discussions_id=<#topic_id#>"><#subject#></a></td> 
                       <td class="font11" style="text-align: right;"><#answers#></td> 
                       <td class="font11"><#list:ext_3#> <#list:ext_4#></td> 
                       <td class="font11"><#created#></td> 
                   </tr> 
               <#/topic_loop#> 
			<#/entry#> 
			<#noentry#> 
			<tr>
				<td colspan="3">Keine Einträge vorhanden!</td>
			</tr> 
			<#/noentry#>  
       <#/entry_loop#> 
	</table>

   	<div class="divButtons">
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_action=post';" style="background-image: url(/media/icons/folder_add.png);">Beitrag erstellen</button>
	</div>

<#/list#>

<#post#>

	<h2>Beitrag erstellen</h2>

	<form action="<#PHP_SELF#>" method="post" name="frm"> 
		<input type="hidden" name="discussions_action" value="save" /> 
		<input type="hidden" name="discussions_parent_id" value="<#discussions_parent_id#>" /> 

		<fieldset>

			<label for="discussions_topic">Betreff:</label>
			<input class="formInput w250" type="text" id="discussions_topic" name="discussions_topic" value="" /><br/> 

			<label for="discussions_text">Nachricht:</label>
	        <textarea class="formTextarea w250" id="discussions_text" name="discussions_text" rows="10" style="width: 250px;"></textarea><br/> 

			<label for="discussions_sendreply">Antworten zu diesem Beitrag per E-Mail schicken</label>
			<input type="checkbox" id="discussions_sendreply" name="discussions_sendreply" value="1" /><br/>

	   </fieldset>

		<div class="divButtons">
			<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_action=post';" style="background-image: url(/media/icons/house.png);">Zurück zur Übersicht</button>
			<button class="formSubmit" onclick="document.frm.submit();" style="background-image: url(/media/icons/disk.png);">Absenden</button>
		</div>
	   
	</form> 
<#/post#> 

<#detail#> 
   
   	<div class="divButtons">
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>';" style="background-image: url(/media/icons/house.png);">Zurück zur Übersicht</button>
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_parent_id=<#discussions_parent_id#>&amp;discussions_action=post';" style="background-image: url(/media/icons/page_white_add.png);">Antwort erstellen</button>
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_action=post';" style="background-image: url(/media/icons/folder_add.png);">Beitrag erstellen</button>
	</div>

	<table class="tblForum"> 
       	<colgroup>
			<col width="50%" />
			<col width="25%" />
			<col width="25%" />
		</colgroup>
		<tr>
           <th>Thema</td> 
           <th>Mitglied</td> 
           <th>Datum</td> 
		</tr>
		<#entry_loop#> 
			<#entry#> 
               <#topic_loop#> 
                   <tr class="<#rowclass#>"> 
                       <td class="font11"><img src="/media/icons/folder.png" align="absmiddle" alt="" />&nbsp;<a href="<#PHP_SELF#>?discussions_action=detail&amp;discussions_id=<#topic_id#>"><#subject#></a></td> 
                       <td class="font11"><#list:ext_3#> <#list:ext_4#></td> 
                       <td class="font11"><#created#></td> 
                   </tr>
               <#/topic_loop#> 
               <#reply_loop#> 
                   <tr class="<#rowclass#>"> 
                       <td class="font11"><#subject#></td> 
                       <td class="font11"><#list:ext_3#> <#list:ext_4#></td> 
                       <td class="font11"><#created#></td> 
                   </tr>
               <#/reply_loop#> 
			<#/entry#> 
			<#noentry#> 
			<tr>
				<td colspan="3">Keine Einträge vorhanden!</td>
			</tr> 
			<#/noentry#>
		<#/entry_loop#>
   </table>
   
   	<div class="divButtons">
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>';" style="background-image: url(/media/icons/house.png);">Zurück zur Übersicht</button>
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_parent_id=<#discussions_parent_id#>&amp;discussions_action=post';" style="background-image: url(/media/icons/page_white_add.png);">Antwort erstellen</button>
		<button class="formSubmit" onclick="document.location.href='<#PHP_SELF#>?discussions_action=post';" style="background-image: url(/media/icons/folder_add.png);">Beitrag erstellen</button>
	</div>
   
<#/detail#> 

<#notpermitted#> 
   <div class="remudafont">
       Sie ben&ouml;tigen einen Account um Inhalte bei REMUDA.de einzustellen. Die Registrierung ist kostenlos.<br />
       Ihre Daten werden von uns nicht an Dritte weitergegeben. Die Daten werden ausschlie&szlig;lich zur Anzeigenabwicklung verwendet.
   </div>
   <div style="margin-top: 7px;">
       <div class="formCaption" style="padding-left: 5px"><span class="font11">Haben Sie sich schon mal bei uns angemeldet?</span></div>
       <div style="background: url(/media/templates/form_bg.jpg) no-repeat; height: 151px">
           <div style="padding-left: 48px; padding-top: 15px" class="remudafont bold">LOGIN</div>
           <form method="post" action="#page:PHP_SELF#">
         
               <input type="hidden" name="table_number" value="1">
               <input type="hidden" name="loginmodul" value="1">
               
               <div style="padding-left: 46px; padding-top: 13px; color: #00820D; text-indent: 2px" class="font10">
                   Benutzername:<br />
                   <input type="text" class="formInput" name="customer_login_1" />
               </div>
               <div style="padding-left: 46px; color: #00820D; text-indent: 2px" class="font10">
                   Kennwort:<br />
                   <input type="password" class="formInput" name="customer_login_3" /> <input type="image" align="absmiddle" src="/media/templates/login_content_arrow_braun.gif" alt="los" name="submit" value="login" />
               </div>
               
           </form>
       </div>
   </div>
<#/notpermitted#>