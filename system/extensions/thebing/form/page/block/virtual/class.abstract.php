<?php

/**
 * Virtueller Block: Abstrakte Klasse
 */
abstract class Ext_Thebing_Form_Page_Block_Virtual_Abstract extends Ext_Thebing_Form_Page_Block {

	/**
	 * @see Ext_Thebing_Form_Page_Block::$set_type
	 */
	const SUBTYPE = '';

	/**
	 * Anderer Key für Title-Übersetzung
	 */
	const TRANSLATION_TITLE = '';

	/**
	 * Info text key
	 *
	 * @var string
	 */
	const INFO_TEXT_KEY = '';

	/**
	 * Virtueller Eltern-Block
	 *
	 * @var Ext_Thebing_Form_Page_Block
	 */
	protected $oVirtualParent = null;

	/**
	 * Flag neben $this->required, damit man das in der Methode isRequired() verwenden kann (siehe Methode)
	 *
	 * @see isRequired()
	 * @var bool
	 */
	protected $bRequired = false;

	/**
	 * @param Ext_Thebing_Form_Page_Block $oVirtualParent
	 */
	public function __construct(Ext_Thebing_Form_Page_Block $oVirtualParent = null) {

		parent::__construct();
		$this->set_type = '';
		$this->parent_area = 0;
		$this->oVirtualParent = $oVirtualParent;

	}

	/**
	 * @inheritdoc
	 */
	public function canValidate() {
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslation($sKey, $sLanguage = null) {

		/** @var Ext_Thebing_Form_Page_Block $oParent */
		$oParent = $this->getNonVirtualParentBlock();

		if($oParent !== null) {
			return $oParent->getTranslation($sKey, $sLanguage);
		}

		return '';

	}

	/**
	 * @inheritdoc
	 */
	public function getTitle($sLanguage = null) {

		// Anderes Feld muss genommen werden
		if(!empty(static::TRANSLATION_TITLE)) {
			return $this->getTranslation(static::TRANSLATION_TITLE, $sLanguage);
		}

		return parent::getTitle($sLanguage);

	}

	/**
	 * @inheritdoc
	 */
	public function getParentBlock() {

		if($this->oVirtualParent !== null) {
			return $this->oVirtualParent;
		}

		return parent::getParentBlock();

	}

	/**
	 * @inheritdoc
	 */
	public function getInputBlockName() {

		$sName  = parent::getInputBlockName();
		$sName .= '[0]['.static::SUBTYPE.']';
		return $sName;

	}

	/**
	 * @inheritdoc
	 */
	public function getInputDataIdentifier() {

		$oParent = $this->getParentBlock();
		return $oParent->getInputDataIdentifier().'_'.static::SUBTYPE;

	}

	/**
	 * @inheritdoc
	 */
	public function hasFormInputValue(MVC_Request $oRequest) {

		$sInputName = $this->getInputBlockName();

		if(strpos($sInputName, '[') !== false) {
			$mValue = $this->getFormInputValue($oRequest);
			return $mValue !== null;
		}

		return parent::hasFormInputValue($oRequest);

	}

	/**
	 * @inheritdoc
	 */
	public function getFormInputValue(MVC_Request $oRequest) {

		$sInputName = $this->getInputBlockName();

		// Keys sind ein String, aber PHP hat aus [] Arrays gemacht
		if(strpos($sInputName, '[') !== false) {

			// Sieht in etwa so aus: block_520_transfers_transfertype[0][transfers_transfertype]
			preg_match('/(.+)\[(\d+)\]\[([a-z0-9_]+)\]/i', $sInputName, $aMatches);

			if($oRequest->exists($aMatches[1])) {
				$aValue = $oRequest->input($aMatches[1]);
				if(isset($aValue[(int)$aMatches[2]][$aMatches[3]])) {
					return $aValue[(int)$aMatches[2]][$aMatches[3]];
				}
			}

			return null;

		}

		return parent::getFormInputValue($oRequest);

	}

	/**
	 * @inheritdoc
	 */
	public function getInfoMessage($sLanguage = null) {
		return $this->getTranslation(static::INFO_TEXT_KEY, $sLanguage);
	}

	/**
	 * Abgeleitet, damit bei den restlichen Feldern der Stern angezeigt werden kann.
	 * Tatsächliche Abhängigkeit geht über das Attribut DependencyRequirement.
	 *
	 * @inheritdoc
	 */
	public function isRequired() {

		if(
			parent::isRequired() ||
			$this->bRequired
		) {
			return true;
		}

		return false;

	}

	/**
	 * Child-Block im Container suchen
	 *
	 * @param string $sClass
	 * @return Ext_Thebing_Form_Page_Block_Virtual_Abstract|null
	 */
	protected function getChildBlock($sClass) {

		$oBlock = null;
		$oParent = $this;
		if(!$this instanceof Ext_Thebing_Form_Page_Block_Virtual_Container) {
			$oParent = $this->getParentBlock();
		}

		foreach($oParent->getChildBlocks(true) as $oChildBlock) {
			if($oChildBlock instanceof $sClass) {
				/** @var Ext_Thebing_Form_Page_Block_Virtual_Abstract $oBlock */
				$oBlock = $oChildBlock;
				break;
			}
		}

		if($oBlock === null) {
			throw new RuntimeException('Could not find '.$sClass.'!');
		}

		return $oBlock;

	}

	/**
	 * Input-Data-Identifier von Child-Block im Container
	 *
	 * @param string $sClass
	 * @return string
	 */
	protected function getChildBlockInputDataIdentifier($sClass) {
		$oBlock = $this->getChildBlock($sClass);
		return $oBlock->getInputDataIdentifier();
	}

	/**
	 * Basis-Array für die Abhängigkeiten der virtuellen Felder, sobald Typ ausgewählt wurde
	 *
	 * @param string $sField
	 * @param array $aDependencyRequirements
	 * @return array
	 */
	protected function getDependencyRequirementAttributeArray($sField, array $aDependencyRequirements) {

		return [
			'type' => 'DependencyRequirement',
			'data' => [
				'dependencies' => [
					[
						'type' => 'Field',
						'name' => $sField,
						'values' => $aDependencyRequirements
					]
				]
			]
		];

	}

}
