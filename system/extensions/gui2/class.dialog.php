<?php

/**
 * 
 */
class Ext_Gui2_Dialog implements \Gui2\Dialog\DialogInterface {

	/**
	 * @var array
	 */
	public $aElements = array();

	/**
	 * @var bool
	 */
	public $bSetElement = true;

	/**
	 * @var array
	 */
	public $aSaveData = array();

	/**
	 * @var array
	 */
	public $aFlexSaveData = array();

	/**
	 * @var array
	 */
	public $aUniqueFields = array();

	/**
	 * @var array
	 */
	public $aUploadData = array();

	/**
	 * @var bool
	 */
	public $save_button = true;

	/**
	 * @var bool
	 */
	public $save_as_new_button = false;

	/**
	 * @var bool
	 */
	public $save_bar_options = false;

	/**
	 * @var string
	 */
	public $save_bar_default_option	= 'close';

	/**
	 * @var array
	 */
	public $aButtons = array();

	/**
	 * @var int
	 */
	public $iFlexRowCount = 0;

	/**
	 * @var string
	 */
	public $sDialogIDTag = 'ID_';

	/**
	 * @var array
	 */
	public $aOptions = array();

	/**
	 * @var bool
	 */
	public $bReadOnly = false;

	/**
	 * @var bool
	 */
	public $bDefaultReadOnly = false;

	/**
	 * @var array
	 */
	protected $_aOptionalAjaxData = array();

	/**
	 * @var array
	 */
	public $aFlexColumnGroups = array();

	/**
	 * @var array
	 */
	private $aValidation = array();

	/**
	 * @var bool
	 */
	public $bSmallLabels = false; // wenig Platz für Labels

	/**
	 * @var bool
	 */
	public $bBigLabels = false; // viel Platz für Labels

	/**
	 * @var array
	 */
	public $aLabelCache	= array();

	/**
	 * @var null
	 */
	protected $_sWDBasic = null;

	/**
	 * @var string
	 */
	public $sAdditional	= '';

	/**
	 * @var int
	 */
	public $width = 1200;

	/**
	 * @var int
	 */
	public $height = 0;
	
	/**
	 * Enthält den where Part aus der GUI->_aTableData
	 */
	protected $_aInitData = array();
	
	/**
	 * @var Ext_Gui2_Dialog_Data
	 */
	protected $_oDataObject = null;
	
	/**
	 * @var Ext_Gui2
	 */
	public $oGui;

	/**
	 * Dialog-Lock-Prüfung aktivieren
	 *
	 * @var bool
	 */
	public $bCheckLock = true;

	/**
	 *
	 * Joined Object Container im Dialog merken, damit Optionen definiert werden können
	 * 
	 * @var Ext_Gui2_Dialog_JoinedObjectContainer[]
	 */
	protected $_aJoinedObjectContainer = array();

	/**
	 * AlertNachrichten die mit dem Öffnen des Dialogs angezeigt werden sollen
	 * @var array
	 */
	private $aAlertMessages = array();

	protected $bShowInfoIcons = true;

	public $access;

	/**
	 * Zähler für innere Tabs
	 * @var int
	 */
	public $iTabCounter = 0;
	
	public $js = null;
	
	/* ==================================================================================================== */

	/**
	 * Wenn der Konstruktor manuell aufgerufen wird, muss $this->$oGui manuell gesetzt werden!
	 * @param type $sTitle
	 * @param type $sTitleNew
	 * @param type $sTitleMultiple 
	 */
	public function  __construct($sTitle='', $sTitleNew='', $sTitleMultiple='') {
		$this->aOptions['title'] = $sTitle;
		$this->aOptions['title_new'] = $sTitleNew;
		$this->aOptions['title_multiple'] = $sTitleMultiple;
	}

	public function setOption($sOption, $mValue) {
		$this->aOptions[$sOption] = $mValue;
	}
	
	public function getOption($sOption, $mDefault = null) {
		return (isset($this->aOptions[$sOption])) ? $this->aOptions[$sOption] : $mDefault;
	}
	
	public function setUploadElement($oElement){
		$this->aUploadData[] = $oElement;
	}

	public function getUploadElement() {
		return $this->aUploadData;
	}

	/**
	 * @param array $aMessage
	 */
	public function addAlertMessage(array $aMessage) {
		$this->aAlertMessages[] = $aMessage;
	}

	public function disableInfoIcons() {
		$this->bShowInfoIcons = false;
		return $this;
	}
	
	/**
	 *
	 * @param string $sLabel
	 * @param string|bool $sMessage
	 * @param string $sType (hint/info/success/error)
	 * @param array $aOptions
	 * @return Ext_Gui2_Html_Div
	 */
	public function createNotification($sLabel, $sMessage=false, $sType = 'error', $aOptions = array()) {

		$oDiv			= $this->create('div');
		$oDiv->class	= 'GUIDialogNotification alert alert-dismissible mb-2';

		if(!empty($aOptions['row_class'])) {
			$oDiv->class .= ' '.$aOptions['row_class'];
		}

		if(!empty($aOptions['row_style'])) {
			$oDiv->style = $aOptions['row_style'];
		}

		if(!empty($aOptions['row_id'])) {
			$oDiv->id = $aOptions['row_id'];
		}

		switch($sType) {
			case 'hint':
				$sIcon = 'fa-warning';
				$oDiv->class .= ' alert-warning';
				break;
			case 'info':
				$sIcon = 'fa-info-circle';
				$oDiv->class .= ' alert-info';
				break;
			case 'success':
				$sIcon = 'fa-check';
				$oDiv->class .= ' alert-success';
				break;
			case 'error':
			default:
				$sIcon = 'fa-ban';
				$oDiv->class .= ' alert-danger';
				break;
		}

		$oDivFlex = $this->create('div');
		$oDivFlex->class = 'flex';
		$oDivFlex->setElement('<div class="flex-shrink-0"><i class="icon fa '.$sIcon.'"></i></div>');

		$oDivContent = $this->create('div');
		$oDivContent->class = 'ml-3';

		$oDivContent->setElement('<h3>'.$sLabel.'</h3>');

		if($sMessage) {
			$oDivContent->setElement('<div class="mt-1"><p>'.$sMessage.'</p></div>');
		}

		$oDivFlex->setElement($oDivContent);

		$bDismissable = $aOptions['dismissible'] ?? true;

		if ($bDismissable) {
			$oDismiss = $this->create('div');
			$oDismiss->class = 'ml-auto pl-3';

			$oDismiss->setElement('
				<div class="-mx-1.5 -my-1.5">
					<button type="button" class="font-medium inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 close" data-dismiss="alert" aria-hidden="true">
						<i class="icon fa fa-times" aria-hidden="true"></i>
					</button>
				</div>
			');

			$oDivFlex->setElement($oDismiss);
		}

		/*$sContent = '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><h4><i class="icon fa '.$sIcon.'"></i><span> '.$sLabel.'</span></h4><div>';

		if($sMessage) {
			$sContent .= $sMessage;
		}

		$sContent .= '</div>';*/

		$oDiv->setElement($oDivFlex);

		return $oDiv;
	}

	public function createExpandableBox($sLabel, $sType = 'default') {

		$oBox = new \Gui2\Element\Expandable($sLabel, $sType);
		
		return $oBox;
	}

	/**
	 * Creat 1 Inputs for each Language.
	 * Save and Read Language Data from Jointables
	 * @param string $sLabel
	 * array(
	 * 'db_alias' => '',
	 * 'db_column' => '',
	 * 'row_class' => '',
	 * 'row_id' => '',
	 * 'required' => 0,
	 * 'i18n_parent_column' => 'parent_id',
	 * 'advanced' => true
	 * )
	 * @param array $aOptions
	 * @param array $aLanguages ( Array with language IDs )
	 * @return Ext_Gui2_Html_Div
	 */
	public function createI18NRow($sLabel, $aOptions = array(), $aLanguages = null){

		if(
			$aLanguages === null &&
			$this->oGui instanceof Ext_Gui2 &&
			$this->oGui->i18n_languages
		) {
			$aLanguages = $this->oGui->i18n_languages;
		}
		
		$oDiv			= $this->create('div');
		$oDiv->class	= 'GUIDialogRow form-group form-group-sm';
		
		$oDiv->setDataAttribute('row-key', $this->generateUniqueRowKey($aOptions));
		
		if(!empty($aOptions['row_class'])){
			$oDiv->class = $aOptions['row_class'];
		}
				
		if(!empty($aOptions['row_id'])){
			$oDiv->id = $aOptions['row_id'];
		}
		
		if(!empty($aOptions['type'])) {
			$sType = $aOptions['type'];
		} else {
			$sType = 'input';
		}
	
		$oDivInput = $this->create('div');
		$oDivInput->class = 'i18n-fields';
		
		$oDivLabel = $this->getLabelDiv($aOptions, $oDivInput);

		$aOptions['label'] = $sLabel;

		$this->manipulateRowLabel($oDivLabel, $aOptions);
		
		$oDivLabel->setElement($sLabel);
		
		// TODO Welchen Sinn hat das hier? Die WDBasic schafft das auch ohne diesen Key beim Speichern
		if(empty($aOptions['i18n_parent_column'])){
			$aOptions['i18n_parent_column'] = 'parent_id';
		}
		
		foreach((array)$aLanguages as $iKey => $aLanguage) {

			$aInputOptions = array();
			$aInputOptions['db_alias']				= $aOptions['db_alias'];

			if(!empty($aOptions['db_column_prefix'])) {

				$aInputOptions['db_column'] = $aOptions['db_column_prefix'].$aLanguage['iso'];

			} else {

				$aInputOptions['db_column'] = $aOptions['db_column'];
				$aInputOptions['i18n']					= 1;
				$aInputOptions['i18n_language']			= $aLanguage['iso'];
				$aInputOptions['i18n_parent_column']	= $aOptions['i18n_parent_column'];
			}

			$aInputOptions['required'] = $aOptions['required'] ?? null;
			$aInputOptions['advanced'] = $aOptions['advanced'] ?? null;
			
			if(isset($aOptions['dependency_visibility'])) {
				$aInputOptions['dependency_visibility']	= $aOptions['dependency_visibility'];
			}
			if(isset($aOptions['dependency'])) {
				$aInputOptions['dependency']			= $aOptions['dependency'];
			}
			if(isset($aOptions['child_visibility'])) {
				$aInputOptions['child_visibility']		= $aOptions['child_visibility'];
			}
			if(!empty($aOptions['joined_object_key'])) {
				$aInputOptions['joined_object_key']		= $aOptions['joined_object_key'];
			}
			if(!empty($aOptions['skip_value_handling'])) {
				$aInputOptions['skip_value_handling']= $aOptions['skip_value_handling'];
			}
			if(!empty($aOptions['format'])) {
				$aInputOptions['format']= $aOptions['format'];
			}
			if (isset($aOptions['max_length'])) {
				$aInputOptions['maxlength'] = $aOptions['max_length'];
			}
			if (isset($aOptions['value'][$aLanguage['iso']])) {
				$aInputOptions['value'] = $aOptions['value'][$aLanguage['iso']];
			}

			$oInput = $this->createSaveField($sType, $aInputOptions);
			
			$oInput->title = $aLanguage['name'];
			$oInput->class = 'i18nInput';
					
			if(!empty($aOptions['readonly'])) {
				$oInput->bReadOnly = $aOptions['readonly'];
			}
			
			if($this->oGui){
				$bAccess = $this->oGui->getDataObject()->checkI18NAccess($aLanguage['iso']);
				if(!$bAccess){
					$oInput->bReadOnly = true;
					$oInput->bDisabledByReadonly = false;
				}
			}

			$oLanguageContainerDiv = new Ext_Gui2_Html_Div();
			$oLanguageContainerDiv->id = 'i18n_container_'.($aOptions['db_column'] ?? '').'_'.($aOptions['db_alias'] ?? '').'_'.$aLanguage['iso'];
			$oLanguageContainerDiv->class = 'input-group';
			
			$oFlagDiv = new Ext_Gui2_Html_Span();
			$oFlagDiv->class = 'i18nFlag input-group-addon';
			$sFlagIcon = Util::getFlagIcon($aLanguage['iso']);
			$oFlagDiv->setElement('<img src="'.$sFlagIcon.'" alt="'.$aLanguage['iso'].'" title="'.$aLanguage['name'].' ('.$aLanguage['iso'].')" />');

			$oLanguageContainerDiv->setElement($oFlagDiv);
			$oLanguageContainerDiv->setElement($oInput);

			// Button, um Value des ersten Inputs in alle Inputs zu übernehmen
			if (
				$iKey === 0 &&
				$sType === 'input'
			) {
				$oBtn = new Ext_Gui2_Html_Button();
				$oBtn->class = 'btn btn-default btn-sm i18n-copy-value';
				$oBtn->title = L10N::t('Wert in alle Felder übernehmen', \Ext_Gui2::$sAllGuiListL10N);
				$oBtn->setElement('<i class="fa fa-long-arrow-down"></i>');
				$oCopyDiv = new Ext_Gui2_Html_Span();
				$oCopyDiv->class = 'input-group-btn';
				$oCopyDiv->setElement($oBtn);
				$oLanguageContainerDiv->setElement($oCopyDiv);
			}

			$oDivInput->setElement($oLanguageContainerDiv);
		}

		$oDiv->setElement($oDivLabel);
		$oDiv->setElement($oDivInput);

		return $oDiv;
	}
	


	/**
	 * @param $sJoinedObjectKey
	 * @param array $aOptions
	 *
	 *		array(
	 * 			'min' => 0|1, # Standard: 1
	 * 			'max' => 255,
	 * 			'no_confirm' => 0|1, Standard: 0
	 *
	 * @return Ext_Gui2_Dialog_JoinedObjectContainer
	 */
	public function createJoinedObjectContainer($sJoinedObjectKey, $aOptions=array()) {
		
		$oContainer = new Ext_Gui2_Dialog_JoinedObjectContainer($sJoinedObjectKey, $this);

		foreach($aOptions as $sKey => $mOption) {
			$oContainer->aOptions[$sKey] = $mOption;
		}
		
		$this->_aJoinedObjectContainer[$sJoinedObjectKey] = $oContainer;

		return $oContainer;

	}

	public function createFlexRow($aData, $sType, $bGroups=false, $iTab = 1) {

		$sLabel = (string)$aData['label'];
		$sGroup = (string)$aData['group'];

		// || als trennzeichen für spalte/alias da "_" evt. in einem von beidem vorkommenkann
		$sName = 'flex['.$sType.']['.$this->iFlexRowCount.']['.$aData['db_column'].'||'.$aData['db_alias'].']';

		$sId = 'flex_'.$sType.'_'.$this->iFlexRowCount.'_'.$aData['db_column'].'_'.$aData['db_alias'];
		
		if($sLabel == "") {
			$sLabel = 'No Title['.$aData['db_column'].']';
		}

		if(!empty($aData['mouseover_title'])) {
			$sLabel .= ' ('.$aData['mouseover_title'].')';
		}

		$oDiv				= $this->create('div');
		$oDiv->class		= 'GUIDialogRow';

		$oDivLabel			= $this->create('div');
		$oDivLabel->class	= 'GUIDialogRowFlexLabelDiv';
		$oDivCheckbox		= $this->create('div');
		$oDivCheckbox->class = 'GUIDialogRowFlexDiv';

		if($bGroups) {

			$oDivGroupLabel			= $this->create('div');
			$oDivGroupLabel->class	= 'GUIDialogGroupLabelDiv';
			$oDivGroupCheckbox		= $this->create('div');
			$oDivGroupCheckbox->class = 'GUIDialogRowFlexDiv';

			if($sGroup) {
				$sGroup = strip_tags($sGroup, '<img><i>');
			} else {
				$sGroup = '&nbsp;';
			}

			$oGroupLabel = $this->create('label');
			$oGroupLabel->setElement($sGroup);

			$oDivGroupLabel->setElement($oGroupLabel);

			$bShowGroupCheckbox = false;
			if(
				$sGroup &&
				!isset($this->aFlexColumnGroups[$sGroup])
			) {

				$oGroupCheckbox			= $this->create('input');
				$oGroupCheckbox->class	= 'group_checkbox';
				$oGroupCheckbox->type	= 'checkbox';
				$oGroupCheckbox->value	= 1;

				$this->aFlexColumnGroups[$sGroup] = count($this->aFlexColumnGroups);

				$oGroupCheckbox->name = 'column_group_'.$iTab.'_'.(int)$this->aFlexColumnGroups[$sGroup];

				$oDivGroupCheckbox->setElement($oGroupCheckbox);

			}

		}

		$oLabel = $this->create('label');
		$oLabel->setElement(strip_tags($sLabel, '<img><i>'));
		$oLabel->for = $sId;
		
		$oDivLabel->setElement($oLabel);

		$this->iFlexRowCount++;
		$oHidden			= $this->create('input');
		$oHidden->type		= 'hidden';
		$oHidden->name		= $sName;
		$oHidden->value		= 0;

		$oCheckbox			= $this->create('input');
		$oCheckbox->type	= 'checkbox';
		$oCheckbox->name	= $sName;
		$oCheckbox->id = $sId;
		if($sGroup) {
			$oCheckbox->class	= 'column_group_'.$iTab.'_'.(int)$this->aFlexColumnGroups[$sGroup];
		}
		$oCheckbox->value = 1;

		if($aData['visible'] == 1) {
			$oCheckbox->checked = "checked";
		}

		//Flexibilität ausgeschaltet, Checkbox auf readonly setzen, Sortierung sollte trotzdem noch möglich sein
		if($aData['flexibility'] === false){
			$oCheckbox->bReadOnly = true;
		}

		$oDivCheckbox->setElement($oHidden);
		$oDivCheckbox->setElement($oCheckbox);

		if($bGroups) {
			$oDiv->setElement($oDivGroupCheckbox);
			$oDiv->setElement($oDivGroupLabel);
		}

		$oDiv->setElement($oDivCheckbox);

		$oDiv->setElement($oDivLabel);

		if($sGroup) {
			$oDiv->style = 'background-color: '.self::getColors($this->aFlexColumnGroups[$sGroup]).';';
		}

		return $oDiv;
	}
	
	/**
	 *
	 * @param string $sTitle
	 * @param bool $bReadOnly
	 * @return Ext_Gui2_Dialog_Tab 
	 */
	public function createTab($sTitle, $bReadOnly = false) {
		$oTab = new Ext_Gui2_Dialog_Tab($sTitle, $bReadOnly);
		$oTab->setDialog($this);
		$oTab->oGui = $this->oGui;
		return $oTab;
	}

	/**
	 * Erzeugt eine TabArea in dem aktuellen Tab
	 * @return Ext_Gui2_Dialog_TabArea
	 */
	public function createTabArea() {
		$oTab = new Ext_Gui2_Dialog_TabArea($this->iTabCounter ?? 0);
		return $oTab;
	}

	/**
	 * Methode, um den Suffix für Namen/IDs eines SaveFields zu bekommen.
	 * 
	 * @param array $aOptions
	 * @return string Suffix
	 */
	public static function getSaveFieldSuffix(array $aOptions)
	{
		
		$sSuffix = '['.(string)($aOptions['db_column'] ?? '').']';

		if(!empty($aOptions['db_alias'])) {
			$sSuffix .= '['.(string)$aOptions['db_alias'].']';
			
			if(
				isset($aOptions['i18n']) &&
				$aOptions['i18n'] == 1
			) {
				$sSuffix .= '['.(string)$aOptions['i18n_language'].']';
			}
			
			if(!empty($aOptions['joined_object_key'])) {
				$iId = 0;
				if(isset ($aOptions['joined_object_key_id'])){
					$iId = $aOptions['joined_object_key_id'];
				}
				$sSuffix .= '['.$iId.']['.(string)$aOptions['joined_object_key'].']';
			}
		}

		return $sSuffix;
	}
	
	/**
	 * $aOptions = array();
	 * $aOptions['db_alias']
	 * $aOptions['db_column']
	 * $aOptions['required']
	 * $aOptions['value']
	 * $aOptions['class']
	 */
	public function createSaveField($sElement, $aOptions, $bSetId = true) {

		if(isset($aOptions['hide_weekday']) && $aOptions['hide_weekday'] != false) {
			$aOptions['hide_weekday'] = true;
		}
		
		$bSaveField = true;
		
		if(isset($aOptions['no_savedata']) && $aOptions['no_savedata'] === true){
			$bSaveField = false;
		}
		
		if($sElement == 'autocomplete') {
			$oElement = $this->create('input');
			$oElement->class = 'autocomplete-hidden';
			$oElement->type = 'hidden';
		} else if($sElement == 'calendar') {
			$oElement = $this->create('input');
			$oElement->class = 'txt form-control input-sm';
			$oElement->type = 'text';
			if(!empty($aOptions['date_period'])) {
				$oElement->setDataAttribute('period-from', $aOptions['date_period']['from']);
				$oElement->setDataAttribute('period-until', $aOptions['date_period']['until']);
			}			
		} else if($sElement == 'hidden') {
			$oElement = $this->create('input');
			$oElement->type = 'hidden';
		} else if($sElement == 'button') {
			$oElement = $this->create('button');
			$oElement->class = 'btn btn-gray';
			$bSaveField = false;
		} else if($sElement == 'upload') {
			$oElement = $this->create('input');
			$oElement->class = 'txt form-control input-sm';
			$oElement->type = 'file';
		} else if($sElement == 'checkbox') {
			$oElement = $this->create('input');
			$oElement->type = 'checkbox';
		} else {

			$oElement = $this->create($sElement);
			if(
				$sElement == 'input' ||
				$sElement == 'color'
			) {
				$oElement->type = 'text';
			} 
			
			if(
				$sElement == 'select' ||
				$sElement == 'input' ||
				$sElement == 'textarea' ||
				$sElement == 'color' ||
				$sElement == 'html'
			) {
				$oElement->class = 'txt form-control input-sm';
			} else {
				$oElement->class = 'txt';
			}
		}

		$sSuffix = self::getSaveFieldSuffix($aOptions);

		if(!empty($aOptions['multi_rows'])) {
			// Ext_Gui2_Dialog::createMultiRow()
			$sSuffix .= '[0]';
		}
		
		$sName 			= 'save'.$sSuffix;
		
		if(!empty($aOptions['field_id_suffix'])) {
			$sSuffix = str_replace('['.$aOptions['db_column'].']', '['.$aOptions['field_id_suffix'].']', $sSuffix);
		}
		
		$sId 			= 'saveid'.$sSuffix;
		$sIdCalendar 	= 'saveid[calendar]'.$sSuffix;
		$sIdAge			= 'saveid[age]'.$sSuffix;

		foreach((array)$aOptions as $sOption => $mOptionData) {

			switch ($sOption) {

				case 'fastselect':
					
					$oElement->multiple = 'multiple';
					$oElement->class = 'fastselect';
					$oElement->{'data-user-option-allowed'} = 'true';
					$oElement->{'data-url'} = $mOptionData['url'];
					$oElement->{'data-load-once'} = 'true';
					#$sName .= '[]';
					
					break;
				
				// Nur für html
				case 'advanced':
					if($sElement == 'html') {
						if($mOptionData === true) {
							$oElement->class = 'advanced';
						} elseif($mOptionData === 'filemanager') {
							$oElement->class = 'filemanager';
						}
					}
					break;

				// Wenn "name" übergeben, überschreibe den standart aufbau
				case 'name':
					if($mOptionData) {
						$sName = $mOptionData;
					}
					break;

				// Zusatz für "name"
				case 'name_suffix':
					if($mOptionData) {
						$sName .= $mOptionData;
					}
					break;

				// Wenn "id" übergeben, überschreibe den standart aufbau
				case 'id':
					$sId = $mOptionData;
					break;

				// Wenn "readonly" übergeben, ist Feld nicht editierbar
				case 'disabled':
				case 'readonly':
					if($mOptionData == 'readonly' || $mOptionData == 'disabled') {
						$oElement->bReadOnly = $mOptionData;
						$oElement->readonly = $mOptionData;
					}
					if($sOption == 'disabled' && $mOptionData === false){
						$oElement->bDisabledByReadonly = false;
					}
					break;

				// Feldvalidation falls angegeben
				case 'regex':
					if($mOptionData) {
						$this->aValidation[] = array('name' => $sName, 'regex' => $mOptionData);
					}
					break;

				// Feldvalidation falls angegeben
				case 'calendar_id':
					if($mOptionData) {
						$sIdCalendar = $mOptionData;
					}
					break;

				case 'multiple':
					if($mOptionData > 0) {
						$sName .= '[]';
						$oElement->multiple = 'multiple';
						$oElement->size = $mOptionData;
					}
					break;

				// CSS setzten
				case 'class':
					if($mOptionData) {
						$oElement->class = $mOptionData;
					}
					break;

				// Required setzten
				case 'required':
					if($mOptionData) {
						$oElement->class = 'required'; 
						//HTML 5 Wurde immer direkt beim öffnen ausgeführt, ansonsten cool :)
						//$oElement->required = "required";
					}
					break;

				// Fälle wo NICHTS passieren darf
				case 'input_div_addon':
				case 'input_div_elements':
				case 'value':
				case 'default_value':
				case 'row_style':
				case 'row_id': 
				case 'inputdiv_style':
				case 'labeldiv_style':
				case 'format':
				case 'jquery_multiple':
				case 'sortable':
				case 'searchable':
				case 'select_options':
				case 'select_options_data':
				case 'label':
				case 'upload':
				case 'db_column':
				case 'db_alias':
				case 'week_day':
				case 'hide_weekday':
				case 'display_age':
				case 'i18n_parent_column':
				case 'i18n_language':
				case 'i18n':
				case 'upload_path':
				case 'add_column_data_filename':
				case 'add_id_filename':
				case 'delete_old_file':
				case 'show_save_message':
				case 'no_path_check':
				case 'joined_object_key':
				case 'joined_object_min':
				case 'joined_object_max':
				case 'joined_object_no_confirm':
				case 'text_after':
					break;

				// Atribute direkt schreiben
				case 'style':
				default:

					if (str_starts_with($sOption, 'data-') && is_array($mOptionData)) {
						$mOptionData = implode(',', $mOptionData);
					}

					if(
						(
							is_numeric($mOptionData) ||
							is_string($mOptionData)
						) &&
						$mOptionData !== ''
					){
						$oElement->$sOption = $mOptionData;
					}
					break;
			}
		}

		// Wenn SELECT Values angegeben wurden
		if(
			$sElement == 'select' &&
			!empty($aOptions['select_options'])
		) {

			foreach($aOptions['select_options'] as $iKey => $mValue){
				$oOption = $this->create('option');
				if(
					// $iKey mit 0 == $aInputOptions['default_value'] mit null => true
					isset($aOptions['default_value']) &&
					$iKey == $aOptions['default_value'] &&
					!is_array($aOptions['default_value'])
				){
					// Select values
					$oOption->selected = 'selected';
				}elseif(
					isset($aOptions['default_value']) &&
					is_array($aOptions['default_value'])
				) {
					// Multiselect values
					foreach((array)$aOptions['default_value'] as $sValue){
						if($iKey == $sValue){
							$oOption->selected = 'selected';
							break;
						}
					}
				}

				$oOption->value = $iKey;
				$oOption->setElement((string)$mValue);

				if (
					!empty($aOptions['select_options_data']) &&
					!empty($aOptions['select_options_data'][$iKey])
				) {
					foreach ($aOptions['select_options_data'][$iKey] as $attributeName => $attributeValue) {
						$oOption->setDataAttribute($attributeName, (string)$attributeValue);
					}
				}
				$oElement->setElement($oOption);
			}
		}

		if(
			!array_key_exists($sId, $this->aUniqueFields) &&
			$bSaveField
		) {
			// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
			$aOptions['element'] = $sElement;
			#__uout($aOptions, 'friedrich2');
			$this->aSaveData[] = $aOptions;
			$this->aUniqueFields[$sId] = 1;
		}

		if($sElement == 'calendar') {
			$oElement->autocomplete = 'off';
			$oElement->class = 'calendar_input';
		}

		$oElement->name = $sName;

		if($bSetId){
			$oElement->id = $sId;
		}	

		// Default Value setzten
		if(
			(
				$sElement == 'checkbox' ||
				$sElement == 'input' ||
				$sElement == 'hidden' ||
				$sElement == 'color'
			)
			&&
			// TODO Ist empty() hier richtig, wenn man auch mal '0' übergeben möchte?
			!empty($aOptions['value'])
		){
			$oElement->value = $aOptions['value'];
		} else if(
			$sElement == 'calendar' &&
			!empty($aOptions['value'])
		){
			$oElement->value = $aOptions['value'];
		} else if(
			(
				$sElement == 'textarea' ||
				$sElement == 'html'  || 
				$sElement == 'button'
			) &&
			!empty($aOptions['value'])
		){
			$oElement->setElement($aOptions['value']);
		} else if(
			$sElement == 'select' &&
			!empty($aOptions['value'])
		){
			// Nichts machen aber erlauben, das passiert im JS automatisch
		} else if(!empty($aOptions['value'])) {
			throw new Exception("Sorry I do not know how I set the Default Value of the SaveField ".$sElement);
		}

		
		if(
			isset($aOptions['typeahead']) &&
			(int)$aOptions['typeahead'] == 1
		) {
			$oElement->class .= ' jTa';
		}

		if(
			isset($aOptions['jquery_multiple']) &&
			(int)$aOptions['jquery_multiple'] == 1
		) {
			$oElement->class .= ' jQm';

			// TODO: Eventuell per Regex checken
			if(strpos($oElement->style, 'width') === false) {
				$oElement->style .= ' width:600px;';
			}
				
		}
		if(
			isset($aOptions['sortable']) &&	
			(int)$aOptions['sortable'] == 1
		) {
			$oElement->class .= ' jQmsort';
		}
		if(
			isset($aOptions['searchable']) &&
			(int)$aOptions['searchable'] == 1
		) {
			$oElement->class .= ' jQmsearch';
		}
		
		if(isset($aOptions['max_length'])){
			$oElement->maxlength = $aOptions['max_length'];
		}
		
		$this->_modifyRow($sElement, $oElement, $aOptions, array(
			'id_calendar' => $sIdCalendar,
			'id_age' => $sIdAge
		));

		return $oElement;
	}

	/**
	 * Abstraktion aus der createRow, da createMultiRow das exakt selber braucht
	 * @param $sType
	 * @param $oElement
	 * @param $aOptions
	 * @param $aAdditional
	 */
	protected function _modifyRow($sType, &$oElement, $aOptions, $aAdditional) {

		if($sType === 'calendar') {

			if(
				!isset($aOptions['readonly']) ||
				$aOptions['readonly'] != 'readonly'
			) {

				$oDiv = $this->create('div');
				$oDiv->class = 'GUIDialogRowCalendarDiv input-group date';

				if(!empty($aOptions['calendar_row_style'])) {
					$oDiv->style .= ' '.$aOptions['calendar_row_style'];
				}

				if(!empty($aOptions['calendar_row_class'])) {
					$oDiv->class .= ' '.$aOptions['calendar_row_class'];
				}

				if(!isset($aOptions['hide_weekday']) || $aOptions['hide_weekday'] != true){
				} else {
					$oDiv->class .= ' calender_input_no_weekday';
				}
				
				$sIcon = '
					<div class="input-group-addon calendar_img">
						<i class="fa fa-calendar"></i>
					</div>';

				$oDiv->setElement($sIcon);
				
				$oDiv->setElement($oElement);

				$sDay = '';
				if(!isset($aOptions['hide_weekday']) || $aOptions['hide_weekday'] != true) {
					if(empty($aOptions['week_day'])) {
						$aOptions['week_day'] = '&nbsp;&nbsp;';
					}
					$sDay = '
					<div class="GUIDialogRowWeekdayDiv input-group-addon">
						'.$aOptions['week_day'].'
					</div>';
				}

				$oDiv->setElement($sDay);
				
				if(
					isset($aOptions['display_age']) &&
					$aOptions['display_age'] == 1
				) {
					$sAge = '<div class="input-group-addon GUIDialogRowAgeDiv" id="'.$aAdditional['id_age'].'"></div>';
					$oDiv->setElement($sAge);
					$oDiv->class = 'with_age';
				}

				$oElement = $oDiv;

			}

		}

	}

	public function create($sElement){

		$sElement = strtolower($sElement);

		switch ($sElement){

			case 'label':
				$oElement = new Ext_Gui2_Html_Label();
				break;

			case 'div':
				$oElement = new Ext_Gui2_Html_Div();
				break;

			case 'ul':
				$oElement = new Ext_Gui2_Html_Ul();
				break;

			case 'li':
				$oElement = new Ext_Gui2_Html_Li();
				break;

			case 'form':
				$oElement = new Ext_Gui2_Html_Form();
				break;

			case 'fieldset':
				$oElement = new Ext_Gui2_Html_Fieldset();
				break;

			case 'h1':
				$oElement = new Ext_Gui2_Html_H1();
				break;

			case 'h2':
				$oElement = new Ext_Gui2_Html_H2();
				break;

			case 'h3':
				$oElement = new Ext_Gui2_Html_H3();
				break;

			case 'h4':
				$oElement = new Ext_Gui2_Html_H4();
				break;

			case 'h5':
				$oElement = new Ext_Gui2_Html_H5();
				break;

			case 'input':
			case 'color':
				$oElement = new Ext_Gui2_Html_Input();
				break;

			case 'button':
				$oElement = new Ext_Gui2_Html_Button();
				break;

			// TODO Wird das noch benötigt nachdem das in createSaveField() ergänzt wurde?
			case 'checkbox':
				$oElement = new Ext_Gui2_Html_Input();
				$oElement->type = "checkbox";
				break;

			case 'textarea':
				$oElement = new Ext_Gui2_Html_Textarea();
				break;

			case 'html':
				$oElement = new Ext_Gui2_Html_Textarea();
				$oElement->class="GuiDialogHtmlEditor";
				break;

			case 'select':
				$oElement = new Ext_Gui2_Html_Select();
				break;

			case 'option':
				$oElement = new Ext_Gui2_Html_Option();
				break;

			case 'span':
				$oElement = new Ext_Gui2_Html_Span();
				break;

			case 'hr':
				$oElement = new Ext_Gui2_Html_Hr();
				break;

			case 'img':
			case 'image':
				$oElement = new Ext_Gui2_Html_Image();
				break;

			case 'text':
			default:
				$oElement = new Ext_Gui2_Html_Text();
				break;
		}

		return $oElement;

	}

	/**
	 * @param $oElement
	 * @throws Exception
	 */
	public function setElement($oElement) {
		
		if(
			$oElement != null &&
			$this->bSetElement
		) {

			if(!is_object($oElement)) {
				throw new Exception("Sorry, I need a Object as Element");
			} else {

				if($oElement instanceof Ext_Gui2) {

					$this->no_padding = 1;
					$this->no_scrolling = 1;
					$this->bSetElement = false;
					$this->save_button = false;

					$oElement->load_admin_header = 0;

					$sParentHash = $oElement->parent_hash;
					if(empty($sParentHash)) {
						$oElement->setParent($this->oGui);
					}
					$this->aElements = array($oElement);

				} elseif($oElement instanceof Ext_Gui2_Page) {
					$this->save_button = false;
					$this->aElements = array($oElement);
				} else {
					$this->aElements[] = $oElement;
				}

			}

		}

	}

	public function getElements() {
		return $this->aElements;
	}

	/**
	 * Input-Element (Objekt) mit Label (Objekt) im Dialog suchen
	 *
	 * @param string $sColumn
	 * @param string $sAlias
	 * @return Ext_Gui2_Html_Abstract[]|null
	 */
	public function searchInputAndLabelElement($sColumn, $sAlias) {

		$oLastLabel = null;
		$cSearch = function($oRootElement) use($sColumn, $sAlias, &$cSearch, &$oLastLabel) {
			/** @var Ext_Gui2_Dialog|Ext_Gui2_Dialog_Basic|Ext_Gui2_Html_Abstract $oRootElement */
			foreach($oRootElement->getElements() as $oElement) {

				// Duck-Typing: Alles überspringen, was aElements nicht hat
				if(!method_exists($oElement, 'getElements')) {
					continue;
				}

				if($oElement instanceof Ext_Gui2_Html_Label) {
					$oLastLabel = $oElement;
				}

				if($oElement->name === 'save['.$sColumn.']['.$sAlias.']') {
					return [$oElement, $oLastLabel];
				}

				$oTmpElement = $cSearch($oElement);
				if($oTmpElement !== null) {
					// break
					return $oTmpElement;
				}

			}

			return null;
		};

		return $cSearch($this);

	}

	public function generateAjaxData($aSelectedIds, $sHash) {

		$oAccess = Access::getInstance();
		
		if($this->oGui) {
			$this->oGui->getDataObject()->checkDialogAccess($this, $aSelectedIds);
		} else {
			// Wenn recht angegeben ist und das recht nicht vorhanden ist
			// nur READONLY
			// Es wird hier nicht der dialog gesperrt da dies über das ICON geschehen soll!
			if(
				$this->access != "" &&
				!$oAccess->hasRight($this->access)
			) {
				$this->bReadOnly = true;
			}
		}

		$aData = array();
		$aData['values'] = array();
		$aData['options'] = (array)$this->aOptions;

		if(empty($aSelectedIds)){
			$iRowId = 0;
			$aData['title'] = $this->aOptions['title_new'];
		} else {
			sort($aSelectedIds);
			$iRowId = implode('_', (array)$aSelectedIds);
			if(
				count($aSelectedIds) > 1 &&
				!empty($this->aOptions['title_multiple'])
			){
				$aData['title'] = $this->aOptions['title_multiple'];
			} else {
				$aData['title'] = $this->aOptions['title'];
			}
		}

		$sDialogId = $this->sDialogIDTag.$iRowId;


		if($this->width > 0){
			$aData['width']					= $this->width;
		}
		if($this->height > 0){
			$aData['height']				= $this->height;
		}

		$aData['tabs']					= array();
		$aData['id']					= $sDialogId;
		$aData['suffix']				= $this->sDialogIDTag;
		$aData['info_icons']			= (int) $this->bShowInfoIcons;
		
		$iTabCount = 0;

		$oAccess = Access::getInstance();
		
		foreach((array)$this->aElements as $iEKey => $oElement){

			if($oElement instanceof Ext_Gui2_Dialog_Tab) {
				// Recht prüfen
				if(
					$oElement->mAccess != "" &&
					!$oAccess->hasRight($oElement->mAccess)
				){
					unset($this->aElements[$iEKey]);
				} else {
					// Counter erhöhen
					$iTabCount++;
				}

			}
		}

		$iTab = 0;
		foreach((array)$this->aElements as $oElement) {

			// Counter für TabAreas zurücksetzen pro neuen Tab
			Ext_Gui2_Dialog_TabArea::$iTabCounter = 0;

			if($oElement instanceof Ext_Gui2_Dialog_Tab) {

				// Wenn der Dialog readonly ist, dann alle Elemente auch readonly setzen
				$oElement->bDefaultReadOnly = $oElement->bReadOnly;
				if($this->bReadOnly){
					$oElement->setModus('readonly');
				}

				$aData['tabs'][$iTab] = array();

				if(
					count($oElement->aElements) == 1 &&
					$oElement->aElements[0] &&
					$oElement->aElements[0] instanceof Ext_Gui2
				) {
					
					if($oElement->aElements[0]->canDisplay()) {
						
						ob_start();
						$oElement->aElements[0]->display(array(), true);
						$sHtml = ob_get_clean();

						$aGui2 = array();
						$aGui2['hash']			= $oElement->aElements[0]->hash;
						$aGui2['instance_hash']	= $oElement->aElements[0]->instance_hash;
						$aGui2['parent_hash']	= $oElement->aElements[0]->parent_hash;
						$aGui2['view']			= $oElement->aElements[0]->sView;
						$aGui2['class_js']		= $oElement->aElements[0]->class_js;
						$aData['tabs'][$iTab]['gui2'][] = $aGui2;

						$aData['tabs'][$iTab]['no_scrolling'] = 1;
						$aData['tabs'][$iTab]['no_padding'] = 1;
						
					} else {
						$oNotification = $this->createNotification(L10N::t('Achtung'), L10N::t('Sie haben keine Rechte für diese Liste!'), 'hint', [
							'dismissible' => false
						]);
						
						$sHtml = $oNotification->generateHtml();
					}

					$aData['tabs'][$iTab]['html'] = $sHtml;

				} elseif(
					count($oElement->aElements) == 1 &&
					$oElement->aElements[0] &&
					$oElement->aElements[0] instanceof Ext_Gui2_Page
				) {

					$sHtml = $oElement->aElements[0]->generateHTML();

					$aGuis = $oElement->aElements[0]->getElements();
					$bParent = true;
					foreach($aGuis as $oGui) {
						if($oGui instanceof Ext_Gui2) {
							$aGui2 = array();
							$aGui2['hash']			= $oGui->hash;
							$aGui2['instance_hash']	= $oGui->instance_hash;
							$aGui2['parent_hash']	= $oGui->parent_hash;
							$aGui2['view']			= $oGui->sView;
							$aGui2['class_js']		= $oGui->class_js;
							$aGui2['page_data']		= $oElement->aElements[0]->getPageData();
							$aGui2['page']			= 1;
							$aGui2['parent']		= (int)$bParent;
							$aData['tabs'][$iTab]['gui2'][] = $aGui2;
							$bParent = false;
						}
					}

					$aData['tabs'][$iTab]['no_scrolling'] = 1;
					$aData['tabs'][$iTab]['no_padding'] = 1;

					$aData['tabs'][$iTab]['html'] = $sHtml;

				} else {

					// ID des Tabs setzen
					$oElement->iId = $iTab;

					$aData['tabs'][$iTab]['no_scrolling']	= $oElement->no_scrolling;
					$aData['tabs'][$iTab]['no_padding']		= $oElement->no_padding;
					$sHtml									= $oElement->generateHTML();
					$sHtml									= str_replace('saveid[', 'save['.$sHash.']['.$sDialogId.'][', $sHtml);
					$aData['tabs'][$iTab]['html']			= $sHtml;

				}

				$aData['tabs'][$iTab]['readonly']		= $oElement->bReadOnly;
				$aData['tabs'][$iTab]['options']		= (array)$oElement->aOptions;
				$aData['tabs'][$iTab]['title']			= $oElement->sTitle;

				$aData['tabs'][$iTab]['js'] = '';

				if(
					isset($oElement->sJS) &&
					!empty($oElement->sJS)
				) {
					$aData['tabs'][$iTab]['js'] .= $oElement->sJS;
					$oElement->sJS = '';
				}

				// Readonly auf Default zurücksetzen
				$oElement->bReadOnly = $oElement->bDefaultReadOnly;

				$iTab++;

			} else if($oElement instanceof Ext_Gui2_Dialog_TabArea){

				$sHtml = $oElement->generateHTML();
				$sHtml = str_replace('saveid[', 'save['.$sHash.']['.$sDialogId.'][', $sHtml);
				$aData['html'] .= $sHtml;

			} else if($iTabCount == 0) {

				if($oElement instanceof Ext_Gui2) {
					ob_start();
					$oElement->display(array(), true);
					$sHtml = ob_get_clean();

					$aGui2 = array();
					$aGui2['hash']			= $oElement->hash;
					$aGui2['instance_hash']	= $oElement->instance_hash;
					$aGui2['parent_hash']	= $oElement->parent_hash;
					$aGui2['view']			= $oElement->sView;
					$aGui2['class_js']		= $oElement->class_js;
					$aData['gui2'][] = $aGui2;

					$aData['no_scrolling']	= 1;
					$aData['no_padding']	= 1;

				} elseif($oElement instanceof Ext_Gui2_Page) {

					$sHtml = $oElement->generateHTML();

					$aGuis = $oElement->getElements();
					$bParent = true;
					foreach($aGuis as $oGui) {
						if($oGui instanceof Ext_Gui2) {
							$aGui2 = array();
							$aGui2['hash']			= $oGui->hash;
							$aGui2['instance_hash']	= $oGui->instance_hash;
							$aGui2['parent_hash']	= $oGui->parent_hash;
							$aGui2['view']			= $oGui->sView;
							$aGui2['class_js']		= $oGui->class_js;
							$aGui2['page_data']		= $oElement->getPageData();
							$aGui2['page']			= 1;
							$aGui2['parent']		= (int)$bParent;
							$aData['gui2'][] = $aGui2;
							$bParent = false;
						}
					}

					$aData['no_scrolling']	= 1;
					$aData['no_padding']	= 1;

				} else {

					$sHtml = $oElement->generateHTML($this->bReadOnly);
					$sHtml = str_replace('saveid[', 'save['.$sHash.']['.$sDialogId.'][', $sHtml);

				}

				if(!isset($aData['html'])) {
					$aData['html'] = '';
				}
				
				$aData['html'] .= $sHtml;
				$aData['js'] = '';

				if(method_exists($oElement, 'generateJS')) {
					$aData['js'] = $oElement->generateJS();
				}

			}
		}

		if(!empty($this->js)) {
			if(!isset($aData['js'])) {
				$aData['js'] = '';
			}
			$aData['js'] .= $this->js;
		}
		
		foreach((array)$this->_aOptionalAjaxData as $sData => $mValue){
			$aData['optional'][$sData] = $mValue;
		}

		$aData['bSaveButton'] = false;
		if($this->save_button){
			$aData['bSaveButton'] = true;
		}

		$aData['bSaveAsNewButton'] = false;
		if($this->save_as_new_button){
			$aData['bSaveAsNewButton'] = true;
		}

		$aData['bSaveBarOptions'] = false;
		if($this->save_bar_options){
			// This array structure because: may be, that other array keys follow...
			$aData['bSaveBarOptions'] = array(
				array(
					'key' => 'save_bar_option_close'
				),
				array(
					'key' => 'save_bar_option_open'
				),
				array(
					'key' => 'save_bar_option_new'
				)
			);

			$aData['bSaveBarDefaultOption'] = $this->save_bar_default_option;
		}

		if(!empty($this->aAlertMessages)) {
			$aData['alert_messages'] = $this->aAlertMessages;
		}
		
		return $aData;

	}

	/*
	 * Funktion liefert ein Array aller Feld IDs und der Regex ausdrücke für die Validation
	 */
	public function getValidationInfo(){
		return $this->aValidation;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getEvents() {

		$aEvents = array();

		// Events von den Select Abhängigkeiten setzen
		$aDependencyFields = array();
		$aDependencyVisibilitys = array();
		$aFields = array();

		foreach((array)$this->aSaveData as $aField) {

			$sKey = self::getKey($aField['db_column'], $aField['db_alias']);

			if(!empty($aField['events'])) {
				$aEvents[$sKey] = (array)$aField['events'];
			}

			if(isset($aField['dependency'])) {
				$aField['dependency'] = (array)$aField['dependency'];
				foreach((array)$aField['dependency'] as $aItem) {
					$aDependencyFields[$aItem['db_column']."|".($aItem['db_alias'] ?? '')] = $aItem;
				}
			}

			if(isset($aField['dependency_visibility'])) {

				// TODO Selbst wenn man mehrere Optionen angeben könnte, wäre das JS zu blöd dafür, alle Bedingungen korrekt zu prüfen
//				if (\Illuminate\Support\Arr::isAssoc($aField['dependency_visibility'])) {
//					$aField['dependency_visibility'] = [$aField['dependency_visibility']];
//				}
				$aDependencyVisibility = $aField['dependency_visibility'];

//				foreach ($aField['dependency_visibility'] as $aDependencyVisibility) {
					if (empty($aDependencyVisibility['id'])) {
						$aDependencyVisibility['element'] = self::getJSKey($aField);
						$aDependencyVisibility['is_field'] = 1;
					} else {
						// Falls es mal kein Feld mit Standard-Namen ist
						// Achtung: Hier muss die Row angegeben werden, da is_field = 0 nicht auf Dialog-Row hochspringt
						$aDependencyVisibility['element'] = $aDependencyVisibility['id'];
						$aDependencyVisibility['is_field'] = 0;
					}
					$aDependencyVisibilitys[] = $aDependencyVisibility;
//				}

			}

			// keine abhängige Sichtbarkeit sondern Kindsichtbarkeiten
			// d.h das element blendet andere elemente ein/aus
			if(isset($aField['child_visibility'])) {

				$aTemp = $aField;

				// kinder durchgeehn
				foreach($aField['child_visibility'] as $aChild) {

					$sElement = '';
					$aTemp['is_field'] = 0;
					$aTemp['is_class'] = 0;
					
					// wenn column alias gegeben ist ist es ein element ansonsten über eine direkte ID gehen
					if(
						!empty($aChild['db_column']) ||
						!empty($aChild['db_alias'])
					) {
						$sElement = self::getJSKey($aChild);
						$aTemp['is_field'] = 1;
					} elseif(!empty($aChild['id'])) {
						$sElement = $aChild['id'];
					} elseif(!empty($aChild['class'])) {
						$sElement = $aChild['class'];
						$aTemp['is_class'] = 1;
						$aTemp['is_field'] = 1;
					}

					if(!empty($aChild['on_values'])) {
						$aTemp['on_values']	= $aChild['on_values'];
					}
					$aTemp['element'] = $sElement;

					$aDependencyVisibilitys[] = $aTemp;

				}

			}

			$aFields[$sKey] = $aField;

		}

		if(!empty($aDependencyFields)) {

			foreach((array)$aDependencyFields as $aDependencyField) {

				$sKey = self::getKey($aDependencyField['db_column'], ($aDependencyField['db_alias'] ?? null));
				
				// Wenn das Feld nicht im Dialog vorhanden ist, Event nicht setzen
				if(!isset($aFields[$sKey])) {
					continue;
				}

				$aField = $aFields[$sKey];
				// Event ist abhängig vom Typ des Feldes
				switch($aField['element']) {
					case 'input':
						$sEvent = 'keyup';
						break;
					case 'select':
					case 'checkbox':
					case 'autocomplete':
					case 'hidden':
						$sEvent = 'change';
						break;
					default:
						throw new Exception('Undefined element "'.$aField['element'].'"');
						break;
				}

				if(!isset($aEvents[$sKey])) {
					$aEvents[$sKey] = array();
				}

				$aEvents[$sKey][] = array(
					'event' => $sEvent,
					'function' => 'prepareUpdateSelectOptions'
				);

			}

		}

		if(!empty($aDependencyVisibilitys)) {

			foreach($aDependencyVisibilitys as $aDependencyVisibilityData){

				$sKey = self::getKey($aDependencyVisibilityData['db_column'], $aDependencyVisibilityData['db_alias']);
				
				// Wenn das Feld nicht im Dialog vorhanden ist, Event nicht setzen
				if(isset($aFields[$sKey])) {

					$sEvent = null;
					$aField = $aFields[$sKey];
					// Event ist abhängig vom Typ des Feldes
					switch($aField['element']) {
						case 'input':
							$sEvent = 'keyup';
							break;
						case 'hidden':
							break;
						case 'select':
						case 'autocomplete':
						case 'checkbox':
							$sEvent = 'change';
							break;
						default:
							throw new Exception('Undefined element "'.$aField['element'].'"');
					}

					if(!isset($aEvents[$sKey])) {
						$aEvents[$sKey] = array();
					}

					$sParam = '$(sId)';
					$sParam .= ','.json_encode($aDependencyVisibilityData['on_values']);
					$sParam .= ',"'.$aDependencyVisibilityData['element'].'"';

					if(empty($aDependencyVisibilityData['is_field'])) {
						$sParam .= ', 1';
					} else {
						$sParam .= ', 0';
					}

					if(!empty($aDependencyVisibilityData['is_class'])) {
						$sParam .= ', 1';
					} else {
						$sParam .= ', 0';
					}

					if($sEvent) {
						$aEvents[$sKey][] = array(
							'event' => $sEvent,
							'function' => 'prepareDependencyVisibility',
							'parameter' => $sParam
						);
					}
					$aEvents[$sKey][] = array(
						'event' => 'openDialog',
						'function' => 'prepareDependencyVisibility',
						'parameter' => $sParam
					);

				}

			}

		}
		
		return $aEvents;
	}

	/**
	 * @param string $sData
	 * @param mixed $mValue
	 */
	public function setOptionalAjaxData($sData, $mValue){
		 $this->_aOptionalAjaxData[$sData] = $mValue;
	}

	/**
	 * @param string $sColumn
	 * @param string $sAlias
	 * @return string
	 */
	public static function getKey($sColumn, $sAlias) {
		return Ext_Gui2_Data::setFieldIdentifier($sColumn, $sAlias);
	}

	/**
	 * Generiert den Column Alias Key für JS
	 *
	 * @param string $aField
	 * @return string
	 */
	public static function getJSKey($aField) {

		$sKey = $aField['db_column'];
		if(!empty($aField['db_alias'])) {
			$sKey .= ']['.$aField['db_alias'];
		}

		// Wenn es sich um ein Sprachabhängiges Feld handelt
		// muss der Key um das Sprachkürzel erweitert werden
		if(isset($aField['i18n'])) {
			$sKey .= ']['.$aField['i18n_language'];
		}
		
		// Wenn es sich um wiederholbare Bereiche handelt muss der Key erweitert werden
		if(!empty($aField['joined_object_key'])) {
			// TODO Erweitern für Wiederholbare Bereiche die MEHR als 1mal wiederholbar sind! (1 ersetzen)
			$sKey .= '][{joined_object_container_key}]['.$aField['joined_object_key'];
		}

		return $sKey;
	}

	protected static function getColors($iIndex=null) {

		$aColors = [];
		$aColors[] = '#bbdefb';
		$aColors[] = '#b3e5fc';
		$aColors[] = '#b2ebf2';
		$aColors[] = '#b2dfdb';
		$aColors[] = '#c8e6c9';
		$aColors[] = '#dcedc8';
		$aColors[] = '#f0f4c3';
		$aColors[] = '#fff9c4';
		$aColors[] = '#ffecb3';
		$aColors[] = '#ffe0b2';
		$aColors[] = '#ffccbc';
		$aColors[] = '#d7ccc8';
		$aColors[] = '#f5f5f5';
		$aColors[] = '#cfd8dc';
		$aColors[] = '#ffcdd2';
		$aColors[] = '#f8bbd0';
		$aColors[] = '#e1bee7';
		$aColors[] = '#d1c4e9';
		$aColors[] = '#c5cae9';
		
		if($iIndex !== null) {
			return $aColors[$iIndex];
		} else {
			return $aColors;
		}

	}

	public function createGuiIcon($sClass, $sTitle){
		$oDivMain = $this->create('div');
		$oDivMain->class = 'divToolbar';
		$oDivElement = $this->create('div');
		$oDivElement->class = 'guiBarElement guiBarLink';
		$oDivIcon = $this->create('div');
		$oDivIcon->class = 'divToolbarIcon';
		$oIcon = $this->create('div');
		$oIcon->class = $sClass;
		$oDivIcon->setElement($oIcon);
		$oDivLabel = $this->create('div');
		$oDivLabel->class = 'divToolbarLabel';
		$oDivLabel->setElement($sTitle);
		$oDivElement->setElement($oDivIcon);
		$oDivElement->setElement($oDivLabel);
		$oDivMain->setElement($oDivElement);

		return $oDivMain;
	}

	/**
	 * @return Ext_Gui2_Dialog_Data
	 */
	public function getDataObject() {

		if(!($this->_oDataObject instanceof Ext_Gui2_Dialog_Data)) {
			$this->_oDataObject = new Ext_Gui2_Dialog_Data;
		}

		$this->_oDataObject->setDialogObject($this);
		$this->_oDataObject->setGuiObject($this->oGui);

		return $this->_oDataObject;
	}

	public function issetDataObject() {

		if($this->_oDataObject instanceof Ext_Gui2_Dialog_Data) {
			return true;
		}

		return false;

	}

	public function setDataObject($sDataObjectName) {

		$this->_oDataObject = new $sDataObjectName();

		$bIssetDataObject = $this->issetDataObject();

		return $bIssetDataObject;

	}

	/**
	 * 
	 */
	public function createSubheading($sHeadline) {

		$oH3 = $this->create('h4');

		$oH3->setElement($sHeadline);
		
		return $oH3;
	}

	protected function getLabelDiv($aInputOptions, $oDivInput=null) {
		
		$oDivLabel = $this->create('label');
		
		$sLabelClass = 'GUIDialogRowLabelDiv control-label';
		
		if($this->bSmallLabels) {
			$sLabelClass .= ' GUIDialogRowLabelDivSmall col-sm-2';
			if($oDivInput !== null) {
				$oDivInput->class	= 'GUIDialogRowInputDiv col-sm-10';
			}
		} elseif($this->bBigLabels) {
			$sLabelClass .= ' GUIDialogRowLabelDivBig col-sm-4';
			if($oDivInput !== null) {
				$oDivInput->class	= 'GUIDialogRowInputDiv col-sm-8';
			}
		} else {
			$sLabelClass .= ' col-sm-3';
			if($oDivInput !== null) {
				$oDivInput->class	= 'GUIDialogRowInputDiv col-sm-9';
			}
		}

		if(
			isset($aInputOptions['required']) &&
			(
				(int)$aInputOptions['required'] === 1 ||
				$aInputOptions['required'] === true
			)
		) {
			$sLabelClass .= ' required';
		}
		
		$oDivLabel->class = $sLabelClass;
		
		return $oDivLabel;
	}


	/**
	 * Gibt ein Div zurück mit den Standart formatierungen einer zeile
	 *
	 * @param $sLabel
	 * @param string $sInputType
	 * @param array $aInputOptions
	 * @return Ext_Gui2_Html_Div|Ext_Gui2_Html_Fieldset|Ext_Gui2_Html_Form|Ext_Gui2_Html_H1|Ext_Gui2_Html_Label|Ext_Gui2_Html_Li|Ext_Gui2_Html_Ul
	 */
	public function createRow($sLabel, $sInputType = 'input', $aInputOptions = array()){
		
		$oDiv			= $this->create('div');
		$oDiv->class	= 'GUIDialogRow form-group form-group-sm';

		$oDiv->setDataAttribute('row-key', $this->generateUniqueRowKey($aInputOptions));

		if(!empty($aInputOptions['row_class'])) {
			$oDiv->class = $aInputOptions['row_class'];
			unset($aInputOptions['row_class']);
		}

		if(!empty($aInputOptions['row_style'])) {
			$oDiv->style = $aInputOptions['row_style'];
		}
		
		if(!empty($aInputOptions['row_id'])) {
			$oDiv->id = $aInputOptions['row_id'];
		}

		$oDivInput = $this->create('div');
		
		$oDivLabel = $this->getLabelDiv($aInputOptions, $oDivInput);
		
		$aInputOptions['label'] = $sLabel;

		$this->manipulateRowLabel($oDivLabel, $aInputOptions);
		
		$oDivLabel->setElement($sLabel);

		if(!empty($aInputOptions['inputdiv_style'])) {
			$oDivInput->style	= $aInputOptions['inputdiv_style'];
		}
		if(!empty($aInputOptions['inputdiv_class'])) {
			$oDivInput->class = $aInputOptions['inputdiv_class'];
		}
		if(!empty($aInputOptions['labeldiv_style'])) {
			$oDivLabel->style	= $aInputOptions['labeldiv_style'];
		}

		$aInputOptions['label'] = $sLabel;

		if(is_object($sInputType)){
			$oInput = $sInputType;
		} else {
			$oInput = $this->createSaveField($sInputType, $aInputOptions);
		}

		if(
			!empty($aInputOptions['default_value']) &&
			(
				$sInputType == 'input' ||
				$sInputType == 'calendar' ||
				$sInputType == 'color'
			)
		) {
			$oInput->value = $aInputOptions['default_value'];
		} elseif(
			!empty($aInputOptions['default_value']) &&
			(
				$sInputType == 'textarea' ||
				$sInputType == 'html'
			)
		) {
			
			$oInput->setElement($aInputOptions['default_value']);

		} else if( $sInputType == 'autocomplete') {

			$oInputGroup = new Ext_Gui2_Html_Div();
			$oInputGroup->class = 'input-group';
			$oInputGroup->style = 'width: 100%;';
			
			$oInput2 = new Ext_Gui2_Html_Input();
			$oInput2->type = 'text';
			$oInput2->class = 'txt form-control';
			$sId = 'autocomplete_input_'.$aInputOptions['db_column'];
			if(!empty($aInputOptions['db_alias'])) {
				$sId .= '_'.$aInputOptions['db_alias'];
			}
			$oInput2->id = $sId;
			if((int)$aInputOptions['required'] == 1){
				$oInput2->class .= ' required';
			}
			if($aInputOptions['readonly'] === true){
				$oInput2->class .= ' readonly';
				$oInput2->bReadOnly = true;
			}
			$oInputGroup->setElement($oInput2);

			// Ladebalken
			$oLoader = new Ext_Gui2_Html_Span();
			$oLoader->id = 'autocomplete_loader_'.$aInputOptions['db_column'];
			$oLoader->class = 'input-group-addon';
			$oLoader->style = 'display: none;';
			$oLoader->setElement('<i class="fa fa-circle-o-notch fa-spin"></i>');
			$oInputGroup->setElement($oLoader);

			$oDivInput->setElement($oInputGroup);

			$oOptions = new Ext_Gui2_Html_Div();
			$oOptions->class = 'autocomplete';
			$sId = 'autocomplete_options_'.$aInputOptions['db_column'];
			if(!empty($aInputOptions['db_alias'])) {
				$sId .= '_'.$aInputOptions['db_alias'];
			}
			$oOptions->id = $sId;
			$oDivInput->setElement($oOptions);

		} else if( $sInputType == 'checkbox') {

			// Wenn kein Wert übergeben wurde
			if(empty($aInputOptions['value'])) {
				$oInput->value = 1;
			}
		
			if(!empty($aInputOptions['default_value'])) {
				$oInput->checked = 'checked';
			}
		
			if(
				!isset($aInputOptions['create_hidden']) ||
				$aInputOptions['create_hidden'] === true
			){
				
				$oInput2 = $this->_createHiddenField($oInput->value, $aInputOptions);
				$oDivInput->setElement($oInput2);
			}
			
		} else if($sInputType == 'upload') {

			$oUploadsDiv = $this->create('div');
			$oUploadsDiv->class = 'gui2_uploads';

			$oUploadDiv = $this->create('div');
			$oUploadDiv->class = 'gui2_upload input-group';

			$oUploadDiv->setElement($oInput);

			if($aInputOptions['multiple'] == 1) {

				$oUploadDiv->setDataAttribute('multiple', 1);

				$oBtnGroup = $this->create('div');
				$oBtnGroup->class = 'input-group-btn';

				$oDivDeleteButton = $this->create('div');
				$oDivDeleteButton->class = 'delete_file btn fa fa-minus-circle input-group-addon';
				$oDivDeleteButton->setDataAttribute('action', 'delete');
				$oBtnGroup->setElement($oDivDeleteButton);

				$oDivAddButton = $this->create('div');
				$oDivAddButton->class = 'add_file btn fa fa-plus-circle input-group-addon';
				$oDivAddButton->setDataAttribute('action', 'add');
				$oBtnGroup->setElement($oDivAddButton);

				$oUploadDiv->setElement($oBtnGroup);

			}

			$oUploadsDiv->setElement($oUploadDiv);

			if($aInputOptions['show_save_message'] == 1) {
				$oUploadsDiv->setDataAttribute('show-save-message', 1);
			}

			$oDivInput->setElement($oUploadsDiv);

		}

		if(
			isset($aInputOptions['selection_gui']) &&
			isset($aInputOptions['selection_settings']) &&
			$aInputOptions['selection_gui'] instanceof Ext_Gui2
		) {

			$sAdditional = $aInputOptions['db_column'];
			if(!empty($aInputOptions['db_alias'])) {
				$sAdditional .= '-'.$aInputOptions['db_alias'];
			}

			if(isset($aInputOptions['selection_settings']['dialog_title'])) {
				$sAddButtonDialogTitle = $aInputOptions['selection_settings']['dialog_title'];
			} else {
				$sAddButtonDialogTitle = $this->oGui->t('Option anlegen');
			}

			$oDialog = $this->oGui->createDialog($sAddButtonDialogTitle, $sAddButtonDialogTitle);

			$oDialog->sDialogIDTag = 'SELECTION_GUI_';
			$oDialog->save_button = false;

			$oDialog->width = $aInputOptions['selection_settings']['dialog_width'];
			$oDialog->height = $aInputOptions['selection_settings']['dialog_height'];

			$oSelectionGui = $aInputOptions['selection_gui'];

			$oDialog->setElement($oSelectionGui);

			//Die parent_gui & parent_hash werden in der setElement befüllt, falls das Element eine GUI ist, das passt
			//aber in dem speziellen Fall nicht, da die selection_gui definitiv kein child sondern eher ein parent ist
			$oSelectionGui->parent_gui		= array();
			$oSelectionGui->parent_hash		= '';

			$this->oGui->addDialog($oDialog, 'selection_gui', $sAdditional);

			$oAddButton = new Ext_Gui2_Html_Button();
			$oAddButton->onclick="return false;";
			$oAddButton->class = 'btn btn-default btn-sm inputDivAddonIcon';
			$oAddButton->setElement('<i class="fa fa-plus"></i>');
			$oAddButton->id = 'selection_gui_button_'.$oInput->id;

			// Achtung: Vorhandene Addons werden überschrieben!
			$aInputOptions['input_div_addon'] = $oAddButton;

		} elseif($sInputType == 'color') {

			$oColorPicker = new Ext_Gui2_Html_I();

			$aInputOptions['input_div_addon'] = $oColorPicker;
		}
		
		if($sInputType !== 'upload') {

			if(
				isset($aInputOptions['input_div_addon']) ||
				isset($aInputOptions['input_div_addon_start'])
			) {
				$oInput = $this->addInputAddon($oInput, $sInputType, $aInputOptions);
			}
		
			$oDivInput->setElement($oInput);
		}

		$oDiv->setElement($oDivLabel);

		if(!empty($aInputOptions['input_div_elements'])) {
			foreach($aInputOptions['input_div_elements'] as $mElement) {
				$oDivInput->setElement($mElement);
			}
		}

		$oDiv->setElement($oDivInput);

		return $oDiv;

	}
	
	protected function addInputAddon($oDivInput, $sInputType, $aInputOptions) {
		
		$oInputGroup = new Ext_Gui2_Html_Div();

		if($sInputType == 'color') {
			$oInputGroup->class = 'input-group color';
		} else {
			$oInputGroup->class = 'input-group';
		}
		
		if(isset($aInputOptions['input_div_addon_style'])) {
			$oInputGroup->style = $aInputOptions['input_div_addon_style'];
		}
		
		if(isset($aInputOptions['input_div_addon_start'])) {
			
			if($aInputOptions['input_div_addon_start'] instanceof Ext_Gui2_Html_Button) {
				$oInputGroupSpan = new Ext_Gui2_Html_Div();
				$oInputGroupSpan->class = 'input-group-btn';
			} else {
				$oInputGroupSpan = new Ext_Gui2_Html_Span();
				$oInputGroupSpan->class = 'input-group-addon';
			}

			$oInputGroupSpan->setElement($aInputOptions['input_div_addon_start']);
			$oInputGroup->setElement($oInputGroupSpan);
			
		}
		
		if($oDivInput instanceof Ext_Gui2_Html_Div) {
			
			$aInputElements = $oDivInput->getElements();
			$oDivInput->clearElements();
			foreach($aInputElements as $oElement) {
				$oInputGroup->setElement($oElement);
			}
			
		} else {
			
			$oInputGroup->setElement($oDivInput);
			
		}

		if(isset($aInputOptions['input_div_addon'])) {
			
			if($aInputOptions['input_div_addon'] instanceof Ext_Gui2_Html_Button) {
				$oInputGroupSpan = new Ext_Gui2_Html_Div();
				$oInputGroupSpan->class = 'input-group-btn';
			} else {
				$oInputGroupSpan = new Ext_Gui2_Html_Span();
				$oInputGroupSpan->class = 'input-group-addon';
			}

			$oInputGroupSpan->setElement($aInputOptions['input_div_addon']);
			$oInputGroup->setElement($oInputGroupSpan);
			
		}

		return $oInputGroup;
	}

	protected function manipulateRowLabel($oLabelDiv, $aRowOptions) {
		
		if(
			$this->bShowInfoIcons &&
			\Core\Handler\SessionHandler::getInstance()->get('system_infotexts_mode') === true &&
			!empty($this->generateUniqueRowKey($aRowOptions))
		) {
			$oInfoIcon = new \Ext_Gui2_Html_I();
			$oInfoIcon->class = 'fa fa-info-circle gui-info-icon editable inactive prototypejs-is-dead';
			$oLabelDiv->setElement($oInfoIcon);
		}	
		
	}
	
	protected function generateUniqueRowKey(array $aInputOptions) {

		if( 
			!($this->oGui instanceof \Ext_Gui2) ||
			(
				isset($aInputOptions['info_icon']) &&
				$aInputOptions['info_icon'] === false
			)
		) {
			return '';
		}

		if(isset($aInputOptions['info_icon_key'])) {
			$sField = $aInputOptions['info_icon_key'];
		} else {
			$sField = $aInputOptions['db_column'] ?? $aInputOptions['db_column_prefix'] ?? '';
			if(!empty($aInputOptions['db_alias'])) {
				$sField .= '_'.$aInputOptions['db_alias'];
			}
		}

		if(empty($sField)) {
			return '';
		}

		return Gui2\Service\InfoIcon\Hashing::encode($this->oGui, $this->sDialogIDTag, $sField);
	}
	
	/**
	 * Erstellt ein Hidden-Feld
	 * z.B. für Checkboxen
	 * 
	 * @param scalar $mValue
	 * @param array $aInputOptions
	 * @return object 
	 */
	protected function _createHiddenField($mValue, $aInputOptions) {

		// Hidden Field erzeuegen mit default 0
		$aHiddenInputOptions = $aInputOptions;
		unset($aHiddenInputOptions['value']);
		$oInput2 = $this->createSaveField('input', $aHiddenInputOptions, false);
		$oInput2->type = 'hidden';
		if(is_numeric($mValue)) {
			$oInput2->value = 0;
		} else {
			$oInput2->value = '';
		}

		return $oInput2;

	}

	/**
	 * Div generieren mit mehreren Inputs
	 *
	 * @TODO Mit AdminLTE und grid funktionieren Text-Optionen nicht mehr richtig
	 *
	 * @TODO: Hier sollte einfach createRow() aufgerufen werden und dann mit getElements() das entsprechene Input geholt werden.
	 * Das würde diese ganze Redundanz hier ersparen…
	 *
	 * @TODO Die ganzen gesetzten Optionen pro Feld gehen als Attribute ins HTML mit rein
	 *
	 * @param string $sLabel
	 * @param array $aData
	 * @return Ext_Gui2_Html_Div
	 * @throws Exception
	 */
	public function createMultiRow($sLabel, $aData) {

		$sAlias = $aData['db_alias'] ?? '';
		$bStoreFields	= true;

		// ob felder gespeichert werden wenn nicht muss die save abgel. werden
		if(isset($aData['store_fields'])) {
			$bStoreFields = (bool)$aData['store_fields'];
		}

		$sAliasPart = '';
		if(!empty($sAlias)) {
			$sAliasPart = '['.(string)$sAlias.']';
		}

		if(!isset($aData['items'])) {
			$aData['items'] = array();
		}

		// Wenn die MultiRow Daten von einem JoinedObject hat, dann direkt setzen
		// Felder ein und derselben MultiRow können eh keine unterschiedliche Informationen zum JoinedObject haben
		$bMultiJoinedObjectData = false;
		if(!empty($aData['joined_object_key'])) {
			$bMultiJoinedObjectData = true;
		}

		$aItems = $aData['items'];

		$oDiv				= new Ext_Gui2_Html_Div();
		$oDiv->class		= 'GUIDialogRow GUIDialogMultiRow form-group form-group-sm form-inline';

		if(!empty($aData['row_class'])) {
			$oDiv->class = $aData['row_class'];
			unset($aData['row_class']);
		}
		
		if(!empty($aData['row_style'])) {
			$oDiv->style = $aData['row_style'];
		}

		$aFirstItem = null;
		if(!empty($aItems)) {
			$aFirstItem = $aItems[0];
			if(!isset($aFirstItem['db_alias']) && isset($aData['db_alias'])) {
				$aFirstItem['db_alias'] = $aData['db_alias'];
			}

			$oDiv->setDataAttribute('row-key', $this->generateUniqueRowKey($aFirstItem));
		}

		$oDivInputContainer = null;
		
		$bGrid = false;
		
		if($this->bSmallLabels) {
			$sInputContainerGridClass = 'col-sm-10';
		} elseif($this->bBigLabels) {
			$sInputContainerGridClass = 'col-sm-8';
		} else {
			$sInputContainerGridClass = 'col-sm-9';
		}
		
		if($sLabel === null) {
			$sInputContainerGridClass = 'col-sm-12';
		}
		
		if(
			isset($aData['input_container']) &&
			$aData['input_container'] === true
		) {
			$oDivInput = new Ext_Gui2_Html_Div();
			$oDivInput->class = 'GUIDialogRowInputDivContainer c0';
			$oDivInputContainer = new Ext_Gui2_Html_Div();
			$oDivInputContainer->class = 'GUIDialogRowInputDiv '.$sInputContainerGridClass;	
			$oDivInputContainer->setElement($oDivInput);
		} elseif(
			isset($aData['grid']) &&
			$aData['grid'] === true
		) {

			$oContainer = new Ext_Gui2_Html_Div();
			$oContainer->class = 'GUIDialogRowInputDiv '.$sInputContainerGridClass;
			
			$oRow = new Ext_Gui2_Html_Div();
			$oRow->class = 'grid-row';

			$oContainer->setElement($oRow);
			
			$iDefaultCols = floor(12/count($aItems));
			$bGrid = true;

		} else {
			$oDivInput = new Ext_Gui2_Html_Div();
			$oDivInput->class = 'GUIDialogRowInputDiv '.$sInputContainerGridClass;
		}

		$sClass = '';
		foreach($aItems as $aItem) {
			if(
				isset($aItem['required']) &&
				$aItem['required']
			) {
				$sClass = 'required';
			}
		}

		if($sLabel !== null) {
			$oDivLabel = $this->getLabelDiv($aData);
			$oDivLabel->class = 'GUIDialogRowLabelDiv control-label '.$sClass;

			if(is_array($aFirstItem)) {
				$this->manipulateRowLabel($oDivLabel, $aFirstItem);
			}

			$oDivLabel->setElement($sLabel);
		}

		if(!empty($aItems)) {

			foreach($aItems as $aItem) {

				if($bGrid === true) {
					if(empty($aItem['grid_cols'])) {
						$aItem['grid_cols'] = $iDefaultCols;
					}
					$oDivInput = new Ext_Gui2_Html_Div();
					$oDivInput->class = 'col-md-'.$aItem['grid_cols'];
				}

				if(isset($aItem['label'])) {
					$oDivInput->setElement($aItem['label']);
				}

				if($bMultiJoinedObjectData) {
					$aItem['joined_object_key'] = $aData['joined_object_key'];
					$aItem['joined_object_min'] = $aData['joined_object_min'];
					$aItem['joined_object_max'] = $aData['joined_object_max'];
					$aItem['joined_object_no_confirm'] = $aData['joined_object_no_confirm'];
				}

				$aItem['db_alias'] = $sAlias;

				if(
					!isset($aItem['name']) &&
					!empty($aData['multi_rows'])
				) {
					$aItem['multi_rows'] = true;
				}

				$aSaveDataOptions = $aItem;

				$aSaveDataOptions['db_alias']	= $sAlias;
				$aSaveDataOptions['label']		= $sLabel;

				$sInput = $aItem['input'];

				// Kalender gesondert behandeln, da Untermenge von input
				if(
					$sInput === 'calendar' ||
					$sInput === 'checkbox' ||
					$sInput === 'hidden'
				) {
					$sInputClass = 'Ext_Gui2_Html_Input';
				} else {
					$sInputClass = 'Ext_Gui2_Html_'.ucfirst($sInput);
				}

				if(class_exists($sInputClass)) {

					// TODO Funktioniert mit grid nicht mehr
					if(!empty($aItem['text_before'])) {
						$oDivInput->setElement($aItem['text_before'].'&nbsp;');
					}

					if(
						(
							$sInput == 'checkbox' &&
							!isset($aItem['create_hidden'])
						) || 
						($aItem['create_hidden'] ?? null) === true
					) {
						$oInput2 = $this->_createHiddenField($aItem['value'], $aItem);
						$oDivInput->setElement($oInput2);
					}
					
					$aOptions = array_filter($aItem, function($key) {
						if(
							$key == 'text_before' ||
							$key == 'text_after' ||
							$key == 'create_hidden' ||
							$key == 'grid_cols' ||
							$key == 'input_div_addon' ||
							$key == 'input_div_addon_start' ||
							$key == 'element_class'
						) {
							return false;
						}
						return true;
					}, ARRAY_FILTER_USE_KEY);
					
					$oInput = $this->createSaveField($sInput, $aOptions);
					
					if(
						isset($aItem['input_div_addon']) ||
						isset($aItem['input_div_addon_start'])
					) {
						$oInput = $this->addInputAddon($oInput, $sInput, $aItem);
					}
						
					if(!empty($aItem['element_class'])) {
						$oInput->class = $aItem['element_class'];
					}
					
					$oDivInput->setElement($oInput);
					
					// TODO Funktioniert mit grid nicht mehr
					if(!empty($aItem['text_after'])) {
						if(
							isset($aItem['text_after_spaces']) && 
							$aItem['text_after_spaces'] === false
						) {
							$sTextBetween = $aItem['text_after'];
						} else {
							$sTextBetween = '&nbsp;'.$aItem['text_after'].'&nbsp;';
						}
						$oDivInput->setElement($sTextBetween);
					}

//					if(
//						!array_key_exists($sId, $this->aUniqueFields) &&
//						$bStoreFields
//					) {
//						// Speichere die Informationen falls noch nicht vorhanden, um später alle speicherfeld daten zu haben
//						$this->aSaveData[] = $aSaveDataOptions;
//						$this->aUniqueFields[$sId] = 1;
//					}

				} elseif($sInput === 'upload') {
					/** @var Ext_Gui2_Dialog_Upload $oUpload */
					$oUpload = $aItem['upload'];
					$oUpload->bRenderOnlyUploadField = true;
					$oDivInput->setElement($oUpload);
				} else {
					throw new Exception('Invalid input (or input not set)!');
				}

				if($bGrid === true) {
					$oRow->setElement($oDivInput);
				}
				
			}
		}

		if($oDivLabel) {
			$oDiv->setElement($oDivLabel);
		}

		if(
			isset($aData['multi_rows']) &&
			$aData['multi_rows'] === true
		) {

			$oAddImg = new Ext_Gui2_Html_I();
			$oAddImg->class = 'btn btn-gray btn_add fa '.Ext_Gui2_Util::getIcon('add');
			$oAddImg->title = $this->oGui->t('Hinzufügen');
			$oDivInput->setElement($oAddImg);

			$oDelImg = new Ext_Gui2_Html_I();
			$oDelImg->class = 'btn btn-gray btn_delete first-parent-not-visible fa '.Ext_Gui2_Util::getIcon('delete');
			$oDelImg->title = $this->oGui->t('Löschen');
			$oDivInput->setElement($oDelImg);

		}

		if($oDivInputContainer !== null) {
			$oDiv->setElement($oDivInputContainer);
		} elseif($bGrid === true) {
			$oDiv->setElement($oContainer);
		} else {
			$oDiv->setElement($oDivInput);
		}

		return $oDiv;
	}
	
	/**
	 *
	 * @param string $sJoinKey
	 * @return Ext_Gui2_Dialog_JoinedObjectContainer
	 */
	public function getJoinedObjectContainer($sJoinedObjectKey)
	{
		if(isset($this->_aJoinedObjectContainer[$sJoinedObjectKey]))
		{
			return $this->_aJoinedObjectContainer[$sJoinedObjectKey];
		}
		else
		{
			throw new Exception('Joined Object Container "' . $sJoinedObjectKey . '" not found!');
		}
	}

	/**
	 * $this->aSaveData durchsuchen nach einem Save-Field anhand DB-Column und DB-Alias
	 *
	 * @param string $sDbColumn
	 * @param string $sDbAlias
	 * @return array|null
	 */
	public function searchSaveDataField($sDbColumn, $sDbAlias = null) {

		foreach($this->aSaveData as $aSaveField) {
			if(
				$sDbColumn === $aSaveField['db_column'] && (
					empty($sDbAlias) ||
					$sDbAlias === $aSaveField['db_alias']
				)
			) {
				return $aSaveField;
			}
		}

		return null;

	}
	
}
