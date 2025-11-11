<div class="row">
	<div class="col-md-12">
		<h4>{'Zu erstellende zusätzliche Dokumente'|L10N}</h4>
		<div class="alert alert-success" style="display: none">{'Die ausgewählten Dokumente werden im Hintergrund generiert.'|L10N}</div>
		<table id="table-additional-service-documents" class="table" data-inquiry-id="{$oInquiry->id}">
			<thead>
			<tr>
				<th></th>
				<th style="">{'Vorlage'|L10N}</th>
				<th style="">{'Erstellt am'|L10N}</th>
				<th style="">{'PDF'|L10N}</th>
			</tr>
			</thead>
			<tbody>
            {foreach $aAdditionalDocumentsData as $aAdditionalDocument}
				{$disabled = $aAdditionalDocument['disabled'] || $aAdditionalDocument['template']->canShowInquiryPositions()}
				<tr data-template-id="{$aAdditionalDocument['template']->id}">
					<td>
						<input type="checkbox" {if !$aAdditionalDocument['created'] && !$disabled}checked{/if} {if $disabled}disabled{/if}>
					</td>
					<td>{$aAdditionalDocument['template']->name}</td>
					<td>{$aAdditionalDocument['created_at']}</td>
					<td>
                        {if $aAdditionalDocument['version']}
							<a target="_blank" href="/storage{$aAdditionalDocument['version']->getPath()}"><i class="fa fa-file-pdf-o fa-colored"></i></a>
                        {/if}
					</td>
				</tr>
            {/foreach}
			</tbody>
			<tfoot>
			<tr>
				<td colspan="4">
					<input class="btn btn-primary btn-sm pull-right" type="button" value="{'Ausgewählte Dokumente generieren'|L10N}">
				</td>
			</tr>
			</tfoot>
		</table>
	</div>
</div>
