<?php

/**
 * Virtueller Block: Container
 */
class Ext_Thebing_Form_Page_Block_Virtual_Container extends Ext_Thebing_Form_Page_Block_Virtual_Abstract {

	/**
	 * Kind-Blöcke dieses Blocks.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getChildBlocks()
	 * @var Ext_Thebing_Form_Page_Block[]
	 */
	public $aChildBlocks = array();

	/**
	 * Die Daten-Attribute als Array.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getBlockDataAttributesArray()
	 * @var mixed[]
	 */
	public $aBlockDataAttributesArray = null;

	/**
	 * Die Daten-Attribute als Callback.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getBlockDataAttributesArray()
	 * @var callable
	 */
	public $mBlockDataAttributesCallback = null;

	/**
	 * Die zusätzlichen Daten-Attribute als String.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getAdditionalBlockDataAttributes()
	 * @var string
	 */
	public $sAdditionalBlockDataAttributes = null;

	/**
	 * Die Daten-Attribute als Array.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getTitleDataAttributesArray()
	 * @var mixed[]
	 */
	public $aTitleDataAttributesArray = null;

	/**
	 * Die Daten-Attribute als Callback.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getTitleDataAttributesArray()
	 * @var callable
	 */
	public $mTitleDataAttributesCallback = null;

	/**
	 * Die Daten-Attribute als Array.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getInputDataAttributesArray()
	 * @var mixed[]
	 */
	public $aInputDataAttributesArray = null;

	/**
	 * Die Daten-Attribute als Callback.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getInputDataAttributesArray()
	 * @var callable
	 */
	public $mInputDataAttributesCallback = null;

	/**
	 * Der Identifier des Eingabe-Elements.
	 *
	 * @see Ext_Thebing_Form_Page_Block::getInputDataIdentifier()
	 * @var string
	 */
	public $sInputDataIdentifier = null;

	/**
	 * @param Ext_Thebing_Form_Page_Block $oVirtualParent
	 */
	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct();
		$this->block_id = self::TYPE_COLUMNS;
		$this->set_type = '';
		$this->parent_area = 0;
		$this->oVirtualParent = $oVirtualParent;

	}

	/**
	 * {@inheritdoc}
	 */
	public function hasAreas() {

		return true;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getAreaWidths() {

		return array(
			0 => 100
		);

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
	public function getChildBlocks($bCheckCache = false) {

		if($this->aChildBlocks !== null) {
			return (array)$this->aChildBlocks;
		}

		return parent::getChildBlocks();

	}

	/**
	 * {@inheritdoc}
	 */
	public function getBlockDataAttributesArray($mSchool, $sLanguage = null) {

		if(is_callable($this->mBlockDataAttributesCallback)) {
			return call_user_func($this->mBlockDataAttributesCallback, $mSchool, $sLanguage);
		}

		if($this->aBlockDataAttributesArray !== null) {
			return $this->aBlockDataAttributesArray;
		}

		return parent::getBlockDataAttributesArray($mSchool, $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getAdditionalBlockDataAttributes($mSchool, $sLanguage = null) {

		if($this->sAdditionalBlockDataAttributes !== null) {
			return $this->sAdditionalBlockDataAttributes;
		}

		return parent::getAdditionalBlockDataAttributes($mSchool, $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getTitleDataAttributesArray($mSchool, $sLanguage = null) {

		if(is_callable($this->mTitleDataAttributesCallback)) {
			return call_user_func($this->mTitleDataAttributesCallback, $mSchool, $sLanguage);
		}

		if($this->aTitleDataAttributesArray !== null) {
			return $this->aTitleDataAttributesArray;
		}

		return parent::getTitleDataAttributesArray($mSchool, $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataAttributesArray($mSchool, $sLanguage = null) {

		if(is_callable($this->mInputDataAttributesCallback)) {
			return call_user_func($this->mInputDataAttributesCallback, $mSchool, $sLanguage);
		}

		if($this->aInputDataAttributesArray !== null) {
			return $this->aInputDataAttributesArray;
		}

		return parent::getInputDataAttributesArray($mSchool, $sLanguage);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getInputDataIdentifier() {

		if($this->sInputDataIdentifier !== null) {
			return $this->sInputDataIdentifier;
		}

		$oParent = $this->getParentBlock();
		return $oParent->getInputDataIdentifier().'_container';

	}

}
