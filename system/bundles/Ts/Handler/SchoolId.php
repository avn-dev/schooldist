<?php

namespace Ts\Handler;

use Core\Handler\SessionHandler as Session;
use \Core\Handler\CookieHandler as Cookie;

class SchoolId {
	
	public function setSchool($iSchoolId) {

		$oSession = Session::getInstance();
		
		$oSession->set('sid', $iSchoolId);

		$this->checkSchool();
		
	}
	
	public function checkSchool() {

		$oSession = Session::getInstance();

		// Wenn School-Id per Cookie gesetzt und nciht per Session
		if(
			$oSession->has('sid') === false &&
			Cookie::is('sid') === true
		) {
			$oSession->set('sid', Cookie::get('sid'));
		}

		$sSql = " SELECT id FROM #table WHERE `active` = 1";
		$aSql = array('table'=>'customer_db_2');
		$aSchoolIds = \DB::getQueryCol($sSql,$aSql);
		$oAccess = new \Ext_Thebing_Access();
		$bFound = false;
		$bAllOk = true;
		$iSchoolCount = 0;

		// Keine Schulen da
		if(empty($aSchoolIds)) {
			$oSession->set('sid', 0);
			$oSession->set('Id0IsOK', 1);
			return;
		}

		foreach($aSchoolIds as $iKey => $iSchoolId){

			$iSchoolRights = $oAccess->countSchoolRights($iSchoolId);

			if($iSchoolRights > 0) {
				$iSchoolCount++;
			}

			if($iSchoolRights <= 0) {
				unset($aSchoolIds[$iKey]);
			}

			if(
				$oSession->get('sid') > 0 &&
				$oSession->get('sid') == $iSchoolId
			) {
				$bFound = true;
			}

		}

		if($iSchoolCount <= 1) {
			$bAllOk = false;
		}

		if(
			$oSession->get('sid') > 0 &&
			$bFound == false
		) {
			$oSession->remove('sid');
		}

		$iCountSchools = count($aSchoolIds);
		if(
			$iCountSchools > 1 && 
			$bAllOk
		) {
			$oSession->set('Id0IsOK', 1);
		} else {
			$oSession->set('Id0IsOK', 0);
		}

		$iSession = 0;

		$bSessionCheck1 = false;
		$bSessionCheck2 = false;

		// Ließt Wenn Schul-ID aus Session
		if(
			$oSession->has('sid') === true && 
			( 
				$oSession->get('sid') > 0 || 
				( 
				$oSession->get('Id0IsOK') == 1 && 
				$oSession->get('sid') == 0
				)
			)
		) {
			$iSession = $oSession->get('sid');
			$bSessionCheck1 = true;
		}

		if(
			!$bSessionCheck1 && 
			!$bSessionCheck2
		) {
			$iFirstSchoolId = reset($aSchoolIds);
			$iSession = $iFirstSchoolId;
		}

		foreach($aSchoolIds as $iSchoolId) {

			if($iSession == $iSchoolId){
				$iSession_ = $iSession;
				break;
			} else {
				$iSession_ = 0;
			}

		}

		$oSession->set('sid', $iSession_);				

		if(
			$oSession->get('sid') == 0 && 
			$oSession->get('Id0IsOK') == 0
		) {
			$iFirstSchoolId = reset($aSchoolIds);
			$oSession->set('sid', $iFirstSchoolId);
		} 

		$iSchoolId = $oSession->get('sid');

		// School-Id in Cookie speichern und eine Woche merken
		Cookie::set('sid', $iSchoolId, time()+(60*60*24*7));

	}
	
}
