<?php

/**
 * Virtueller Block: Preise > Währung
 */
class Ext_Thebing_Form_Page_Block_Virtual_Prices_Currency extends Ext_Thebing_Form_Page_Block {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'prices_currency';

	/**
	 * Virtueller Eltern-Block.
	 *
	 * @var Ext_Thebing_Form_Page_Block
	 */
	private $oVirtualParent = null;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct();
		$this->block_id = self::TYPE_SELECT;
		$this->set_type = self::SUBTYPE;
		$this->css_class = 'block-prices-currency';
		$this->parent_area = 0;
		$this->oVirtualParent = $oVirtualParent;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getParentBlock() {

		if($this->oVirtualParent !== null) {
			return $this->oVirtualParent;
		}

		return parent::getParentBlock();

	}

	/**
	 * {@inheritdoc}
	 */
	public function getTitle($sLanguage = null) {

		return $this->getNonVirtualParentBlock()->getTranslation('priceCurrency', $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function canValidate() {

		return false;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataAttributesArray($mSchool, $sLanguage = null) {

		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($mSchool);
		$sLanguage = $this->getDynamicLanguage($sLanguage);
		$oPage = $this->getPage();
		$aAttributes = parent::getInputDataAttributesArray($mSchool, $sLanguage);

		if(
			$oPage === null ||
			$oSchool->id < 1
		) {
			return $aAttributes;
		}

		$oForm = $oPage->getForm();

		if($oForm === null) {
			return $aAttributes;
		}

		$aOptions = $oForm->getSelectedCurrencies($oSchool);
		$aOptions = $this->convertSelectOptions($aOptions);

		$aAttributes[] = array(
			'type' => 'StaticSelectOptions',
			'data' => array(
				'select_options' => $aOptions
			)
		);

		$aAttributes[] = array(
			'type' => 'KeepValueSynced',
			'data' => array(
				'identifier' => self::SUBTYPE
			)
		);

		$aAttributes[] = array(
			'type' => 'TriggerAjaxRequest',
			'data' => array(
				'task' => 'prices'
			)
		);

		return $aAttributes;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataIdentifier() {

		$oParent = $this->getParentBlock();
		return $oParent->getInputDataIdentifier().'_'.self::SUBTYPE;

	}

}
