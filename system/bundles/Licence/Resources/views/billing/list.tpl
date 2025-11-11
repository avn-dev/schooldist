{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
		<!-- bootstrap wysihtml5 - text editor -->
		{*<link rel="stylesheet" href="/wdmvc/assets/adminlte/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">*}
		
		<style>
			body {
				padding: 10px;
			}
			
			td.file {
				text-align: center;
			}
			
			td.file .fa {
				cursor: pointer
			}
		</style>
		
{/block}

{block name="content"}
	<div class="row">
		<div class="col-md-12">
			<div class="box">

				<div class="box-header with-border">
					<h3 class="box-title">{'Abrechnungen'|L10N} ({$date_format->formatByValue($date_range->from->format('Y-m-d'))} - {$date_format->formatByValue($date_range->until->format('Y-m-d'))})</h3>
				</div>

				{if !empty($billings)}
				
					<div class="box-body no-padding">

						<table class="table table-striped">
							<thead>
								<tr>
									<th>{'Datum'|L10N}</th>
									<th style="width: 100px;">{'Nummer'|L10N}</th>
									<th style="width: 100px;">{'Total'|L10N}</th>
									<th style="width: 250px;">{'Zahlungen'|L10N}</th>
									<th style="width: 200px; text-align: center;">{'Status'|L10N}</th>
									<th style="width: 50px; text-align: center;">{'PDF'|L10N}</th>
								</tr>
							</thead>
							<tbody>
								{foreach $billings as $billing}
									<tr>
										<td>{$date_format->formatByValue($billing['date'])}</td>
										<td>{$billing['number']}</td>
										<td style="text-align: right;">{$amount_format->formatByValue($billing['price'])} {if !empty($billing['currency'])}{$billing['currency']}{else}€{/if}</td>
										<td style="text-align: right;">
											
											<table style="width: 100%">
										
										{foreach $billing['payments'] as $payment}
											<tr>
											<td class="text-left">{$datetime_format->formatByValue($payment['created'])}</td>
											<td class="text-right">{$amount_format->formatByValue($payment['amount'])} {if !empty($billing['currency'])}{$billing['currency']}{else}€{/if}</td>
											</tr>
										{foreachelse}
										 
										{/foreach}
												<tr>
													<td class="text-left">{'Summe'|L10N}</td>
													<td class="text-right">{$amount_format->formatByValue($billing['payed_amount'])} {if !empty($billing['currency'])}{$billing['currency']}{else}€{/if}</td>
												</tr>
											</table>
										</td>
										<td style="text-align: center;">
											{if $billing['state'] === 'paid'}
												<small class="label bg-green">{'Bezahlt'|L10N}</small>
											{else}
												<small class="label bg-yellow">{'Offen'|L10N}</small>
											{/if}										
										</td>
										<td style="text-align: center;">
											<a href="/licence/billing/pdf/{{$billing['id']}}" target="_blank">
												<i class="fa fa-file"></i>
											</a>
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				
				{else}
					<div class="box-body">
						<div class="callout callout-info">
							<h4>{'Information'|L10N}</h4>

							<p>{'Für den gewählten Zeitraum stehen keine Abrechnungen zur Verfügung.'|L10N}</p>
						</div>
					</div>
				{/if}
					
				<!-- ./box-body -->
				<div class="box-footer">
					{'Total'|L10N}: {$total}
				</div>
			</div>
		</div>
	</div>
{/block}

{block name="footer"}

{/block}