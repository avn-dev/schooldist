<?php

include_once \Util::getDocumentRoot().'phpunit/school/testSetup.php';

/**
 * Unittest für die Anwesenheitsberechnung
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class AttendanceTest2 extends schoolTestSetup 
{

	protected $_oSchool;
	
	protected $_oInquiry;
	
	protected $_oCombinationCourse;
	
	protected $_oCourse1;
	
	protected $_oCourse2;
	
	protected $_oTeacher1;
	
	protected $_oTeacher2;
	
	protected $_oTemplate1;
	
	protected $_oTemplate2;
	
	protected $_oClass;
	
	protected $_oJourneyCourse1;
	
	protected $_oJourneyCourse2;
	
	protected $_oJourneyCombinationCourse;

	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp()
	{
		parent::setUp();

		// Schule anlegen
		$this->_oSchool	= $this->_createDefaultSchool();
		
		// Buchung anlegen
		$this->_oInquiry = $this->_createDefaultInquiry($this->_oSchool);
		
		// Zwei Kurse + Kombi anlegen
		$this->_oCourse1 = new Ext_Thebing_Tuition_Course();
		$this->_oCourse1->active = 1;
		$this->_oCourse1->lesson_duration = 45;
		$this->_oCourse1->name_short = 'C1';
		$this->_oCourse1->save();
		
		$this->_oCourse2 = new Ext_Thebing_Tuition_Course();
		$this->_oCourse2->active = 1;
		$this->_oCourse2->lesson_duration = 45;
		$this->_oCourse2->name_short = 'C2';
		$this->_oCourse2->save();
		
		$this->_oCombinationCourse = new Ext_Thebing_Tuition_Course();
		$this->_oCombinationCourse->active = 1;
		$this->_oCombinationCourse->lesson_duration = 45;
		$this->_oCombinationCourse->name_short = 'C2';
		$this->_oCombinationCourse->combination = 1;
		$this->_oCombinationCourse->combined_courses = array(
			$this->_oCourse1->id,
			$this->_oCourse2->id,
		);
		$this->_oCombinationCourse->save();
		
		// Zwei Lehrer anlegen
		$oTeacher1 = new Ext_Thebing_Teacher();
		$oTeacher1->firstname = 'TeacherFirstname1';
		$oTeacher1->lastname = 'TeacherLastname1';
		$oTeacher1->username = 'TeacherUsername1';
		$oTeacher1->save();
		$this->_oTeacher1 = $oTeacher1;
		
		$oTeacher2 = new Ext_Thebing_Teacher();
		$oTeacher2->firstname = 'TeacherFirstname2';
		$oTeacher2->lastname = 'TeacherLastname2';
		$oTeacher2->username = 'TeacherUsername2';
		$oTeacher2->save();
		$this->_oTeacher2 = $oTeacher2;
		
		// 2 Vorlagen anlegen
		$oTemplate1 = new Ext_Thebing_Tuition_Template();
		$oTemplate1->name = 'Vorlage1';
		$oTemplate1->from = '08:00:00';
		$oTemplate1->until = '11:00:00';
		$oTemplate1->lessons = 4;
		$oTemplate1->save();
		$this->_oTemplate1 = $oTemplate1;
		
		$oTemplate2 = new Ext_Thebing_Tuition_Template();
		$oTemplate2->name = 'Vorlage2';
		$oTemplate2->from = '12:00:00';
		$oTemplate2->until = '14:15:00';
		$oTemplate2->lessons = 3;
		$oTemplate2->save();
		$this->_oTemplate2 = $oTemplate2;
		
		//Standard Klasse anlegen
		$oClass = new Ext_Thebing_Tuition_Class();
		$oClass->name = 'TmpClass';
		$oClass->courses = array($this->_oCourse1->id, $this->_oCourse2->id);
		$oClass->save();
		$this->_oClass = $oClass;
		
		//Kurse anlegen
		$this->_oJourneyCourse1 = $this->_createJourneyCourse($this->_oInquiry, array(
			'from'		=> '2012-10-15',
			'until'		=> '2012-11-02',
			'weeks'		=> 3,
			'course_id'	=> $this->_oCourse1->id,
		));
		
		$this->_oJourneyCourse2 = $this->_createJourneyCourse($this->_oInquiry, array(
			'from'		=> '2012-10-15',
			'until'		=> '2012-10-26',
			'weeks'		=> 2,
			'course_id'	=> $this->_oCourse2->id,
		));
		
		$this->_oJourneyCombinationCourse = $this->_createJourneyCourse($this->_oInquiry, array(
			'from'		=> '2012-10-15',
			'until'		=> '2012-11-02',
			'weeks'		=> 3,
			'course_id'	=> $this->_oCombinationCourse->id,
		));
	}
	
	/**
	 * Anwesenheitsüberprüfung gruppiert nach Lehrer mit Zeitfilter
	 */
	public function test2SamePeriodAllocationsWithTimerangeGroupByTeacher()
	{
		//2 Blöcke mit Zuweisung anlegen
		$oAllocation1 = $this->_createTuitionAllocation($this->_oJourneyCourse1, $this->_oTeacher1, $this->_oCourse1, $this->_oTemplate1, 1);
		$oAllocation2 = $this->_createTuitionAllocation($this->_oJourneyCourse1, $this->_oTeacher1, $this->_oCourse1, $this->_oTemplate2, 1);

		//Anwesenheit eintragen nur für Woche1
		$this->_saveAttendance($oAllocation1, array(
			'3' => '45',
		));

		$oAttendanceCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->_oJourneyCourse1);
		$oAttendanceCalculator->addAllocation($oAllocation1);
		$oAttendanceCalculator->addAllocation($oAllocation2);
		#$oAttendanceCalculator->setMode('journey_course_teacher');
		$oAttendanceCalculator->setPeriod('2012-10-15', '2012-10-21');
		#$oAttendanceCalculator->setTeacher($this->_oTeacher1);
		$fAttendance = $oAttendanceCalculator->calculate();

		$this->assertEquals(95, round($fAttendance, 2), 'First week');

		//@todo: Das gleiche noch einmal, nur das diesmal für $oAllocation2 auch eine Anwesenheit eingetragen wird, 
		//das geht aber erst nach der Strukturumstellung...
	}

	/**
	 * Anwesenheitsüberprüfung gruppiert nach Kurs mit Zeitfilter
	 */
	public function test2SamePeriodAllocationsWithTimerangeGroupByJourneyCourse()
	{
		//2 Blöcke mit 2 gleichen Lehrern + Zuweisung anlegen
		$oAllocation1 = $this->_createTuitionAllocation($this->_oJourneyCourse1, $this->_oTeacher1, $this->_oCourse1, $this->_oTemplate1, 1);
		$oAllocation2 = $this->_createTuitionAllocation($this->_oJourneyCourse1, $this->_oTeacher1, $this->_oCourse1, $this->_oTemplate2, 1);

		$oAttendanceCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->_oJourneyCourse1);
		#$oAttendanceCalculator->setMode('journey_course');
		$oAttendanceCalculator->setPeriod('2012-10-15', '2012-10-21');

		//Anwesenheit eintragen
		$this->_saveAttendance($oAllocation1, array(
			'2' => '20',
		));

		$oAttendanceCalculator->addAllocation($oAllocation1);
		$fAttendance = $oAttendanceCalculator->calculate();
		
		// 20min / (45min*4) * 5 )
		$this->assertEquals(97.78, round($fAttendance, 2), 'First week');
	}

	/**
	 * Anwesenheitsüberprüfung gruppiert nach Kurs mit Zeitfilter
	 */
	public function test2SamePeriodAllocationsWithTimerangeAndDifferentTeachersGroupByJourneyCourse()
	{
		//2 Blöcke mit 2 gleichen Lehrern + Zuweisung anlegen
		$oAllocation1 = $this->_createTuitionAllocation($this->_oJourneyCourse1, $this->_oTeacher1, $this->_oCourse1, $this->_oTemplate1, 1);
		$oAllocation2 = $this->_createTuitionAllocation($this->_oJourneyCourse1, $this->_oTeacher2, $this->_oCourse1, $this->_oTemplate2, 1);

		$oAttendanceCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->_oJourneyCourse1);
		#$oAttendanceCalculator->setMode('journey_course');
		$oAttendanceCalculator->setPeriod('2012-10-15', '2012-10-21');

		//Anwesenheit eintragen
		$this->_saveAttendance($oAllocation1, array(
			'2' => '20',
		));
		
		$oAttendanceCalculator->addAllocation($oAllocation1);

		$fAttendance = $oAttendanceCalculator->calculate(); 
	
		$this->assertEquals(97.78, round($fAttendance, 2), 'First week');

		$this->_saveAttendance($oAllocation2, array(
			'2' => '60',
		));
		
		$oAttendanceCalculator->addAllocation($oAllocation2);

		$fAttendance = $oAttendanceCalculator->calculate();
	
		$this->assertEquals(94.92, round($fAttendance, 2), 'First week');
	}
	
	protected function _createTuitionAllocation($oJourneyCourse, $oTeacher, $oCourse, $oTemplate, $iWeek)
	{		
		if(
			!WDDate::isDate($oJourneyCourse->from, WDDate::DB_DATE) ||
			!WDDate::isDate($oJourneyCourse->until, WDDate::DB_DATE)
		)
		{
			throw new Exception('From & Until not defined!');
		}
		
		//1 zurücksetzen, damit wir nicht mit Woche "0" anfangen müssen
		$iWeek	= $iWeek - 1;
		
		$dFrom	= $oJourneyCourse->from;
		$oDate	= new WDDate($dFrom, WDDate::DB_DATE);
		$oDate->add($iWeek, WDDate::WEEK);

		//Block anlegen
		$oBlock = new Ext_Thebing_School_Tuition_Block();
		$oBlock->teacher_id = $oTeacher->id;
		$oBlock->class_id = $this->_oClass->id;
		$oBlock->days = array(1,2,3,4,5);
		$oBlock->week = $oDate->get(WDDate::DB_DATE);
		$oBlock->template_id = $oTemplate->id;
		$oBlock->save();

		//Zuweisung erstellen
		$oAllocation = new Ext_Thebing_School_Tuition_Allocation();
		$oAllocation->block_id = $oBlock->id;
		$oAllocation->course_id = $oCourse->id;
		$oAllocation->inquiry_course_id = $oJourneyCourse->id;
		$oAllocation->save();
		
		return $oAllocation;
	}
	
	protected function _saveAttendance(Ext_Thebing_School_Tuition_Allocation $oAllocation, $aAttendance)
	{
		$aDays			= Ext_Thebing_System::getAttendanceDays();
		
		#$oBlock			= $oAllocation->getBlock();
		#$oTeacher		= $oBlock->getTeacher();
		#$oJourneyCourse	= $oAllocation->getJourneyCourse();
		#$oInquiry		= $oJourneyCourse->getInquiry();
		#$oCourse		= $oAllocation->getCourse();
		
		$aInsert = array(
			'allocation_id' => $oAllocation->id,
		);
		
		foreach($aAttendance as $iDay => $iAttendance)
		{
			$sDay = $aDays[$iDay];
			
			$aInsert[$sDay] = $iAttendance;
		}
		
		DB::insertData('kolumbus_tuition_attendance', $aInsert);
	}
}