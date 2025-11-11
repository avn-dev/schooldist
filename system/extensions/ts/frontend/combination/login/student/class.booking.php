<?php


class Ext_TS_Frontend_Combination_Login_Student_Booking extends Ext_TS_Frontend_Combination_Login_Student_Abstract
{
	protected function _setData()
	{
		$oInquiry				= $this->_getInquiry();
		$oSchool				= $oInquiry->getSchool();
		$iSchoolId				= (int)$oSchool->id;
		$sLanguage				= $this->_getLanguage();
		$aInquiryCourses		= (array)$oInquiry->getCourses();
		$aInquiryAccommodations	= (array)$oInquiry->getAccommodations();
		$oFormatDate			= new Ext_Thebing_Gui2_Format_Date(false, $iSchoolId);

		$oFormCourses = new Ext_TS_Frontend_Combination_Login_Student_Form($this);

		$sField					= 'name_'.$sLanguage;

		foreach($aInquiryCourses as $aInquiryCourse)
		{
			$oCourse		= Ext_Thebing_Tuition_Course::getInstance($aInquiryCourse['course_id']);
			$oLevel			= Ext_Thebing_Tuition_Level::getInstance($aInquiryCourse['level_id']);

			try
			{
				$sCourseName	= $oCourse->$sField;
			}
			catch(Exception $e)
			{
				$sCourseName = $oCourse->name_en;
			}

			try
			{
				$sLevelName	= $oLevel->$sField;
			}
			catch(Exception $e)
			{
				$sLevelName = $oLevel->name_en;
			}

			$iWeeks = (int)$aInquiryCourse['weeks'];
			$sFrom	= $oFormatDate->format($aInquiryCourse['from']);
			$sUntil	= $oFormatDate->format($aInquiryCourse['until']);

			$oFormCourses->addRow('input', 'Course', $sCourseName, array('readonly' => true));
			$oFormCourses->addRow('input', 'Level', $sLevelName, array('readonly' => true));
			$oFormCourses->addRow('input', 'Weeks', $iWeeks, array('readonly' => true));
			$oFormCourses->addRow('input', 'From', $sFrom, array('readonly' => true));
			$oFormCourses->addRow('input', 'Until', $sUntil, array('readonly' => true));
		}

		$this->_assign('sCoursesData', $oFormCourses);

		$oFormTransfer = Ext_TS_Frontend_Combination_Login_Student_Transfer::getTransferForm($this, $oInquiry);
		$this->_assign('sTransferData', $oFormTransfer);

		$oFormAccommodations = new Ext_TS_Frontend_Combination_Login_Student_Form($this);

		foreach($aInquiryAccommodations as $aInquiryAccommodation)
		{
			$oAccommodation = Ext_Thebing_Accommodation_Category::getInstance($aInquiryAccommodation['accommodation_id']);
			$oRoomType		= Ext_Thebing_Accommodation_Roomtype::getInstance($aInquiryAccommodation['roomtype_id']);
			$oMeal			= Ext_Thebing_Accommodation_Meal::getInstance($aInquiryAccommodation['meal_id']);

			try
			{
				$sAccommodationName	= $oAccommodation->$sField;
			}
			catch(Exception $e)
			{
				$sAccommodationName = $oAccommodation->name_en;
			}

			try
			{
				$sRoomType	= $oRoomType->$sField;
			}
			catch(Exception $e)
			{
				$sRoomType = $oRoomType->name_en;
			}

			try
			{
				$sMeal	= $oMeal->$sField;
			}
			catch(Exception $e)
			{
				$sMeal = $oMeal->name_en;
			}

			$iWeeks = (int)$aInquiryAccommodation['weeks'];
			$sFrom	= $oFormatDate->format($aInquiryAccommodation['from']);
			$sUntil	= $oFormatDate->format($aInquiryAccommodation['until']);

			$oFormAccommodations->addRow('input', 'Accommodation', $sAccommodationName, array('readonly' => true));
			$oFormAccommodations->addRow('input', 'Room', $sRoomType, array('readonly' => true));
			$oFormAccommodations->addRow('input', 'Meal', $sMeal, array('readonly' => true));
			$oFormAccommodations->addRow('input', 'Weeks', $iWeeks, array('readonly' => true));
			$oFormAccommodations->addRow('input', 'From', $sFrom, array('readonly' => true));
			$oFormAccommodations->addRow('input', 'Until', $sUntil, array('readonly' => true));
		}

		$this->_assign('sAccommodationsData', $oFormAccommodations);

		$this->_setTask('showBookingData');
	}
}