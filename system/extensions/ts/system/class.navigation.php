<?php

class Ext_TS_System_Navigation extends Ext_TC_System_Navigation {

	/**
	 * @var int 
	 */
	protected $_iCount = 11;

	protected function setStructure() {

		$iSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');

		$oClient = Ext_Thebing_Client::getInstance();
		$aSchools = Ext_Thebing_Client::getSchoolList(false);

		$bAllSchools = true;
		if($iSchoolId > 0) {
			$bAllSchools = false;
		}
		
		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "enquiries";
		$oTop->sTitle		= "Anfragen";
		$oTop->sL10NAddon	= "Fidelo » Enquiries";
		$oTop->mAccess		= array('thebing_students_contact_request', '');
		$oTop->sKey 		= "ts.enquiries";
		$oTop->iExtension	= 0;
		$oTop->iLoadContent = 0;
		$oTop->sUrl			= '/ts/enquiry/page';
		$oTop->sIcon = 'fa-inbox';

		$this->addTopNavigation($oTop);

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_invoice";
		$oTop->sTitle		= "Buchungen";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_invoice_icon', '');
		$oTop->sKey 		= 'ts.inquiries';
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;
		$oTop->sIcon = 'fa-file-text-o';

		$aInboxlist = $oClient->getInboxList(false, false, true);
		if(count($aInboxlist) > 1) {
			foreach($aInboxlist as $aInbox) {

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->sL10NAddon = "Thebing » Invoice » Inbox";

				if($aInbox['name'] == '') {
					$sName = 'Buchungen';
				} else {
					$sName = $aInbox['name'];
					$oChild->bTranslate = false;
				}

				$oChild->mAccess		= array('thebing_invoice_inbox_'.$aInbox['id'], '');
				$oChild->sTitle			= $sName;
				$oChild->iSubpoint		= 0;
				$oChild->sUrl			= '/admin/extensions/ts/inquiry.html?inbox_id='.$aInbox['id'];
				$oChild->sKey			= 'ts.inbox.'.$aInbox['id'];

				$oTop->addChild($oChild);

			}
		} else {

			$aInbox = reset($aInboxlist);

			$oTop->iExtension = 0;
			$oTop->sUrl = '/admin/extensions/ts/inquiry.html?inbox_id='.$aInbox['id'];

		}

		$this->addTopNavigation($oTop);

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_students";
		$oTop->sTitle		= "Schüler";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_students_icon', '');
		$oTop->sKey 		= 'ts.students';
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;
		$oTop->sIcon = 'fa-users';

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_simple_view', '');
		$oChild->sTitle			= "Einfache Schülerliste";
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->sUrl			= "/admin/extensions/thebing/students/simple.html";
		$oChild->sKey			= "ts.students.simple";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_welcome_list', '');
		$oChild->sTitle			= "Willkommensliste";
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->sUrl			= "/admin/extensions/thebing/students/welcome.html";
		$oChild->sKey			= "ts.students.welcome";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_welcome_list', '');
		$oChild->sTitle			= "Anwesende Schüler";
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->sUrl			= "/admin/extensions/thebing/students/checkedin.html";
		$oChild->sKey			= "ts.students.checked_in";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_departure_list', '');
		$oChild->sTitle			= "Abreiseliste";
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->sUrl			= "/admin/extensions/thebing/students/departure.html";
		$oChild->sKey			= "ts.students.departure";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_feedback_list', '');
		$oChild->sTitle			= "Feedbackliste";
		$oChild->sL10NAddon		= "List of results";
		$oChild->sUrl			= "/wdmvc/gui2/page/ts_feedback_results";
		$oChild->sKey			= "ts.students.feedback";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_visa_list', '');
		$oChild->sTitle			= "Visaliste";
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->sUrl			= "/admin/extensions/thebing/students/visum.html";
		$oChild->sKey			= "ts.students.visa";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_students_catalog_request', '');
		$oChild->sTitle			= "Catalouge Request";
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sUrl			= "/admin/extensions/kolumbus_catalogue.html";
		$oChild->sKey			= "ts.students.feedback";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('ts_students_sponsoring_list', '');
		$oChild->sTitle			= "Gesponsorte Schüler";
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->sUrl			= "/gui2/page/ts_students_sponsoring";
		$oChild->sKey			= "ts.students.sponsoring";
		$oTop->addChild($oChild);

		$this->addTopNavigation($oTop);

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_tuition";
		$oTop->sTitle		= "Klassenplanung";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_tuition_icon', '');
		$oTop->sKey			= "ts.tuition";
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;
		$oTop->sIcon = 'fas fa-chalkboard-teacher';

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = '';
			$oChild->sTitle = 'Klassenplanung';
			$oChild->sL10NAddon = "Thebing » Menü Links";
			$oChild->iSubpoint = 0;
			$oChild->sKey = "ts.tuition.scheduling";
			$oTop->addChild($oChild);

			if(!Ext_Thebing_System::isAllSchools()) {
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess = array('thebing_tuition_planificaton', '');
				$oChild->sTitle = 'Planung';
				$oChild->sL10NAddon = "Thebing » Tuition » Planification";
				$oChild->iSubpoint = 1;
				$oChild->sUrl = '/ts-tuition/scheduling/page/view';
				$oChild->sKey = "ts.tuition.scheduling_view";
				$oTop->addChild($oChild);
			}

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_tuition_classes', '');
			$oChild->sTitle			= 'Klassen';
			$oChild->sL10NAddon		= \Ext_Thebing_Tuition_Class_Gui2::TRANSLATION_PATH;
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/tuition/classes.html';
			$oChild->sKey 			= "ts.tuition.classes";
			$oTop->addChild($oChild);

//				Angefangen, noch nicht fertig
//			$oChild = new Ext_TC_System_Navigation_LeftItem();
//			$oChild->mAccess		= array('thebing_tuition_classes', '');
//			$oChild->sTitle			= 'Klassentest';
//			$oChild->sL10NAddon		= "Thebing » Tuition » Classes";
//			$oChild->iSubpoint		= 1;
//			$oChild->sUrl			= '/gui2/page/TsTuition_classes';
//			$oTop->addChild($oChild);

			if(!Ext_Thebing_System::isAllSchools()) {
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_overview', '');
				$oChild->sTitle			= 'Eigene Übersicht';
				$oChild->sL10NAddon		= "Thebing » Tuition » Own overview";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/own_overview_results.html';
				$oChild->sKey 			= "ts.tuition.own_overview";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_course_list', '');
				$oChild->sTitle			= 'Gebuchte Kurse';
				$oChild->sL10NAddon		= "Thebing » Tuition » Courselist";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/wdmvc/gui2/page/ts_inquiry_journey_courses';
				$oChild->sKey 			= "ts.tuition.inquiry_courses";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess = 'thebing_tuition_course_list';
				$oChild->sTitle = 'Gebuchte Prüfungen';
				$oChild->sL10NAddon = "Thebing » Tuition » Exams";
				$oChild->iSubpoint = 1;
				$oChild->sUrl = '/wdmvc/gui2/page/TsTuition_exam_courses';
				$oChild->sKey = "ts.tuition.exams";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_certificates', '');
				$oChild->sTitle			= 'Zertifikate';
				$oChild->sL10NAddon		= "Thebing » Tuition » Certificates";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/TsTuition_certificates';
				$oChild->sKey 			= "ts.tuition.certificates";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_progress_report', '');
				$oChild->sTitle			= 'Fortschrittsbericht';
				$oChild->sL10NAddon		= "Thebing » Tuition » Progress Report";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/progress_report.html';
				$oChild->sKey 			= "ts.tuition.progress_report";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_placement_test', '');
				$oChild->sTitle			= 'Einstufungstests';
				$oChild->sL10NAddon		= "Thebing » Tuition » Placementtest Results";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/TsTuition_placementtest_results';
				$oChild->sKey 			= "ts.tuition.placementtest_results";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_teacher_overview', '');
				$oChild->sTitle			= 'Lehrerübersicht';
				$oChild->sL10NAddon		= "Thebing » Tuition » Lehrerübersicht";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/ts/teacher-overview';
				$oChild->sKey 			= "ts.tuition.teachers_overview";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= '';
				$oChild->sTitle			= 'Anwesenheit';
				$oChild->sL10NAddon		= "Thebing » Attendance";
				$oChild->iSubpoint		= 0;
				$oChild->sKey 			= "ts.tuition.attendance";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_edit_attendance', '');
				$oChild->sTitle			= 'Bearbeiten';
				$oChild->sL10NAddon		= Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH;
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/attendance2.html?view=edit';
				$oChild->sKey 			= "ts.tuition.attendance.edit";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_attendance_list', '');
				$oChild->sTitle			= 'Übersicht';
				$oChild->sL10NAddon		= Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH;
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/attendance2.html';
				$oChild->sKey 			= "ts.tuition.attendance.list";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess = ['thebing_tuition_attendance_score_progress_report', ''];
				$oChild->sTitle = (new TsStatistic\Generator\Statistic\AttendanceScoreProgress())->getTitle();
				$oChild->sL10NAddon = 'Thebing » Management';
				$oChild->iSubpoint = 1;
				$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=AttendanceScoreProgress';
				$oChild->bTranslate = false;
				$oChild->sKey 			= "ts.tuition.attendance.score_report";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= '';
				$oChild->sTitle			= 'Prüfung';
				$oChild->sL10NAddon		= "Thebing » Menü Links";
				$oChild->iSubpoint		= 0;
				$oChild->sKey 			= "ts.tuition.examination";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_examination', '');
				$oChild->sTitle			= 'Prüfungen';
				$oChild->sL10NAddon		= "Thebing » Tuition » Examination";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/TsTuition_examination';
				$oChild->sKey 			= "ts.tuition.examination.list";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_examination_sections', '');
				$oChild->sTitle			= 'Prüfungskategorien';
				$oChild->sL10NAddon		= "Thebing » Tuition » Resources » Examination Sections";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/examination/sections.html';
				$oChild->sKey 			= "ts.tuition.examination.sections";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_examination_templates', '');
				$oChild->sTitle			= 'Prüfungsvorlagen';
				$oChild->sL10NAddon		= "Thebing » Tuition » Resources » Examination Samples";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_examination_templates';
				$oChild->sKey 			= "ts.tuition.examination.templates";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= '';
				$oChild->sTitle			= 'Lehrerverwaltung';
				$oChild->sL10NAddon		= "Thebing » Menü Links";
				$oChild->iSubpoint		= 0;
				$oChild->sKey 			= "ts.tuition.teachers";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_teachers', '');
				$oChild->sTitle			= 'Lehrer';
				$oChild->sL10NAddon		= "Thebing » Tuition » Teachers";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/teachers.html';
				$oChild->sKey 			= 'ts.tuition.teachers.list';
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_teacher_contracts', '');
				$oChild->sTitle			= 'Lehrerverträge';
				$oChild->sL10NAddon		= "Thebing » Tuition » Teachers » Contracts";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_contracts/teacher';
				$oChild->sKey 			= "ts.tuition.teachers.contracts";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_teachers_absence', '');
				$oChild->sTitle			= 'Lehrer Abwesenheit';
				$oChild->sL10NAddon		= "Thebing » Tuition » Teachers » Absence";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/absence.html?item=teacher';
				$oChild->sKey 			= "ts.tuition.teachers.absence";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_marketing_teachercategories', '');
				$oChild->sTitle			= 'Lehrer Kostenkategorien';
				$oChild->sL10NAddon		= "Thebing » Marketing » Teacher Categoriese";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_teachercategories';
				$oChild->sKey 			= "ts.tuition.teachers.categories";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess = ['ts_tuition_teacher_availability', ''];
				$oChild->sTitle = 'Verfügbarkeit';
				$oChild->sL10NAddon = TsTuition\Gui2\Data\TeacherAvailability::L10NPath;
				$oChild->iSubpoint = 1;
				$oChild->sUrl = '/wdmvc/gui2/page/TsTuition_teacher_availability';
				$oChild->sKey = "ts.tuition.teachers.availability";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->sKey			= 'thebing_tuition_resources';
				$oChild->mAccess		= array('thebing_tuition_resources', '');
				$oChild->sTitle			= 'Resources';
				$oChild->sL10NAddon		= "Thebing » Menü Links";
				$oChild->iSubpoint		= 0;
				$oChild->sKey 			= "ts.tuition.resources";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resources_courses', '');
				$oChild->sTitle			= 'Kurse';
				$oChild->sL10NAddon		= Ext_Thebing_Tuition_Course_Gui2::L10N_PATH;
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_courses';
				$oChild->sKey 			= 'ts.tuition.resources.courses';
				$oTop->addChild($oChild);

                $oChild = new Ext_TC_System_Navigation_LeftItem();
                $oChild->mAccess		= array('thebing_tuition_resource_course_categories', '');
                $oChild->sTitle			= 'Kurskategorien';
                $oChild->sL10NAddon		= "Thebing » Tuition » Courses Categories";
                $oChild->iSubpoint		= 1;
                $oChild->sUrl			= '/gui2/page/Ts_courses_categories';
				$oChild->sKey 			= "ts.tuition.resources.course_categories";
                $oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_proficiency', '');
				$oChild->sTitle			= 'Leistungsstände';
				$oChild->sL10NAddon		= "Thebing » Tuition » Levels";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_tuition_level';
				$oChild->sKey 			= "ts.tuition.resources.levels";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_levelgroup', '');
				$oChild->sTitle			= 'Kurssprachen';
				$oChild->sL10NAddon		= "Thebing » Tuition » Levelgroups";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_courselanguages';
				$oChild->sKey 			= "ts.tuition.resources.course_languages";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_classrooms', '');
				$oChild->sTitle			= 'Klassenzimmer';
				$oChild->sL10NAddon		= "Thebing » Tuition » Classrooms";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_classrooms';
				$oChild->sKey 			= "ts.tuition.resources.classrooms";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resources_buildings', '');
				$oChild->sTitle			= 'Gebäude';
				$oChild->sL10NAddon		= "Thebing » Tuition » Resources » Buildings";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/admin/extensions/thebing/tuition/buildings.html';
				$oChild->sKey 			= "ts.tuition.resources.buildings";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_edit_placement_test', '');
				$oChild->sTitle			= 'Einstufungstest Einstellungen';
				$oChild->sL10NAddon		= "Thebing » Tuition » Placementtests";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/ts/tuition/gui2/page/placementtests';
				$oChild->sKey 			= "ts.tuition.resources.placementtest_questions";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_tuition_templates', '');
				$oChild->sTitle			= 'Kursvorlagen';
				$oChild->sL10NAddon		= "Thebing » Tuition » Templates";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_tuitiontemplates';
				$oChild->sKey 			= "ts.tuition.resources.templates";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resource_overview', '');
				$oChild->sTitle			= 'Own overview';
				$oChild->sL10NAddon		= "Thebing » Tuition » Own overview";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_tuition_overview';
				$oChild->sKey 			= "ts.tuition.resources.overview";
				$oTop->addChild($oChild);
				
				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resources_colors', '');
				$oChild->sTitle			= 'Klassenfarben';
				$oChild->sL10NAddon		= "Thebing » Tuition » Resources » Colors";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/Ts_tuition_colors';
				$oChild->sKey 			= "ts.tuition.resources.colors";
				$oTop->addChild($oChild);

				$oChild = new Ext_TC_System_Navigation_LeftItem();
				$oChild->mAccess		= array('thebing_tuition_resources_colors', '');
				$oChild->sTitle			= 'Abwesenheitsgründe';
				$oChild->sL10NAddon		= "Thebing » Tuition » Resources » Absence reasons";
				$oChild->iSubpoint		= 1;
				$oChild->sUrl			= '/gui2/page/TsTuition_absence_reasons';
				$oChild->sKey 			= "ts.tuition.absence.reasons";
				$oTop->addChild($oChild);

	//			$oChild = new Ext_TC_System_Navigation_LeftItem();
	//			$oChild->mAccess		= array('thebing_tuition_resources_modules', '');
	//			$oChild->sTitle			= 'Module';
	//			$oChild->sL10NAddon		= "Thebing » Tuition » Resources » Modules";
	//			$oChild->iSubpoint		= 1;
	//			$oChild->sUrl			= '/admin/extensions/thebing/tuition/modules.html';
	//			$oTop->addChild($oChild);

			}

		$this->addTopNavigation($oTop);

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_accommodation";
		$oTop->sTitle		= "Unterkunft";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_accommodation_icon', '');
		$oTop->sKey 		= "ts.accommodation";
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;
		$oTop->sIcon = 'fa-home';

		if(!Ext_Thebing_System::isAllSchools()) {

            $oChild = new Ext_TC_System_Navigation_LeftItem();
            $oChild->mAccess		= array('thebing_accommodation_communicate_overview', '');
            $oChild->sTitle			= 'Kommunikation';
            $oChild->sL10NAddon		= "Thebing » Accommodation » Overview";
            $oChild->iSubpoint		= 0;
            $oChild->sUrl			= '/gui2/page/Ts_accommodation_communication';
			$oChild->sKey 			= "ts.accommodation.communication";
            $oTop->addChild($oChild);
		}
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= '';
		$oChild->sTitle			= 'Unterbringung';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey 			= "ts.accommodation.matching";
		$oTop->addChild($oChild);

		if(!Ext_Thebing_System::isAllSchools()) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accommodation_family_matching', '');
			$oChild->sTitle			= 'Host family matching';
			$oChild->sL10NAddon		= "Thebing » Accommodation » Matching";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/accommodation/matching_hostfamily.html';
			$oChild->sKey 			= "ts.accommodation.family_matching";
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accommodation_other_matching', '');
			$oChild->sTitle			= 'Other accommodation matching';
			$oChild->sL10NAddon		= "Thebing » Accommodation » Matching";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/accommodation/matching_residence.html';
			$oChild->sKey 			= "ts.accommodation.other_matching";
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accommodation_parking_matching', '');
			$oChild->sTitle			= 'Parking';
			$oChild->sL10NAddon		= "Thebing » Accommodation » Matching";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/accommodation/matching_residence.html?view=parking';
			$oChild->sKey 			= "ts.accommodation.parking_matching";
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accommodation_mealplan', '');
			$oChild->sTitle			= 'Verpflegungsplan';
			$oChild->sL10NAddon		= "Thebing » Accommodation » Mealplan";
			$oChild->sUrl			= $this->generateUrl('TsAccommodation.meal_plan');
			$oChild->iSubpoint		= 1;
			$oChild->sKey 			= "ts.accommodation.meal_plan";
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_availability', '');
		$oChild->sTitle			= 'Verfügbarkeit';
		$oChild->sL10NAddon		= "Thebing » Accommodation » Availability";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/ts/accommodation/availability';
		$oChild->sKey 			= "ts.accommodation.availability";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_other_cleaning', '');
		$oChild->sTitle			= 'Putzplan';
		$oChild->sL10NAddon		= "Thebing » Accommodation » Room Cleaning Plan";
		$oChild->iSubpoint		= 0;
		$oChild->sUrl           = '/gui2/page/TsAccommodation_cleaning';
		$oChild->sKey 			= "ts.accommodation.cleaning";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_resources', '');
		$oChild->sTitle			= 'Resources';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey 			= "ts.accommodation.resources";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_accommodations', '');
		$oChild->sTitle			= 'Unterkunftsanbieter';
		$oChild->sL10NAddon		= Ext_Thebing_Accommodation_Gui2::L10N_PATH;
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/thebing/accommodation/accommodations.html';
		$oChild->sKey 			= "ts.accommodation.resources.providers";
		$oTop->addChild($oChild);
		
		if(!Ext_Thebing_System::isAllSchools()) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accommodation_contracts', '');
			$oChild->sTitle			= 'Unterkunftsverträge';
			$oChild->sL10NAddon		= "Thebing » Tuition » Accommodations » Contracts";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_contracts/accommodation';
			$oChild->sKey 			= "ts.accommodation.resources.contracts";
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_categories', '');
		$oChild->sTitle			= 'Kategorien';
		$oChild->sL10NAddon		= "Thebing » Accommodation » Categories";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_accommodation_categories';
		$oChild->sKey 			= "ts.accommodation.resources.categories";
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_roomtypes', '');
		$oChild->sTitle			= 'Räume';
		$oChild->sL10NAddon		= "Thebing » Accommodation » Roomtypes";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_accommodation_roomtypes';
		$oChild->sKey 			= "ts.accommodation.resources.roomtypes";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_meals', '');
		$oChild->sTitle			= 'Verpflegung';
		$oChild->sL10NAddon		= "Thebing » Accommodation » Meals";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_accommodation_meals';
		$oChild->sKey 			= "ts.accommodation.resources.meals";
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_accommodationcategories', '');
		$oChild->sTitle			= 'Kostenkategorien';
		$oChild->sL10NAddon		= "Thebing » Marketing » Accommodation Categories";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_accommodation_costcategories';
		$oChild->sKey 			= "ts.accommodation.resources.costcategories";
		$oTop->addChild($oChild);
		

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accommodation_billing_terms', '');
		$oChild->sTitle			= 'Abrechnungskategorien';
		$oChild->sL10NAddon		= "Thebing » Accounting » Accommodation";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/wdmvc/gui2/page/ts_accommodation_provider_payment_categories';
		$oChild->sKey 			= "ts.accommodation.resources.payment_categories";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= 'thebing_accommodation_requirements';
		$oChild->sTitle			= 'Voraussetzungen';
		$oChild->sL10NAddon		= "Thebing » Accounting » Voraussetzung";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl = '/wdmvc/gui2/page/TsAccommodation_requirement_list';
		$oChild->sKey = "ts.accommodation.resources.requirement_list";
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= 'thebing_accommodation_other_cleaning';
		$oChild->sTitle			= 'Reinigungsarten';
		$oChild->sL10NAddon		= "Thebing » Accommodation » Room Cleaning Types";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl = '/gui2/page/TsAccommodation_cleaning_types';
		$oChild->sKey = "ts.accommodation.resources.cleaning_types";
		$oTop->addChild($oChild);

		$this->addTopNavigation($oTop);

		if(!Ext_Thebing_System::isAllSchools()) {

			$oTop = new Ext_TC_System_Navigation_TopItem();
			$oTop->sName		= "ac_pickup";
			$oTop->sTitle		= "Transfer";
			$oTop->sL10NAddon	= "Thebing » Menü";
			$oTop->mAccess		= array('thebing_pickup_icon', '');
			$oTop->sKey 		= "ts.transfer";
			$oTop->iExtension	= 1;
			$oTop->iLoadContent = 0;
			$oTop->sIcon = 'fa-car';

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_pickup_confirmation', '');
			$oChild->sTitle			= 'Transfer';
			$oChild->sL10NAddon		= "Thebing » Pickup » Confirmation";
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= '/admin/extensions/thebing/pickup/transfer.html';
			$oChild->sKey 			= 'ts.transfer.pickup.confirmation';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_pickup_resources', '');
			$oChild->sTitle			= 'Resources';
			$oChild->sL10NAddon		= "Thebing » Menü Links";
			$oChild->iSubpoint		= 0;
			$oChild->sKey 			= 'ts.transfer.resources';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_pickup_resources_airports', '');
			$oChild->sTitle			= 'Reiseorte';
			$oChild->sL10NAddon		= "Thebing » Pickup » Destination";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/pickup/airports.html';
			$oChild->sKey 			= 'ts.transfer.resources.airports';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_pickup_resources_companies', '');
			$oChild->sTitle			= 'Anbieter';
			$oChild->sL10NAddon		= "Thebing » Pickup » Companies";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/pickup/companies.html';
			$oChild->sKey 			= 'ts.transfer.resources.companies';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_pickup_resources_packages', '');
			$oChild->sTitle			= 'Kosten und Preise';
			$oChild->sL10NAddon		= "Thebing » Marketing » Costs";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_transfer_package';
			$oChild->sKey 			= 'ts.transfer.resources.packages';
			$oTop->addChild($oChild);

			$this->addTopNavigation($oTop);

			$oTop = new Ext_TC_System_Navigation_TopItem();
			$oTop->sName		= "insurance";
			$oTop->sTitle		= "Versicherungen";
			$oTop->sL10NAddon	= "Thebing » Insurances";
			$oTop->mAccess		= array('thebing_insurance_icon', '');
			$oTop->sKey 		= 'ts.insurance';
			$oTop->iExtension	= 1;
			$oTop->iLoadContent = 0;
			$oTop->sIcon = 'fa-shield';

            $oChild = new Ext_TC_System_Navigation_LeftItem();
            $oChild->mAccess		= array('thebing_insurance_icon', '');
            $oChild->sTitle			= 'Kunden';
            $oChild->sL10NAddon		= 'Thebing » Insurances » Customer';
            $oChild->iSubpoint		= 0;
            $oChild->sUrl			= '/gui2/page/Ts_insurances_customer';
			$oChild->sKey 			= 'ts.insurance.customers';
            $oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_insurance_icon', '');
			$oChild->sTitle			= 'Ressourcen';
			$oChild->sL10NAddon		= 'Thebing » Insurances';
			$oChild->iSubpoint		= 0;
			$oChild->sKey 			= 'ts.insurance.resources';
			$oTop->addChild($oChild);
					
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_insurance_icon', '');
			$oChild->sTitle			= 'Anbieter';
			$oChild->sL10NAddon		= 'Thebing » Insurances » Provider';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_insurance_provider';
			$oChild->sKey 			= 'ts.insurance.resources.provider';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_insurance_icon', '');
			$oChild->sTitle			= 'Versicherungen';
			$oChild->sL10NAddon		= 'Thebing » Insurances » Insurance';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_insurance';
			$oChild->sKey 			= 'ts.insurance.resources.insurances';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_insurance_icon', '');
			$oChild->sTitle			= 'Wochen';
			$oChild->sL10NAddon		= 'Thebing » Insurances » Weeks';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_insurance_week';
			$oChild->sKey 			= 'ts.insurance.resources.weeks';
			$oTop->addChild($oChild);

			$this->addTopNavigation($oTop);

			$oTop = new Ext_TC_System_Navigation_TopItem();
			$oTop->sName		= "ac_activities_icon";
			$oTop->sTitle		= "Aktivitäten";
			$oTop->sL10NAddon	= "TS » Activities"; // Auch als Konstante
			$oTop->mAccess		= array('thebing_activities_icon', '');
			$oTop->sKey 		= "ts.activities";
			$oTop->iExtension	= 1;
			$oTop->iLoadContent = 0;
			$oTop->sIcon = 'fa-bicycle';

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_activities_icon', ''];
			$oChild->sTitle = 'Planung';
			$oChild->sL10NAddon = "TS » Activities";
			$oChild->sUrl = '/ts/activities/scheduling';
			$oChild->iSubpoint = 0;
			$oChild->sKey = 'ts.activities.scheduling';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_activities_icon', ''];
			$oChild->sTitle = 'Gebuchte Aktivitäten';
			$oChild->sL10NAddon = "TS » Activities";
			$oChild->sUrl = '/gui2/page/TsActivities_booked_activities';
			$oChild->iSubpoint = 0;
			$oChild->sKey = 'ts.activities.booked_activities';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_activities_icon', '');
			$oChild->sTitle			= 'Ressourcen';
			$oChild->sL10NAddon		= "TS » Activities";
			$oChild->iSubpoint		= 0;
			$oChild->sKey = 'ts.activities.resources';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_activities_icon', '');
			$oChild->sTitle			= 'Aktivitäten';
			$oChild->sL10NAddon		= "TS » Activities";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/TsActivities_activities';
			$oChild->sKey = 'ts.activities.resources.activities';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_activities_icon', '');
			$oChild->sTitle			= 'Anbieter';
			$oChild->sL10NAddon		= "TS » Activities";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/TsActivities_activities_providers';
			$oChild->sKey = 'ts.activities.resources.providers';
			$oTop->addChild($oChild);

			$this->addTopNavigation($oTop);

		}

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_accounting";
		$oTop->sTitle		= "Buchhaltung";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_accounting_icon', '');
		$oTop->sKey 		= "ts.accounting";
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;

		$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		if($oSchool) {
			$iCurrencyId = $oSchool->getCurrency();

			$oCurrency = Ext_Thebing_Currency::getInstance($iCurrencyId);
			$oTop->sIcon = AdminLte\Helper\Fontawesome::getCurrencyIcon($oCurrency->iso4217);
		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_payment_overview', '');
		$oChild->sTitle			= 'Rechnungsübersicht';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.accounting.invoices';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_payment_overview', '');
		$oChild->sTitle			= 'Allgemeine Rechnungsübersicht';
		$oChild->sL10NAddon		= "Thebing » Invoice » Overview";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/ts/accounting/invoice_overview.html';
		$oChild->sKey = 'ts.accounting.invoices_overview';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_release_documents_list', '');
		$oChild->sTitle			= 'Dokumentenfreigabe';
		$oChild->sL10NAddon		= "Thebing » Invoice » Release";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/ts/document/release.html';
		$oChild->sKey = 'ts.accounting.invoices_release';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= 'thebing_accounting_booking_stack';
		$oChild->sTitle			= 'Buchungsstapel';
		$oChild->sL10NAddon		= "Thebing » Invoice » Booking Stack";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/ts_booking_stack';
		$oChild->sKey = 'ts.accounting.booking_stack';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = ['thebing_accounting_proforma', ''];
		$oChild->sTitle = 'Proforma umwandeln';
		$oChild->sL10NAddon = "Thebing » Accounting » Proforma";
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/gui2/page/ts_document_proforma';
		$oChild->sKey = 'ts.accounting.proformas';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = ['ts_accounting_partialinvoices', ''];
		$oChild->sTitle = 'Teilrechnungen';
		$oChild->sL10NAddon = "Thebing » Accounting";
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/gui2/page/ts_accounting_partial_invoices';
		$oChild->sKey = 'ts.accounting.partial_invoices';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_release_documents_list', '');
		$oChild->sTitle			= 'Einzahlungen';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.accounting.payments';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_client_payments', '');
		$oChild->sTitle			= 'Kundenzahlungen';
		$oChild->sL10NAddon		= "Thebing » Invoice » Inbox";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_students_payments';
		$oChild->sKey = 'ts.accounting.payments_students';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_agency_payments', '');
		$oChild->sTitle			= 'Agenturzahlungen';
		$oChild->sL10NAddon		= "Thebing » Accounting » Agency Payments";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/thebing/accounting/agency_payments.html';
		$oChild->sKey = 'ts.accounting.payments_agency';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_incoming_inquiry_payments', '');
		$oChild->sTitle			= 'Zahlungseingänge';
		$oChild->sL10NAddon		= "Thebing » Accounting » Incoming inquiry payments";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_incoming_inquiry_payments';
		$oChild->sKey = 'ts.accounting.payments_inquiry';
		$oTop->addChild($oChild);

        $oChild = new Ext_TC_System_Navigation_LeftItem();
        $oChild->mAccess		= array('thebing_accounting_incoming_payments', '');
        $oChild->sTitle			= 'Payment details';
        $oChild->sL10NAddon		= "Thebing » Accounting » Payment details";
        $oChild->iSubpoint		= 1;
        $oChild->sUrl			= '/gui2/page/Ts_inquiry_payment_details';
		$oChild->sKey = 'ts.accounting.payments_inquiry_details';
        $oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_incoming_accounts_receivable', '');
		$oChild->sTitle			= 'Außenstände';
		$oChild->sL10NAddon		= "Thebing » Accounting » Receivables";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_invoice_receivables';
		$oChild->sKey = 'ts.accounting.invoices_receivables';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = ['thebing_accounting_assign_client_payments', ''];
		$oChild->sTitle = 'Zahlungen zuweisen';
		$oChild->sL10NAddon = "Thebing » Accounting » Assign client payments";
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/wdmvc/gui2/page/ts_inquiry_payments_unallocated';
		$oChild->sKey = 'ts.accounting.payments_unallocated';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = ['ts_accounting_debtors_report', ''];
		$oChild->sTitle = 'Debitoren';
		$oChild->sL10NAddon = "Thebing » Accounting » Debtors report";
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/gui2/page/tsAccounting_debtors_report';
		$oChild->sKey = 'ts.accounting.debtors_report';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_release_documents_list', '');
		$oChild->sTitle			= 'Auszahlungen';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.accounting.provisions';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_account_provision', '');
		$oChild->sTitle			= 'Provision auszahlen';
		$oChild->sL10NAddon		= "Thebing » Accounting » Provision";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/TsAccounting_provision';
		$oChild->sKey = 'ts.accounting.provisions_list';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_manual_creditnote', '');
		$oChild->sTitle			= 'Manuelle Gutschriften';
		$oChild->sL10NAddon		= "Thebing » Accounting » Manual Transactions";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/TsAccounting_manual_creditnotes/main';
		$oChild->sKey = 'ts.accounting.provisions_manual_creditnoted';
		$oTop->addChild($oChild);

		if(!$bAllSchools) {
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_incorrect_accounting', '');
			$oChild->sTitle			= 'Überbezahlungen';
			$oChild->sL10NAddon		= "Thebing » Accounting » Incorrect Accountings";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_accounting_erroraccountings';
			$oChild->sKey = 'ts.accounting.incorrect_accountings';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_cheque', '');
			$oChild->sTitle			= 'Schecks';
			$oChild->sL10NAddon		= Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart();
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_accounting_cheque';
			$oChild->sKey = 'ts.accounting.cheque';
			$oTop->addChild($oChild);
			
		}

		if(!$bAllSchools) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_teacher_payments', '');
			$oChild->sTitle			= 'Anbieter bezahlen';
			$oChild->sL10NAddon		= "Thebing » Menü Links";
			$oChild->iSubpoint		= 0;
			$oChild->sKey = 'ts.accounting.provider_payments';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_teacher_payments', '');
			$oChild->sTitle			= 'Lehrer bezahlen';
			$oChild->sL10NAddon		= "Thebing » Accounting » Teachers";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/TsAccounting_teacher_payments';
			$oChild->sKey = 'ts.accounting.provider_payments.teacher';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_teacher_payments_groupings', '');
			$oChild->sTitle			= 'Bezahlte Lehrer';
			$oChild->sL10NAddon		= "Thebing » Accounting » Teachers";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/ts/accounting/payment_overview_teacher.html';
			$oChild->sKey = 'ts.accounting.provider_payments.teacher_overview';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_accommodation_payments', '');
			$oChild->sTitle			= 'Unterkunftsanbieter bezahlen';
			$oChild->sL10NAddon		= "Thebing » Accounting » Accommodation";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/wdmvc/gui2/page/ts_accommodation_provider_payments';
			$oChild->sKey = 'ts.accounting.provider_payments.accommodation';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_accommodation_payments_groupings', '');
			$oChild->sTitle			= 'Bezahlte Unterkünfte';
			$oChild->sL10NAddon		= "Thebing » Accounting » Accommodations";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/ts/accounting/payment_overview_accommodation.html';
			$oChild->sKey = 'ts.accounting.provider_payments.accommodation_overview';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_pickup_payments', '');
			$oChild->sTitle			= 'Transfer bezahlen';
			$oChild->sL10NAddon		= "Thebing » Accounting » Pickup";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_accounting_pickup';
			$oChild->sKey = 'ts.accounting.provider_payments.transfer';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_accounting_pickup_payments', '');
			$oChild->sTitle			= 'Bezahlte Transfers';
			$oChild->sL10NAddon		= "Thebing » Accounting » Pickup";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/ts/accounting/payment_overview_transfer.html';
			$oChild->sKey = 'ts.accounting.provider_payments.transfer_overview';
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_accounting_resources', '');
		$oChild->sTitle			= 'Ressourcen';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.accounting.resources';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_companies', '');
		$oChild->sTitle			= 'Firmen';
		$oChild->sL10NAddon		= \TsAccounting\Gui2\Data\Company::L10N_PATH;
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/gui2/page/ts_company";
		$oChild->sKey = 'ts.accounting.resources.company';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_vat', '');
		$oChild->sTitle			= 'Steuersätze';
		$oChild->sL10NAddon		= 'Thebing » Admin » Accounting';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/tc/admin/vat.html';
		$oChild->sKey = 'ts.accounting.resources.vat';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_company_template_receipttext', '');
		$oChild->sTitle			= 'Vorlagen für Belegtexte';
		$oChild->sL10NAddon		= 'Thebing » Admin » Companies » Accounting';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/ts/company/template/receipt_text.html';
		$oChild->sKey = 'ts.accounting.resources.receipt_text';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = array('thebing_admin_payment_methods', '');
		$oChild->sTitle = 'Zahlmethoden';
		$oChild->sL10NAddon = "Thebing » Admin » Payments";
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/gui2/page/Ts_payment_methods';
		$oChild->sKey = 'ts.accounting.resources.payment_methods';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = ['thebing_marketing_payment_groups', ''];
		$oChild->sTitle = 'Zahlungsbedingungen';
		$oChild->sL10NAddon = Ext_TS_Payment_Condition_Gui2_Data::L10N_PATH;
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/gui2/page/ts_payment_conditions';
		$oChild->sKey = 'ts.accounting.resources.payment_conditions';
		$oTop->addChild($oChild);

		$this->addTopNavigation($oTop);

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_marketing";
		$oTop->sTitle		= "Marketing";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_marketing_icon', '');
		$oTop->sKey 		= "ts.marketing";
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;
		$oTop->sIcon = 'fa-rocket';//fa-bullhorn';

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_resources', '');
		$oChild->sTitle			= 'Agenturen';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.marketing.agencies';
		$oTop->addChild($oChild);

		/*$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_agencies', '');
		$oChild->sTitle			= 'Agenturen OLD';
		$oChild->sL10NAddon		= "Thebing » Marketing » Agenciegroups";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/thebing/marketing/agencies.html';
		$oTop->addChild($oChild);*/

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_agencies', '');
		$oChild->sTitle			= 'Agenturen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Agenciegroups";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/ts/companies/gui2/page/agencies';
		$oChild->sKey = 'ts.marketing.agencies.list';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_agency_categories', '');
		$oChild->sTitle			= 'Agenturkategorien';
		$oChild->sL10NAddon		= "Thebing » Admin » Agenturkategorien";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_agencycategory';
		$oChild->sKey = 'ts.marketing.agencies.categories';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_agenciegroups', '');
		$oChild->sTitle			= 'Agenturgruppen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Agenciegroups";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_agenciesgroups';
		$oChild->sKey = 'ts.marketing.agencies.groups';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_provisions', '');
		$oChild->sTitle			= 'Provisionsgruppen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Provisions";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_marketing_provisions';
		$oChild->sKey = 'ts.marketing.agencies.provisions';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_agency_list', '');
		$oChild->sTitle			= 'Agenturlisten';
		$oChild->sL10NAddon		= "Thebing » Marketing » Agency Lists";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_marketing_agency_list';
		$oChild->sKey = 'ts.marketing.agencies.agencies_lists';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_sponsoring', '');
		$oChild->sTitle			= 'Sponsoren';
		$oChild->sL10NAddon		= "Thebing » Marketing » Sponsoring";
		$oChild->iSubpoint		= 0;
		$oChild->sUrl			= '/ts-sponsoring/sponsoring/page';
		$oChild->sKey			= 'ts.marketing.sponsoring';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_marketing_resources', '');
		$oChild->sTitle			= 'Feedback Formular';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.marketing.feedback';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_marketing_topics', '');
		$oChild->sTitle			= "Themen anlegen";
		$oChild->sL10NAddon		= "Thebing » Marketing » Topics";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/marketing/topics.html";
		$oChild->sKey = 'ts.marketing.feedback.topics';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_marketing_questions', '');
		$oChild->sTitle			= "Fragen anlegen";
		$oChild->sL10NAddon		= "Thebing » Marketing » Questions";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/ts/marketing/feedback/questions.html";
		$oChild->sKey = 'ts.marketing.feedback.questions';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_marketing_ratings', '');
		$oChild->sTitle			= "Skala anlegen";
		$oChild->sL10NAddon		= "Thebing » Marketing » Ratings";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/marketing/ratings.html";
		$oChild->sKey = 'ts.marketing.feedback.ratings';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_marketing_questionnaires', '');
		$oChild->sTitle			= "Fragebogen anlegen";
		$oChild->sL10NAddon		= "Thebing » Marketing » Questionnaires";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/marketing/questionnaires.html";
		$oChild->sKey = 'ts.marketing.feedback.questionnaires';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = array('thebing_marketing_complaints', '');
		$oChild->sTitle = 'Beschwerden';
		$oChild->sL10NAddon = \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH;
		$oChild->iSubpoint = 0;
		$oChild->sUrl = '/wdmvc/tc-complaints/complaint/page';
		$oChild->sKey = 'ts.marketing.complaints';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_special', '');
		$oChild->sTitle			= 'Spezial';
		$oChild->sL10NAddon		= "Thebing » Marketing » Special";
		$oChild->iSubpoint		= 0;
		$oChild->sUrl			= '/gui2/page/Ts_marketing_special';
		$oChild->sKey = 'ts.marketing.specials';
		$oTop->addChild($oChild);

		if(!Ext_Thebing_System::isAllSchools()) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_prices', '');
			$oChild->sTitle			= 'Kosten & Preise';
			$oChild->sL10NAddon		= "Thebing » Menü Links";
			$oChild->iSubpoint		= 0;
			$oChild->sKey = 'ts.marketing.prices';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_prices', '');
			$oChild->sTitle			= 'Preise - Allgemein';
			$oChild->sL10NAddon		= "Thebing » Marketing » Prices";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/marketing/prices.html';
			$oChild->sKey = 'ts.marketing.prices.main';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_prices', '');
			$oChild->sTitle			= 'Preise - Versicherung';
			$oChild->sL10NAddon		= "Thebing » Marketing » Prices";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/marketing/prices_insurances.html';
			$oChild->sKey = 'ts.marketing.prices.insurances';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_activities_icon', '');
			$oChild->sTitle			= 'Preise - Aktivitäten';
			$oChild->sL10NAddon		= "Thebing » Marketing » Activities";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= $this->generateUrl('TsActivities.prices');
			$oChild->sKey = 'ts.marketing.prices.activities';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_costs', '');
			$oChild->sTitle			= 'Kosten - Allgemein';
			$oChild->sL10NAddon		= "Thebing » Marketing » Costs";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/marketing/costs.html';
			$oChild->sKey = 'ts.marketing.prices.costs';
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = array('thebing_marketing_resources', '');
		$oChild->sTitle = 'Resources Marketing';
		$oChild->sL10NAddon = "Thebing » Menü Links";
		$oChild->iSubpoint = 0;
		$oChild->sKey = 'ts.marketing.instruments';
		$oTop->addChild($oChild);

        $oChild = new Ext_TC_System_Navigation_LeftItem();
        $oChild->mAccess = array('core_marketing_referrers', '');
        $oChild->sTitle = 'Wie sind Sie auf uns aufmerksam geworden?';
        $oChild->sL10NAddon = 'Thebing » Marketing » Referrer';
        $oChild->iSubpoint = 1;
        $oChild->sUrl = '/gui2/page/Tc_referrers/school';
		$oChild->sKey = 'ts.marketing.instruments.referrer';
        $oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = array('thebing_marketing_status', '');
		$oChild->sTitle = 'Schülerstatus';
		$oChild->sL10NAddon = "Thebing » Marketing » Student Status";
		$oChild->iSubpoint = 1; 
		$oChild->sUrl = '/gui2/page/Ts_marketing_student_status_list';
		$oChild->sKey = 'ts.marketing.instruments.student_status';
		$oTop->addChild($oChild);

		if(!Ext_Thebing_System::isAllSchools()) {
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_subject', '');
			$oChild->sTitle			= 'Betreff';
			$oChild->sL10NAddon		= "Thebing » Marketing » Betreff";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_marketing_subject';
			$oChild->sKey = 'ts.marketing.instruments.subject';
			$oTop->addChild($oChild);
			
 			$oChild = new Ext_TC_System_Navigation_LeftItem();
 			$oChild->mAccess		= array('thebing_marketing_activity', '');
 			$oChild->sTitle			= 'Aktivität';
 			$oChild->sL10NAddon		= "Thebing » Marketing » Aktivität";
 			$oChild->iSubpoint		= 1;
 			$oChild->sUrl			= '/gui2/page/Ts_marketing_activity';
			$oChild->sKey = 'ts.marketing.instruments.activity';
 			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_resources_complaints', '');
			$oChild->sTitle			= 'Beschwerdenkategorien';
			$oChild->sL10NAddon		= \TsComplaints\Gui2\Data\Complaint::TRANSLATION_PATH;
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/wdmvc/tc-complaints/category/page';
			$oChild->sKey = 'ts.marketing.instruments.complaints_categories';
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_resources', '');
		$oChild->sTitle			= 'Resources';
		$oChild->sL10NAddon		= "Thebing » Menü Links";
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.marketing.resources';
		$oTop->addChild($oChild);

		if(!Ext_Thebing_System::isAllSchools()) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_admin_reasons', '');
			$oChild->sTitle			= 'Gründe';
			$oChild->sL10NAddon		= "Thebing » Admin » Reasons";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_reasons';
			$oChild->sKey = 'ts.marketing.resources.reasons';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_saisons', '');
			$oChild->sTitle			= 'Saison';
			$oChild->sL10NAddon		= "Thebing » Marketing » Saison";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_seasons';
			$oChild->sKey = 'ts.marketing.resources.seasons';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_public_holidays', '');
			$oChild->sTitle			= 'Feiertage';
			$oChild->sL10NAddon		= "Thebing » Marketing » Public Holiday";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_public_holidays';
			$oChild->sKey = 'ts.marketing.resources.public_holidays';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_school_holidays', '');
			$oChild->sTitle			= 'Schulferien';
			$oChild->sL10NAddon		= "Thebing » Marketing » School Holiday";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/absence.html?item=holiday';
			$oChild->sKey = 'ts.marketing.resources.school_holidays';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_additional_costs', '');
			$oChild->sTitle			= 'Zusätzliche Kosten';
			$oChild->sL10NAddon		= "Thebing » Marketing » Additionalcosts";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_marketing_additionalcosts';
			$oChild->sKey = 'ts.marketing.resources.additionalcosts';
			$oTop->addChild($oChild);

//						$oChild = new Ext_TC_System_Navigation_LeftItem();
//						$oChild->mAccess		= array('thebing_marketing_fixcosts', '');
//						$oChild->sTitle			= 'Fixkosten';
//						$oChild->sL10NAddon		= "Thebing » Marketing » Fixcosts";
//						$oChild->iSubpoint		= 1;
//						$oChild->sUrl			= '/admin/extensions/thebing/marketing/fixcosts.html';
//						$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_cancelation_fee', '');
			$oChild->sTitle			= 'Stornogebühren';
			$oChild->sL10NAddon		= "Thebing » Marketing » Stornofee";
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/admin/extensions/thebing/marketing/cancellationfees.html';
			$oChild->sKey = 'ts.marketing.resources.cancellationfees';
			$oTop->addChild($oChild);

		}
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_lektionen', '');
		$oChild->sTitle			= 'Lektionen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Course units";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_marketing_course_units';
		$oChild->sKey = 'ts.marketing.resources.course_units';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_weeks', '');
		$oChild->sTitle			= 'Kostenwochen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Kostenwochen";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_marketing_cost_weeks';
		$oChild->sKey = 'ts.marketing.resources.cost_weeks';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_marketing_weeks', '');
		$oChild->sTitle			= 'Preiswochen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Weeks";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_marketing_weeks';
		$oChild->sKey = 'ts.marketing.resources.weeks';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_countrygroups', '');
		$oChild->sTitle			= 'Ländergruppen';
		$oChild->sL10NAddon		= "Thebing » Marketing » Country Lists";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_marketing_country_groups';
		$oChild->sKey 			= 'ts.marketing.country_groups';
		$oTop->addChild($oChild);


		$this->addTopNavigation($oTop);

		if(!empty($aSchools)) {

			$oTop = new Ext_TC_System_Navigation_TopItem();
			$oTop->sName		= "ac_management";
			$oTop->sTitle		= "Auswertungen";
			$oTop->sL10NAddon	= "Thebing » Menü";
			$oTop->mAccess		= array('thebing_management_icon', '');
			$oTop->sKey 		= "ts.management";
			$oTop->iExtension	= 1;
			$oTop->iLoadContent = 0;
			$oTop->sIcon = 'fa-line-chart';

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_reports', '');
			$oChild->sTitle			= 'Übersicht';
			$oChild->sL10NAddon		= Ext_Thebing_Management_Statistic::$_sDescription;
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= '/admin/extensions/thebing_results.html';
			$oChild->sKey = 'ts.management.reporting.legacy';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['ts_reporting_overview', ''];
			$oChild->sTitle = 'Übersicht v2';
			$oChild->sL10NAddon = TsReporting\Entity\Report::TRANSLATION_PATH;
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/ts/reports';
			$oChild->sKey = 'ts.management.reporting';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_reports_standard', '');
			$oChild->sTitle			= 'Standardstatistiken 1';
			$oChild->sL10NAddon		= 'Thebing » Management';
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= '/admin/extensions/thebing_results.html?page_id=system';
			$oChild->sKey = 'ts.management.reporting.legacy_system';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_reports_standard2', '');
			$oChild->sTitle			= Ext_Thebing_Management_Statistic_Static_UkQuartlerlyReport::getTitle();
			$oChild->sL10NAddon		= 'Thebing » Management';
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= '/admin/extensions/thebing/management/static/ukquartlerlyreport.php';
			$oChild->sKey = 'ts.management.reporting.static.ukquartlerlyreport';
			$oChild->bTranslate		= false;
			$oTop->addChild($oChild);

//			$oChild = new Ext_TC_System_Navigation_LeftItem();
//			$oChild->mAccess		= array('thebing_management_reports_persessionrevenue', '');
//			$oChild->sTitle			= Ext_Thebing_Management_Statistic_Static_PerSessionRevenue::getTitle();
//			$oChild->sL10NAddon		= 'Thebing » Management';
//			$oChild->iSubpoint		= 0;
//			$oChild->sUrl			= '/admin/extensions/thebing/management/static/persessionrevenue.html';
//			$oChild->bTranslate		= false;
//			$oTop->addChild($oChild);
//
//			$oChild = new Ext_TC_System_Navigation_LeftItem();
//			$oChild->mAccess = array('thebing_management_reports_prepaidpersession', '');
//			$oChild->sTitle = Ext_Thebing_Management_Statistic_Static_PrepaidPerSession::getTitle();
//			$oChild->sL10NAddon = 'Thebing » Management';
//			$oChild->iSubpoint = 0;
//			$oChild->sUrl = '/admin/extensions/thebing/management/static/prepaidpersession.html';
//			$oChild->bTranslate = false;
//			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_reports_booking_export_per_course', '');
			$oChild->sTitle			= Ext_Thebing_Management_Statistic_Static_BookingExportPerCourse::getTitle();
			$oChild->sL10NAddon		= 'Thebing » Management';
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= '/admin/extensions/thebing/management/static/bookingexportpercourse.html';
			$oChild->sKey = 'ts.management.reporting.static.bookingexportpercourse';
			$oChild->bTranslate		= false;
			$oTop->addChild($oChild);

//			$oChild = new Ext_TC_System_Navigation_LeftItem();
//			$oChild->mAccess		= array('thebing_management_reports_lsftaxdeclaration', '');
//			$oChild->sTitle			= Ext_Thebing_Management_Statistic_Static_LsfTaxDeclaration::getTitle();
//			$oChild->sL10NAddon		= 'Thebing » Management';
//			$oChild->iSubpoint		= 0;
//			$oChild->sUrl			= '/admin/extensions/thebing/management/static/lsftaxdeclaration.html';
//			$oChild->bTranslate		= false;
//			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_reports_mothertongueperinbox', '');
			$oChild->sTitle			= Ext_Thebing_Management_Statistic_Static_MotherTonguePerInbox::getTitle();
			$oChild->sL10NAddon		= 'Thebing » Management';
			$oChild->iSubpoint		= 0;
			$oChild->sUrl			= '/admin/extensions/thebing/management/static/mothertongueperinbox.html';
			$oChild->sKey = 'ts.management.reporting.static.mothertongueperinbox';
			$oChild->bTranslate		= false;
			$oTop->addChild($oChild);

			// Funktioniert seit der Umstellung nicht mehr (#8838)
//				$oChild = new Ext_TC_System_Navigation_LeftItem();
//				$oChild->mAccess = ['thebing_management_reports_due_payments', ''];
//				$oChild->sTitle = (new TsStatistic\Generator\Statistic\DuePayments())->getTitle();
//				$oChild->sL10NAddon = 'Thebing » Management';
//				$oChild->iSubpoint = 0;
//				$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=DuePayments';
//				$oChild->bTranslate = false;
//				$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_feedback_provider_sum', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\FeedbackSum())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=FeedbackSum';
			$oChild->sKey = 'ts.management.reporting.static.feedbacksum';
			$oChild->bTranslate = false;
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_agency_studentweeks_yearandcountry', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\Agency\StudentWeeksPerYearAndCountry())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=Agency/StudentWeeksPerYearAndCountry';
			$oChild->sKey = 'ts.management.reporting.static.studentweeksperyearandcountry';
			$oChild->bTranslate = false;
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_agency_paymentrevenue', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\Agency\PaymentRevenue())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=Agency/PaymentRevenue';
			$oChild->sKey = 'ts.management.reporting.static.agencypaymentrevenue';
			$oChild->bTranslate = false;
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_payment_deferredincome', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\Payment\DeferredIncome())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=Payment/DeferredIncome';
			$oChild->sKey = 'ts.management.reporting.static.paymentdeferredincome';
			$oChild->bTranslate = false;
			$oTop->addChild($oChild);

//			$oChild = new Ext_TC_System_Navigation_LeftItem();
//			$oChild->mAccess = ['thebing_management_reports_payment_deferredincome', ''];
//			$oChild->sTitle = (new TsStatistic\Generator\Statistic\Payment\DeferredIncomePayment())->getTitle();
//			$oChild->sL10NAddon = 'Thebing » Management';
//			$oChild->iSubpoint = 0;
//			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=Payment/DeferredIncomePayment';
//			$oChild->bTranslate = false;
//			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_payment_debtorreport', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\Payment\DebtorReport())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=Payment/DebtorReport';
			$oChild->sKey = 'ts.management.reporting.static.paymentdebtorreport';
			$oChild->bTranslate = false;
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_standard2', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\Quic())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=Quic';
			$oChild->sKey = 'ts.management.reporting.static.quic';
			$oChild->bTranslate = false;
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['thebing_management_reports_course_schedule', ''];
			$oChild->sTitle = (new TsStatistic\Generator\Statistic\CoursePlanReport())->getTitle();
			$oChild->sL10NAddon = 'Thebing » Management';
			$oChild->iSubpoint = 0;
			$oChild->sUrl = '/wdmvc/ts-statistic/statistic/page?statistic=CoursePlanReport';
			$oChild->bTranslate = false;
			$oChild->sKey = 'ts.management.reporting.static.courseplanereport';
			$oTop->addChild($oChild);

			$oPages = new Ext_Thebing_Management_Page();
			$aPages = $oPages->getListByUserRight();

			foreach((array)$aPages as $iPageID => $sPage) {
				if(
					!empty($iPageID) &&
					!empty($sPage)
				) {
					$oChild = new Ext_TC_System_Navigation_LeftItem();
					$oChild->mAccess		= array('thebing_management_reports', '');
					$oChild->sTitle			= $sPage;
					$oChild->bTranslate		= false;
					$oChild->iSubpoint		= 0;
					$oChild->sUrl			= '/admin/extensions/thebing_results.html?page_id=' . $iPageID;
					$oChild->sKey = 'ts.management.reporting.page_'.$iPageID;
					$oTop->addChild($oChild);
				}
			}

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_resources', '');
			$oChild->sTitle			= 'Ressourcen';
			$oChild->sL10NAddon		= 'Thebing » Management';
			$oChild->iSubpoint		= 0;
			$oChild->sKey = 'ts.management.resources';
			$oTop->addChild($oChild);
			
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_pages', '');
			$oChild->sTitle			= 'Seiten';
			$oChild->sL10NAddon		= 'Thebing » Management » Seiten';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_pages';
			$oChild->sKey = 'ts.management.resources.pages';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_management_statistics', '');
			$oChild->sTitle			= 'Statistiken';
			$oChild->sL10NAddon		= 'Thebing » Management » Statistiken';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_statistics';
			$oChild->sKey = 'ts.management.resources.statistics.legacy';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['ts_reporting_definitions', ''];
			$oChild->sTitle = 'Statistiken v2';
			$oChild->sL10NAddon = TsReporting\Entity\Report::TRANSLATION_PATH;
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/gui2/page/TsReporting_reports';
			$oChild->sKey = 'ts.management.resources.statistics';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = ['ts_reporting_definitions', ''];
			$oChild->sTitle = 'Altersgruppen';
			$oChild->sL10NAddon = TsReporting\Entity\Report::TRANSLATION_PATH;
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/gui2/page/TsReporting_age_groups';
			$oChild->sKey = 'ts.management.resources.age_groups';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = array('thebing_management_resources', '');
			$oChild->sTitle = 'Einstellungen';
			$oChild->sL10NAddon = 'Thebing » Management » Statistiken';
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/admin/extensions/thebing/management/config.html';
			$oChild->sKey = 'ts.management.resources.config';
			$oTop->addChild($oChild);

			$this->addTopNavigation($oTop);

		}

		$oTop = new Ext_TC_System_Navigation_TopItem();
		$oTop->sName		= "ac_admin";
		$oTop->sTitle		= "Admin";
		$oTop->sL10NAddon	= "Thebing » Menü";
		$oTop->mAccess		= array('thebing_admin_icon', '');
		$oTop->sKey 		= "ts.admin";
		$oTop->iExtension	= 1;
		$oTop->iLoadContent = 0;
		$oTop->sIcon = 'fa-gears';

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= '';
		$oChild->sTitle			= 'Mitarbeiter';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.admin.users';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_users', '');
		$oChild->sTitle			= 'Liste';
		$oChild->sL10NAddon		= 'Thebing » Admin » User';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/tc/admin/user.html';
		$oChild->sKey = 'ts.admin.users.list';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_usergroups', '');
		$oChild->sTitle			= 'Benutzergruppen';
		$oChild->sL10NAddon		= 'Thebing » Admin » Benutzergruppen';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_usergroups';
		$oChild->sKey = 'ts.admin.user.groups';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = array('thebing_admin_users_functions', '');
		$oChild->sTitle	= 'Kategorien';
		$oChild->sL10NAddon	= 'Thebing » Admin » Kategorien';
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/gui2/page/Tc_system_type_mappings/users';
		$oChild->sKey = 'ts.admin.user.system_types';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_contacts', '');
		$oChild->sTitle			= 'Kontakte';
		$oChild->sL10NAddon		= 'Thebing » Admin » Kontakte';
		$oChild->iSubpoint		= 0;
		$oChild->sUrl = '/gui2/page/ts_contact';
		$oChild->sKey = 'ts.admin.contacts';
		$oTop->addChild($oChild);

		$oChildTop = new Ext_TC_System_Navigation_LeftItem();
		$oChildTop->mAccess		= array('thebing_admin_icon', '');
		$oChildTop->sTitle			= 'Vorlagen';
		$oChildTop->sL10NAddon		= 'Thebing » Admin » Templates';
		$oChildTop->iSubpoint		= 0;
		$oChildTop->sKey = 'ts.admin.templates';
		$oTop->addChild($oChildTop);

		if(\Core\Handler\SessionHandler::getInstance()->get('sid') > 0) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_admin_file_upload', '');
			$oChild->sTitle			= 'Dateiupload';
			$oChild->sL10NAddon		= 'Thebing » Admin » Upload';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_uploads';
			$oChild->sKey = 'ts.admin.templates.uploads';
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_template_types', '');
		$oChild->sTitle			= 'PDF Layouts';
		$oChild->sL10NAddon		= 'Thebing » Admin » Vorlagen Typen';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/thebing/admin/templatetypes.html';
		$oChild->sKey = 'ts.admin.templates.template_types';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_template_types', '');
		$oChild->sTitle			= 'PDF Vorlagen';
		$oChild->sL10NAddon		= 'Thebing » Admin » PDF Vorlagen';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_pdf_templates';
		$oChild->sKey = 'ts.admin.templates.pdf';
		$oTop->addChild($oChild);

		/*$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_email_layouts', '');
		$oChild->sTitle			= 'E-Mail Layouts';
		$oChild->sL10NAddon		= 'Thebing » Admin » E-mail Layouts';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_email_layouts';
		$oChild->sKey = 'ts.admin.templates.email_layouts_legacy';
		$oTop->addChild($oChild);*/

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_templates_email_layouts', '');
		$oChild->sTitle			= 'E-Mail Layouts';
		$oChild->sL10NAddon		= $oChildTop->sL10NAddon.' E-mail Layouts';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_email_layouts';
		$oChild->sKey = 'ts.admin.templates.email_layouts';
		$oTop->addChild($oChild);

		/*$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_email_templates', '');
		$oChild->sTitle			= 'E-Mail Vorlagen';
		$oChild->sL10NAddon		= 'Thebing » Admin » E-mail Templates';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_email_templates';
		$oChild->sKey = 'ts.admin.templates.templates_legacy';
		$oTop->addChild($oChild);*/

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_templates_email', '');
		$oChild->sTitle			= 'E-Mail Vorlagen';
		$oChild->sL10NAddon		= $oChildTop->sL10NAddon." E-mail Templates";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/tc/admin/template/email.html';
		$oChild->sKey = 'ts.admin.templates.email';
		$oTop->addChild($oChild);

		/*$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_communication_signatures', '');
		$oChild->sTitle			= "E-Mail Signaturen 2";
		$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » E-Mail Signatures";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/admin/communication/signatures.html";
		$oTop->addChild($oChild);*/

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_templates_sms', '');
		$oChild->sTitle			= "SMS Vorlagen";
		$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » SMS Templates";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/admin/template/sms.html";
		$oChild->sKey = 'ts.admin.templates.sms';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_templates_app', '');
		$oChild->sTitle			= "App Vorlagen";
		$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » App Templates";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/admin/template/app.html";
		$oChild->sKey = 'ts.admin.templates.app';
		$oTop->addChild($oChild);

		// TODO komplett entfernen
		if (\Util::isDebugIP()) {
			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess = array('core_admin_templates_automatic', '');
			$oChild->sTitle = 'E-Mail automatisch';
			$oChild->sL10NAddon = 'Thebing » Admin » E-mail Cronjob';
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/gui2/page/Tc_email_automatic';
			$oChild->sKey = 'ts.admin.email_automatic.legacy';
			$oTop->addChild($oChild);
		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_contract_templates', '');
		$oChild->sTitle			= 'Vertragsvorlagen';
		$oChild->sL10NAddon		= 'Thebing » Admin » Contract templates';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_contract_templates';
		$oChild->sKey = 'ts.admin.templates.contract';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= '';
		$oChild->sTitle			= 'Verwaltung';
		$oChild->sL10NAddon		= 'Thebing » Admin » Control';
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.admin.main';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_schools', '');
		$oChild->sTitle			= 'Schulen';
		$oChild->sL10NAddon		= 'Thebing » Admin » Schools';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_admin_schools';
		$oChild->sKey = 'ts.admin.schools';
		$oTop->addChild($oChild);

        $oChild = new Ext_TC_System_Navigation_LeftItem();
        $oChild->mAccess		= array('thebing_admin_inbox', '');
        $oChild->sTitle			= 'Eingänge';
        $oChild->sL10NAddon		= 'Thebing » Admin » Inbox';
        $oChild->iSubpoint		= 1;
        $oChild->sUrl			= '/gui2/page/Ts_inbox';
		$oChild->sKey = 'ts.admin.inbox';
        $oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_numberranges', '');
		$oChild->sTitle			= 'Nummernkreise';
		$oChild->sL10NAddon		= 'Thebing Core » Number ranges';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_numberranges';
		$oChild->sKey = 'ts.admin.numberrange';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_update', '');
		$oChild->sTitle			= 'Systemupdate';
		$oChild->sL10NAddon		= 'System » Update';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/update.html';
		$oChild->sKey = 'admin.update';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_clientdata', '');
		$oChild->sTitle			= 'Generelle Einstellungen';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/thebing/admin/clientdata.html';
		$oChild->sKey = 'ts.admin.config';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_emailaccounts', '');
		$oChild->sTitle			= 'E-Mail-Einstellungen';
		$oChild->sL10NAddon		= 'Thebing » Admin » E-Mail-Adressen';
		$oChild->iSubpoint		= 1;
		//$oChild->sUrl			= '/admin/extensions/tc/admin/communication/emailaccount.html';
		$oChild->sUrl			= '/admin/communication/email_accounts/wizard';
		$oChild->sKey = 'ts.admin.emailaccounts';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess = array('core_external_apps', '');
		$oChild->sTitle	= 'Externe Apps';
		$oChild->sL10NAddon	= 'Thebing » Admin » External Apps';
		$oChild->iSubpoint = 1;
		$oChild->sUrl = '/external-apps';
		$oChild->sKey = 'admin.apps';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_exchangerate', '');
		$oChild->sTitle			= "Wechselkurse";
		$oChild->sL10NAddon		= "Thebing » Admin » Exchange rates";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/admin/exchangerates/tables.html";
		$oChild->sKey = 'ts.admin.exchangerates';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_event_manager', '');
		$oChild->sTitle			= 'Ereignissteuerung';
		$oChild->sL10NAddon		= 'Thebing » Admin » Events';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_event_management';
		$oChild->sKey = 'ts.admin.events';
		$oTop->addChild($oChild);

		/*$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_mail_spool', '');
		$oChild->sTitle			= 'Mail-Spool';
		$oChild->sL10NAddon		= 'Thebing » Admin » Mail Spool';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/tc/admin/communication/mail_spool.html';
		$oChild->sKey = 'ts.admin.mail_spool';
		$oTop->addChild($oChild);*/

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= '';
		$oChild->sTitle			= 'Sonstiges';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.admin.others';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_absence_categories', '');
		$oChild->sTitle			= 'Abwesenheitskategorien';
		$oChild->sL10NAddon		= 'Thebing » Admin » Absence » Categories';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_absence_categories';
		$oChild->sKey = 'ts.admin.absence_categories';
		$oTop->addChild($oChild);

		if(\Core\Handler\SessionHandler::getInstance()->get('sid') > 0) {


			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_admin_visumstatus', '');
			$oChild->sTitle			= 'Visa Typen';
			$oChild->sL10NAddon		= 'Thebing » Menü Links';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_visumstatus';
			$oChild->sKey = 'ts.admin.visum_status';
			$oTop->addChild($oChild);

		}

        $oChild = new Ext_TC_System_Navigation_LeftItem();
        $oChild->mAccess		= array('core_admin_templates_fonts', '');
        $oChild->sTitle			= 'Schriftarten';
        $oChild->sL10NAddon		= 'Thebing » Admin » Fonts';
        $oChild->iSubpoint		= 1;
        $oChild->sUrl			= '/gui2/page/Tc_fonts';
		$oChild->sKey = 'ts.admin.fonts';
        $oTop->addChild($oChild);

        if(\Core\Handler\SessionHandler::getInstance()->get('sid') > 0) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			$oChild->mAccess		= array('thebing_marketing_position_order', '');
			$oChild->sTitle			= 'Rechnungspositionen';
			$oChild->sL10NAddon		= 'Thebing » Marketing » Position Order';
			$oChild->iSubpoint		= 1;
			$oChild->sUrl			= '/gui2/page/Ts_positionorder';
			$oChild->sKey = 'ts.admin.positions';
			$oTop->addChild($oChild);

		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('ts_admin_pos', '');
		$oChild->sTitle			= 'Verkaufsstellen';
		$oChild->sL10NAddon		= 'TS » Admin » Points of sale';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/ts_pos';
		$oChild->sKey = 'ts.admin.point_of_sale';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_synergee_xml', '');
		$oChild->sTitle			= 'Synergee XML Import';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/thebing/admin/synergee_xml.html';
		$oChild->sKey = 'ts.admin.synergee';
		$oTop->addChild($oChild);

        $oChild = new Ext_TC_System_Navigation_LeftItem();
        $oChild->mAccess = array('core_admin_communication_category', '');
        $oChild->sTitle = 'Kommunikationskategorien';
        $oChild->sL10NAddon = 'Communication';
        $oChild->iSubpoint = 1;
        $oChild->sUrl = '/gui2/page/Tc_communication_category';
		$oChild->sKey = 'ts.admin.communication.category';
        $oTop->addChild($oChild);

		if(Ext_TC_Util::isDevSystem()) {

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			//$oChild->mAccess = array('thebing_admin_statisticfields', '');
			$oChild->sTitle = 'Statistik';
			$oChild->sL10NAddon = 'Thebing » Statistik';
			$oChild->iSubpoint = 0;
			$oChild->sKey = 'ts.admin.statistics';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			//$oChild->mAccess = array('thebing_admin_statisticfields', '');
			$oChild->sTitle = 'Statistikfelder';
			$oChild->sL10NAddon = 'Thebing » Admin » Statistikfelder';
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/admin/extensions/thebing/admin/statisticfields.html';
			$oChild->sKey = 'ts.admin.statistics.fields';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			//$oChild->mAccess = array('thebing_admin_statisticfields', '');
			$oChild->sTitle = 'Abhängigkeit Detailliste';
			$oChild->sL10NAddon = 'Thebing » Admin » Statistik » Abhängigkeit';
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/admin/extensions/thebing/admin/statistic_relation.html?type=2';
			$oChild->sKey = 'ts.admin.statistics.relations_2';
			$oTop->addChild($oChild);

			$oChild = new Ext_TC_System_Navigation_LeftItem();
			//$oChild->mAccess = array('thebing_admin_statisticfields', '');
			$oChild->sTitle = 'Abhängigkeit Summe';
			$oChild->sL10NAddon = 'Thebing » Admin » Statistik » Abhängigkeit';
			$oChild->iSubpoint = 1;
			$oChild->sUrl = '/admin/extensions/thebing/admin/statistic_relation.html?type=1';
			$oChild->sKey = 'ts.admin.statistics.relations_1';
			$oTop->addChild($oChild);
		}

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_icon', '');
		$oChild->sTitle			= 'Frontend';
		$oChild->sL10NAddon		= 'Thebing » Admin » Frontend';
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.admin.frontend';
		$oTop->addChild($oChild);
		
		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_registration_form', '');
		$oChild->sTitle			= 'Formulare';
		$oChild->sL10NAddon		= 'Thebing » Admin » Formulare';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_frontend_forms';
		$oChild->sKey = 'ts.admin.frontend.forms';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_frontend_translations', '');
		$oChild->sTitle			= 'Frontendübersetzungen ';
		$oChild->sL10NAddon		= 'Thebing » Admin » Formulare';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/translations.html?view=frontend';
		$oChild->sKey = 'ts.admin.frontend.translations';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_frontend_token', '');
		$oChild->sTitle			= 'API Token';
		$oChild->sL10NAddon		= 'API';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/TcApi_api_token';
		$oChild->sKey = 'ts.admin.frontend.api_token';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_frontend_templates', '');
		$oChild->sTitle			= 'Vorlagen';
		$oChild->sL10NAddon		= 'Thebing » Admin » Frontend';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_frontend_templates';
		$oChild->sKey = 'ts.admin.frontend.templates';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_frontend_combinations', '');
		$oChild->sTitle			= 'Kombinationen';
		$oChild->sL10NAddon		= 'Thebing » Admin » Frontend';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_frontend_combinations';
		$oChild->sKey = 'ts.admin.frontend.combinations';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_student_handbook', '');
		$oChild->sTitle			= 'App-Inhalte';
		$oChild->sL10NAddon		= 'Thebing » App » FAQ';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/TsStudentApp_app_contents';
		$oChild->sKey = 'ts.admin.frontend.app_contents';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_frontend_preview', '');
		$oChild->sTitle			= "Vorschau";
		$oChild->sL10NAddon		= "Thebing » Admin »  » Preview";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/admin/extensions/tc/admin/frontend_preview.php";
		$oChild->sKey = 'ts.admin.frontend.preview';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('ts_admin_frontend_course_structure', '');
		$oChild->sTitle			= "Kursstruktur";
		$oChild->sL10NAddon		= "Thebing » Admin » Frontend » Course structure";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= $this->generateUrl('TsFrontend.course_structure_page');
		$oChild->sKey = 'ts.admin.frontend.course_structure';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('ts_admin_frontend_course_superordinate', '');
		$oChild->sTitle			= "Übergeordnete Kurse";
		$oChild->sL10NAddon		= "Thebing » Admin » Frontend » Superordinate courses";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= $this->generateUrl('Gui2.page', ['sName' => 'TsFrontend_superordinate_courses']);
		$oChild->sKey = 'ts.admin.frontend.course_superordinate';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->sTitle			= 'Benutzeroberfläche';
		$oChild->sL10NAddon		= 'GUI';
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.admin.interface';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_gui2_designer', '');
		$oChild->sTitle			= 'Filtersets';
		$oChild->sL10NAddon		= 'GUI';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/extensions/tc/gui2/filter/sets.html';
		$oChild->sKey = 'ts.admin.interface.filtersets';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_user_flexibility', '');
		$oChild->sTitle			= 'Individuelle Felder';
		$oChild->sL10NAddon		= 'Thebing » Admin » Flexibility';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_flexible_fields/main';
		$oChild->sKey = 'ts.admin.interface.flexibility';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_customerupload', '');
		$oChild->sTitle			= 'Kundendaten Upload';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Ts_customerupload';
		$oChild->sKey = 'ts.admin.interface.customer_uploads';
		$oTop->addChild($oChild);

		$oChildTop = new Ext_TC_System_Navigation_LeftItem();
		$oChildTop->mAccess			= array('core_admin_parallelprocessing_error_stack', '');
		$oChildTop->sTitle			= "Hintergrundaufgaben";
		$oChildTop->sL10NAddon		= "Thebing Core » Parallel Processing";
		$oChildTop->iSubpoint		= 0;
		$oChildTop->sKey = 'ts.admin.queues';
		$oTop->addChild($oChildTop);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('core_admin_parallelprocessing_error_stack', '');
		$oChild->sTitle			= "Fehlgeschlagene Hintergrundaufgaben";
		$oChild->sL10NAddon		= $oChildTop->sL10NAddon." » Error Stack";
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= "/wdmvc/gui2/page/tc_error_stack";
		$oChild->sKey = 'ts.admin.queues.error_stack';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= array('thebing_admin_contacts', '');
		$oChild->sTitle			= 'Protokoll';
		$oChild->sL10NAddon		= 'Core » Logs';
		$oChild->iSubpoint		= 0;
		$oChild->sUrl = '/gui2/page/core_logs';
		$oChild->sKey = 'ts.admin.logs';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= ['ac_admin_languages', ''];
		$oChild->sTitle			= 'Intern';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 0;
		$oChild->sKey = 'ts.admin.languages';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= ['ac_admin_languages', ''];
		$oChild->sTitle			= 'Backendübersetzungen';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/admin/backend_translations.html';
		$oChild->sKey = 'ts.admin.translations';
		$oTop->addChild($oChild);

		$oChild = new Ext_TC_System_Navigation_LeftItem();
		$oChild->mAccess		= ['core_admin_nationalities', ''];
		$oChild->sTitle			= 'Nationalitäten';
		$oChild->sL10NAddon		= 'Thebing » Menü Links';
		$oChild->iSubpoint		= 1;
		$oChild->sUrl			= '/gui2/page/Tc_nationalities';
		$oChild->sKey = 'ts.admin.nationalities';
		$oTop->addChild($oChild);

//		$oChild = new Ext_TC_System_Navigation_LeftItem();
//		$oChild->mAccess		= array('ac_admin_languages', '');
//		$oChild->sTitle			= 'Muttersprachen';
//		$oChild->sL10NAddon		= 'Thebing » Menü Links';
//		$oChild->iSubpoint		= 1;
//		$oChild->sUrl			= '/admin/extensions/thebing/admin/mothertonge.html';
//		$oTop->addChild($oChild);

//		$oChild = new Ext_TC_System_Navigation_LeftItem();
//		$oChild->mAccess		= array('ac_admin_clients', '');
//		$oChild->sTitle			= 'Rechte';
//		$oChild->sL10NAddon		= 'Thebing » Menü Links';
//		$oChild->iSubpoint		= 1;
//		$oChild->sUrl			= '/admin/extensions/thebing/admin/access.html';
//		$oTop->addChild($oChild);
//
//		$oChild = new Ext_TC_System_Navigation_LeftItem();
//		$oChild->mAccess		= array('ac_admin_clients', '');
//		$oChild->sTitle			= 'Lizenzen';
//		$oChild->sL10NAddon		= 'Thebing » Menü Links';
//		$oChild->iSubpoint		= 1;
//		$oChild->sUrl			= '/admin/extensions/thebing/admin/licence.html';
//		$oTop->addChild($oChild);

		$this->addTopNavigation($oTop);

	}

	public function getAccess($sRight = ''){

		$oNavi = new self();
		$aTops = $oNavi->getTopNavigationObjects();
		// Elemente durchgehen und aktuelles Recht suchen
		foreach((array)$aTops as $oTop){
			$aChilds = $oTop->getChilds();
			foreach((array)$aChilds as $oChild){
				if($oChild->sUrl == $_SERVER['REQUEST_URI']){
					$mAccess = $oChild->mAccess;
					if(
						$sRight != "" &&
						is_array($mAccess)
					){
						$mAccess[1] = $sRight;
					}
					return $mAccess;
				}
			}
		}

		return '';
	}

	/**
	 * increments the position of this element
	 */
	protected function _incrementElementPosition() {

		// Positionen der Standard Elemente (Erweiterungen, Papierkorb, ...)
		$aBasicElementsPositions = array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110);

		$iCount = $this->_iCount + 1;

		// Falls sich die Position des neuen Elementes mit der Position eines 
		// Standard-Elementes überschneidet, muss diese Position übersprungen werden
		if(in_array($iCount, $aBasicElementsPositions)) {
			++$iCount;
		}

		$this->_iCount = $iCount;
	}

}
