<table class="table">
	<tr>
		<th class="w-48">{$lang_image}</th>
		<th class="w-auto">{$lang_link}</th>
	</tr>
	{foreach from=$aImages item=image}
	<tr>
		<td class="w-48 align-middle text-center">{$image.preview}</td>
		<td class="align-top !p-1.5"><strong>{$image.description}</strong><br /><br />{$link}{$image.filename}</td>
	</tr>
	{/foreach}
</table>