<?php

// Define table keys
global $aTableKeys;
global $strPdfPath;
global $aTypeNames;

// Include required files
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");

// Create the configuration object
$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

// Create an smarty object
$oSmarty = new \Cms\Service\Smarty();

// Is user logged in?
if(
	isset($user_data['id']) && 
	(int)$user_data['id'] > 0
) {

	// Display one document
	if(
		isset($_VARS['document_id']) && 
		(int)$_VARS['document_id'] > 0
	) {
		$sShow = 'one';
	}

	// Display a list of documents
	else {
		$sShow = 'list';
	}

	/* ************************************************************* LIST *** */

	// If is set the document type
	$sAddWhere = '';
	if($oConfig->type != '') {
		$sAddWhere .= " AND `type` = '".$oConfig->type."'";
	}
	if($oConfig->date != '') {
		// Create the timestamp
		$aDate = explode('.', $oConfig->date);
		$iTimestamp = mktime(1,0,0, $aDate[1], $aDate[0], $aDate[2]);
		$sAddWhere .= " AND `date` >= '".date('YmdHis', $iTimestamp)."'";
	}

	// Display a list of documents
	if($sShow == 'list') {

		$sSQL = "
			SELECT
				`d`.*,
				`u`.`firstname`,
				`u`.`lastname`,
				`u`.`email`,
				UNIX_TIMESTAMP(`d`.`date`) AS `date`,
				`p`.`days`
			FROM
				`office_documents` AS d
					LEFT OUTER JOIN
				`system_user` AS u
					ON
				`d`.`editor_id` = `u`.`id`
					LEFT OUTER JOIN
				`office_payment_terms` AS p
					ON
				`d`.`payment` = `p`.`id`
			WHERE
				`d`.`customer_id`	= :iCustomerID
					AND
				`d`.`state`			!= 'draft'
					AND
				`d`.`active`		= 1
					".$sAddWhere."
			ORDER BY
				`d`.`date` DESC
		";
		$aSQL = array('iCustomerID' => $user_data['id']);

		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		// Prepare the list for the template
		foreach((array)$aResult as $iKey => $aValue) {
			$aResult[$iKey]['type'] = $aTypeNames[$aValue['type']];
			$aResult[$iKey]['state'] = $aDocumentStates[$aValue['state']];
			$aResult[$iKey]['date'] = date('d.m.Y', $aValue['date']);
			$aResult[$iKey]['pay_date'] = date('d.m.Y', $aValue['date'] + $aValue['days'] * 24 * 60 * 60);
			$aResult[$iKey]['price_net'] = number_format($aValue['price_net'], 2, ',', '.');
		}

		$oSmarty->assign('aDocuments', $aResult);

	}

	/* ************************************************************** ONE *** */

	// Display one document
	elseif($sShow == 'one') {

		$sSQL = "
			SELECT
				`id`,
				`type`,
				`number`
			FROM
				`office_documents`
			WHERE
				`id`			= :iDocumentID AND
				`customer_id`	= :iCustomerID AND
				`state`			!= 'draft' AND
				`active`		= 1
				".$sAddWhere."
			LIMIT
				1
		";
		$aSQL = array(
			'iDocumentID'	=> (int)$_VARS['document_id'],
			'iCustomerID'	=> $user_data['id']
		);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		if(!empty($aResult)) {

			// Clear buffer
			ob_end_clean();

			// Define the file name
			$sFileName = $aResult[0]['type'].'_'.$aResult[0]['number'].'.pdf';

			// Create the document
			$oPDF = new Ext_Office_PDF((int)$oConfig->form_id, (int)$aResult[0]['id'], true);

			// Display document
			$oPDF->showPDFFile($sFileName);

		}

	}

}

// Display template
$oSmarty->displayExtension($element_data);
