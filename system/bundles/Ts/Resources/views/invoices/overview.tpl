<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
	<div>
        {include file="system/bundles/Ts/Resources/views/invoices/booking_positions.tpl"}
	</div>
	<div>
		<h3>{'Rechnungen'|L10N}</h3>
		<table class="table">
			<tr>
				<th style="width: 40px;">{'PDF'|L10N}</th>
				<th style="width: 100px;">{'Nummer'|L10N}</th>
				<th style="width: auto;">{'Typ'|L10N}</th>
				<th style="width: 80px;">{'Datum'|L10N}</th>
				<th style="width: 100px;">{'Betrag'|L10N}</th>
				{*<th style="width: 100px;">&nbsp;</th>*}
			</tr>
			{assign var=fTotal value=0}
			{foreach $aInvoices as $aInvoice}
				{assign var=oInvoice value=\Ext_Thebing_Inquiry_Document::getInstance($aInvoice.id)}
				<tr class="{if $oInquiry->has_invoice && $oInvoice->isProforma()}text-muted{/if}">
					<td class="text-center"><a target="_blank" href="/storage{$oInvoice->getLastVersion()->getPath()}"><i class="fa fa-file-pdf-o fa-colored"></i></a></td>
					<td>{$aInvoice.document_number}</td>
					<td>{$oInvoice->getLabel()}</td>
					<td>{\Ext_Thebing_Format::LocalDate($oInvoice->getLastVersion()->date)}</td>
					<td class="text-right">{\Ext_Thebing_Format::Number($oInvoice->getAmount(), $oInquiry->currency_id)}</td>
					{*<td>...</td>*}
				</tr>
				{if !$oInquiry->has_invoice || !$oInvoice->isProforma()}
					{assign var=fTotal value=$fTotal + $oInvoice->getAmount()}
				{/if}
			{/foreach}
			<tr>
				<th colspan="4">{'Gesamt'|L10N}</th>
				<th class="text-right">{\Ext_Thebing_Format::Number($fTotal, $oInquiry->currency_id)}</th>
				{*<th>&nbsp;</th>*}
			</tr>
		</table>
		
		<div id="partial_invoices_container">
		{if $oInquiry->partial_invoices_terms}
			{include file="system/bundles/Ts/Resources/views/invoices/partial_invoices.tpl"}
		{/if}
		</div>
			
	</div>
</div>
		