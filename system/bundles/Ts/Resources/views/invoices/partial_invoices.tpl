	
		<h3>
			{'Teilrechnungen'|L10N} 
			<span class="pull-right partial-invoices-refresh" title="{'Teilrechnungen neu generieren'|L10N|escape}">&nbsp;<i class="fas fa-sm fa-sync"></i></span>
			<span class="pull-right partial-invoices-loading" style="display:none;">&nbsp;<i class="fas fa-spinner fa-sm fa-pulse"></i></span>
			<span class="pull-right partial-invoices-add" title="{'Anzahlungsrechnung ergänzen'|L10N|escape}">&nbsp;<i class="fas fa-sm fa-plus"></i></span>
		</h3>
		
		<div id="partial_invoices_deposit_row" style="display:none;">

			<form method="post" id="partial_invoices_deposit_form">
				<div class="form-group form-group-sm">
					<label for="partial_invoices_deposit_date" class="col-sm-4 control-label">{'Datum'|L10N|escape}</label>
					<div class="col-sm-8">						
						<div class="GUIDialogRowCalendarDiv input-group date">
							<div class="input-group-addon calendar_img">
								<i class="fa fa-calendar"></i>
							</div>
							<input type="text" class="txt form-control input-sm calendar_input" input="calendar" name="partial_invoices_deposit[date]" id="partial_invoices_deposit_date">
							<div class="GUIDialogRowWeekdayDiv input-group-addon">Mo</div>
						</div>
					</div>
				</div>
				<div class="form-group form-group-sm">
					<label for="partial_invoices_deposit_date" class="col-sm-4 control-label">{'Betrag'|L10N|escape}</label>
					<div class="col-sm-8">
						<input type="number" step="0.01" class="form-control input-sm" id="partial_invoices_deposit_amount" name="partial_invoices_deposit[amount]">
					</div>
				</div>
				<div class="form-group form-group-sm">
					<div class="col-sm-offset-4 col-sm-8">
						<button type="submit" class="btn btn-sm btn-primary">{'Speichern'|L10N|escape}</button>
						<button type="reset" class="btn btn-sm btn-default">{'Abbrechen'|L10N|escape}</button>
					</div>
				</div>
			</form>

		</div>
					
		<table class="table">
			<tr>
				<th style="width:80px;">{'Fälligkeit'|L10N}</th>
				<th style="width:100px;">{'Typ'|L10N}</th>
				<th style="width:auto;">{'Zeitraum'|L10N}</th>
				<th style="width:100px;">{'Betrag'|L10N}</th>
				<th style="width:70px;">{'Aktionen'|L10N}</th>
			</tr>
			{assign var=fTotal value=0}
			{assign var=aTypes value=Ext_TS_Payment_Condition_Gui2_Data::getTypeOptions(true)}
			{foreach $aPartialInvoices as $oPartialInvoice}
				<tr {if $oPartialInvoice->converted !== null}style="background-color: {\Ext_Thebing_Util::getColor('good')};"{/if} data-id="{$oPartialInvoice->id}" data-type="{$oPartialInvoice->type}">
					<td>{\Ext_Thebing_Format::LocalDate($oPartialInvoice->date)}</td>
					<td>{$aTypes[$oPartialInvoice->type]}</td>
					<td>{\Ext_Thebing_Format::LocalDate($oPartialInvoice->from)} - {\Ext_Thebing_Format::LocalDate($oPartialInvoice->until)}</td>
					<td class="text-right">{\Ext_Thebing_Format::Number($oPartialInvoice->amount, $oInquiry->currency_id)}</td>
					<td>
						{if $oPartialInvoice->isNext()}
							<i class="fas fa-check mark-generated" title="{'Als "Generiert" markieren.'|L10N|escape}"></i>
							<!--<i class="fas fa-plus generate" title="{'Generieren'|L10N}"></i>-->
						{/if}
						
						{if $oPartialInvoice->isLatestConverted()}
							<i class="fas fa-undo unmark-generated" title="{'Als "Noch nicht generiert" markieren.'|L10N|escape}"></i>
						{/if}
					</td>
				</tr>
				{assign var=fTotal value=$fTotal + $oPartialInvoice->amount}
			{/foreach}
			<tr>
				<th colspan="3">{'Gesamt'|L10N}</th>
				<th class="text-right">{\Ext_Thebing_Format::Number($fTotal, $oInquiry->currency_id)}</th>
				<th>&nbsp;</th>
			</tr>
		</table>
