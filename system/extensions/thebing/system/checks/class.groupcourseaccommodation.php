<?php

class Ext_Thebing_System_Checks_Groupcourseaccommodation extends GlobalChecks {

	public function getTitle()
	{
		return 'Group Course/Accommodation Update';
	}

	public function  getDescription()
	{
		return '...';
	}

	public function executeCheck(){
		global $user_data, $_VARS;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$sSql = "SELECT
						*
					FROM
						`kolumbus_groups`
					WHERE
						`active` = 1
				";

		$aResult = DB::getQueryData($sSql);

		foreach((array)$aResult as $aGroupData){

			$oGroup				= new Ext_Thebing_Inquiry_Group((int)$aGroupData['id']);

			$aCourses			= $oGroup->getCourses('all');
			$aAccommodations	= $oGroup->getAccommodations('all');

			$aInquirys			= $oGroup->getInquiries();

			foreach((array)$aInquirys as $oInquiry){
				$aInquiryCourses[$oInquiry->id]			= $oInquiry->getCourses(true, true);
				$aInquiryAccommodations[$oInquiry->id]	= $oInquiry->getAccommodations(true, true);
			}

			// Kurse
			foreach((array)$aCourses as $iKey => $aCourseData) {
				$oGroupCourse = new Ext_Thebing_Inquiry_Group_Course((int)$aCourseData['id']);

				foreach((array)$aInquirys as $oInquiry) {
					
					$oInquiryCourse = null;
					foreach($aInquiryCourses[$oInquiry->id] as $oCheckInquiryCourse) {
						if(
							$oCheckInquiryCourse->course_id == $oGroupCourse->course_id &&
							$oCheckInquiryCourse->weeks == $oGroupCourse->weeks &&
							$oCheckInquiryCourse->from == $oGroupCourse->from &&
							$oCheckInquiryCourse->until == $oGroupCourse->until								
						) {
							$oInquiryCourse = $oCheckInquiryCourse;
							break;
						}
					}

					if(
						$oInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course
					) {
						$oInquiryCourse->groups_course_id = (int)$oGroupCourse->id;
						if($oInquiryCourse->validate() === true) {
							$oInquiryCourse->save();
						} else {
							echo "Course object not valid: Inquiry ID: ".$oInquiry->id.", Group ID: ".$aGroupData['id']."<br/>\n";
						}
					} else {
						echo "No course object: Inquiry ID: ".$oInquiry->id.", Group ID: ".$aGroupData['id']."<br/>\n";
					}
				}
			}

			// Unterkünfte
			foreach((array)$aAccommodations as $iKey => $aAccommodationData){
				$oGroupAccommodation = new Ext_Thebing_Inquiry_Group_Accommodation((int)$aAccommodationData['id']);

				foreach((array)$aInquirys as $oInquiry) {
					
					$oInquiryAccommodation = null;
					foreach($aInquiryAccommodations[$oInquiry->id] as $oCheckInquiryAccommodation) {
						if(
							$oCheckInquiryAccommodation->accommodation_id == $oGroupAccommodation->accommodation_id &&
							$oCheckInquiryAccommodation->roomtype_id == $oGroupAccommodation->roomtype_id &&
							$oCheckInquiryAccommodation->meal_id == $oGroupAccommodation->meal_id &&
							$oCheckInquiryAccommodation->weeks == $oGroupAccommodation->weeks &&
							$oCheckInquiryAccommodation->from == $oGroupAccommodation->from &&
							$oCheckInquiryAccommodation->until == $oGroupAccommodation->until								
						) {
							$oInquiryAccommodation = $oCheckInquiryAccommodation;
							break;
						}
					}

					if(
						$oInquiryAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation
					) {

						$oInquiryAccommodation->groups_accommodation_id = (int)$oGroupAccommodation->id;
						if($oInquiryAccommodation->validate() === true) {
							$oInquiryAccommodation->save();
						} else {
							echo "Accommodation object not valid: Inquiry ID: ".$oInquiry->id.", Group ID: ".$aGroupData['id']."<br/>\n";
						}

					} else {
						echo "No accommodation object: Inquiry ID: ".$oInquiry->id.", Group ID: ".$aGroupData['id']."<br/>\n";
					}
				}
			}


		}


		return true;

	}

}
