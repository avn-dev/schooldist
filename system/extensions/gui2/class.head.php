<?php

/**
 * Class Ext_Gui2_Head
 *
 * @property string $db_column
 * @property string $db_alias
 * @property string $db_type
 * @property string $select_column
 * @property string $sortable_column
 * @property int $sortable
 * @property int $searchable
 * @property string $width
 * @property string $width_resize
 * @property string $title
 * @property string $order
 * @property string $order_settings
 * @property string $css_class
 * @property $inplaceEditor
 * @property $inplaceEditorType
 * @property $inplaceEditorOptions
 * @property $inplaceEditorStart
 * @property string|boolean $css_overflow
 * @property Ext_Gui2_View_Format_Interface $format
 * @property Ext_Gui2_View_Format_Interface $post_format
 * @property Ext_Gui2_View_Style_Interface $style
 * @property Ext_Gui2_View_Event_Interface $event
 * @property string $regex
 * @property boolean $small
 * @property boolean $default
 * @property Ext_Gui2_HeadGroup|null $group
 * @property string $mouseover_title
 * @property boolean $flexibility'
 * @property string $wdsearch_type
 * @property array $i18n
 */
class Ext_Gui2_Head extends Ext_Gui2_Config_Basic {

	/**
	 * @var array
	 */
	protected $_aConfig = array(
		'db_column' 		=> 'id',
		'db_alias'			=> '',
		'db_type'			=> 'varchar',	//varchar, int, float, timestamp
		'select_column'		=> '',			// Im SELECT selectierter Wert für die anzeige
		'sortable_column' => null,
		'sortable'			=> 1,			// sortierbar oder nicht?
		'searchable'		=> 1,			// suchbar ( nur bei aktiver WDSearch )
		'width'				=> 50,			// NUR Zahlen!!
		'width_resize'		=> false,		// definiert ob die Breite sich "vergößert" falls platz übrig ist
		'title'				=> '',
		'order'				=> '',
		'order_settings'	=> null,		// Array mit Feldern und Richtung, falls nicht normal sortiert werden soll
		'css_class'			=> '',
		'inplaceEditor'		=> 0,
		'inplaceEditorType'	=> 'default',	// default / select
		'inplaceEditorOptions' => array(),	// array(array('text'=>'', 'value' => ''))
		'inplaceEditorStart' => 0,			// bool, direkt anzeigen
		'css_overflow'		=> false,		// false, 'clip', 'ellipsis', '<string>'
		'format'			=> 'Text',		// Ext_Gui2_View_Format_Interface Klasse
		'post_format' => null, // Formatklasse für nachträgliche Formatierung (relevant bei Index)
		'style'				=> 'Column',	// Ext_Gui2_View_Style_Interface Klasse
		'event'				=> NULL,		// Ext_Gui2_View_Event_Interface Klasse
		'regex'				=> '',			// Wird auf die Anezeige angewand (preg_match[0])
		'small'				=> false,		// Die Überschrift wird kleiner dargestellt
		'group'				=> NULL,		// Objekt der Gruppe, optional
		'mouseover_title'	=> '',
		'flexibility'=> true,
		'default' => true,
		'wdsearch_type'		=> '', // '', 'email'
		'i18n' => array() // I18N-Daten aus YML-Config oder Ext_TC_Gui2::addLanguageColumns()
	);

	public function checkConfig($sConfig, $mValue){
		if($sConfig == 'width' && !is_numeric($mValue)){
			return false;
		}
		return true;
	}

	public function addCssClass($sClassName) {
		
		if(strlen($this->css_class) > 0) {
			$this->css_class .= ' ';
		}

		// Nur hinzufügen, wenn noch nicht vorhanden
		if(strpos($this->css_class, $sClassName) === false) {
			$this->css_class .= $sClassName;
		}
		
	}

	public function removeCssClass($sClassName){
		$this->css_class = str_replace($sClassName, '', $this->css_class);
	}

}