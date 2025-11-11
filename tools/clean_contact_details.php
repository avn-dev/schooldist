<?php

include_once(\Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

/**
 * 1
 */
error_reporting(E_ALL ^ E_STRICT ^ E_NOTICE);
ini_set('display_errors', 1);

#die('no action');

$sSql = "DELETE FROM tc_contacts_details WHERE value = ''";
DB::executeQuery($sSql);

$bBackup = Util::backupTable('tc_contacts_details');

__out($bBackup);

// Fälschlich inaktive Einträge aktivieren
$sSql = "
	SELECT 
		d_i.*
	FROM 
		tc_contacts c JOIN 
		tc_contacts_details d_i ON c.id = d_i.contact_id AND d_i.active = 0 LEFT JOIN 
		tc_contacts_details d_a ON c.id = d_a.contact_id AND d_i.type = d_a.type AND d_a.active = 1
	WHERE 
		d_a.active IS NULL
	ORDER BY 
		c.id DESC";

$aResults = (array)DB::getQueryRows($sSql);

foreach($aResults as $aResult) {
	DB::updateData('tc_contacts_details', ['active' => 1], '`id` = '.(int)$aResult['id']);
}

__out($aResults);

// Doppelte Einträge entfernen
$sSql = "
	SELECT 
		*,
        COUNT(id) `count`
	FROM 
		tc_contacts_details
	WHERE
		active = 1
	GROUP BY
		contact_id,
		type,
		value
	HAVING 
		`count` > 1
";

$aResults = (array)DB::getQueryRows($sSql);

foreach($aResults as $aResult) {
	$sSql = "UPDATE tc_contacts_details SET active = 0 WHERE contact_id = :contact_id AND type = :type AND value = :value AND id != :id";
	$aSql = [
		'contact_id' => $aResult['contact_id'],
		'type' => $aResult['type'],
		'value' => $aResult['value'],
		'id' => $aResult['id']
	];
	DB::executePreparedQuery($sSql, $aSql);
}

__out($aResults);

die('no action');
