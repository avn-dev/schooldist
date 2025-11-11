
{if $_VARS.ecard_key != ""}

	{if $arrEcard.id}
	
		<h2>Grußkarte "{$arrEcard.name}"</h2>
	
		<img src="#page:imgbuilder:{$arrEcard.imgbuilder_id}#" alt="{$arrEcard.name|escape}" />
	
		<table>
			<tr>
				<th>Von</th>
				<td><a href="mailto:{$arrEcard.sender_email}">{$arrEcard.sender_name}</a></td>
			</tr>
			<tr>
				<th>Nachricht</th>
				<td>{$arrEcard.message|@nl2br}</td>
			</tr>
		</table>

	{else}

		<h2>Es wurde keine Grußkarte mit dieser ID gefunden.</h2>

	{/if}

{elseif $_VARS.ecard_task == "send"}

	{if $arrEcard.id}
	
		<h2>Grußkarte "{$arrEcard.name}"</h2>

		<p>Ihre Grußkarte wurde erfolgreich versendet.</p>

	{else}

		<h2>Es wurde keine Grußkarte mit dieser ID gefunden.</h2>

	{/if}

{elseif $_VARS.ecard_task == "detail"}

	{if $arrEcard.id}
	
		<h2>Grußkarte "{$arrEcard.name}"</h2>
	
		<img src="#page:imgbuilder:{$arrEcard.imgbuilder_id}#" alt="{$arrEcard.name|escape}" />
	
		<form method="post" action="{$_SERVER.PHP_SELF}">
			<input type="hidden" name="ecard_task" value="send"/>
			<input type="hidden" name="ecard_id" value="{$arrEcard.name|escape}"/>

			<input type="text" class="txt" name="ecard_sender_name" value="{$_VARS.ecard_sender_name|escape}" />
			<input type="text" class="txt" name="ecard_sender_email" value="{$_VARS.ecard_sender_email|escape}" />
			<input type="text" class="txt" name="ecard_recipient_name" value="{$_VARS.ecard_recipient_name|escape}" />
			<input type="text" class="txt" name="ecard_recipient_email" value="{$_VARS.ecard_recipient_email|escape}" />
			
			<textarea class="txt" name="ecard_message">{$_VARS.ecard_message|escape}</textarea>
			
			<input type="submit" name="Grußkarte senden" />
	
		</form>

		<p class="hint">
		* Bitte füllen Sie alle mit einem Sternchen markierten Felder aus.
		</p>

	{else}

		<h2>Es wurde keine Grußkarte mit dieser ID gefunden.</h2>

	{/if}

{else}

	{foreach item=arrEcard from=$arrEcards}
	
		{$arrEcard.name}<br/>
		<a href="{$_SERVER.PHP_SELF}?ecard_task=detail&amp;ecard_id={$arrEcard.id}"><img src="#page:imgbuilder:4:#page|imgbuilder|{$arrEcard.imgbuilder_id}##" border="0" alt="{$arrEcard.name|escape}" /></a>
	
	{foreachelse}
	
		<h2>Es wurden keine Grußkarten gefunden.</h2>
	
	{/foreach}

{/if}
