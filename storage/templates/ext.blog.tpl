{if $sFlag == 'list'}
	<table style="width:100%;">
		<tr>
			<td>
				<b>{$oBlog->title}</b>
				<br />
				<hr size="1px" style="color:#DEDEDE;" />
			</td>
		</tr>
		{foreach from=$aList item='aItem'}
			<tr>
				<td style="background-color:#CC0000; padding:3px;">
					<a style="color:#FFF;" href="#page:PHP_SELF#?entry_id={$aItem.id}">{$aItem.title}</a>
				</td>
			</tr>
			<tr>
				<td>
					<i>{$aItem.created|date_format:'%x %X'} | {$aItem.user_name}</i>
				</td>
			</tr>
			<tr>
				<td>
					{$aItem.text|truncate:600:'...'}
					<br /><br />
					<i>Kommentare: {$aItem.commentsCount}</i>
				</td>
			</tr>
		{/foreach}
	</table>
{/if}



{if $sFlag == 'one'}
	<table style="width:100%;">
		<tr>
			<td style="background-color:#CC0000; padding:3px; color:#FFF;">
				<b>{$oEntry->title}</b>
			</td>
		</tr>
		<tr>
			<td>
				<i>{$oEntry->created|date_format:'%x %X'} | {$aMember.0.firstname} {$aMember.0.lastname}</i>
				<br />
				{$oEntry->text}
				<br /><br />
				<hr size="1px" style="color:#DEDEDE;" />
				<h2>Kommentare:</h2>
			</td>
		</tr>
		{foreach from=$aList item='aItem'}
			<tr>
				<td>
					<i>{$aItem.created|date_format:'%x %X'} | {$aItem.name} | {$aItem.email}</i>
					<br />
					{$aItem.comment}
					<hr size="1px" style="color:#DEDEDE;" />
				</td>
			</tr>
		{/foreach}
	</table>
	<form method="post" action="#page:PHP_SELF#">
	<input type="hidden" name="entry_id" value="{$oEntry->id|escape}" />
	<input type="hidden" name="task" value="save_comment" />
		<div style="float:left; width:260px;">
			Name:
			<br />
			<input style="width:250px;" name="name" />
			<br /><br />
		</div>
		E-Mail:
		<br />
		<input style="width:250px;" name="email" />
		<br /><br />
		Kommentar:
		<br />
		<textarea style="width:510px; height:100px;" name="comment" rows="" cols=""></textarea>
		<br />
		<br />
		<input type="submit" name="submit" value="Senden" />
	</form>
{/if}