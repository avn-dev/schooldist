<?php

/**
 * Check the WDDate
 */
class WDDateTest extends coreTestSetup
{
	/**
	 * Test the WDDate::add() and WDDate::sub() methods
	 */
	public function testAddSub()
	{
		$oDate = new WDDate('2004-02-29 12:00:00', WDDate::DB_DATETIME);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->add(1, WDDate::YEAR);

		$this->assertEquals('2005-02-28 12:00:00', $oDate->get(WDDate::DB_DATETIME));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2004-01-31 12:00:00', WDDate::DB_DATETIME);

		$oDate->add(1, WDDate::MONTH);

		$this->assertEquals('2004-02-29 12:00:00', $oDate->get(WDDate::DB_DATETIME));

		$oDate->add(1, WDDate::MONTH);

		$this->assertEquals('2004-03-29 12:00:00', $oDate->get(WDDate::DB_DATETIME));

		$oDate->add(1, WDDate::MONTH);

		$this->assertEquals('2004-04-29 12:00:00', $oDate->get(WDDate::DB_DATETIME));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2004-01-31 12:00:00', WDDate::DB_DATETIME);

		$oDate->add(2, WDDate::MONTH);

		$this->assertEquals('2004-03-31 12:00:00', $oDate->get(WDDate::DB_DATETIME));

		$oDate->add(24, WDDate::MONTH);

		$this->assertEquals('2006-03-31 12:00:00', $oDate->get(WDDate::DB_DATETIME));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2006-03-31 12:00:00', WDDate::DB_DATETIME);

		$oDate							// 2006-03-31 12:00:00
			->add(8, WDDate::SECOND)	// 2006-03-31 12:00:08
			->add(7, WDDate::MINUTE)	// 2006-03-31 12:07:08
			->add(6, WDDate::HOUR)		// 2006-03-31 18:07:08
			->add(5, WDDate::WEEK)		// 05.05.2006 18:07:08
			->add(4, WDDate::DAY)		// 09.05.2006 18:07:08
			->add(3, WDDate::MONTH)		// 09.08.2006 18:07:08
			->add(2, WDDate::QUARTER)	// 09.02.2007 18:07:08
			->add(1, WDDate::YEAR);		// 09.02.2008 18:07:08

		$this->assertEquals('2008-02-09 18:07:08', $oDate->get(WDDate::DB_DATETIME));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1950-06-15 12:00:00', WDDate::DB_DATETIME);

		$oDate							// 1950-06-15 12:00:00
			->sub(8, WDDate::SECOND)	// 1950-06-15 11:59:52
			->sub(7, WDDate::MINUTE)	// 1950-06-15 11:52:52
			->sub(6, WDDate::HOUR)		// 1950-06-15 05:52:52
			->sub(5, WDDate::WEEK)		// 1950-05-11 05:52:52
			->sub(4, WDDate::DAY)		// 1950-05-07 05:52:52
			->sub(3, WDDate::MONTH)		// 1950-02-07 05:52:52
			->sub(2, WDDate::QUARTER)	// 1949-08-07 05:52:52
			->sub(1, WDDate::YEAR);		// 1948-08-07 05:52:52

		$this->assertEquals('1948-08-07 05:52:52', $oDate->get(WDDate::DB_DATETIME));
	}


	/**
	 * Test the auto correcture of month day by changing of month or year
	 */
	public function testCorrectureOfMonthDay()
	{
		$oDate = new WDDate('2012-08-31', WDDate::DB_DATE);

		$oDate->set(2, WDDate::MONTH);

		$this->assertEquals('2012-02-29', $oDate->get(WDDate::DB_DATE));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate = new WDDate('2012-02-29', WDDate::DB_DATE);

		$oDate->add(1, WDDate::YEAR);

		$this->assertEquals('2013-02-28', $oDate->get(WDDate::DB_DATE));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate = new WDDate('2012-02-29', WDDate::DB_DATE);

		$oDate->set(2015, WDDate::YEAR);

		$this->assertEquals('2015-02-28', $oDate->get(WDDate::DB_DATE));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate = new WDDate('1808-02-29', WDDate::DB_DATE);

		$oDate->set(1812, WDDate::YEAR);

		$this->assertEquals('1812-02-29', $oDate->get(WDDate::DB_DATE));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate = new WDDate('1808-02-29', WDDate::DB_DATE);

		$oDate->sub(8, WDDate::YEAR);

		$this->assertEquals('1800-02-28', $oDate->get(WDDate::DB_DATE));
	}


	/**
	 * Test the WDDate::_mktime()
	 */
	public function testMKTime()
	{
		$oDate = new WDDate();

		$this->assertEquals(-3785132808, $oDate->makeTS(14, 13, 12, 1, 20, 1850));
		$this->assertEquals(+1332554400, $oDate->makeTS(2, 00, 00, 3, 24, 2012));
		$this->assertEquals(+1332558000, $oDate->makeTS(3, 00, 00, 3, 24, 2012));
		$this->assertEquals(+1178449933, $oDate->makeTS(11, 12, 13, 5, 6, 2007));
	}


	/**
	 * Test the calculation of week number
	 */
	public function testWeekNumber()
	{
		$oDate = new WDDate();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2044-01-03', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2043-12-28', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2009-12-28', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2004-12-27', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1964-12-28', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1959-12-28', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1960-01-03', WDDate::DB_DATE);

		$this->assertEquals(53, $oDate->get(WDDate::WEEK));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1940-12-30', WDDate::DB_DATE);

		$this->assertEquals(1, $oDate->get(WDDate::WEEK));
	}


	/**
	 * Test the calculation of week day number
	 */
	public function testWeekDayNumber()
	{
		$oDate = new WDDate();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1950-09-22', WDDate::DB_DATE);

		$this->assertEquals(5, $oDate->get(WDDate::WEEKDAY));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1861-02-12', WDDate::DB_DATE);

		$this->assertEquals(2, $oDate->get(WDDate::WEEKDAY));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2012-10-31', WDDate::DB_DATE);

		$this->assertEquals(3, $oDate->get(WDDate::WEEKDAY));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2093-03-22', WDDate::DB_DATE);

		$this->assertEquals(7, $oDate->get(WDDate::WEEKDAY));
	}


	/**
	 * Test the WDDate::_isLeapYear() method
	 */
	public function testLeapYear()
	{
		$oDate = new WDDate();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1951', WDDate::YEAR);

		$this->assertEquals(365, $oDate->get(WDDate::YEAR_DAYS));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2000', WDDate::YEAR);

		$this->assertEquals(366, $oDate->get(WDDate::YEAR_DAYS));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('2004', WDDate::YEAR);

		$this->assertEquals(366, $oDate->get(WDDate::YEAR_DAYS));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDate->set('1800', WDDate::YEAR);

		$this->assertEquals(365, $oDate->get(WDDate::YEAR_DAYS));
	}
}

?>
