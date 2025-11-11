<?php

/**
 * @property int $id
 * @property string $name
 * @property string $usage
 * @property int $use_default_template
 * @property string $code
 * @property string $key
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property string $last_use (TIMESTAMP)
 * @property string $field_mode (ENUM)
 */
class Ext_TC_Frontend_Template extends Ext_TC_Frontend_CombinationTemplate
{
	// Tabellenname
	protected $_sTable		= 'tc_frontend_templates';
	protected $_sTableAlias	= 'tc_ft';

	protected static $aTemplateContentCache = array();

	protected $_aFormat = array(
		'name' => array(
			'required'	=> true
		),
		'usage' => array(
			'required'	=> true
		),
//		'code' => array(
//			'required' => true
//		),
//		'key' => array(
//			'required' => true,
//			'not_changeable' => true
//		),
	);

	protected $_aJoinTables = array(
		'errormessages' => array(
			'table' => 'tc_frontend_templates_messages',
			'foreign_key_field' => array('type', 'message'),
			'primary_key_field' => 'template_id'
		),
		'css' => array(
			'table' => 'tc_frontend_templates_css',
			'foreign_key_field' => array('type', 'class'),
			'primary_key_field' => 'template_id'
		),
		'templates' => array(
			'table' => 'tc_frontend_templates_templates',
			'foreign_key_field' => array('type', 'template'),
			'primary_key_field' => 'template_id'
		)
	);

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent',
	 *			'check_active'=>true,
	 *			'orderby'=>position,
	 *			'orderby_type'=>ASC
	 *			'query' => false,
	 *			'cloneable' => true,
	 *			'on_delete' => 'cascade' / '' ( nur bei "childs" möglich )
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'fields' => array(
			'class'=>'Ext_TC_Frontend_Template_Field',
				'key'=>'template_id',
				'type'=>'child',
				'check_active'=>true,
				'query' => false,
				'cloneable' => true,
				'on_delete' => 'cascade'
		)	
	);
	
	/**
	 * @inheritdoc
	 */
	public function __get($sName) {

		if(
			mb_strpos($sName, 'custom_errormessage') !== false ||
			mb_strpos($sName, 'custom_css') !== false ||
			mb_strpos($sName, 'custom_template') !== false
		) {

			$aTypes = explode('_', $sName, 3);
			$sType = $aTypes[2];
			$mValue = '';

			$aData = array();
			$sValueField = '';
			
			if(mb_strpos($sName, 'custom_errormessage') !== false){
				$aData = (array)$this->errormessages;
				$sValueField = 'message';
			} else if(mb_strpos($sName, 'custom_css') !== false){
				$aData = (array)$this->css;
				$sValueField = 'class';
			} else if(mb_strpos($sName, 'custom_template') !== false){
				$aData = (array)$this->templates;
				$sValueField = 'template';
			}
			
			foreach((array)$aData as $aValue) {
				if($aValue['type'] == $sType) {
					$mValue = $aValue[$sValueField];
				}
			}

		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}

	/**
	 * @inheritdoc
	 */
	public function __set($sName, $mValue) {

		if(
			mb_strpos($sName, 'custom_errormessage') !== false ||
			mb_strpos($sName, 'custom_css') !== false ||
			mb_strpos($sName, 'custom_template') !== false
		) {

			$aTypes = explode('_', $sName, 3);
			$sType = $aTypes[2];
			
			$aData = array();
			$sValueField = '';
			
			if(mb_strpos($sName, 'custom_errormessage') !== false){
				$aData = (array)$this->errormessages;
				$sValueField = 'message';
			} else if(mb_strpos($sName, 'custom_css') !== false){
				$aData = (array)$this->css;
				$sValueField = 'class';
			} else if(mb_strpos($sName, 'custom_template') !== false){
				$aData = (array)$this->templates;
				$sValueField = 'template';
			}

			foreach($aData as $iKey=>$aItem) {
				if($aItem['type'] == $sType) {
					unset($aData[$iKey]);
				}
			}

			foreach((array)$mValue as $sValue) {
				$aData[] = array(
					'type' => $sType,
					$sValueField => $sValue
				);
			}

			if(mb_strpos($sName, 'custom_errormessage') !== false){
				$this->errormessages = $aData;
			} else if(mb_strpos($sName, 'custom_css') !== false){
				$this->css = $aData;
			} else if(mb_strpos($sName, 'custom_template') !== false){
				$this->templates = $aData;
			}

		} else {
			parent::__set($sName, $mValue);
		}

	}

	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {

		// Checkbox deaktivieren wenn Anwendungsfall dafür nicht geeignet
		$aDefaultTemplates = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Gui2_Data', 'getUsagesWithDefaultTemplates');
		if(!isset($aDefaultTemplates[$this->usage])) {
			$this->use_default_template = 0;
		}

		return parent::save($bLog);

	}

	/**
	 * Template-Pfad für Smarty->fetch()
	 *
	 * @return string
	 */
	public function getTemplateForSmarty() {

		if($this->use_default_template) {
			$aDefaultTemplates = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Gui2_Data', 'getUsagesWithDefaultTemplates');
			$sPath = $aDefaultTemplates[$this->usage];

			if(!is_file($sPath)) {
				throw new RuntimeException('Default template for usage "'.$this->usage.'" doesn\'t exist!');
			}

		} else {
			$sPath = 'string:'.$this->code;
		}

		return $sPath;

	}

	public function getKey() {
		return $this->key;
	}
	
	/**
	 * Liefert ein Template-objekt anhand eines Schlüssels
	 * @Todo sollte überarbeitet werden, da es nur einen Rückgabe-Typ geben sollte
	 *
	 * @param string $sKey
	 * @return boolean|Ext_TC_Frontend_Template
	 */
	public static function getByKey($sKey) {
		$oRepository = Ext_TC_Frontend_Template::getRepository();
		$oTemplate = $oRepository->findOneBy(array('key' => $sKey));

		if($oTemplate instanceof Ext_TC_Frontend_Template) {
			return $oTemplate;
		}
		
		return false;		
	}

	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()){

		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		// dependency_field_id austauschen
		$aCloneFields = $oClone->getJoinedObjectChilds('fields');
		foreach($aCloneFields as $oCloneField) {
			$aCloneFieldDependencies = $oCloneField->getJoinedObjectChilds('parent_fields_dependencies');
			foreach($aCloneFieldDependencies as $oCloneFieldDependency) {
				if($oCloneFieldDependency->dependency_field_id != 0) {
					$oCloneFieldDependency->dependency_field_id = $this->_aJoinedObjectCopyIds['fields'][$oCloneFieldDependency->dependency_field_id];
					$oCloneFieldDependency->save();
				}
			}
		}

		return $oClone;
	}
	
	/**
	 * get all Messages
	 * @return array 
	 */
	public function getMessages(){
		$aMessages = (array)$this->errormessages;
		return $aMessages;
	}

	public function getMessageForType(string $type): string {
		$message = collect($this->getMessages())->firstWhere('type', $type);
		return (is_array($message)) ? $message['message'] : "";
	}

	/**
	 * get all CSS
	 * @return array 
	 */
	public function getCss(){
		$aCss = (array)$this->css;
		return $aCss;
	}
	
//	/**
//	 * check if the template has a field for an entity
//	 * @param string $sAlias
//	 * @param string $sColumn
//	 * @return boolean 
//	 */
//	public function hasEntityField($sAlias, $sColumn){
//		
//		$bTemplateField		= false;
//
//		$aFields			= (array)$this->getJoinedObjectChilds('fields');
//		
//		foreach ($aFields as $oField){
//			if($oField->field == $sIdentifier){
//				$bTemplateField = true;
//				break;
//			}
//		}
//		
//		return $bTemplateField;
//	}
	
	/**
	 * Liefert ein Feldobjekt anhand eines Platzhalters
	 * 
	 * @param string $sIdentifier
	 * @return boolean|Ext_TC_Frontend_Template_Field
	 */
	public function getField($sIdentifier){			
		$oRepository = Ext_TC_Frontend_Template_Field::getRepository();
		$oField = $oRepository->findOneBy(array('template_id' => $this->id, 'placeholder' => $sIdentifier));
	
		if($oField instanceof Ext_TC_Frontend_Template_Field) {
			return $oField;
		}
		
		return false;
	}
	


	public static function getDefaultTemplates($sFieldTemplatePrefix = null) {

		if(empty($sFieldTemplatePrefix)) {
			$sFieldTemplatePrefix = 'default';
		}
		
		$sTemplatePath = Ext_TC_Frontend_Template::getTemplatePath();

		$aTemplateFiles = (array)glob($sTemplatePath.''.$sFieldTemplatePrefix.'.*.tpl');

		$aReturn = array();
		foreach($aTemplateFiles as $sTemplateFile) {
			$aPathInfo = pathinfo($sTemplateFile);
			$sFieldName = $aPathInfo['filename'];
			$sFieldName = str_replace($sFieldTemplatePrefix.'.', '', $sFieldName);
			$aReturn[$sFieldName] = file_get_contents($sTemplateFile);
		}

		return $aReturn;
	}

	public static function getTemplatePath() {

		$sSecureDir = Ext_TC_Util::getSecureDirectory(true);
		$sTemplatePath = $sSecureDir.'templates/frontend/';

		return $sTemplatePath;
	}
	
	/**
	 * Liefert den Inhalt eines Feldvorlage und schaut dabei ob ein Prefix für eine individuelle Feldvorlage
	 * angegeben wurde
	 *
	 * @param string $sFieldType
	 * @return string
	 */
	public function getDefaultTemplateContent($sFieldType) {

		if(!empty(self::$aTemplateContentCache[$this->id][$sFieldType])) {
			return self::$aTemplateContentCache[$this->id][$sFieldType];
		}

		if($this->field_mode == 'individual') {

			$sVariableName = 'custom_template_fieldtemplate_'.$sFieldType;	

			self::$aTemplateContentCache[$this->id][$sFieldType] = $this->$sVariableName;

		} else {

			if($this->field_mode == 'prefix') {
				$sFieldTemplatePrefix = $this->custom_template_fieldtemplateprefix;
			} else {
				$sFieldTemplatePrefix = 'default';
			}
			
			$sTemplatePath = self::getTemplatePath();

			$sTemplateFile = $sTemplatePath.$sFieldTemplatePrefix.'.'.$sFieldType.'.tpl';		

			if(!is_file($sTemplateFile)) {
				throw new Exception('Field template "'.$sFieldTemplatePrefix.'.'.$sFieldType.'.tpl" doesn\'t exists!');
			}

			self::$aTemplateContentCache[$this->id][$sFieldType] = file_get_contents($sTemplateFile);
			
		}

		return self::$aTemplateContentCache[$this->id][$sFieldType];
	}

}
