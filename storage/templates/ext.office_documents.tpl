<div id="list">
	<a>
		<div class="sLink" style="border: 1px solid #CCC; cursor:pointer;">
			<div style="float:right;">
				<img style="position:relative; top:5px; left:-5px;" src="/media/down2.png" alt="" />
			</div>
			<div style="padding:3px; background-color:#F8F8F6; border: 1px solid #FFF;">
				Angebote
			</div>
		</div>
	</a>
	<div>
		{if isset($aDocuments.0)}
			<table style="width:100%;">
				<tr>
					<td><b>Nummer</b></td>
					<td><b>Status</b></td>
					<td><b>Bearbeiter</b></td>
					<td><b>Datum</b></td>
					<td style="text-align:right;"><b>Summe (Netto)</b></td>
					<td></th>
				</tr>
				{foreach from=$aDocuments item=aDocument}
					<tr>
						<td>{$aDocument.number}</td>
						<td>
							{if $aDocument.state == 'Freigegeben'}
								versendet
							{elseif $aDocument.state == 'Angenommen / Bezahlt'}
								beauftragt
							{elseif $aDocument.state == 'Abgelehnt'}
								abgelehnt
							{else}
								{$aDocument.state}
							{/if}
						</td>
						<td><div onclick="document.location.href = 'mailto:{$aDocument.email}'; return false;" style="cursor:pointer;">
								{$aDocument.firstname} {$aDocument.lastname}
							</div></td>
						<td>{$aDocument.date}</td>
						<td style="text-align:right;">{$aDocument.price_net} €</td>
						<td style="text-align:right;">
							<img onclick="window.open('{$_SERVER.PHP_SELF}?document_id={$aDocument.id}');" style="cursor:pointer;" src="/admin/media/acrobat.png" alt="Dokument anzeigen" style="border:0;" />
						</td>
					</tr>
				{/foreach}
			</table>
		{else}
			<div style="padding:5px;">Zur Zeit sind keine Angebote vorhanden.</div>
		{/if}
	</div>