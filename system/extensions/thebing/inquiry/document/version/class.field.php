<?php

class Ext_Thebing_Inquiry_Document_Version_Field extends Ext_Thebing_Basic {
	
	protected $_sTable = 'kolumbus_inquiries_documents_versions_fields';
	protected static $_sStaticTable = 'kolumbus_inquiries_documents_versions_fields';

	protected $_aFormat = array(
								'changed' => array(
									'format' => 'TIMESTAMP'
									),
								'created' => array(
									'format' => 'TIMESTAMP'
									)
							);

	/*
	 * Liefert das Feldobject zu einer VersionId und BlockId
	 */
	static public function getFieldObject($iVersionId, $iBlockId){
		$sSql = "SELECT
						`id`
					FROM
						`kolumbus_inquiries_documents_versions_fields`
					WHERE
						`active` = 1 AND
						`version_id` = :version_id AND
						`block_id` = :block_id
					LIMIT 1";
		$aSql				= array();
		$aSql['version_id'] = (int)$iVersionId;
		$aSql['block_id']	= (int)$iBlockId;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($aResult[0]['id'] > 0){
			$oReturn = self::getInstance($aResult[0]['id']);
		}else{
			$oReturn = NULL;
		}
		return $oReturn;
	}


}