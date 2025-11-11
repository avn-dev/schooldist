<?php

include_once \Util::getDocumentRoot().'phpunit/school/accommodation/AmountSetUp.php';

/**
 * Unittest für die Unterkunftspreisberechnung
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class CalculateAmountNormalTest extends AmountSetUp
{
	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp()
    {
		$this->_sPriceStructure = 'normal';
		
		parent::setUp();
		
		//Wochen anlegen, bei der normalen Preisstruktur wird keine Dauer angegeben, nur eine Startwoche
		//
		//Falls mal diese Wochen nicht ausreichen, dann bitte hier keine speziellen Setups für die Wochen 
		//dazu ergänzen, bei manchen tests geht man davon aus, dass ab der 6.Woche nur noch Extrawochen kommen
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week' => 1,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week' => 2,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week' => 3,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week' => 4,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week' => 5,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week' => 6,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'title'			=> 'Extra Week',
			'extra'			=> 1,
		));
		
		//Unterkunftskategorie ist von den Wochen abhängig, darum erstellen wir die Unterkunftskategorie hier
		//Wenn kein zweiter Parameter mit week daten übergeben werden, dann werden alle Wochen der Schule in die Unterkunftskategorie übernommen
		$this->_oAccommodationCategory = $this->_createDefaultAccommodationCategory($this->_oSchool);
		
		//Preise für die Hauptsaison anlegen
		$this->_createWeekPrices(1, array(
			100,
			180,
			270,
			340,
			425,
			480,
			70,
		));
		
		//Preise für die 1.Hochsaison anlegen
		$this->_createWeekPrices(2, array(
			110,
			210,
			315,
			400,
			500,
			570,
			80,
		));
		
		//Preise für die 2.Hochsaison anlegen
		$this->_createWeekPrices(3, array(
			120,
			230,
			345,
			440,
			550,
			630,
			90,
		));
		
		//Preise für die 3.Hochsaison anlegen
		$this->_createWeekPrices(4, array(
			130,
			250,
			375,
			480,
			600,
			690,
			95,
		));
		
		//Preise für die 4.Hochsaison anlegen
		$this->_createWeekPrices(5, array(
			140,
			270,
			405,
			520,
			650,
			750,
			100,
		));
		
		//Preise für die 5.Hochsaison anlegen
		$this->_createWeekPrices(6, array(
			150,
			290,
			435,
			560,
			700,
			810,
			110,
		));
		
		//Preise für die 6.Hochsaison anlegen
		$this->_createWeekPrices(7, array(
			160,
			310,
			465,
			600,
			750,
			870,
			120,
		));
	}
	
	/**
	 * 1 Saisonwechsel von Hauptsaison auf Hochsaison
	 */
	public function test1MainSeasonAnd1HighSeason()
	{

		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-01-15',
			'until'	=> '2012-02-19',
			'weeks'	=> '5',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(455, $fAmount);
		
		//Das gleiche mit Startwoche 1 testen
		$fAmountWithStartWeek = $this->_calculateAmount($oJourneyAccommodation, 1);
		
		$this->_assertPriceEquals(410, $fAmountWithStartWeek);

	}
	
	/**
	 * Unterkunft trifft 3 Hochsaisons
	 * Hier arbeiten wir auch teilweise mit Extrawochen
	 */
	public function test3HighSeasons()
	{
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-02-26',
			'until'	=> '2012-04-15',
			'weeks'	=> '7',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);

		$this->_assertPriceEquals(725, $fAmount);
	}
	
	/**
	 * Zuerst Hochsaison, dann Hauptsaison & dann wieder Hochsaison
	 * Hier arbeiten wir nur mit Extrawochen
	 */
	public function test1MainSeasonAnd2HighSeasons()
	{
		$this->_makeAccommodationCategoryOnlyExtraWeek();
		
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-04-08',
			'until'	=> '2012-06-17',
			'weeks'	=> '10',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);

		$this->_assertPriceEquals(810, $fAmount);
	}

	/**
	 * Unterkunft fängt genau am Tag des Saisonwechsels(zu einer Hochsaison) an
	 */
	public function test1HighSeasonWithSeasonChangeStartDate()
	{
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-06-03',
			'until'	=> '2012-06-17',
			'weeks'	=> '2',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(270, $fAmount);
	}

	/**
	 * Zuerst Hauptsaison, dann Hochsaison 1, dann Hochsaison 2, dann Hochsaison 1, dann wieder Hauptsaison 
	 * (Hochsaison1 beinhaltet Hochsaison2)
	 * Hier arbeiten wir teilweise mit Extrawochen(Saisonübergreifend)
	 */
	public function test4SeasonChanges()
	{
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-06-24',
			'until'	=> '2012-09-23',
			'weeks'	=> '13',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(1420, $fAmount);
	}
	
	/**
	 * Zuerst Hochsaison1, dann Hochsaison2, dann wieder Hochsaison1
	 * (Hochsaison1 beinhaltet Hochsaison2)
	 */
	public function testHighSeasonContainsOtherHighSeason()
	{
		// Kompletter Zeitraum
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-07-15',
			'until'	=> '2012-09-02',
			'weeks'	=> '7',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(950, $fAmount);
		
		// Erste Woche
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-07-15',
			'until'	=> '2012-07-22',
			'weeks'	=> '1',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(150, $fAmount);
		
		// Sechs einzelne Wochen
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-07-22',
			'until'	=> '2012-09-02',
			'weeks'	=> '6',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(840, $fAmount);
		
		// Sechs Folgewochen
		$fAmountWithStartWeek = $this->_calculateAmount($oJourneyAccommodation, 1);
		
		$this->_assertPriceEquals(800, $fAmountWithStartWeek);

	}
	
	/**
	 * Unterkunft liegt nur in der Hauptsaison
	 */
	public function testOneMainSaison()
	{
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-11-11',
			'until'	=> '2012-12-02',
			'weeks'	=> '3',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(270, $fAmount);
		
		// Das gleiche mit Startwoche 2 testen
		$fAmountWithStartWeek = $this->_calculateAmount($oJourneyAccommodation, 2);
		
		$this->_assertPriceEquals(245, $fAmountWithStartWeek);

	}

}