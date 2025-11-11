<?php
global $oAccessFrontend;

if(
	$oAccessFrontend instanceof Access_Frontend &&
	$oAccessFrontend->checkValidAccess() === true
) {

	$_SESSION['office']['client_id'] = 1;
	
	$iCustomerId = $oAccessFrontend->customer_id;

	$oCustomer = new Ext_Office_Customer(null, $iCustomerId);

	$oContract = new Ext_Office_Contract();
	$aContracts = $oContract->getContractsList('product', null, $oCustomer->id);

	?>
		
	<table class="table table-condensed table-hover">
			<thead>
				<tr>
					<th style="width: 40px;">ID</th>
					<th style="width: 40px;">Anzahl</th>
					<th style="width: 200px;">Produkt</th>
					<th style="width: auto;">Details</th>
					<th style="width: 80px;">Start</th>
					<th style="width: 80px;">Ende</th>
					<th style="width: 80px;">Interval</th>
					<th style="width: 80px;">Betrag</th>
				</tr>
			</thead>
			<tbody>	
<?
	foreach((array)$aContracts as $aContract) {
		?>
				<tr>
					<td style="text-align: right;"><?=$aContract['id']?></td>
					<td style="text-align: right;"><?=(int)$aContract['amount']?></td>
					<td><?=$aContract['product']?></td>
					<td><?=nl2br($aContract['text'])?></td>
					<td><?=strftime("%x", $aContract['start'])?></td>
					<td><?=(($aContract['end'])?strftime("%x", $aContract['end']):'')?></td>
					<td><?=$aContract['interval']?> <?=(($aContract['interval']==1)?'Monat':'Monate')?></td>
					<td style="text-align: right;"><?=number_format($aContract['total'], 2, ",", ".")?> €</td>
				</tr>
		<?
	}
?>
			</tbody>
	</table>

		
		
	<? 
	
}