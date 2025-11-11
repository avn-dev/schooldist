<?php

class Ext_Thebing_Db_StoredFunctions extends Ext_TC_Db_StoredFunctions {

	public static function updateStoredFunctions() {
		self::getAge();
		self::getSubAmountByDates();
		//self::getTransferLocation();
		self::getCurrencyFactor();
		self::calcAmountByCurrencyFactors();
		self::calcWeeksFromAccommodationDates();
		self::calcWeeksFromCourseDates();
		self::calcWeeksPart();
		self::getCorrectCourseStartDay();
		self::getRealDateFromTuitionWeek();
	}

	/**
	 * Create a function which calculates the age in years by birthdate
	 */
	public static function getAge()
	{

		$sSQL = "
			DROP FUNCTION IF EXISTS
				getAge
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION getAge(sDate DATE)
				RETURNS INT
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN CASE 
					WHEN sDate = '0000-00-00' THEN NULL
					ELSE TIMESTAMPDIFF(YEAR, sDate, CURDATE())
				END;
			END
		";
		DB::executeQuery($sSQL);
	}


	/**
	 * Get a part of amount by dates
	 */
	public static function getSubAmountByDates()
	{
		$sSQL = "
			DROP FUNCTION IF EXISTS
				getSubAmountByDates
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION getSubAmountByDates(iAmount DECIMAL(18,9), sTimeFrom DATE, sTimeTill DATE, sEntryFrom DATE, sEntryTill DATE)
				RETURNS DECIMAL(18,9)
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN(
					IF(
						sEntryTill >= sTimeFrom AND sEntryFrom <= sTimeTill,
						IF(
							sEntryFrom < sTimeFrom OR sEntryTill > sTimeTill,
							(
								(
									iAmount / (DATEDIFF(sEntryTill, sEntryFrom) + 1)
								) *
								(
									(DATEDIFF(sEntryTill, sEntryFrom) + 1) -
									(
										IF(
											sEntryTill > sTimeTill AND sEntryFrom < sTimeFrom,
											(
												DATEDIFF(sTimeFrom, sEntryFrom) +
												DATEDIFF(sEntryTill, sTimeTill)
											),
											(
												IF(
													sEntryFrom < sTimeFrom,
													DATEDIFF(sTimeFrom, sEntryFrom),
													DATEDIFF(sEntryTill, sTimeTill)
												)
											)
										)
									)
								)
							),
							iAmount
						),
						0
					)
				);
			END
		";
		DB::executeQuery($sSQL);
	}


	/**
	 * Create a function which returns the currency factor by ID and date
	 */
	/*public static function getTransferLocation()
	{
		$sSQL = "
			DROP FUNCTION IF EXISTS
				getTransferLocation
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION getTransferLocation(sType TEXT, iId INT)
				RETURNS TEXT
				DETERMINISTIC
				READS SQL DATA
			BEGIN

				DECLARE sName TEXT;

				IF iId = 0 THEN
					SET sName = sType;
				ELSEIF sType = 'location' THEN
					SET sName = (
						SELECT
							IF(`airport` = '', `airport_en`, `airport`)
						FROM
							`kolumbus_airports`
						WHERE
							`id` = iId
						LIMIT 1
					);
				ELSEIF sType = 'accommodation' THEN
					SET sName = (
						SELECT
							`ext_33`
						FROM
							`customer_db_4`
						WHERE
							`id` = iId
						LIMIT 1
					);
				ELSEIF sType = 'school' THEN
					SET sName = (
						SELECT
							`ext_1`
						FROM
							`customer_db_2`
						WHERE
							`id` = iId
						LIMIT 1
					);
				END IF;

				RETURN sName;

			END
		";
		//DB::executeQuery($sSQL);
	}*/


	/**
	 * Create a function which returns the currency factor by ID and date
	 */
	public static function getCurrencyFactor()
	{
		$sSQL = "
			DROP FUNCTION IF EXISTS
				getCurrencyFactor
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION getCurrencyFactor(iCurrencyID INT, sDate DATE)
				RETURNS TEXT
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN(
					IFNULL(
						(
							SELECT
								`factor`
							FROM
								`kolumbus_currency_factor`
							WHERE
								`active`		= 1 AND
								`currency_id`	= iCurrencyID AND
								`date`			=
								(
									SELECT
										MAX(`date`)
									FROM
										`kolumbus_currency_factor`
									WHERE
										`active`		= 1 AND
										`currency_id`	= iCurrencyID AND
										`date`			<= sDate
								)
							LIMIT
								1
						),
						1.0
					)
				);
			END
		";
		DB::executeQuery($sSQL);
	}

	/**
	 * Währungsbetrag umrechnen anhand Ursprungswährung (plus Datum) und Zielwährung (plus Datum)
	 *
	 * Achtung: Wenn die Währung gleich ist (aber nicht das Datum), findet eine Inflationsbereinigung statt!
	 */
	public static function calcAmountByCurrencyFactors()
	{
		$sSQL = "
			DROP FUNCTION IF EXISTS
				calcAmountByCurrencyFactors
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION calcAmountByCurrencyFactors(iAmount DECIMAL(18,9), iCurrencyFrom INT, sDateFrom DATE, iCurrencyTo INT, sDateTo DATE)
				RETURNS DECIMAL(18,9)
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				DECLARE iFactorFrom, iFactorTo DECIMAL(10,4);
				SET iFactorFrom = getCurrencyFactor(iCurrencyFrom, sDateFrom);
				SET iFactorTo = getCurrencyFactor(iCurrencyTo, sDateTo);
				RETURN(
					IF(
						iAmount IS NULL,
						0,
						(
							iAmount /
							IF(
								iFactorFrom > 0,
								iFactorFrom,
								1
							)
						) *
						IF(
							iFactorTo > 0,
							iFactorTo,
							1
						)
					)
				);
			END
		";
		DB::executeQuery($sSQL);
	}


	/**
	 * Create a function which calculates the real days of an accommodation
	 */
	public static function calcWeeksFromAccommodationDates()
	{
		$sSQL = "
			DROP FUNCTION IF EXISTS
				calcAccommodationWeeksPart
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION calcAccommodationWeeksPart(sFrom DATE, sTill DATE)
				RETURNS DECIMAL(18,9)
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN(
					IF(sFrom > sTill, 0, (DATEDIFF(sTill, sFrom) + 1 /* incl. Endtag  */) / 7)
				);
			END
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			DROP FUNCTION IF EXISTS
				calcWeeksFromAccommodationDates
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION calcWeeksFromAccommodationDates(sPeriodFrom DATE, sPeriodTill DATE, sAccommFrom DATE, sAccommTill DATE)
				RETURNS DECIMAL(18,9)
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN(
					IF(
						(
							/*
								---|----------|--- Zeitraum
								-----|-----|------ Unterkunft
							*/
							sPeriodTill >= sAccommTill AND sPeriodFrom <= sAccommFrom
						),
						calcAccommodationWeeksPart(sAccommFrom, sAccommTill),
						IF(
							(
								/*
									---|----------|--- Zeitraum
									-----|----------|- Unterkunft
								*/
								sPeriodTill <= sAccommTill AND sPeriodFrom <= sAccommFrom
							),
							calcAccommodationWeeksPart(sAccommFrom, sPeriodTill),
							IF(
								(
									/*
										---|----------|--- Zeitraum
										-|----------|----- Unterkunft
									*/
									sPeriodTill >= sAccommTill AND sPeriodFrom >= sAccommFrom
								),
								calcAccommodationWeeksPart(sPeriodFrom, sAccommTill),
								/*
									---|----------|--- Zeitraum
									-|--------------|- Unterkunft
								*/
								calcAccommodationWeeksPart(sPeriodFrom, sPeriodTill)
							)
						)
					)
				);
			END
		";
		DB::executeQuery($sSQL);
	}


	/**
	 * Create a function which calculates the real days of a course
	 */
	public static function calcWeeksFromCourseDates()
	{

		self::calcWeeksPart();

		$sSQL = "
			DROP FUNCTION IF EXISTS
				calcWeeksFromCourseDates
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION calcWeeksFromCourseDates(sPeriodFrom DATE, sPeriodTill DATE, sCourseFrom DATE, sCourseTill DATE)
				RETURNS DECIMAL(18,9)
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN(
					IF(
						(
							sCourseFrom IS NULL OR 
							sCourseTill IS NULL
						),
						NULL,
						IF(
							(
								/*
									Zeitraum berührt nicht
								*/
								sPeriodFrom > sCourseTill OR sPeriodTill < sCourseFrom
							),
							0,
							IF(
								(
									/*
										---|----------|--- Zeitraum
										-----|-----|------ Kurs
									*/
									sPeriodTill >= sCourseTill AND sPeriodFrom <= sCourseFrom
								),
								calcWeeksPart(sCourseFrom, sCourseTill),
								IF(
									(
										/*
											---|----------|--- Zeitraum
											-----|----------|- Kurs
										*/
										sPeriodTill <= sCourseTill AND sPeriodFrom <= sCourseFrom
									),
									calcWeeksPart(sCourseFrom, sPeriodTill),
									IF(
										(
											/*
												---|----------|--- Zeitraum
												-|----------|----- Kurs
											*/
											sPeriodTill >= sCourseTill AND sPeriodFrom >= sCourseFrom
										),
										calcWeeksPart(sPeriodFrom, sCourseTill),
										/*
											---|----------|--- Zeitraum
											-|--------------|- Kurs
										*/
										calcWeeksPart(sPeriodFrom, sPeriodTill)
									)
								)
							)
						)
					)
				);
			END
		";
		DB::executeQuery($sSQL);
	}

	/**
	 * Create a function which calculates the real days of a course
	 */
	public static function calcWeeksPart()
	{
		$sSQL = "
			DROP FUNCTION IF EXISTS
				calcWeeksPart
		";
		DB::executeQuery($sSQL);

		$sSQL = "
			CREATE FUNCTION calcWeeksPart(sFrom DATE, sTill DATE)
				RETURNS DECIMAL(18,9)
				DETERMINISTIC
				READS SQL DATA
			BEGIN
				RETURN(
					IF(
						(
							IF(
								sFrom > sTill, 0,
								(
									(
										(
											DATEDIFF(
												IF(
													CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 6, /* Samstag */
													sTill - INTERVAL 1 DAY,
													IF(
														CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 0, /* Sonntag */
														sTill - INTERVAL 2 DAY,
														sTill
													)
												),
												IF(
													CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 6, /* Samstag */
													sFrom + INTERVAL 2 DAY,
													IF(
														CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 0, /* Sonntag */
														sFrom + INTERVAL 1 DAY,
														sFrom
													)
												)
											)
										) + 1 /* incl. Endtag  */
									) -
									(
										TRUNCATE(
											(
												(
													DATEDIFF(
														IF(
															CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 6, /* Samstag */
															sTill - INTERVAL 1 DAY,
															IF(
																CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 0, /* Sonntag */
																sTill - INTERVAL 2 DAY,
																sTill
															)
														),
														IF(
															CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 6, /* Samstag */
															sFrom + INTERVAL 2 DAY,
															IF(
																CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 0, /* Sonntag */
																sFrom + INTERVAL 1 DAY,
																sFrom
															)
														)
													)
												) + 1 /* incl. Endtag  */
											) / 7,
											0
										) * 2
									)
								) / 5
							)
						) > 0,
						(
							IF(
								sFrom > sTill, 0,
								(
									(
										(
											DATEDIFF(
												IF(
													CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 6, /* Samstag */
													sTill - INTERVAL 1 DAY,
													IF(
														CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 0, /* Sonntag */
														sTill - INTERVAL 2 DAY,
														sTill
													)
												),
												IF(
													CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 6, /* Samstag */
													sFrom + INTERVAL 2 DAY,
													IF(
														CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 0, /* Sonntag */
														sFrom + INTERVAL 1 DAY,
														sFrom
													)
												)
											)
										) + 1 /* incl. Endtag  */
									) -
									(
										TRUNCATE(
											(
												(
													DATEDIFF(
														IF(
															CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 6, /* Samstag */
															sTill - INTERVAL 1 DAY,
															IF(
																CAST(DATE_FORMAT(sTill, '%w') AS UNSIGNED) = 0, /* Sonntag */
																sTill - INTERVAL 2 DAY,
																sTill
															)
														),
														IF(
															CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 6, /* Samstag */
															sFrom + INTERVAL 2 DAY,
															IF(
																CAST(DATE_FORMAT(sFrom, '%w') AS UNSIGNED) = 0, /* Sonntag */
																sFrom + INTERVAL 1 DAY,
																sFrom
															)
														)
													)
												) + 1 /* incl. Endtag  */
											) / 7,
											0
										) * 2
									)
								) / 5
							)
						),
						0
					)
				);
			END
		";
		DB::executeQuery($sSQL);

	}

	/**
	 * MySQL-Äquivalent von Ext_Thebing_Util::getCorrectCourseStartDay()
	 *
	 * @see Ext_Thebing_Util::getCorrectCourseStartDay()
	 */
	public static function getCorrectCourseStartDay() {

		DB::executeQuery("
			DROP FUNCTION IF EXISTS
				getCorrectCourseStartDay
		");

		DB::executeQuery("
			CREATE FUNCTION getCorrectCourseStartDay(sMonday DATE, iCourseStartDay TINYINT)
				RETURNS DATE
				DETERMINISTIC
			BEGIN
				IF WEEKDAY(sMonday) = 0 THEN
					RETURN(
						sMonday +
						INTERVAL IF(
							iCourseStartDay <= 4,
							iCourseStartDay - 1,
							iCourseStartDay - 8
						) DAY
					);
				ELSE
					SIGNAL SQLSTATE 'ERR0R'
						SET MESSAGE_TEXT = 'getCorrectCourseStartDay(): GIVEN DATE IS NOT A MONDAY';
				END IF;
			END
		");

	}

	/**
	 * MySQL-Äquivalent von Ext_Thebing_Util::getRealDateFromTuitionWeek()
	 *
	 * @see Ext_Thebing_Util::getRealDateFromTuitionWeek()
	 */
	public static function getRealDateFromTuitionWeek() {

		DB::executeQuery("
			DROP FUNCTION IF EXISTS
				getRealDateFromTuitionWeek
		");

		DB::executeQuery("
			CREATE FUNCTION getRealDateFromTuitionWeek(sWeek TEXT, iWeekday TINYINT, iCourseStartDay TINYINT)
				RETURNS DATE
				DETERMINISTIC
			BEGIN
				RETURN(
					getCorrectCourseStartDay(
						sWeek,
						iCourseStartDay
					) +
					INTERVAL IF(
						iWeekday = iCourseStartDay,
						0,
						IF(
							iWeekday > iCourseStartDay,
							iWeekday - iCourseStartDay,
							7 - (iCourseStartDay - iWeekday)
						)
					) DAY
				);
			END
		");

	}
}