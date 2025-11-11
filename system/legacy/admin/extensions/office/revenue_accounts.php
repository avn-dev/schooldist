<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Access_Backend::checkAccess('office');

if(isset($_VARS['revenue_account'])) {
	
	$aDocumentIds = [];
	
	foreach($_VARS['revenue_account'] as $iItemId=>$iRevenueAccountId) {

		if($iRevenueAccountId > 0) {

			$aData = array(
				'revenue_account' => (int)$iRevenueAccountId
			);
			DB::updateData('office_document_items', $aData, "`id` = ".(int)$iItemId);

			$sSql = "
				SELECT 
					`document_id` 
				FROM 
					`office_document_items` 
				WHERE 
					`id` = :id
			";
			$aSql = [
				'id' => (int)$iItemId
			];
			$iDocumentId = DB::getQueryOne($sSql, $aSql);

			$aDocumentIds[$iDocumentId] = $iDocumentId;

		}

	}
	
	foreach($aDocumentIds as $iDocumentId) {

		$oDocument = new Ext_Office_Document($iDocumentId);
		$oDocument->save();

	}
	
}

Admin_Html::loadAdminHeader();

?>

<div class="divHeader">
	<h1><?=L10N::t('Erlöskonto &raquo; Zuweisung', 'Office')?></h1>
</div>

<?php

$sSql = "
	SELECT
		`odi`.*
	FROM
		`office_documents` `od` JOIN
		`office_document_items` `odi` ON
			`od`.`id` = `odi`.`document_id`
	WHERE
		`od`.`type` IN ('account', 'credit', 'cancellation_invoice') AND
		`od`.`state` != 'draft' AND
		`odi`.`active` = 1 AND
		`odi`.`revenue_account` = 0
		";
$aItems = (array)DB::getQueryRows($sSql);

$oRevenueAccounts = \Office\Entity\RevenueAccounts::getInstance();
$aRevenueAccounts = (array)$oRevenueAccounts->getArrayList(true);
$aRevenueAccounts = array(0=>'') + $aRevenueAccounts;

?>

<div style="padding: 30px;">
	
	<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
		
		<?=printTableStart()?>
		<?php
		foreach($aItems as $aItem) {
			printFormSelect($aItem['product'].'<div class="note" style="font-weight: normal;">'.nl2br($aItem['description']).'</div>', 'revenue_account['.$aItem['id'].']', $aRevenueAccounts);
		}
		?>
		<?=printTableEnd()?>
		
		<?=printSubmit('Erlöskonten speichern')?>
		
	</form>
	
</div>

<?php

Admin_Html::loadAdminFooter();

?>