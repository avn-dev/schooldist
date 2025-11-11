<?php

/**
 * Unittest für Kursinformationen für eine Buchung
 * Folgende Informationen werden getestet
 * - aktuelle Kurswoche (Woche x von y Kursbezogen)
 * - Woche gesamt (Wochen x von y Buchungsbezogen)
 * - Status des Schülers (N,C,V,VR,L)
 * 
 * @author Mehmet Durmaz
 */
class CourseDataTest extends coreTestSetup
{
	/**
	 * Überprüfen ob Status "N" nur für einen Kurs funktioniert
	 */
	public function testStateN()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-02-22'
			)
		));
		
		$oDate = new WDDate('2013-02-11', WDDate::DB_DATE);

		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'N',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 2,
			'weeks_from'			=> 1,
			'weeks'					=> 2,
		));
	}
	
	/**
	 * Überprüfen ob Status "C" nur für einen Kurs funktioniert
	 */
	public function testStateC()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-03-01'
			)
		));
		
		$oDate = new WDDate('2013-02-18', WDDate::DB_DATE);

		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 3,
			'weeks_from'			=> 2,
			'weeks'					=> 3,
		));
	}
	
	/**
	 * Überprüfen ob Status "L" nur für einen Kurs funktioniert
	 */
	public function testStateL()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-03-01'
			)
		));
		
		$oDate = new WDDate('2013-02-25', WDDate::DB_DATE);

		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'L',
			'current_weeks_from'	=> 3,
			'current_weeks'			=> 3,
			'weeks_from'			=> 3,
			'weeks'					=> 3,
		));
	}
	
	/**
	 * Überprüfen ob Informationen mit Kursen für verschiedene Zeiträume klappen
	 */
	public function testMultipleCoursesDifferentTimeFrame()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-03-01'
			),
			array(
				'from'		=> '2013-03-18',
				'until'		=> '2013-03-29'
			),
		));
		
		// Bei der ersten Woche des Folgekurses, darf nicht "N" kommen, sondern "C"
		$oDate = new WDDate('2013-03-18', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 2,
			'weeks_from'			=> 4,
			'weeks'					=> 5,
		));
		
		// Bei der letzten Woche des Erstkurses, darf nicht "L" kommen, sondern "C"
		$oDate = new WDDate('2013-02-25', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 3,
			'current_weeks'			=> 3,
			'weeks_from'			=> 3,
			'weeks'					=> 5,
		));
		
		// Bei der ersten Woche des Erstkurses, muss "N" kommen
		$oDate = new WDDate('2013-02-11', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'N',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 3,
			'weeks_from'			=> 1,
			'weeks'					=> 5,
		));
		
		// Bei der letzten Woche des Folgekurses, muss "L" kommen
		$oDate = new WDDate('2013-03-25', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'L',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 2,
			'weeks_from'			=> 5,
			'weeks'					=> 5,
		));
	}
	
	/**
	 * Überprüfen ob Informationen mit Kursen die vom Zeitraum her sich überschneiden klappen
	 */
	public function testMultipleCoursesOverlap()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-03-01'
			),
			array(
				'from'		=> '2013-02-25',
				'until'		=> '2013-03-08'
			),
		));
		
		// Für den ersten Kurs wäre das Status "L", für den anderen Status "N", doch kombiniert sollte es Status "C" sein (Kurs1 Wochen Test)
		$oDate = new WDDate('2013-02-25', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 3,
			'current_weeks'			=> 3,
			'weeks_from'			=> 3,
			'weeks'					=> 4,
		));
		
		// Für den ersten Kurs wäre das Status "L", für den anderen Status "N", doch kombiniert sollte es Status "C" sein (Kurs2 Wochen Test)
		$oDate = new WDDate('2013-02-25', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 2,
			'weeks_from'			=> 3,
			'weeks'					=> 4,
		));
		
		// Status N Check
		$oDate = new WDDate('2013-02-11', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'N',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 3,
			'weeks_from'			=> 1,
			'weeks'					=> 4,
		));
		
		// Status L Check
		$oDate = new WDDate('2013-03-04', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'L',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 2,
			'weeks_from'			=> 4,
			'weeks'					=> 4,
		));
		
	}
	
	/**
	 * Überprüfen ob Informationen mit Kursen mit dem selben Startdatum(beinhaltende Kurse) klappen
	 */
	public function testMultipleCoursesIncluding()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-03-15'
			),
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-02-22'
			),
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-03-22'
			),
		));
		
		$oDate = new WDDate('2013-02-18', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 5,
			'weeks_from'			=> 2,
			'weeks'					=> 6,
		));
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 2,
			'weeks_from'			=> 2,
			'weeks'					=> 6,
		));
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 3);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 6,
			'weeks_from'			=> 2,
			'weeks'					=> 6,
		));
		
	}
	
	/**
	 * Überprüfen ob Informationen mit Schülerferien klappt
	 */
	public function testHoliday()
	{
		$oInquiry = $this->getInquiryWithCourses(array(
			array(
				'from'		=> '2013-02-11',
				'until'		=> '2013-02-22'
			),
			array(
				'from'		=> '2013-03-11',
				'until'		=> '2013-03-22'
			),
		));
		
		$aHolidays = array(
			array(
				'from'		=> '2013-02-25',
				'until'		=> '2013-03-08',
				'id'		=> 'holiday_1',
			)
		);
		
		$oInquiry->holidays = $aHolidays;
		
		// 1.Woche Status "N"
		$oDate = new WDDate('2013-02-11', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'N',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 2,
			'weeks_from'			=> 1,
			'weeks'					=> 6,
		));
		
		// 2.Woche Status "C"
		$oDate = new WDDate('2013-02-18', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'C',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 2,
			'weeks_from'			=> 2,
			'weeks'					=> 6,
		));
		
		// 3.Woche Status "V"
		$oDate = new WDDate('2013-02-25', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'V',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 2,
			'weeks_from'			=> 3,
			'weeks'					=> 6,
		));
		
		// 4.Woche Status "V"
		$oDate = new WDDate('2013-03-04', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 1);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'V',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 2,
			'weeks_from'			=> 4,
			'weeks'					=> 6,
		));
		
		// 5.Woche Status "VR"
		$oDate = new WDDate('2013-03-11', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'VR',
			'current_weeks_from'	=> 1,
			'current_weeks'			=> 2,
			'weeks_from'			=> 5,
			'weeks'					=> 6,
		));
		
		// 6.Woche Status "L"
		$oDate = new WDDate('2013-03-18', WDDate::DB_DATE);
		
		$oCourseData = new Ext_TS_Inquiry_Journey_Course_Data($oInquiry, $oDate, 2);
		
		$this->checkCourseData($oCourseData, array(
			'state'					=> 'L',
			'current_weeks_from'	=> 2,
			'current_weeks'			=> 2,
			'weeks_from'			=> 6,
			'weeks'					=> 6,
		));
	}
	
	/**
	 * Die ganzen asserts über die zentrale Methode einfügen
	 * 
	 * @param Ext_TS_Inquiry_Journey_Course_Data $oCourseData
	 * @param array $aData 
	 */
	public function checkCourseData(Ext_TS_Inquiry_Journey_Course_Data $oCourseData, array $aData)
	{
		$sState				= $oCourseData->getState();
		
		$iCurrentWeeksFrom	= $oCourseData->getCurrentWeeksFrom();
		
		$iCurrentWeeks		= $oCourseData->getCurrentWeeks();
		
		$iWeeksFrom			= $oCourseData->getWeeksFrom();
		
		$iWeeks				= $oCourseData->getWeeks();

		$this->assertEquals($aData['state'], $sState, '"state" sollte "' . $aData['state'] . '" sein, es kommt aber "' . $sState . '" raus!');
		
		$this->assertEquals($aData['current_weeks_from'], $iCurrentWeeksFrom, '"current_weeks_from" sollte "' . $aData['current_weeks_from'] . '" sein, es kommt aber "' . $iCurrentWeeksFrom . '" raus!');
		
		$this->assertEquals($aData['current_weeks'], $iCurrentWeeks, '"current_weeks" sollte "' . $aData['current_weeks'] . '" sein, es kommt aber "' . $iCurrentWeeks . '" raus!');
		
		$this->assertEquals($aData['weeks_from'], $iWeeksFrom, '"weeks_from" sollte "' . $aData['weeks_from'] . '" sein, es kommt aber "' . $iWeeksFrom . '" raus!');
		
		$this->assertEquals($aData['weeks'], $iWeeks, '"weeks" sollte "' . $aData['weeks'] . '" sein, es kommt aber "' . $iWeeks . '" raus!');
	}
	
	/**
	 * Buchung mit Kursen anlegen (wird nicht in die DB gespeichert)
	 * 
	 * @param array $aInquiryCourses
	 * @return Ext_TS_Inquiry 
	 */
	public function getInquiryWithCourses(array $aInquiryCourses)
	{
		$oInquiry = new Ext_TS_Inquiry();
		
		$oJourney = $oInquiry->getJourney();
		
		$iCounter = 1;
		
		foreach($aInquiryCourses as $aInquiryCourse)
		{
			$aData	= array(
				'from'			=> $aInquiryCourse['from'],
				'until'			=> $aInquiryCourse['until'],
				'id'			=> $iCounter,
				'visible'		=> 1,
				'active'		=> 1,
				'journey_id'	=> $oJourney->id,
			);

			$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getObjectFromArray($aData);
			
			$oJourney->getJoinedObjectChild('courses', $oJourneyCourse);
			
			$iCounter++;
		}
		
		return $oInquiry;
	}
}