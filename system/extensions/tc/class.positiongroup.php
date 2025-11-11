<?php 

class Ext_TC_Positiongroup extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_positiongroups';
	protected $_sTableAlias = 'tc_p';

	protected $_aJoinedObjects = array(
		'sections'=>array(
			'class'=>'Ext_TC_Positiongroup_Section',
			'key'=>'positiongroup_id',
			'type'=>'child',
			'check_active'=>true,
			'orderby'=>'position',
	 		'query' => false
		)
	);

	/**
	 * get Entries for Selects
	 * @return type
	 */
	public static function getSelectOptions(){
		$oTemp = new self();
		$aList = $oTemp->getArrayList(true);
		return $aList;
	}

	/**
	 * Ableitung um immer mindestens eine Section zurückzugeben
	 * @param string $sMixed
	 * @return array 
	 */
	public function getJoinedObjectChilds($sMixed = null, $bCheckCache=false) {

		$aReturn = parent::getJoinedObjectChilds($sMixed, $bCheckCache);
		
		if(
			$sMixed == 'sections' &&
			empty($aReturn)
		) {
			$aReturn[0] = new Ext_TC_Positiongroup_Section(0);
			$aReturn[0]->getJoinTableData('positions');
		}
		
		return $aReturn;

	}
	
	/**
	 * Liefert ein Array mit Section Objekten
	 * @return Ext_TC_Positiongroup_Section[]
	 */
	public function getSections() {
		
		$aSections = $this->getJoinedObjectChilds('sections', true);
		
		return (array)$aSections;
		
	}

	/**
	 * Ermittelt alle noch nicht zugeordneten Positiontypen der Gruppe
	 */
	public function getUnallocatedPositionTypes() {
		
		$aAllocatedPositionTypes = array();
		
		$aUnallocatedPositionTypes = Ext_TC_Positiongroup_Gui2_Data::getPositionTypes();
		
		if($this->id > 0) {
			$aSections = $this->getSections();
	
			foreach($aSections as $oSection) {
				foreach($oSection->positions as $sPositionKey) {
					unset($aUnallocatedPositionTypes[$sPositionKey]);
				}	
			}
		}

		return array_keys($aUnallocatedPositionTypes);
		
	}
	
}