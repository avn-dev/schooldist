<?php

include_once \Util::getDocumentRoot().'phpunit/school/testSetup.php';

/**
 * Unittest für die Anwesenheitsberechnung
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class AttendanceTest extends schoolTestSetup {

	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp() {
		
		parent::setUp();

		// Schule anlegen
		$this->oSchool = $this->_createDefaultSchool();
		$this->iSchoolId = $this->oSchool->id;

		// Klassenzimmer anlegen
		$oClassroom = new Ext_Thebing_Tuition_Classroom;
		$oClassroom->save();
		$iClassroomId = $oClassroom->id;
		
		// Zwei Lehrer anlegen
		$this->oTeacher1 = new Ext_Thebing_Teacher();
		$this->oTeacher1->firstname = 'Lehrer';
		$this->oTeacher1->lastname = '1';
		$this->oTeacher1->save();
		$this->iTeacherId1 = $this->oTeacher1->id;
		
		$this->oTeacher2 = new Ext_Thebing_Teacher();
		$this->oTeacher2->firstname = 'Lehrer';
		$this->oTeacher2->lastname = '2';
		$this->oTeacher2->save();
		$this->iTeacherId2 = $this->oTeacher2->id;

		// Zwei Kurse anlegen
		$this->oCourse1 = new Ext_Thebing_Tuition_Course();
		$this->oCourse1->active = 1;
		$this->oCourse1->lesson_duration = 45;
		$this->oCourse1->name_short = 'C1';
		$this->oCourse1->save();
		$this->iCourse1 = $this->oCourse1->id;

		$this->oCourse2 = new Ext_Thebing_Tuition_Course();
		$this->oCourse2->active = 1;
		$this->oCourse2->lesson_duration = 45;
		$this->oCourse2->name_short = 'C2';
		$this->oCourse2->save();
		$this->iCourse2 = $this->oCourse2->id;

		// Buchung anlegen mit drei Kursen über drei Wochen		
		$this->oInquiry = $this->_createDefaultInquiry($this->oSchool);

		$aCourse = array(
			'course_id' => $this->iCourse1,
			'weeks' => 3,
			'from' => '2012-09-10',
			'until' => '2012-09-29',
			'calculate' => 1,
			'visible' => 1,
			'active' => 1,
			'for_tuition' => 1
		);
		$this->oJourneyCourse1 = $this->_createJourneyCourse($this->oInquiry, $aCourse);		
		$this->iJourneyCourse1 = $this->oJourneyCourse1->id;

		$aCourse = array(
			'course_id' => $this->iCourse2,
			'weeks' => 3,
			'from' => '2012-09-10',
			'until' => '2012-09-29',
			'calculate' => 1,
			'visible' => 1,
			'active' => 1,
			'for_tuition' => 1
		);
		$this->oJourneyCourse2 = $this->_createJourneyCourse($this->oInquiry, $aCourse);
		$this->iJourneyCourse2 = $this->oJourneyCourse2->id;

		// Uhrzeit anlegen
		$oTemplate = new Ext_Thebing_Tuition_Template;
		$oTemplate->school_id = $this->iSchoolId;
		$oTemplate->custom = 1;
		$oTemplate->from = '08:00:00';
		$oTemplate->until = '12:00:00';
		$oTemplate->lessons = 4;
		$oTemplate->save();
		$iTemplateId1 = $oTemplate->id;

		$oTemplate = new Ext_Thebing_Tuition_Template;
		$oTemplate->school_id = $this->iSchoolId;
		$oTemplate->custom = 1;
		$oTemplate->from = '12:00:00';
		$oTemplate->until = '16:00:00';
		$oTemplate->lessons = 4;
		$oTemplate->save();
		$iTemplateId2 = $oTemplate->id;

		$oTemplate = new Ext_Thebing_Tuition_Template;
		$oTemplate->school_id = $this->iSchoolId;
		$oTemplate->custom = 1;
		$oTemplate->from = '16:00:00';
		$oTemplate->until = '20:00:00';
		$oTemplate->lessons = 4;
		$oTemplate->save();
		$iTemplateId3 = $oTemplate->id;

		$oTemplate = new Ext_Thebing_Tuition_Template;
		$oTemplate->school_id = $this->iSchoolId;
		$oTemplate->custom = 1;
		$oTemplate->from = '20:00:00';
		$oTemplate->until = '24:00:00';
		$oTemplate->lessons = 4;
		$oTemplate->save();
		$iTemplateId4 = $oTemplate->id;

		// Klasse anlegen
		$oClass = new Ext_Thebing_Tuition_Class;
		$oClass->name = 'Class';
		$oClass->school_id = $this->iSchoolId;
		$oClass->start_week = '2012-09-10';
		$oClass->weeks = 3;
		$oClass->active = 1;
		$oClass->courses = array($this->iCourse1, $this->iCourse2);
		$oClass->save();
		$iClassId = $oClass->id;

		$aWeeks = array(
			'2012-09-10',
			'2012-09-17',
			'2012-09-24'
		);
		
		$aWeekAttendance = array(
			array(
				0, 90, 30, 60
			),
			array(
				165, 135, 120, 105
			),
			array(
				90, 135, 15, 150
			)
		);

		foreach($aWeeks as $iWeek=>$sWeek) {

			// Blöcke anlegen
			$oBlock = new Ext_Thebing_School_Tuition_Block;
			$oBlock->school_id = $this->iSchoolId;
			$oBlock->teacher_id = $this->iTeacherId1;
			$oBlock->room_id = $iClassroomId;
			$oBlock->class_id = $iClassId;
			$oBlock->week = $sWeek;
			$oBlock->days = array(1,2,3,4,5);
			$oBlock->template_id = $iTemplateId1;
			$oBlock->save();
			$iBlockId1 = $oBlock->id;

			$oBlock = new Ext_Thebing_School_Tuition_Block;
			$oBlock->school_id = $this->iSchoolId;
			$oBlock->teacher_id = $this->iTeacherId2;
			$oBlock->room_id = $iClassroomId;
			$oBlock->class_id = $iClassId;
			$oBlock->week = $sWeek;
			$oBlock->days = array(1,2,3,4,5);
			$oBlock->template_id = $iTemplateId2;
			$oBlock->save();
			$iBlockId2 = $oBlock->id;

			$oBlock = new Ext_Thebing_School_Tuition_Block;
			$oBlock->school_id = $this->iSchoolId;
			$oBlock->teacher_id = $this->iTeacherId1;
			$oBlock->room_id = $iClassroomId;
			$oBlock->class_id = $iClassId;
			$oBlock->week = $sWeek;
			$oBlock->days = array(1,2,3,4,5);
			$oBlock->template_id = $iTemplateId3;
			$oBlock->save();
			$iBlockId3 = $oBlock->id;

			$oBlock = new Ext_Thebing_School_Tuition_Block;
			$oBlock->school_id = $this->iSchoolId;
			$oBlock->teacher_id = $this->iTeacherId2;
			$oBlock->room_id = $iClassroomId;
			$oBlock->class_id = $iClassId;
			$oBlock->week = $sWeek;
			$oBlock->days = array(1,2,3,4,5);
			$oBlock->template_id = $iTemplateId4;
			$oBlock->save();
			$iBlockId4 = $oBlock->id;

			// Zuweisungen
			$oAllocation = new Ext_Thebing_School_Tuition_Allocation;
			$oAllocation->block_id = $iBlockId1;
			$oAllocation->inquiry_course_id = $this->iJourneyCourse1;
			$oAllocation->course_id = $this->iCourse1;
			$oAllocation->save();

			$oAllocation = new Ext_Thebing_School_Tuition_Allocation;
			$oAllocation->block_id = $iBlockId2;
			$oAllocation->inquiry_course_id = $this->iJourneyCourse1;
			$oAllocation->course_id = $this->iCourse1;
			$oAllocation->save();

			$oAllocation = new Ext_Thebing_School_Tuition_Allocation;
			$oAllocation->block_id = $iBlockId3;
			$oAllocation->inquiry_course_id = $this->iJourneyCourse2;
			$oAllocation->course_id = $this->iCourse2;
			$oAllocation->save();

			$oAllocation = new Ext_Thebing_School_Tuition_Allocation;
			$oAllocation->block_id = $iBlockId4;
			$oAllocation->inquiry_course_id = $this->iJourneyCourse2;
			$oAllocation->course_id = $this->iCourse2;
			$oAllocation->save();
			
			// Anwesenheit eintragen
			$aData = array(
				'inquiry_id' => $this->oInquiry->id,
				'id' => $this->oJourneyCourse1->id,
				'teacher_id' => $this->oTeacher1->id,
				'course_id' => $this->oCourse1->id
			);
			$aDays = array(
				1 => $aWeekAttendance[$iWeek][0],
				2 => $aWeekAttendance[$iWeek][0],
				3 => $aWeekAttendance[$iWeek][0],
				4 => $aWeekAttendance[$iWeek][0],
				5 => $aWeekAttendance[$iWeek][0]
			);
			$this->_saveAttendance($aDays, $sWeek, $aData);

			$aData = array(
				'inquiry_id' => $this->oInquiry->id,
				'id' => $this->oJourneyCourse1->id,
				'teacher_id' => $this->oTeacher2->id,
				'course_id' => $this->oCourse1->id
			);
			$aDays = array(
				1 => $aWeekAttendance[$iWeek][1],
				2 => $aWeekAttendance[$iWeek][1],
				3 => $aWeekAttendance[$iWeek][1],
				4 => $aWeekAttendance[$iWeek][1],
				5 => $aWeekAttendance[$iWeek][1]
			);
			$this->_saveAttendance($aDays, $sWeek, $aData);

			$aData = array(
				'inquiry_id' => $this->oInquiry->id,
				'id' => $this->oJourneyCourse2->id,
				'teacher_id' => $this->oTeacher1->id,
				'course_id' => $this->oCourse2->id
			);
			$aDays = array(
				1 => $aWeekAttendance[$iWeek][2],
				2 => $aWeekAttendance[$iWeek][2],
				3 => $aWeekAttendance[$iWeek][2],
				4 => $aWeekAttendance[$iWeek][2],
				5 => $aWeekAttendance[$iWeek][2]
			);
			$this->_saveAttendance($aDays, $sWeek, $aData);

			$aData = array(
				'inquiry_id' => $this->oInquiry->id,
				'id' => $this->oJourneyCourse2->id,
				'teacher_id' => $this->oTeacher2->id,
				'course_id' => $this->oCourse2->id
			);
			$aDays = array(
				1 => $aWeekAttendance[$iWeek][3],
				2 => $aWeekAttendance[$iWeek][3],
				3 => $aWeekAttendance[$iWeek][3],
				4 => $aWeekAttendance[$iWeek][3],
				5 => $aWeekAttendance[$iWeek][3]
			);
			$this->_saveAttendance($aDays, $sWeek, $aData);

		}

	}

	/**
	 * Anwesenheit pro Buchung 
	 */
	public function testInquiry() {

		// Woche 1
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oInquiry);
		$oCalculator->setMode('inquiry');
		$oCalculator->setPeriod('2012-09-10', '2012-09-16');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(75, $fAttendance, 'First week');

		// Woche 2
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oInquiry);
		$oCalculator->setMode('inquiry');
		$oCalculator->setPeriod('2012-09-17', '2012-09-23');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(27.08, round($fAttendance, 2), 'Second week');

		// Woche 3
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oInquiry);
		$oCalculator->setMode('inquiry');
		$oCalculator->setPeriod('2012-09-24', '2012-09-30');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(45.83, round($fAttendance, 2), 'Third week');

		// Gesamt
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oInquiry);
		$oCalculator->setMode('inquiry');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(49.31, round($fAttendance, 2), 'Complete');
 
	}

	/**
	 * Anwesenheit pro Kursbuchung und Lehrer 
	 */
	public function testJourneyCourseTeacher() {

		// Woche 1
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course_teacher');
		$oCalculator->setPeriod('2012-09-10', '2012-09-16');
		$oCalculator->setTeacher($this->oTeacher1);

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(100, $fAttendance, 'First week');

		// Woche 2
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course_teacher');
		$oCalculator->setPeriod('2012-09-17', '2012-09-23');
		$oCalculator->setTeacher($this->oTeacher1);

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(8.33, round($fAttendance, 2), 'Second week');

		// Woche 3
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course_teacher');
		$oCalculator->setPeriod('2012-09-24', '2012-09-30');
		$oCalculator->setTeacher($this->oTeacher1);

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(50, round($fAttendance, 2), 'Third week');

		// Gesamt
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course_teacher');
		$oCalculator->setTeacher($this->oTeacher1);

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(52.78, round($fAttendance, 2), 'Complete');

	}

	/**
	 * Anwesenheit pro Kursbuchung 
	 */
	public function testJourneyCourse() {

		// Woche 1
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course');
		$oCalculator->setPeriod('2012-09-10', '2012-09-16');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(75, $fAttendance, 'First week');

		// Woche 2
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course');
		$oCalculator->setPeriod('2012-09-17', '2012-09-23');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(16.67, round($fAttendance, 2), 'Second week');

		// Woche 3
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course');
		$oCalculator->setPeriod('2012-09-24', '2012-09-30');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(37.5, round($fAttendance, 2), 'Third week');

		// Gesamt
		$oCalculator = new Ext_Thebing_Tuition_Attendance_Calculator($this->oJourneyCourse1);
		$oCalculator->setMode('journey_course');

		$fAttendance = $oCalculator->calculate();

		$this->assertEquals(43.06, round($fAttendance, 2), 'Complete');

	}

	/**
	 * Speichert die Anwesenheit
	 * @param array $aDays
	 * @param string $sWeek
	 * @param array $aData 
	 */
	protected function _saveAttendance($aDays, $sWeek, $aData) {

		$oAttendance = new Ext_Thebing_Attendance(
			$aData['inquiry_id'],
			$aData['id'],
			$sWeek,
			$aData['teacher_id'],
			$aData['course_id']
		);

		$oAttendance->saveAttendance($aDays);

	}
	
}