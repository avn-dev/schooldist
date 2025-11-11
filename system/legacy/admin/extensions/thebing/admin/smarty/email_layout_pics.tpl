<table class="table guiTableHead">
	<tr>
		<th class="ELPLeftRow">{$lang_image}</th>
		<th class="ELPRightRow">{$lang_link}</th>
	</tr>
	{foreach from=$aImages item=image}
	<tr>
		<td class="ELPLeftRow ELPImageRow">{$image.preview}</td>
		<td class="ELPLinkRow"><strong>{$image.description}</strong><br /><br />{$link}{$image.filename}</td>
	</tr>
	{/foreach}
</table>