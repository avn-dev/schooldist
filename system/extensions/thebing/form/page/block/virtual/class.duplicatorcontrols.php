<?php

/**
 * Virtueller Block: Buttons zum Duplizieren von Blöcken
 */
class Ext_Thebing_Form_Page_Block_Virtual_Duplicatorcontrols extends Ext_Thebing_Form_Page_Block_Virtual_Abstract {

	/**
	 * @param Ext_Thebing_Form_Page_Block $oVirtualParent
	 */
	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct();
		$this->block_id = self::TYPE_UNDEFINED;
		$this->set_type = self::SUBTYPE_SPECIAL_DUPLICATOR_CONTROLS;
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
	public function isSpecialBlock() {

		return true;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getDuplicateAddButtonText($mSchool, $sLanguage = null) {

		$oParent = $this->getNonVirtualParentBlock();
		$sLanguage = $this->getDynamicLanguage($sLanguage);

		switch($oParent->block_id) {
			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
			case Ext_Thebing_Form_Page_Block::TYPE_FEES:
				return $oParent->getTranslation('add', $sLanguage);
		}

		return parent::getDuplicateAddButtonText($mSchool, $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getDuplicateRemoveButtonText($mSchool, $sLanguage = null) {

		$oParent = $this->getNonVirtualParentBlock();
		$sLanguage = $this->getDynamicLanguage($sLanguage);

		switch($oParent->block_id) {
			case Ext_Thebing_Form_Page_Block::TYPE_COURSES:
			case Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
			case Ext_Thebing_Form_Page_Block::TYPE_INSURANCES:
			case Ext_Thebing_Form_Page_Block::TYPE_FEES:
				return $oParent->getTranslation('remove', $sLanguage);
		}

		return parent::getDuplicateRemoveButtonText($mSchool, $sLanguage);

	}

}
