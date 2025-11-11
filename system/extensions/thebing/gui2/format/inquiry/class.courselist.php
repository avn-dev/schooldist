<?php

class Ext_Thebing_Gui2_Format_Inquiry_Courselist extends Ext_Gui2_View_Format_Abstract
{	
	protected $_sTitle;
	
	protected $_sType;

	public function __construct($sType) {
		$this->_sType = $sType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$this->_sTitle		= '';
		$sReturn			= '';
		$aInfos				= explode('#', $aResultData['course_data']);
		$iMax				= count($aInfos) - 1;
		$aCourses			= array();
		$oDate				= new WDDate();
		$sMinCourseStart	= null;
		$iFirstCourseId		= 0;

		if($this->_sType === 'course_weeks') {
			$sReturn = 0; // PHP 7.1
		}

		if(
			$this->_sType == 'course_name' ||
			$this->_sType == 'first_course_name'
		) {
			$oCourse		= new Ext_Thebing_Tuition_Course();
			$aCourses		= $oCourse->getArrayListSchool();
			
			$oSchool	= Ext_Thebing_School::getSchoolFromSession();
			$sLanguage	= $oSchool->getInterfaceLanguage();
			$sNameField	= 'name_'.$sLanguage;
		}
		/*
		 *  Nur den ersten Kurs für die "Erste Kurs" Spalte nehmen.
		 */
		
		if($this->_sType === 'first_course_name' && count($aInfos) > 1) {
			$aInfos = (array)reset($aInfos);
		}
		
		foreach($aInfos as $iKey => $sInfo){
			
			$aData = explode('_', $sInfo);
			
			if(
				$this->_sType === 'course_name' ||
				$this->_sType === 'first_course_name'
			){
				$iCourseId = $aData[3];

				if(
					isset($aCourses[$iCourseId]) &&
					isset($aCourses[$iCourseId][$sNameField]) &&
					isset($aCourses[$iCourseId]['name_short'])
				){
					$sReturn .= $aCourses[$iCourseId]['name_short'];

					if($this->_sType === 'course_name')
					{
						$this->_sTitle .= $aCourses[$iCourseId][$sNameField];

						if(
							$iKey < $iMax
						){
							$sReturn .= '<br />';
							$this->_sTitle .= '<br />';
						}
					}
				}
			}elseif(
				$this->_sType == 'level_name'
			){
				if(
					$iKey > 0
				){
					$sReturn .= ',';
				}
				$sReturn .= $aData[4];
			}elseif(
				$this->_sType == 'course_start'
			){
				if(
					$iKey > 0
				){
					$sReturn .= ',';
				}
				$sReturn .= $aData[1];
			}elseif(
				$this->_sType == 'course_end'
			){
				if(
					$iKey > 0
				){
					$sReturn .= ',';
				}
				$sReturn .= $aData[2];
			}elseif(
				$this->_sType == 'course_weeks'
			){
				$sReturn += (int)$aData[5];
			}
			// #2323: Soll nur Kürzel drin stehen
//			elseif(
//				$this->_sType == 'first_course_name'
//			){
//				$dCourseStart = $aData[1];
//				
//				if(
//					WDDate::isDate($dCourseStart, WDDate::DB_DATE)
//				){
//					$oDate->set($dCourseStart, WDDate::DB_DATE);
//					
//					if(
//						$sMinCourseStart === null
//					){
//						$sMinCourseStart	= $dCourseStart;
//						$iFirstCourseId		= $aData[3];
//						
//					}else{
//						
//						$iCompare = $oDate->compare($sMinCourseStart, WDDate::DB_DATE);
//						
//						if(
//							$iCompare < 0
//						){
//							$sMinCourseStart	= $dCourseStart;
//							$iFirstCourseId		= $aData[3];
//						}
//					}
//					
//					if(
//						isset($aCourses[$iFirstCourseId]) &&
//						isset($aCourses[$iFirstCourseId][$sNameField])
//					){
//						$sReturn = $aCourses[$iFirstCourseId][$sNameField];
//					}
//				}
//			}
		}
		
		if(
			$this->_sType == 'level_name'
		){
//			$oFormatLevelList	= new Ext_Thebing_Gui2_Format_Inquiry_LevelList();
//			$sReturn			= $oFormatLevelList->format($sReturn);
		}elseif(
			$this->_sType == 'course_start'	||
			$this->_sType == 'course_end'
		){			
			$oFormatDateList	= new Ext_Thebing_Gui2_Format_Date_List();
			$sReturn			= $oFormatDateList->format($sReturn);
		}
		
		return $sReturn;
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {
		
		$aReturn			= array();
		$aReturn['content'] = (string)$this->_sTitle;
		$aReturn['tooltip'] = true;

		return $aReturn;
	}
}
