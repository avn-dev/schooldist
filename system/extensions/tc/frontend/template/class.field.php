<?php
/**
 * WDBASIC der GUI »Felder« als Tab im Dialog
 *
 * @property int $id
 * @property int $changed
 * @property int $created
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $template_id
 * @property int $used
 * @property int $field_id
 * @property string $area ENUM
 * @property string $display ENUM
 * @property string $placeholder
 * @property string $label
 * @property int $editable
 * @property int $mandatory_field
 * @property string $mandatory_field_error
 * @property string $description
 * @property string $field_css_classes
 * @property int $overwrite_template
 * @property string $template
 */
class Ext_TC_Frontend_Template_Field extends Ext_TC_Basic {

	const sDescriptionPart = 'Thebing Core » Templates » Frontend';

	protected $_sTable = 'tc_frontend_templates_fields';

	protected $_sTableAlias = 'tc_ftf';
	
	/**
	 * Eine Liste mit Verknüpfungen (1-n)
	 *
	 * array(
	 *		'items'=>array(
	 *				'table'=>'',
	 *				'foreign_key_field'=>'',
	 *				'primary_key_field'=>'id',
	 *				'sort_column'=>'',
	 *				'class'=>'', // funktioniert nut wenn bei foreign_key_field ein String angegeben ist mit dem Feldname der die ID der angegebenen Klasse enthält
	 *				'autoload'=>true,
	 *				'check_active'=>true,
	 *				'delete_check'=>false,
	 *				'cloneable' => true,
	 *				'static_key_fields'=>array(),
	 *				'join_operator' => 'LEFT OUTER JOIN' // aktuell nur bei getListQueryData,
	 *				'i18n' => false, // hierbei wird pro Sprache ein Join erzeugt im Query per getListQuery Data
	 * 				'readonly' => false // Nur abrufen, nicht speichern
	 *			)
	 * )
	 *
	 * foreign_key_field kann auch ein Array sein
	 *
	 * @var <array>
	 */
	protected $_aJoinTables = array(
		'extrafields'=>array(
			'table'=>'tc_frontend_templates_fields_to_extrafields',
			'foreign_key_field'	=>	'extrafield_id',
			'primary_key_field'	=>	'template_field_id',
			'autoload' => false,
			'cloneable' => true
		),
		'gui_design_elements'=>array(
			'table'=>'tc_frontend_templates_fields_to_gui_design_elements',
			'foreign_key_field'	=>	'design_element_id',
			'primary_key_field'	=>	'template_field_id',
			'autoload' => false,
			'cloneable' => true
		),
		'mappings'=>array(
			'table'=>'tc_frontend_templates_fields_to_mappings',
			'foreign_key_field'	=>	array('mapping_alias', 'mapping_column'),
			'primary_key_field'	=>	'template_field_id',
			'autoload' => false,
			'cloneable' => true
		),
        'fields_i18n' => array(
			'table' => 'tc_frontend_templates_fields_i18n',
	 		'foreign_key_field'=> array('description'),
	 		'primary_key_field'=> 'field_id'
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
	 *			'orderby_type'=>ASC,
	 *			'orderby_set'=>false
	 *			'query' => false,
     *          'readonly' => false,
	 *			'cloneable' => true,
	 *			'static_key_fields' = array('field' => 'value'),
	 *			'on_delete' => 'cascade' / '' ( nur bei "childs" möglich ),
	 *			'bidirectional' => false // legt fest, ob eine Verknüpfung in beide Richtungen besteht
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array(
	 	'frontend_template'=>array(
			'class'=>'Ext_TC_Frontend_Template',
			'key'=>'template_id',
			'type'=>'parent',
			'check_active'=>true,
			'readonly' => true,
		),
		'parent_fields_dependencies' => array(
			'class' => 'Ext_TC_Frontend_Template_Field_Dependency',
			'key' => 'field_id',
			'type' => 'child',
			'check_active' => true
		)
	);

	public function getFrontendTemplate() {
		return $this->getJoinedObject('frontend_template');
	}

	public function isUnused() {
		return $this->used == 0;
	}
	
	public function updateUsedStatus() {
		if($this->isUnused() === false) {
			return;
		}
		
		$this->used = 1;
		$this->save();
	}


	/**
	 * Gibt die Select Options für die Bereiche zurück, die man im Dialog unter »Einstellungen« findet
	 * @static
	 * @param bool $bWithEmptyItem
	 * @return array
	 */
	public static function getFieldAreas($bWithEmptyItem=false)
	{
		$aOptions = array(
			'standard' => L10N::t('Standard', self::sDescriptionPart),
			'checkbox' =>  L10N::t('Checkbox', self::sDescriptionPart),
			'individual' => L10N::t('Individuelles Feld', self::sDescriptionPart),
		);

		if($bWithEmptyItem) {
			$aOptions = Ext_TC_Util::addEmptyItem($aOptions);
		}

		return $aOptions;
	}

	public function checkIfPlaceholderIsUnique()
	{

		$aSql = array(
			'id' => $this->id,
			'placeholder' => $this->placeholder,
			'template_id' => $this->template_id
		);

		$sSql = "
			SELECT
				`placeholder`
			FROM
				`tc_frontend_templates_fields`
			WHERE
				`placeholder` = :placeholder AND
				`placeholder` != '' AND
				`template_id` = :template_id AND
				`id` != :id
		";

		$aResult = DB::getQueryOne($sSql, $aSql);
		$bResult = empty($aResult);

		return $bResult;

	}

	public function delete() {

		// Beim Löschen den Platzhalter ändern da man denselben Platzhalter sonst nicht erneut verwenden kann
		$this->placeholder .= '_'.\Util::generateRandomString(5);

		return parent::delete();
	}

	public function validate($bThrowExceptions=false)
	{
		$aErrors = parent::validate($bThrowExceptions);

		$bPlaceholderUnique = $this->checkIfPlaceholderIsUnique();

		if(!$bPlaceholderUnique) {

			if(!is_array($aErrors)) {
				$aErrors = array();
			}

			$aErrors[$this->_sTableAlias.'.placeholder'][] = 'NOT_UNIQUE';
		}

		return $aErrors;

	}
	
	public function __get($sName) {

		if($sName == 'field') {
			
			switch ($this->area) {
				case 'checkbox':
					$aFields = $this->extrafields;
					$iField = reset($aFields);
					$sField = '';
					if($iField > 0){
						$sField = 'checkbox.'.$iField;
					}
					return $sField;
					break;
				case 'individual':
					$aFields = $this->gui_design_elements;
					$iField = reset($aFields);
					$sField = '';
					if($iField > 0){
						$sField = 'individual.'.$iField;
					}
					return $sField;
					break;
				case 'standard':
					$aFields = $this->mappings;
					$aField = reset($aFields);
					$sField = '';
					if(count($aFields) > 0){
						$sField = $aField['mapping_alias'].'.'.$aField['mapping_column'];
					}
					return $sField;
					break;
			}
			
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}

	public function __set($sName, $mValue) {

		if($sName == 'field') {
			
			$aValue		= explode('.', $mValue);
			$sAlias		= '';
			$sColumn	= '';
			
			if(count($aValue) == 2){
				$sAlias		= $aValue[0];
				$sColumn	= $aValue[1];
			}
			
			switch ($sAlias) {
				case 'checkbox':
					if($sColumn){
						$this->extrafields = array($sColumn);
					} else {
						$this->extrafields = array();
					}
					$this->gui_design_elements = array();
					$this->mappings = array();
					break;
				case 'individual':
					if($sColumn){
						$this->gui_design_elements = array($sColumn);
					} else {
						$this->gui_design_elements = array();
					}
					$this->extrafields = array();
					$this->mappings = array();
					break;
				default:
					if($sColumn){
						$this->mappings = array(
							array(
								'mapping_alias' => $sAlias,
								'mapping_column' => $sColumn
							)
						);
					} else {
						$this->mappings = array();
					}
					$this->gui_design_elements = array();
					$this->extrafields = array();
					break;
			}
		} else {
			parent::__set($sName, $mValue);
		}

	}
		
	public function getEntityFieldName(){
		$aMappings = $this->mappings;
		$aMappings = reset($aMappings);
		
		if(!empty($aMappings)){
			$sName = $aMappings['mapping_column'];
		}  else {
			$sName = $this->field;
		}
		
		return $sName;
	}
	
	public function getEntityFieldAlias(){
		
		$aMappings = (array)$this->mappings;
		$aMappings = reset($aMappings);

		if(!empty($aMappings)){
			$sName = $aMappings['mapping_alias'];
		}  else {
			$sName = '';
		}
		
		return $sName;
	}

	/**
	 * Liefert die Abhänigkeiten bei der Validierung
	 * 
	 * @return Ext_TC_Frontend_Template_Field_Dependency[]
	 */
	public function getValidationDependencies() {		
		return $this->getJoinedObjectChilds('parent_fields_dependencies', true);
	}
	
    /**
     * Liefert den Hinweistext dieses Feldes
     * 
     * @param string $sLanguage
     * @return string
     */
    public function getDescription($sLanguage = null) {
        return $this->getI18NName('fields_i18n', 'description', $sLanguage);
    }
    
}
