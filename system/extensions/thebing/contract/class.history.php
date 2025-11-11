<?php

/**
* @property $id 
* @property $created 	
* @property $changed 	
* @property $active 	
* @property $creator_id 	
* @property $user_id 	
* @property $contract_id 	
* @property $pdf_template_id 	
* @property $valid_from 	
* @property $valid_until 	
* @property $comment 	
* @property $sent 	
* @property $sent_by 	
* @property $confirmed 	
* @property $confirmed_by 	
* @property $txt_intro 	
* @property $file
*/

class Ext_Thebing_Contract_History extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_contracts_versions';
	protected $_sTableAlias = 'kcontv';

	protected $_aJoinedObjects = array(
									'kcont'=>array(
										'class'=>'Ext_Thebing_Contract',
										'key'=>'contract_id'
									)
								);

	public function  __get($sName) {

		if($sName == 'type_name') {
			if($this->_aData['id'] > 0) {
				$oContract = $this->getContract();
				$oTemplate = $oContract->getContractTemplate();
				$sValue = $oTemplate->type_name;
			} else {
				$sValue = '';
			}
		} elseif($sName == 'name') {
			if($this->_aData['id'] > 0) {
				$oContract = $this->getContract();
				$oFormat = new Ext_Thebing_Gui2_Format_Contract_ItemName();
				$sValue = $oFormat->format('', $oDummy, $oContract->aData);
			} else {
				$sValue = '';
			}
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {
		global $user_data;

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$aQueryData['sql'] = "
				SELECT
					`kcont`.*,
					`kcontv`.*,
					`kcontv`.`id`,
					`kcontt`.`name` `template_name`
					{FORMAT}
				FROM
					`{TABLE}` `kcontv` JOIN
					`kolumbus_contracts` `kcont` ON
						`kcontv`.`contract_id` = `kcont`.`id` JOIN
					`kolumbus_contract_templates` `kcontt` ON
						`kcontt`.`id` = `kcont`.`contract_template_id`
			";

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	public function save($bLog = true) {
		throw new Exception("Saving is not allowed in this class!");
	}

}