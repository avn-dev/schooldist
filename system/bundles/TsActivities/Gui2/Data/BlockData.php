<?php

namespace TsActivities\Gui2\Data;

use Carbon\Carbon;
use TsActivities\Entity\Activity;
use TsActivities\Gui2\Selection;

/**
 * @method Activity\Block getWDBasicObject($aSelectedIds)
 */
class BlockData extends \Ext_Thebing_Gui2_Data {

	public static function createGui() {

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$oGui = new \Ext_Thebing_Gui2_Communication('ts_activities_planning', '\TsActivities\Gui2\Data\BlockData');
		$oData = $oGui->getDataObject(); /** @var self $oData */
		$oGui->gui_description = \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH;
		$oGui->setWDBasic(Activity\Block::class);
		$oGui->class_js = 'SchedulingGuiClass';

		$oGui->setOption('locale', \Ext_TC_System::getInterfaceLanguage());
		$oGui->setOption('activity_starttime', $oSchool->activity_starttime);
		$oGui->setOption('activity_endtime', $oSchool->activity_endtime);
		$oGui->setOption('activities', Activity::getActivitiesForSelect(\Ext_TC_System::getInterfaceLanguage()));
		$oGui->setOption('student_status', \Ext_Thebing_Marketing_Studentstatus::getList(true));
		$oGui->setOption('inboxes', \Ext_Thebing_System::getInboxListForSelect(false, false));
		$oGui->setOption('communication_model_class', \TsActivities\Entity\Activity\BlockTraveller::class);

		// Initiale Werte für Filter
		$oGui->setOption('filters', [
			'search' => '',
			'booking_state' => 'unallocated',
			'activity' => '',
			'inbox' => '',
			'student_status' => ''
		]);

		$oGui->addCss('/assets/ts-tuition/css/progress_report.css', null);

		$aOptionalData = [
			'js' => [
				'/admin/extensions/thebing/js/communication.js',
				'/admin/extensions/tc/js/communication_gui.js',
			],
			'css' => [
				'/assets/tc/css/communication.css'
			]
		];

		$oGui->addOptionalData($aOptionalData);

		$oGui->getDataObject()->aIconData['new'] = ['dialog_data' => $oData->getDialog(false)];
		$oGui->getDataObject()->aIconData['edit'] = ['dialog_data' => $oData->getDialog(true)];
		$oGui->getDataObject()->aIconData['communication_activity'] = ['additional' => 'activity'];

		return $oGui;

	}

	/**
	 * Wird nur überschrieben, damit man die Klasse manipulieren kann, da die übergebenen IDs nicht zum Block gehören
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		// ID wird manuell in JS übertragen und ist nicht mehr die ID vom Block
		if($sIconAction === 'communication') {
			$this->_oGui->setWDBasic('\TsActivities\Entity\Activity\BlockTraveller');
		}
		
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($sIconAction === 'communication') {
			$this->_oGui->setWDBasic('\TsActivities\Entity\Activity\Block');
		}
		
		return $aData;
	}


	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$block = $this->getWDBasicObject($aSelectedIds);
		$block->school_id = \Ext_Thebing_School::getSchoolFromSession()->id;

		if (empty($aSelectedIds)) {

			if ($block->exist()) {
				throw new \DomainException('Block exists but no selected ids');
			}

			// Ist nur beim ersten Aufruf vorhanden, nicht beim Reload (dependency)
			if($this->request->exists('date')) {
				$date = Carbon::parse($this->request->input('date'));
				$week = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay($date, 1));

				$block->start_week = $week->toDateString();

				/** @var \TsActivities\Entity\Activity\BlockDay $day */
				$day = $block->getJoinedObjectChild('days');
				$day->day = $date->isoWeekday();

				// Wochenansicht hat Uhrzeit, Monatsansicht 0 Uhr
				if (!$date->isStartOfDay()) {
					$day->start_time = $date->toTimeString();
					$day->end_time = $date->clone()->addMinutes(30)->toTimeString();
				}

			}

		}

		return parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

	}

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public function getDialog($bEdit) {

		$aActivities = Activity::getActivitiesForSelect(\Ext_TC_System::getInterfaceLanguage());
		$aCompanions = Activity\Block::getRepository()->getCompanionsForSelect();
		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$iStartTimeStamp = gmmktime((int)substr($oSchool->activity_starttime, 0, 2), (int)substr($oSchool->activity_starttime, 3, 2));
		$iEndTimeStamp = gmmktime((int)substr($oSchool->activity_endtime, 0, 2), (int)substr($oSchool->activity_endtime, 3, 2));
		if($iStartTimeStamp == $iEndTimeStamp) {
			$iStartTimeStamp = 0;
			$iEndTimeStamp = 85500;
		}

		$aTimes = \Ext_Thebing_Util::getTimeRows('format', 5, $iStartTimeStamp, $iEndTimeStamp);
		$oGui = $this->_oGui;

		$oDialog = $oGui->createDialog($oGui->t('Block "{name}" editieren'), $oGui->t('Aktivität planen'));

		if($bEdit === true) {
			$oDialog->setElement($oDialog->createNotification($oGui->t('Hinweis!'), $oGui->t('Eine Veränderung der geplanten Aktivität hat Auswirkungen auf die gesamte Laufzeit der Aktivität (Vergangenheit und Zukunft). Bitte legen Sie einen neuen Eintrag an, wenn das nicht gewünscht ist.'), 'info'));
		}

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'),'input', array(
			'db_column' => 'name',
			'required'	=> true,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Aktivität'),'select', array(
			'db_alias' => 'ts_act',
			'db_column' => 'activities',
			'required'	=> true,
			'select_options' => $aActivities,
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => 1,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Anbieter'),'select', array(
			'db_alias' => 'ts_actb',
			'db_column' => 'provider_id',
			'selection' => new Selection\Providers(),
			'dependency' => [
				[
					'db_column'	=> 'activities',
					'db_alias' => 'ts_act',
				]
			],
		)));

		// Zwingend notwendig
		$oDialog->setElement($oDialog->createSaveField('hidden', [
			'db_column' => 'school_id'
		]));
		$oDialog->setElement($oDialog->createSaveField('hidden', [
			'db_column' => 'start_week'
		]));

		$oDialog->setElement($oDialog->createRow($oGui->t('Wochenanzahl'),'input', array(
			'db_alias' => 'ts_actb',
			'db_column' => 'weeks',
			'required' => true,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Wiederholung alle "X" Wochen'),'input', array(
			'db_alias' => 'ts_actb',
			'db_column' => 'repeat_weeks',
			'required' => true,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('App/Formular'),'select', array(
			'db_alias' => 'ts_actb',
			'db_column' => 'frontend_release',
			'format' => new \Ext_Gui2_View_Format_Null(),
			'select_options' => \Ext_TC_Util::addEmptyItem(
				[
					Activity\Block::FRONTEND_VISIBLE => $oGui->t('Nur anzeigen'),
					Activity\Block::FRONTEND_BOOKABLE => $oGui->t('Anzeigen und Buchbar'),
				],
				$oGui->t('Nicht anzeigen'),
				'' // Wichtig für Format-Klasse
			),
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Anzeigen ab Anzahl von Tagen vor Leistungsbeginn'),'input', array(
            'db_alias' => 'ts_actb',
            'db_column' => 'frontend_min_visible_days_ahead',
            'format' => new \Ext_Gui2_View_Format_Null(),
            'dependency_visibility' => [
                'db_alias' => 'ts_actb',
                'db_column' => 'frontend_release',
                'on_values' => [Activity\Block::FRONTEND_VISIBLE, Activity\Block::FRONTEND_BOOKABLE]
            ]
        )));

		$oDialog->setElement($oDialog->createRow($oGui->t('Buchbar bis Anzahl von Tagen vor Leistungsbeginn'),'input', array(
			'db_alias' => 'ts_actb',
			'db_column' => 'frontend_min_bookable_days_ahead',
			'format' => new \Ext_Gui2_View_Format_Null(),
			'dependency_visibility' => [
				'db_alias' => 'ts_actb',
				'db_column' => 'frontend_release',
				'on_values' => [Activity\Block::FRONTEND_BOOKABLE]
			]
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Bewerben'),'checkbox', array(
			'db_alias' => 'ts_actb',
			'db_column' => 'advertise',
			'dependency_visibility' => [
				'db_alias' => 'ts_actb',
				'db_column' => 'frontend_release',
				'on_values' => [Activity\Block::FRONTEND_VISIBLE, Activity\Block::FRONTEND_BOOKABLE]
			]
		)));

		$oJoinedObjectContainer = $oDialog->createJoinedObjectContainer('days', array(
			'min' => 1
		));

		$oJoinedObjectContainer ->setElement($oJoinedObjectContainer->createRow($oGui->t('Wochentag'), 'select', array(
			'db_column' => 'day',
			'db_alias' => 'ts_actbd',
			'select_options' => \Ext_Thebing_Util::getDays()
		)));

		$oJoinedObjectContainer ->setElement($oJoinedObjectContainer->createRow($oGui->t('Von'), 'select', array(
			'db_column' => 'start_time',
			'db_alias' => 'ts_actbd',
			'class' => 'template_field_from',
			'select_options' => $aTimes,
			'format' => new \Ext_Thebing_Gui2_Format_Time()
		)));

		$oJoinedObjectContainer ->setElement($oJoinedObjectContainer->createRow($oGui->t('Bis'), 'select', array(
			'db_column' => 'end_time',
			'db_alias' => 'ts_actbd',
			'default_value' => end($aTimes),
			'class' => 'template_field_until',
			'select_options'  => $aTimes,
			'format' => new \Ext_Thebing_Gui2_Format_Time()
		)));

		$oJoinedObjectContainer->setElement($oJoinedObjectContainer->createRow($oGui->t('Ort'),'input', array(
			'db_alias' => 'ts_actbd',
			'db_column' => 'place'
		)));

		$oJoinedObjectContainer->setElement($oJoinedObjectContainer->createRow($oGui->t('Begleiter'),'select', array(
			'db_alias' => 'ts_actbd',
			'db_column' => 'companion',
			'select_options' => $aCompanions,
			'multiple' => 5,
			'jquery_multiple' => true,
		)));

		$oJoinedObjectContainer->setElement($oJoinedObjectContainer->createRow($oGui->t('Kommentar'),'textarea', array(
			'db_alias' => 'ts_actbd',
			'db_column' => 'comment',
		)));

		$oDialog->setElement($oJoinedObjectContainer);

		return $oDialog;

	}

	public static function convertErrorKeyToMessage($sKey) {

		if($sKey === 'WEEKS_BIGGER_THAN_REPEAT_WEEKS') {
			$sMessage = \L10N::t('Die Anzahl der Wochen muss größer sein als die Wiederholung.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
		} elseif($sKey === 'START_AND_END_TOO_CLOSE') {
			$sMessage = \L10N::t('Die Start- und Endzeit muss unterschiedlich sein.', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
		} elseif ($sKey === 'START_BIGGER_THAN_END') {
			$sMessage = \L10N::t('Die Endzeit muss größer als die Startzeit sein,', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
		} elseif ($sKey === 'VISIBLE_DAYS_MUST_BE_GREATER') {
			$sMessage = \L10N::t('Die Anzahl der Tage für "Buchbar bis" muss kleiner sein als für "Anzeigen ab".', \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
		} else {
			$sMessage = parent::convertErrorKeyToMessage($sKey);
		}

		return $sMessage;
	}

	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['confirm'] = $this->t('Bestätigen');
		$aData['month'] = $this->t('Monat');
		$aData['week'] = $this->t('Woche');
		$aData['day'] = $this->t('Tag');
		$aData['list'] = $this->t('Liste');
		$aData['today'] = $this->t('Heute');
		$aData['refresh'] = $this->t('Aktualisieren');
		$aData['name'] = $this->t('Name');
		$aData['activity'] = $this->t('Aktivität');
		$aData['student_status'] = $this->t('Schülerstatus');
		$aData['inbox'] = $this->t('Inbox');
		$aData['booking_state'] = $this->t('Buchungsstatus');
		$aData['booking_state_unallocated'] = $this->t('Nicht zugewiesen');
		$aData['booking_state_activity'] = $this->t('Aktivität gebucht');
		$aData['booking_state_no_activity'] = $this->t('Keine Aktivität gebucht');
		$aData['allocated_students'] = $this->t('Zugewiesene Schüler');
		$aData['unallocated_students'] = $this->t('Nicht zugewiesene Schüler');
		$aData['no_students'] = $this->t('Keine Schüler verfügbar.');
		$aData['select_activity'] = $this->t('Bitte eine Aktivität wählen.');
		$aData['allocate_students'] = $this->t('Schüler zuweisen');
		$aData['allocate_select_activity'] = $this->t('Bitte eine Aktivität auswählen, zu welcher der Schüler zugewiesen werden soll.');
		$aData['allocate_book'] = $this->t('Die folgenden Schüler haben die Aktiviät nicht gebucht. Bitte bestätigen, dass die Aktivität automatisch gebucht werden soll:');
		$aData['delete_allocation'] = $this->t('Soll die Zuweisung des Schülers wirklich gelöscht werden?');
		$aData['delete_block'] = $this->t('Soll die geplante Aktivität wirklich gelöscht werden? Es werden alle Blöcke, Wochen und Zuweisungen in Vergangenheit und Zukunft gelöscht.');
		$aData['blocks_booked_short'] = $this->t('GB');
		$aData['blocks_booked'] = $this->t('Gebuchte Blöcke (total)');
		$aData['blocks_allocated_short'] = $this->t('ZB');
		$aData['blocks_allocated'] = $this->t('Zugewiesene Blöcke (total)');
		$aData['communication'] = $this->t('Kommunikation');
		$aData['export'] = $this->t('Export');
		$aData['students'] = $this->t('Schüler');
		$aData['leaders'] = $this->t('Begleiter');

		return $aData;
	}

}