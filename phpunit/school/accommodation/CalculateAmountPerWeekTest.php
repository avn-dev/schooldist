<?php

include_once \Util::getDocumentRoot().'phpunit/school/accommodation/AmountSetUp.php';

/**
 * Unittest für die Unterkunftspreisberechnung
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class CalculateAmountPerWeekTest extends AmountSetUp
{
	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp()
    {
		$this->_sPriceStructure = 'week';
		
		parent::setUp();
		
		//Wochen anlegen, bei der Preis pro Woche Preisstruktur wird eine Dauer angegeben
		//
		//Falls mal diese Wochen nicht ausreichen, dann bitte hier keine speziellen Setups für die Wochen 
		//dazu ergänzen, bei manchen tests geht man davon aus, dass ab der 6.Woche nur noch Extrawochen kommen
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week'	=> 1,
			'week_count'	=> 3,
		));
		
		$this->_aWeeks[] = $this->_createWeek($this->_oSchool, array(
			'start_week'	=> 4,
			'week_count'	=> 10,
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
			70,
			60,
			50,
		));
		
		//Preise für die 1.Hochsaison anlegen
		$this->_createWeekPrices(2, array(
			80,
			70,
			60,
		));
		
		//Preise für die 2.Hochsaison anlegen
		$this->_createWeekPrices(3, array(
			90,
			80,
			70,
		));
		
		//Preise für die 3.Hochsaison anlegen
		$this->_createWeekPrices(4, array(
			100,
			90,
			80,
		));
		
		//Preise für die 4.Hochsaison anlegen
		$this->_createWeekPrices(5, array(
			110,
			100,
			90,
		));
		
		//Preise für die 5.Hochsaison anlegen
		$this->_createWeekPrices(6, array(
			120,
			110,
			100,
		));
		
		//Preise für die 6.Hochsaison anlegen
		$this->_createWeekPrices(7, array(
			130,
			120,
			110,
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
		
		$this->_assertPriceEquals(320, $fAmount);
		
		//Das gleiche mit Startwoche 10 testen
		$fAmountWithStartWeek = $this->_calculateAmount($oJourneyAccommodation, 10);
		
		$this->_assertPriceEquals(270, $fAmountWithStartWeek);

	}
	
	/**
	 * Unterkunft trifft 3 Hochsaisons
	 */
	public function test3HighSeasons()
	{
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-02-26',
			'until'	=> '2012-04-15',
			'weeks'	=> '7',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);

		$this->_assertPriceEquals(570, $fAmount);
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

		$this->_assertPriceEquals(640, $fAmount);
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
		
		$this->_assertPriceEquals(220, $fAmount);
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
		
		$this->_assertPriceEquals(1260, $fAmount);
	}
	
	/**
	 * Zuerst Hochsaison1, dann Hochsaison2, dann wieder Hochsaison1
	 * (Hochsaison1 beinhaltet Hochsaison2)
	 */
	public function testHighSeasonContainsOtherHighSeason()
	{
		$oJourneyAccommodation = $this->_createJourneyAccommodation(array(
			'from'	=> '2012-07-22',
			'until'	=> '2012-09-02',
			'weeks'	=> '6',
		));
		
		$fAmount = $this->_calculateAmount($oJourneyAccommodation);
		
		$this->_assertPriceEquals(690, $fAmount);
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
		
		$this->_assertPriceEquals(210, $fAmount);
		
		//Das gleiche mit Startwoche 2 testen
		$fAmountWithStartWeek = $this->_calculateAmount($oJourneyAccommodation, 2);
		
		$this->_assertPriceEquals(180, $fAmountWithStartWeek);
	}
}