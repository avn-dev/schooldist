<?php

/**
 * @property integer $id
 * @property integer $active
 * @property integer $creator_id
 * @property integer $user_id
 * @property integer $form_id ID des zugehörigen Formulars (siehe Ext_Thebing_Form)
 * @property string $type
 * @property integer $position
 */
class Ext_Thebing_Form_Page extends Ext_Thebing_Basic {

	const BLOCK_DEPENDENCIES = [
		'booking' => [
			Ext_Thebing_Form_Page_Block::TYPE_PAYMENT => [],
			Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY => [],
			Ext_Thebing_Form_Page_Block::TYPE_UPLOAD => []
		],
		'enquiry' => [
			Ext_Thebing_Form_Page_Block::TYPE_TEXTAREA => [
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_CLASS_CATEGORY,
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_CLASS_LEVEL,
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_CATEGORY,
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_ACCOMMODATION_ROOM_TYPE,
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_FOOD,
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_TYPE,
				Ext_Thebing_Form_Page_Block::SUBTYPE_TEXTAREA_ENQUIRY_TRANSFER_LOCATIONS
			]
		]
	];

	protected $_sTable = 'kolumbus_forms_pages';

	protected $_sTableAlias = 'kfp';

	protected $_aFormat = array(
		'form_id' => array(
			'required' => true,
			'validate' => 'INT_POSITIVE'
		)
	);

	protected $_aJoinedObjects = array(
		'blocks' => array(
			'class' => 'Ext_Thebing_Form_Page_Block',
			'key' => 'page_id',
			'type' => 'child',
			'check_active' => true,
			'cloneable' => true,
			'on_delete' => 'cascade',
			'orderby' => 'position',
			'orderby_type' => 'ASC',
			'static_key_fields' => array(
				'parent_id' => 0
			)
		),
		'form' => array(
			'class' => 'Ext_Thebing_Form',
			'key' => 'form_id',
			'type' => 'parent'
		)
	);

	protected $_aTranslations;

	public function __get($sName) {

		Ext_Gui2_Index_Registry::set($this);

		if($sName == 'translations') {
			$mValue = $this->_aTranslations;
		} elseif(strpos($sName, 'title_') !== false) {
			$aTemp = explode('_', $sName, 2);
			$mValue = $this->_aTranslations[$aTemp[1]];
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}

	public function __set($sName, $mValue) {

		if(strpos($sName, 'title_') !== false) {
			$aTemp = explode('_', $sName, 2);
			$this->_aTranslations[$aTemp[1]] = $mValue;
		} else {
			parent::__set($sName, $mValue);
		}

	}

	/**
	 * Gibt die nächste Positionsnummer für das angegebene Formular zurück.
	 *
	 * Wenn keine Formular-ID angegeben ist wird das verknüpfte Formular verwendet.
	 *
	 * @param integer $iFormID
	 * @return integer
	 */
	public function getNextPosition($iFormID = null) {

		if(is_null($iFormID)) {
			$iFormID = $this->form_id;
		}

		$sSQL = "
			SELECT
				MAX(`position`)
			FROM
				`kolumbus_forms_pages`
			WHERE
				`active` = 1 AND
				`form_id` = :iFormID
		";
		$aSQL = array('iFormID' => $iFormID);
		$iPosition = (int)DB::getQueryOne($sSQL, $aSQL);

		$iPosition++;
		return $iPosition;

	}

	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if ($mValidate === true) {
			if (!empty($this->type)) {
				foreach ($this->getForm()->getPages() as $oPage) {
					if ($oPage->type !== $this->type && System::d('debugmode') == 0) {
						$mValidate = ['type' => 'PAGE_TYPES_CANNOT_BE_MIXED'];
						break;
					}
				}
			}
		}

		if ($mValidate === true) {
			if (!empty($this->type)) {
				$aDenied = collect(self::BLOCK_DEPENDENCIES)
					->except($this->type)
					->mapWithKeys(function ($item) {
						return $item; // flatten mit Keys
					});

				foreach ($this->getBlocks() as $oBlock) {
					if (
						$aDenied->has($oBlock->block_id) && (
							// Ganzer Block-Typ oder Block-Typ mit Untertypen (Input-Felder)
							empty($aDenied[$oBlock->block_id]) ||
							in_array($oBlock->set_type, $aDenied[$oBlock->block_id])
						)
					) {
						$mValidate = ['type' => 'PAGE_HAS_INVALID_BLOCKS'];
						break;
					}
				}

			}
		}

		return $mValidate;

	}

	/**
	 * {@inheritdoc}
	 *
	 * @param boolean $bUpdateAdditionals
	 */
	public function save($bLog = true, $bUpdateAdditionals = true) {

		// Die Einstellungen in lokale Variablen übernehmen, werden ggf. während des Speichervorgangs
		// geändert und am Ende dann wieder an das Objekt zurück übergeben
		$aTranslations = $this->_aTranslations;

		if($this->id <= 0) {
			$this->position = $this->getNextPosition();
		}

		parent::save($bLog);

		if($bUpdateAdditionals) {
			$this->saveTranslations($aTranslations);
		}

//		if($this->active == 0) {
//
//			$sSQL = "
//				UPDATE
//					`kolumbus_forms_pages_blocks`
//				SET
//					`active` = 0
//				WHERE
//					`page_id` = :iPageID
//			";
//			$aSQL = array('iPageID' => $this->id);
//			DB::executePreparedQuery($sSQL, $aSQL);
//
//		}

		// Die Einstellungen wieder von den lokalen Variablen in das Objekt zurück übergeben
		$this->_aTranslations = $aTranslations;

		return $this;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _loadData($iDataID) {

		parent::_loadData($iDataID);

		if($iDataID <= 0) {
			return;
		}

		$sSQL = "
			SELECT
				`language`,
				`content`
			FROM
				`kolumbus_forms_translations`
			WHERE
				`active` = 1 AND
				`item` = 'page' AND
				`item_id` = :iPageID AND
				`field` = 'title'
		";
		$aSQL = array(
			'iPageID' => $this->id
		);
		$this->_aTranslations = DB::getQueryPairs($sSQL, $aSQL);

	}

	/**
	 * Gibt die Liste aller aktiven Blöcke dieser Seite zurück.
	 *
	 * @return Ext_Thebing_Form_Page_Block[]
	 */
	public function getBlocks() {

		$aBlocks = $this->getJoinedObjectChilds('blocks');
		return $aBlocks;

	}

	/**
	 * Gibt das Formular zurück zu dem diese Seite gehört.
	 *
	 * @return Ext_Thebing_Form
	 */
	public function getForm() {

		$oForm = $this->getJoinedObject('form');
		return $oForm;

	}

	/**
	 * Gibt den Titel der Seite in der angegebenen Sprache zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getTitle($sLanguage = null) {

		$sLanguage = (string)$sLanguage;

		if(strlen($sLanguage) < 1) {
			$oForm = $this->getForm();
			$sLanguage = $oForm->default_language;
		}

		if(!isset($this->_aTranslations[$sLanguage])) {
			return '';
		}

		$sTitle = (string)$this->_aTranslations[$sLanguage];
		return $sTitle;

	}

	/**
	 * Gibt die Daten-Attribute zur Verwendung im HTML zurück.
	 *
	 * @uses Ext_Thebing_Form_Page::getPageDataAttributesArray()
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return string
	 */
	public function getPageDataAttributes($mSchool, $sLanguage = null) {

		$sAttributes = '';

		$aData = $this->getPageDataAttributesArray($mSchool, $sLanguage);
		if(count($aData) > 0) {
			$sData = htmlentities(json_encode($aData));
			$sAttributes .= ' data-dynamic-config="'.$sData.'" ';
		}

		$sAttributes .= ' data-validateable="page" data-form-navigation="page" ';

		$sAttributes = trim($sAttributes);

		if(strlen($sAttributes) > 0) {
			$sAttributes = ' '.$sAttributes.' ';
		}

		return $sAttributes;

	}

	/**
	 * Gibt die Daten-Attribute als Array zurück.
	 *
	 * @param integer|Ext_Thebing_School|Ext_Thebing_School_Proxy $mSchool
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public function getPageDataAttributesArray($mSchool, $sLanguage = null) {

		return array();

	}

	/**
	 * Gibt true zurück wenn diese Seite die erste Seite des Formulars ist, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isFirstPage() {

		$oForm = $this->getForm();
		$aPages = $oForm->getPages();
		$oFirstPage = array_shift($aPages);

		return ($oFirstPage->id == $this->id);

	}

	/**
	 * Gibt true zurück wenn diese Seite die letzte Seite des Formulars ist, ansonsten false.
	 *
	 * @return boolean
	 */
	public function isLastPage() {

		$oForm = $this->getForm();
		$aPages = $oForm->getPages();
		$oLastPage = array_pop($aPages);

		return ($oLastPage->id == $this->id);

	}

	/**
	 * Gibt einen gültigen Sprach-Code zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	protected function getDynamicLanguage($sLanguage) {

		$sLanguage = (string)$sLanguage;

		if(strlen($sLanguage) < 1) {
			$oForm = $this->getForm();
			$sLanguage = $oForm->default_language;
		}

		return $sLanguage;

	}

	/**
	 * Gibt den Titel/Text für den Zurück-Button zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getPreviousPageButtonText($sLanguage = null) {

		$sLanguage = $this->getDynamicLanguage($sLanguage);
		return $this->getForm()->getTranslation('prevbtn', $sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für den Zurück-Button zurück.
	 *
	 * @return string
	 */
	public function getPreviousPageButtonDataAttributres() {

		return ' data-form-navigation="prev" ';

	}

	/**
	 * Gibt den Titel/Text für den Weiter-Button zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getNextPageButtonText($sLanguage = null) {

		$sLanguage = $this->getDynamicLanguage($sLanguage);
		return $this->getForm()->getTranslation('nextbtn', $sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für den Weiter-Button zurück.
	 *
	 * @return string
	 */
	public function getNextPageButtonDataAttributres() {

		return ' data-form-navigation="next" ';

	}

	/**
	 * Gibt den Titel/Text für den Absenden-Button zurück.
	 *
	 * Wenn keine Sprache angegeben ist ($sLanguage leer/null) wird die Standardsprache des Formulars verwendet.
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getSubmitButtonText($sLanguage = null) {

		$sLanguage = $this->getDynamicLanguage($sLanguage);
		return $this->getForm()->getTranslation('sendbtn', $sLanguage);

	}

	/**
	 * Gibt die Daten-Attribute für den Absenden-Button zurück.
	 *
	 * @return string
	 */
	public function getSubmitButtonDataAttributres() {

		return ' data-form-navigation="submit" ';

	}

	/**
	 * {@inheritdoc}
	 */
	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {

		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		$oClone->_aTranslations = $this->_aTranslations;

		$oClone->saveTranslations($oClone->_aTranslations);

		return $oClone;

	}

	/**
	 * Übersetzungen speichern
	 *
	 * @param mixed[] $aTranslations
	 */
	protected function saveTranslations($aTranslations) {

		if(DB::getLastTransactionPoint() === null) {
			throw new RuntimeException(__METHOD__.': Not in a transaction!');
		}

		$sSQL = "
			DELETE FROM
				`kolumbus_forms_translations`
			WHERE
				`item`		= 'page' AND
				`item_id`	= :iPageID
		";
		$aSQL = array(
			'iPageID' => $this->id
		);
		DB::executePreparedQuery($sSQL, $aSQL);

		foreach((array)$aTranslations as $sLanguage => $sContent) {
			$oTranslation = new Ext_Thebing_Form_Translation();
			$oTranslation->item = 'page';
			$oTranslation->item_id = $this->id;
			$oTranslation->language = $sLanguage;
			$oTranslation->field = 'title';
			$oTranslation->content = $sContent;
			$oTranslation->save();
		}

	}

}
