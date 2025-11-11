<?php

/**
 * Virtueller Block: Preise > Preisliste
 */
class Ext_Thebing_Form_Page_Block_Virtual_Prices_Display extends Ext_Thebing_Form_Page_Block {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = 'prices_display';

	/**
	 * Virtueller Eltern-Block.
	 *
	 * @var Ext_Thebing_Form_Page_Block
	 */
	private $oVirtualParent = null;

	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct();
		$this->block_id = self::TYPE_STATIC_TEXT;
		$this->set_type = self::SUBTYPE;
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
	public function getBlockText($sLanguage = null) {

		return '-----';

	}

	/**
	 * {@inheritdoc}
	 */
	public function getTranslation($sKey, $sLanguage = null) {

		return $this->getNonVirtualParentBlock()->getTranslation($sKey, $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockDataAttributesArray($mSchool, $sLanguage = null) {

		$aAttributes = parent::getBlockDataAttributesArray($mSchool, $sLanguage);

		$aAttributes[] = array(
			'type' => 'AjaxListResult',
			'data' => array(
				'task' => 'prices',
				'result_group' => $this->getInputDataIdentifier()
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
