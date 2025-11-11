<?php
/**
 * @property $id
 * @property $changed 	
 * @property $created 	
 * @property $active 	
 * @property $user_id 	
 * @property $school_id 	
 * @property $from 	
 * @property $until 	
 * @property $interval
 */

class Ext_Thebing_School_ClassTimes extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_tuition_classes_times';
	protected $_sTableAlias = 'ktct';

	public function  save($bLog = true) {
		$bSuccess = true;
		try{
			parent::save($bLog);
		}catch(DB_QueryFailedException $oException){
			$bSuccess = false;
		}

		/*
		 * Dieser Abschnitt ist buggy und kann dafür sorgen, dass vorhandene Vorlagen falsch geändert werden
		 * @todo Am besten nur anhand der Zeit prüfen, ob es Vorlagen gibt, die außerhalb liegen und Fehler schmeißen. Nix einfach so ändern!
		 */
//		if(0 && $bSuccess){
//
//			$aTimesOptions		= $this->getSchool()->getClassTimesOptions('format');
//			$aTimesOptionsKeys	= array_keys($aTimesOptions);
//
//			$sSql = "
//				SELECT
//					*,
//					TIME_TO_SEC(`from`) `from_seconds`,
//					TIME_TO_SEC(`until`) `until_seconds`
//				FROM
//					`kolumbus_tuition_templates`
//				WHERE
//					`active` = 1 AND
//					`school_id` = :school_id AND
//					(
//						`from` NOT IN(:atimes) OR
//						`until` NOT IN(:atimes)
//					)
//			";
//
//			$aSql = array(
//				'school_id' => $this->school_id,
//				'atimes'	=> $aTimesOptions
//			);
//
//			$aResult	= DB::getPreparedQueryData($sSql, $aSql);
//			$aTimes		= $this->getSchool()->getClassTimes();
//
//			foreach($aResult as $aTuitionTemplateInfo) {
//				
//				foreach($aTimes as $oClassTime) {
//					
//					$iFromSeconds = $oClassTime->getFromSeconds();
//					$iUntilSeconds = $oClassTime->getUntilSeconds();
//					
//					if(
//						$aTuitionTemplateInfo['from_seconds'] >= $iFromSeconds &&
//						$aTuitionTemplateInfo['from_seconds'] <= $iUntilSeconds
//					) {
//						$aUpdate = array(
//							'until' => $oClassTime->until
//						);
//
//						DB::updateData('kolumbus_tuition_templates', $aUpdate, 'id='.$aTuitionTemplateInfo['id']);
//					} elseif(
//						$aTuitionTemplateInfo['until_seconds'] >= $iFromSeconds &&
//						$aTuitionTemplateInfo['until_seconds'] <= $iUntilSeconds
//					) {
//						$aUpdate = array(
//							'from' => $oClassTime->from
//						);
//
//						DB::updateData('kolumbus_tuition_templates', $aUpdate, 'id='.$aTuitionTemplateInfo['id']);
//					} else {
//						//Vorlage löschen??
//					}
//
//				}
//			}
//		}

		return $this;
	}

	public function  validate($bThrowExceptions = false){

		$mReturn = parent::validate($bThrowExceptions);
		if($mReturn===true){
			$sSql = "
				SELECT
					COUNT(*)
				FROM
					#table
				WHERE
					:from <= `until` AND
					:until >= `from` AND
					`school_id` = :school_id AND
					`active` = 1 AND
					`id` <> :id
			";

			$aSql = array(
				'table'		=> $this->_sTable,
				'from'		=> $this->from,
				'until'		=> $this->until,
				'school_id'	=> $this->school_id,
				'id'		=> (int)$this->id,
			);

			$iCount = (int)DB::getQueryOne($sSql, $aSql);
			if($iCount>0){
				return array(
					L10N::t('Es existieren andere Unterrichtzeiten in diesem Zeitraum, bitte ändern Sie die Unterrichtszeit!','Thebing » Admin » Schools')
				);
			}else{
				return true;
			}
		}else{
			return $mReturn;
		}


	}

	/**
	 * @return Ext_Thebing_School
	 */
	public function getSchool(){
		$oSchool = Ext_Thebing_School::getInstance($this->school_id);
		return $oSchool;
	}

	public function getFromSeconds() {
		return strtotime('1970-01-01 '.$this->from.' UTC');
	}

	public function getUntilSeconds() {
		return strtotime('1970-01-01 '.$this->until.' UTC');
	}
	
}