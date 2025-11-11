<?php

namespace TsCanvas;

use smtech\CanvasPest\CanvasPest;
use smtech\CanvasPest\CanvasObject;

/**
 * CANVAS
 * 
 * - Wir arbeiten mit den SIS-IDs (dadurch können wir Abfragen mit unseren IDs machen)
 * 
 * see: https://canvas.instructure.com/doc/api/index.html
 */
class Api {
	
	const FIX_ACCOUNT_ID = 1;
	
	private $oApi;
		
	private $iAccountId;
	
	private $oLog;
	
	private $aErrors = [];
	
	public function __construct() {
		$this->oApi = new CanvasPest(\System::d(Handler\ExternalApp::KEY_URL, ''), \System::d(Handler\ExternalApp::KEY_ACCESS_TOKEN, ''));
		// Account kann in der system_config überschrieben werden (sonst 1)
		$this->iAccountId = \System::d('canvas_account_id', self::FIX_ACCOUNT_ID);
		$this->oLog = \Log::getLogger('canvas_api');
	}

	/**
	 * Überschreibt die AccountId
	 * 
	 * @param int $iAccountId
	 * @return $this
	 */
	public function setAccountId(int $iAccountId) {
		$this->iAccountId = $iAccountId;
		return $this;
	}
	
	/**
	 * Prüft ob ein Schüler in Canvas existiert
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Traveller $oContact
	 * @return bool
	 */
	public function hasUser(\Ext_TS_Inquiry_Contact_Traveller $oContact) {
		
		$sUrl = '/users/sis_user_id:'.$this->buildSisId($oContact);
		
		try {
			$oUser = $this->oApi->get($sUrl);
		} catch(\Exception $e) {			
			return false;
		}

		return true;				
	}

	/**
	 * Erstellt einen Schüler in Canvas
	 * see: https://canvas.instructure.com/doc/api/users.html#method.users.create
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Traveller $oContact
	 * @return \smtech\CanvasPest\CanvasObject|false
	 */
	public function createUser(\Ext_TS_Inquiry_Contact_Traveller $oContact) {
		
		$oEmail = $oContact->getFirstEmailAddress(false);
		
		if(
			!$oEmail ||
			empty($oEmail->email)
		) {
			// Canvas braucht eine E-Mail-Adresse für den Schüler
			$this->oLog->addError('Create user failed', ['contact' => $oContact->getId, 'message' => 'No valid email address']);
			return false;
		}
				
		$aUserData = $this->generateUserData($oContact);
		$aUserData['skip_registration'] = true;

		$aUser = [
			'user' => $aUserData,
			'pseudonym' => [
				'unique_id' => $oEmail->email,
				'password' => \Util::generateRandomString(10),
				'sis_user_id' => $oContact->getId(),
				'send_confirmation' => true
			]
		];

		try {
			$oUser = $this->oApi->post($this->buildUrl('/accounts/{account_id}/users'), $aUser);
		} catch(\Exception $e) {
			$this->oLog->addError('Create user failed', ['user' => $aUser, 'message' => $e->getMessage()]);
			$this->aErrors[] = $e->getMessage();
			return false;
		}

		return $oUser;	
	}
	
	/**
	 * Aktualisiert die Daten eines Schülers 
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Traveller $oContact
	 * @return \smtech\CanvasPest\CanvasObject|false
	 */
	public function updateUser(\Ext_TS_Inquiry_Contact_Traveller $oContact) {
	
		$aUserData = $this->generateUserData($oContact);
		
		$aUser = [
			'user' => $aUserData,
		];
		
		try {
			$oUser = $this->oApi->put('/users/sis_user_id:'.$this->buildSisId($oContact), $aUser);
		} catch(\Exception $e) {
			$this->oLog->addError('Update user failed', ['user' => $aUser, 'message' => $e->getMessage()]);
			$this->aErrors[] = $e->getMessage();
			return false;
		}
		
		return $oUser;	
	}
	
	/**
	 * Generiert die allgemeinen Daten zu einem Schüler
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Traveller $oContact
	 * @return array
	 */
	private function generateUserData(\Ext_TS_Inquiry_Contact_Traveller $oContact) : array {
		return [
			'name' => $oContact->firstname.' '.$oContact->lastname,
			'sortable_name' => $oContact->lastname.', '.$oContact->firstname,
			'locale' => strtolower($oContact->corresponding_language),
			'birthdate' => $oContact->birthday,
		];
	}
	
	/**
	 * Prüft ob ein Schüler in Canvas existiert
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Traveller $oContact
	 * @return bool
	 */
	public function hasCourse(\Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {

		try {
			$oCourse = $this->oApi->get('/courses/sis_course_id:'.$this->buildSisId($oJourneyCourse));
		} catch(\Exception $e) {
			return false;
		}

		return true;				
	}
	
	/**
	 * Erstellt einen Kurs in Canvas
	 * see: https://canvas.instructure.com/doc/api/courses.html#method.courses.create
	 * 
	 * @param \Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return \smtech\CanvasPest\CanvasObject|false
	 */
	public function createCourse(\Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {
		
		$oTuitionCourse = $oJourneyCourse->getCourse();
		
		$aCourse = [
			'course' => [
				'name' => $oTuitionCourse->getName('de'),
				'sis_course_id' => $this->buildSisId($oJourneyCourse),
				'start_at' => $oJourneyCourse->from.'T00:00Z',
				'end_at' => $oJourneyCourse->until.'T00:00Z'
			]
		];

		try {
			$oCourse = $this->oApi->post($this->buildUrl('/accounts/{account_id}/courses'), $aCourse);
		} catch(\Exception $e) {
			$this->aErrors[] = $e->getMessage();
			$this->oLog->addError('Create course failed', ['course' => $aCourse, 'message' => $e->getMessage()]);
			return false;
		}

		return $oCourse;
		
	}
	
	/**
	 * Erstellt einen Blueprint course anhand des Elternkurses
	 * @see https://canvas.instructure.com/doc/api/blueprint_courses.html
	 * 
	 * @param \Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return boolean
	 */
	public function createBlueprintAssociation(\Ext_TS_Inquiry_Journey_Course $oJourneyCourse, $mTemplateId = 'default') {
		
		$oTuitionCourse = $oJourneyCourse->getCourse();
		
		if(empty($oTuitionCourse->canvas_course_id)) {
			$this->oLog->addError('Blueprint association failed', ['tuition_course' => $oTuitionCourse->getId(), 'message' => 'No source course id given!']);
			return false;
		}
		
		$oCourse = $this->createCourse($oJourneyCourse);
		
		if($oCourse === false) {
			return false;
		}
		
		// sis_course_id funktioniert leider nicht 
		$aAssociation = [
			'course_ids_to_add' => [$oCourse->id]
		];
		
		try {
			$oAssociation = $this->oApi->post($this->buildUrl('/courses/'.$oTuitionCourse->canvas_course_id.'/blueprint_templates/'.$mTemplateId.'/update_associations'), $aAssociation);
		} catch(\Exception $e) {
			$this->oLog->addError('Blueprint association failed', ['association' => $aAssociation, 'message' => $e->getMessage()]);
			$this->aErrors[] = $e->getMessage();
			return false;
		}
		
		// sync
		
		$this->oApi->post($this->buildUrl('/courses/'.$oTuitionCourse->canvas_course_id.'/blueprint_templates/'.$mTemplateId.'/migrations'), [
			'comment' => 'Fidelo: added new bluebrint association',
			'send_notification' => false
		]);
		
		return $oAssociation;
	}
	
	/**
	 * @todo
	 * 
	 * @param \Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return boolean
	 */
	public function createCourseClone(\Ext_TS_Inquiry_Journey_Course $oJourneyCourse) { 
		
		$oTuitionCourse = $oJourneyCourse->getCourse();
		
		if(empty($oTuitionCourse->canvas_course_id)) {
			$this->oLog->addError('Copy course failed', ['tuition_course' => $oTuitionCourse->getId(), 'message' => 'No source course id given!']);
			return false;
		}
		
		$oCourse = $this->createCourse($oJourneyCourse);
		
		if($oCourse === false) {
			return false;
		}
		
		$aMigration = [
			'migration_type' => 'course_copy_importer',
			'settings' => [
				'source_course_id' => $oTuitionCourse->canvas_course_id
			]
		];
		
		try {
			$oMigration = $this->oApi->post($this->buildUrl('/courses/sis_course_id:'.$this->buildSisId($oJourneyCourse).'/content_migrations'), $aMigration);
		} catch(\Exception $e) {
			$this->oLog->addError('Copy course failed', ['migration' => $aMigration, 'message' => $e->getMessage()]);
			$this->aErrors[] = $e->getMessage();
			return false;
		}
		
		return $oMigration;
	}
	
	/**
	 * Weist einen Schüler zu einem Kurs zu
	 * see: https://canvas.instructure.com/doc/api/enrollments.html#method.enrollments_api.create
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Traveller $oContact
	 * @param \Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return \smtech\CanvasPest\CanvasObject|false
	 */
	public function assignUserToCourse(\Ext_TS_Inquiry_Contact_Traveller $oContact, \Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {
		
		$aEnrollment = [
			'enrollment' => [
				'user_id' => 'sis_user_id:'.$this->buildSisId($oContact),
				'type' => 'StudentEnrollment',
				'enrollment_state' => 'active'
			]
		];
		
		try {
			$oEnrollment = $this->oApi->post('/courses/sis_course_id:'.$this->buildSisId($oJourneyCourse).'/enrollments', $aEnrollment);
		} catch(\Exception $e) {
			$this->oLog->addError('User enrollment failed', ['course' => $aEnrollment, 'message' => $e->getMessage()]);
			$this->aErrors[] = $e->getMessage();
			return false;
		}
		
		return $oEnrollment;		
	}
	
	/**
	 * Ersetzt globale Platzhalter in der URL
	 * 
	 * @param string $sUrl
	 * @return string
	 */
	private function buildUrl(string $sUrl) : string {
		$sUrl = str_replace('{account_id}', $this->iAccountId, $sUrl);
		return $sUrl;
	}
	
	/**
	 * Generiert die SIS-ID für eine Entität
	 * 
	 * @return string
	 */
	private function buildSisId(\WDBasic $oEntity) : string {
		
		$sPrefix = '';
//		if($oEntity instanceof \Ext_TS_Inquiry_Journey_Course) {
//			$sPrefix = 'I';
//		} else if($oEntity instanceof \Ext_Thebing_Tuition_Course) {
//			$sPrefix = 'T';
//		}
		
		return $sPrefix.$oEntity->getId();
	}
	
	/**
	 * Liefert die Fehler die bei der Übertragung entstanden sind
	 * 
	 * @return array
	 */
	public function getErrors() {
		return $this->aErrors;
	}
}

