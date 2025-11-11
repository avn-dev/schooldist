<?php 

/**
 * Rechteverwaltung Bereiche WDBASIC
 *
 * @deprecated 
 */
class Ext_TC_Access_Section extends Ext_TC_Basic {

	protected $_sTable = 'tc_access_sections';

	static protected $sClassName = 'Ext_TC_Access_Section';

	private static $aInstance = null;
	
	public function getClassName() {
		return self::$sClassName;
	}

	protected $_aJoinedObjects = array(
		'access' => array(
			'class' => 'Ext_TC_Access',
			'key' => 'section_id',
			'type' => 'child',
			'check_active' => true
		)
	);
	
	static public function getInstance($iDataId = 0) {

		$sClass = self::$sClassName;

		if($iDataId == 0) {
			return new $sClass($iDataId);
		}

		if(!isset(self::$aInstance[$sClass][$iDataId])) {
			try {
				self::$aInstance[$sClass][$iDataId] = new $sClass($iDataId);
			} catch(Exception $e) {
				error(print_r($e, 1));
			}
		}

		return self::$aInstance[$sClass][$iDataId];
	}

	public function save($bLog = true)
	{

		// Default Rechte setzen
		if(
			$this->id == 0 &&
			$this->active
		) {

			$aRights = array(
				'new' => 'New',
				'edit' => 'Edit',
				'delete' => 'Delete',
				'show' => 'Show'
			);

			foreach($aRights as $sKey=>$sRight) {
				$oChild = $this->getJoinedObjectChild('access');
				$oChild->name = $sRight;
				$oChild->key = $sKey;
			}

		}

		return parent::save($bLog);

	}
	
}
