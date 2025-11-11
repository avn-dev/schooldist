<?php


class Ext_Thebing_Examination_Version_Gui2 extends Ext_Thebing_Gui2_Data
{
	public function switchAjaxRequest($_VARS)
	{
		$aTransfer = array();
		
		$aTransfer = $this->_switchAjaxRequest($_VARS);
		if(
			$_VARS['action'] == 'contract_open' &&
			$_VARS['task'] == 'request'
		)
		{
			$aSelectedIds	= $_VARS['id'];
			if(is_array($aSelectedIds))
			{
				$iSelectedId	= (int)end($aSelectedIds);
			}
			else
			{
				$iSelectedId	= 0;
			}

			$oVersion			= Ext_Thebing_Examination_Version::getInstance($iSelectedId);
			$oExamination		= $oVersion->getExamination();
			$iCurrentVersion	= $oVersion->version_nr;
			$oDocument			= Ext_Thebing_Inquiry_Document::getInstance($oExamination->document_id);
			$oVersion			= $oDocument->getVersion($iCurrentVersion);

			$sFilepath		= $oVersion->getPath(false);
			$sFileTest		= $oVersion->getPath(true);

			#$sFileTest = \Util::getDocumentRoot().$sFilepath;

			$aTransfer['action'] = 'openUrl';

			if(is_file($sFileTest)) {
				$sFilepath = str_replace('/storage/', '', $sFilepath);
				$aTransfer['url'] = '/storage/download/'.$sFilepath;
			} else {
				$aTransfer['error'] = array(L10N::t('Das Dokument konnte nicht gefunden werden! Bitte speichern Sie den Vertrag neu ab.', $this->_oGui->gui_description));
			}
		}

		echo json_encode($aTransfer);
	}

	public static function getCourseArrayList($iInquiryId)
	{
		$oInquiryCourseSearch = new Ext_Thebing_Inquiry_Course_Search();

		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();

		$oInquiryCourseSearch->setSelect("`ktc`.`name_".$sInterfaceLanguage."` `course_name`");

		$oInquiryCourseSearch->setSelect("`ktc`.`id` `course_id`");

		$oInquiryCourseSearch->setSelect("`kic`.`id` `inquiry_course_id`");

		$oInquiryCourseSearch->setSelect("`ts_tcps`.`id` `program_service_id`");

		// Könnte man auch einfach mit IFNULL prüfen aber da from und until schon in der Ext_Thebing_Inquiry_Course_Search
		// gesetzt werden werden hier eigene Alias benutzt mit anschließender if-else unten
		$oInquiryCourseSearch->setSelect("`ts_tcps`.`from` `program_course_from`");
		$oInquiryCourseSearch->setSelect("`ts_tcps`.`until` `program_course_until`");

		$oInquiryCourseSearch->setJoin("INNER JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON
						`ts_tcps`.`program_id` = `kic`.`program_id` AND
						`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
						`ts_tcps`.`active` = 1
		");
		$oInquiryCourseSearch->setJoin("INNER JOIN
					`kolumbus_tuition_courses` `ktc` ON
						`ktc`.`id` = `ts_tcps`.`type_id` AND
						`ktc`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT."
		");
		$oInquiryCourseSearch->setWhere("`ts_i_j`.`inquiry_id` = :inquiry_id");
		$oInquiryCourseSearch->setValue('inquiry_id', (int)$iInquiryId);
		$oInquiryCourseSearch->setOrder('`kic`.`from` DESC');

		$aResult	= $oInquiryCourseSearch->getResult();

		$aBack		= array();
		if(is_array($aResult))
		{
			foreach($aResult as $aData)
			{
				if(!is_null($aData['program_course_from']) && !is_null($aData['program_course_until'])) {
					$dDateFrom			= Ext_Thebing_Format::LocalDate($aData['program_course_from']);
					$dDateUntil			= Ext_Thebing_Format::LocalDate($aData['program_course_until']);
				} else {
					$dDateFrom			= Ext_Thebing_Format::LocalDate($aData['from']);
					$dDateUntil			= Ext_Thebing_Format::LocalDate($aData['until']);
				}

				$iInquiryCourseId	= $aData['inquiry_course_id'];
				$iCourseId			= $aData['course_id'];
				$iProgramServiceId	= $aData['program_service_id'];
				$iInquiryCourseCourse = $iInquiryCourseId.'_'.$iCourseId.'_'.$iProgramServiceId;

				$aBack[$iInquiryCourseCourse] = $aData['course_name'].', '.$dDateFrom.' - '.$dDateUntil;
			}
		}

		return $aBack;
	}
}
