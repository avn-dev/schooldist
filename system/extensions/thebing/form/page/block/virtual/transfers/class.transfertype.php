<?php

/**
 * Virtueller Block: Transfer > Transfer
 */
class Ext_Thebing_Form_Page_Block_Virtual_Transfers_Transfertype extends Ext_Thebing_Form_Page_Block_Virtual_Abstract {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'transfers_transfertype';

	const TRANSLATION_TITLE = 'type';

	public function __construct() {

		parent::__construct();
		$this->block_id = self::TYPE_SELECT;
		$this->set_type = self::SUBTYPE;
		$this->parent_area = 0;

	}

	/**
	 * @inheritdoc
	 */
	public function canValidate() {
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function getSelectOptions($mSchool, $sLanguage = null, $bAddEmpty = true, $sEmptyText = '', $sEmptyValue = '0') {

		$oForm = $this->getPage()->getForm();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oParent = $this->getParentBlock();
		$aOptions = array();
		$aSettings = $oParent->_aSettings;

		$aRelevantSettings = array(
			'transNo'.$oSchool->id => array('transNo', 'no'),
			'transArr'.$oSchool->id => array('transArr', 'arrival'),
			'transDep'.$oSchool->id => array('transDep', 'departure'),
			'transArrDep'.$oSchool->id => array('transArrDep', 'arr_dep')
		);
		foreach($aRelevantSettings as $sKey => $aData) {
			if(
				isset($aSettings[$sKey]) &&
				$aSettings[$sKey]
			) {
				$aOptions[$aData[1]] = $oParent->getTranslation($aData[0], $sLanguage);
			}
		}

		 if($bAddEmpty) {
		 	Ext_TC_Util::addEmptyItem($aOptions, $oForm->getTranslation('defaultdd', $sLanguage));
		 }

		 return $aOptions;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataAttributesArray($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oParent = $this->getParentBlock();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		if(
			$oParent === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$aOptions = $this->getSelectOptions($oSchool, $sLanguage);
		$aOptions = $this->convertSelectOptions($aOptions);

		$aAttributes[] = array(
			'type' => 'StaticSelectOptions',
			'data' => array(
				'select_options' => $aOptions
			)
		);

		$aAttributes[] = array(
			'type' => 'TriggerAjaxRequest',
			'data' => array(
				'task' => 'prices'
			)
		);

		if($this->required) {
			$aAttributes[] = [
				'type' => 'ValidateInput',
				'data' => [
					'message' => $this->getTranslation('error', $sLanguage),
					'algorithm' => 'SelectOptionsBlacklist',
					'blacklist' => ['0', 'no']
				]
			];
		}

		return $aAttributes;

	}

}
