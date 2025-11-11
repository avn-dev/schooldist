<?php 

class Ext_TC_Positiongroup_Section extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_positiongroups_sections';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_ps';

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'positiongroup'=>array(
			'class'=>'Ext_TC_Positiongroup',
			'key'=>'positiongroup_id',
			'type'=>'parent'
		)
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'positions' => array(
			'table' => 'tc_positiongroups_sections_positions',
			'primary_key_field'=> 'section_id',
			'foreign_key_field'=> 'position_type',
			'sort_column'=>'position',
			'autoload' => false
		),
		'i18n' => array(
			'table' => 'tc_positiongroups_sections_i18n',
	 		'foreign_key_field'=> array('language_iso', 'name'),
	 		'primary_key_field'=> 'section_id'
		)
	);
	
	/**
	 * Gibt den Namen in der passenden Sprache zurück
	 *
	 * @return string
	 */
	public function getName($sLanguage=null) {
		
		if($this->id <= 0){
			$sName = L10N::t('Einzelpositionen');
		} else {
			$sName = $this->getI18NName('i18n', 'name', $sLanguage);
		}

		return $sName;
	}

	/**
	 * @param string $sName
	 * @return mixed|string
	 */
	public function __get($sName) {

		if($sName == 'name') {
			return $this->getName();
		} else {
			return parent::__get($sName);
		}

	}
	
	/**
	 * Prüft, ob die übergebenen Positionen bei der Section ausgewählt wurden
	 * 
	 * @param array $aSections
	 * @return boolean
	 */
	public function checkPositions(array $aSections) {
		$aDiff = array_intersect($aSections, $this->positions);
		// Schnittmenge muss dieselbe Länge wie die übergebenen Positionen haben, dann sind 
		// die Positionen ausgewählt
		if(count($aDiff) === count($aSections)) {
			return true;
		}
		
		return false;
	}
	
}