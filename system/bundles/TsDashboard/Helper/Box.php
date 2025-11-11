<?php

namespace TsDashboard\Helper;

use Admin\Instance;
use Illuminate\Http\Request;

/**
 * Die Klasse bei den Dashboard-Elementen nutzen, wenn die Box je nach Schule anderen Inhalt liefern soll
 */
class Box extends \Admin\Helper\Welcome\Box {

	/**
	 * @var \Ext_Thebing_School 
	 */
	protected $oSchool;
	
	public function __construct(array $aBox) {
		
		parent::__construct($aBox);

		$this->oSchool = \Ext_Thebing_School::getSchoolFromSession();
		
		$this->updateParameter();
		
	}

	protected function updateParameter() {
		
	}
	
	public function setSchool(\Ext_Thebing_School $oSchool=null) {

		$oSession = \Core\Handler\SessionHandler::getInstance();
		
		if($oSchool === null) {	
			$oSession->remove('sid');
		} else {
			$oSession->set('sid', $oSchool->id);
		}

		$this->oSchool = $oSchool;
		
		$this->updateParameter();

	}
	
	public function updateCache(Instance $admin, Request $request) {
		
		// All schools
		$this->setSchool(null);
		$this->getContent(true);
		
		$this->oLog->addInfo('Update cache successful '.$this->getTitle(), [null, $this->sLanguage]);
		
		$aSchools = \Ext_Thebing_School::getObjectList();
		
		foreach($aSchools as $oSchool) {

			$this->setSchool($oSchool);
			$this->getContent(true);

			$this->oLog->addInfo('Update cache successful '.$this->getTitle(), [$oSchool->id, $this->sLanguage]);
		
		}
		
	}
	
	protected function getCacheKey() {

		$sCacheKey = parent::getCacheKey();
		
		if(
			$this->oSchool !== null &&
			$this->oSchool->exist()
		) {
			$sCacheKey .= '_'.$this->oSchool->id;
		}

		return $sCacheKey;
	}
}