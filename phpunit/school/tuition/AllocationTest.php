<?php

include_once \Util::getDocumentRoot().'phpunit/school/testSetup.php';

/**
 * Unittest für die Methoden der Zuweisungen der Klassenplanung
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class AllocationTest extends schoolTestSetup
{
	protected $_oTemplate;
	
	protected $_oClass;
	
	protected $_oCourse;
	
	protected $_oJourneyCourse;

	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp()
	{
		parent::setUp();
	}
	
	/**
	 * Testet eine Simple Funktion aus den Zuweisungen, die nur dafür da ist bestimmte Zeiträume aus einem Array zu filtern
	 */
	public function testFilterDates()
	{
		$aDates = array(
			'2012-10-15' => '1',
			'2012-10-16' => '1',
			'2012-10-17' => '1',
		);

		$oAllocation		= new Ext_Thebing_School_Tuition_Allocation();
		$aFilteredDates		= $oAllocation->filterDates($aDates, '2012-10-16', '2012-10-16');


		$this->assertArrayNotHasKey('2012-10-15', $aFilteredDates);
		$this->assertArrayHasKey('2012-10-16', $aFilteredDates);
		$this->assertArrayNotHasKey('2012-10-17', $aFilteredDates);
	}

	/**
	 * Testet ob über die Zuweisungsklasse die Lektionsdauer zurück geben kann pro Tag
	 */
	public function testLessonDurations()
	{
		// Schule anlegen
		$oSchool	= $this->_createDefaultSchool();

		// Buchung anlegen
		$oInquiry = $this->_createDefaultInquiry($oSchool);

		// Vorlage anlegen
		$oTemplate = new Ext_Thebing_Tuition_Template();
		$oTemplate->name = 'Vorlage1';
		$oTemplate->from = '08:00:00';
		$oTemplate->until = '11:00:00';
		$oTemplate->lessons = 4;
		$oTemplate->save();

		// Kurs anlegen
		$oCourse = new Ext_Thebing_Tuition_Course();
		$oCourse->active = 1;
		$oCourse->lesson_duration = 45;
		$oCourse->name_short = 'C1';
		$oCourse->save();

		// Standard Klasse anlegen
		$oClass = new Ext_Thebing_Tuition_Class();
		$oClass->name = 'TmpClass';
		$oClass->courses = array($oCourse->id);
		$oClass->save();

		// Kurs buchen
		$oJourneyCourse = $this->_createJourneyCourse($oInquiry, array(
			'from'		=> '2012-10-15',
			'until'		=> '2012-11-02',
			'weeks'		=> 3,
			'course_id'	=> $oCourse->id,
		));

		$oBlock = new Ext_Thebing_School_Tuition_Block();
		$oBlock->template_id = $oTemplate->id;
		$oBlock->class_id = $oClass->id;
		$oBlock->days = array(1,2,3,4,5);
		$oBlock->week = '2012-10-15';
		$oBlock->save();

		$oAllocation = new Ext_Thebing_School_Tuition_Allocation();
		$oAllocation->block_id = $oBlock->id;
		$oAllocation->inquiry_course_id = $oJourneyCourse->id;
		$oAllocation->course_id = $oCourse->id;
		$oAllocation->save();

		$aLessonDurations = $oAllocation->getLessonDurations();

		$this->assertEquals(array(
			'2012-10-15' => 180,
			'2012-10-16' => 180,
			'2012-10-17' => 180,
			'2012-10-18' => 180,
			'2012-10-19' => 180,
		), $aLessonDurations);

		$aLessonDurations = $oAllocation->getLessonDurations('2012-10-16', '2012-10-19');

		$this->assertEquals(array(
			'2012-10-16' => 180,
			'2012-10-17' => 180,
			'2012-10-18' => 180,
			'2012-10-19' => 180,
		), $aLessonDurations);
	}
	
	/**
	 * Testet ob über die Zuweisungsklasse die Anwesenheit zurück geben kann pro Tag
	 */
	public function testAttendaces()
	{
		// Schule anlegen
		$oSchool	= $this->_createDefaultSchool();
		
		// Buchung anlegen
		$oInquiry = $this->_createDefaultInquiry($oSchool);
		
		// Vorlage anlegen
		$oTemplate = new Ext_Thebing_Tuition_Template();
		$oTemplate->name = 'Vorlage1';
		$oTemplate->from = '08:00:00';
		$oTemplate->until = '11:00:00';
		$oTemplate->lessons = 4;
		$oTemplate->save();
		
		// Kurs anlegen
		$oCourse = new Ext_Thebing_Tuition_Course();
		$oCourse->active = 1;
		$oCourse->lesson_duration = 45;
		$oCourse->name_short = 'C1';
		$oCourse->save();
		
		// Standard Klasse anlegen
		$oClass = new Ext_Thebing_Tuition_Class();
		$oClass->name = 'TmpClass';
		$oClass->courses = array($oCourse->id);
		$oClass->save();
		
		// Kurs buchen
		$oJourneyCourse = $this->_createJourneyCourse($oInquiry, array(
			'from'		=> '2012-10-15',
			'until'		=> '2012-11-02',
			'weeks'		=> 3,
			'course_id'	=> $oCourse->id,
		));
		
		$oBlock = new Ext_Thebing_School_Tuition_Block();
		$oBlock->template_id = $oTemplate->id;
		$oBlock->class_id = $oClass->id;
		$oBlock->days = array(1,2,3,4,5);
		$oBlock->week = '2012-10-15';
		$oBlock->save();
		
		$oAllocation = new Ext_Thebing_School_Tuition_Allocation();
		$oAllocation->block_id = $oBlock->id;
		$oAllocation->inquiry_course_id = $oJourneyCourse->id;
		$oAllocation->course_id = $oCourse->id;
		$oAllocation->save();
		
		$oAttendance = new Ext_Thebing_Tuition_Attendance();
		$oAttendance->allocation_id = $oAllocation->id;
		$oAttendance->mo = '45';
		$oAttendance->di = '30';
		$oAttendance->fr = '60';
		$oAttendance->save();
		
		$aAttendaces = $oAllocation->getAttendances();

		$this->assertEquals(array(
			'2012-10-15' => '45.00',
			'2012-10-16' => '30.00',
			'2012-10-17' => '0.00',
			'2012-10-18' => '0.00',
			'2012-10-19' => '60.00',
		), $aAttendaces);
		
		$aAttendaces = $oAllocation->getAttendances('2012-10-16', '2012-10-18');
		
		$this->assertEquals(array(
			'2012-10-16' => '30.00',
			'2012-10-17' => '0.00',
			'2012-10-18' => '0.00',
		), $aAttendaces);
	}
}
