{if $_VARS.office_tickets.task == "add"}

	<form action="{$_SERVER.PHP_SELF}" method="post">
		<input type="hidden" name="office_tickets[task]" value="save" />
	
		{if count($aErrors) > 0}
		<p class="pError">
			#page:_LANG['Es ist ein Fehler aufgetreten. Bitte füllen Sie']#
		</p>
		{/if}

		<fieldset>
			<div class="divInputContainer {if in_array("priority", $aErrors)}divError{/if}">
				<label>#page:_LANG['Priorität']#</label>
				<select class="txt" name="office_tickets[priority]" id="priority" value="{$_VARS.office_tickets.priority|escape}">
				{foreach from=$aPriorities key=iKey item=sPriority}
					<option value="{$iKey}"{if $iKey == $_VARS.office_tickets.priority} selected="selected"{/if}>#page:_LANG['{$sPriority}']#</option>
				{/foreach}
				</select>
			</div>
			<div class="divInputContainer {if in_array("headline", $aErrors)}divError{/if}">
				<label>#page:_LANG['Titel']#</label>
				<input class="txt" type="text" name="office_tickets[headline]" id="headline" value="{$_VARS.office_tickets.headline|escape}" />
			</div>
			<div class="divInputContainer {if in_array("description", $aErrors)}divError{/if}">
				<label>#page:_LANG['Problem']#</label>
				<textarea class="txt" name="office_tickets[description]" id="description" >{$_VARS.office_tickets.description|escape}</textarea>
			</div>
		</fieldset>
		<p style="text-align: right;">
			<a href="{$_SERVER.PHP_SELF}"><input type="image" src="#page:imgbuilder:3:#wd:_LANG['zurück']##" value="#wd:_LANG['zurück']#" /></a> 
			<input type="image" src="#page:imgbuilder:3:#wd:_LANG['Speichern']##" value="#wd:_LANG['Speichern']#" />
		</p>
	
	</form>

{elseif $_VARS.office_tickets.task == "detail"}

	<form><fieldset>
	
	<table class="tblContent">
		<colgroup>
			<col width="15%"/>
			<col width="15%"/>
		</colgroup>
		<tr>
			<th>#page:_LANG['Status']#</th>
			<td>#page:_LANG['{$aTicket.state}']#</td>
		</tr>
		<tr>
			<th>#page:_LANG['Priorität']#</th>
			<td {$aTicket.priority_color}>#page:_LANG['{$aTicket.priority}']#</td>
		</tr>
		<tr>
			<th>#page:_LANG['Bearbeiter']#</th>
			<td>{$aTicket.assigned_user}</td>
		</tr>
		<tr>
			<th>#page:_LANG['Fälligkeit']#</th>
			<td>{$aTicket.due_date|date_format:"%x"}</td>
		</tr>
		<tr>
			<th>#page:_LANG['Titel']#</th>
			<td>{$aTicket.headline}</td>
		</tr>
		<tr>
			<td colspan="2">{$aTicket.description|nl2br}</td>
		</tr>
	</table>
	<h2>Kommentare</h2>
	<table class="tblContent">
		<colgroup>
			<col style="width: 130px;"/>
			<col style="width: 130px;"/>
			<col style="width: auto;"/>
		</colgroup>
		<tr>
			<th>Von</th>
			<th>Datum</th>
			<th>Kommentar</th>
		</tr>
		{foreach from=$aTicket.comments item=aComment}
		<tr>
			<td style="vertical-align: top;">{$aComment.firstname} {$aComment.lastname}</td>
			<td style="vertical-align: top;">{$aComment.created|date_format:"%x %X"}</td>
			<td style="vertical-align: top;">{$aComment.comment|nl2br}</td>
		</tr>
		{foreachelse}
		<tr>
			<td colspan="3">
				#page:_LANG['Es liegen noch keine Kommentare vor.']#
			</td>
		</tr>
		{/foreach}
	</table>
	
	<p style="text-align: right;">
		<a href="{$_SERVER.PHP_SELF}"><input type="image" src="#page:imgbuilder:3:#wd:_LANG['zurück']##" value="#wd:_LANG['zurück']#" /></a>
	</p>
	
	</fieldset></form>

{else}

<form><fieldset>

<table class="tblContent">
	<colgroup>
		<col width="15%"/>
		<col width="15%"/>
		<col width="40%"/>
		<col width="25%"/>
		<col width="10%"/>
	</colgroup>
	<tr>
		<th>#page:_LANG['Status']#</th>
		<th>#page:_LANG['Priorität']#</th>
		<th>#page:_LANG['Titel']#</th>
		<th>#page:_LANG['Bearbeiter']#</th>
		<th>#page:_LANG['Fälligkeit']#</th>
	</tr>

{foreach from=$aTickets item=aTicket}
	<tr>
		<td>#page:_LANG['{$aTicket.state}']#</td>
		<td {$aTicket.priority_color}>#page:_LANG['{$aTicket.priority}']#</td>
		<td><a href="{$_SERVER.PHP_SELF}?office_tickets[task]=detail&amp;office_tickets[id]={$aTicket.id}">{$aTicket.headline}</td>
		<td>{$aTicket.assigned_user}</td>
		<td>{$aTicket.due_date|date_format:"%x"}</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="5">#page:_LANG['Keine Tickets vorhanden.']#</td>
	</tr>
{/foreach}

</table>

<p style="text-align: right;">
	<a href="{$_SERVER.PHP_SELF}?office_tickets[task]=add"><input type="image" src="#page:imgbuilder:3:#wd:_LANG['Support-Ticket erstellen']##" value="#wd:_LANG['Support-Ticket erstellen']#" /></a>
</p>

</fieldset></form>
 

{/if}