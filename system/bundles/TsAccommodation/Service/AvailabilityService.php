<?php

namespace TsAccommodation\Service;

use TcStatistic\Model\Table\Cell;
use TcStatistic\Model\Table\Row;
use TcStatistic\Model\Table\Table;
use WDDate;
use Carbon\Carbon;

class AvailabilityService
{
	// The default GUI description
	public static $sDescription = 'Thebing » Accommodation » Availability';

	// The smarty object
	protected $oSmarty;

	/**
	 * @var Carbon
	 */
	protected $oFrom;

	/**
	 * @var Carbon
	 */
	protected $oTill;

	// The accommodation category
	protected $iCategoryId;

	// The view
	protected $sView;

	// The school object
	protected $oSchool;

	// School default language
	protected $sDefaultLang;

	// The results counters
	protected $aResults;

	// The single days array
	protected $aDays;

	// Available categories
	protected $aCategories;

	// Available roomtypes
	protected $aRoomtypes;
	
	protected $aAllCategories;

	/* ==================================================================================================== */

	/**
	 * The constructor
	 *
	 * @param int $iFrom
	 * @param int $iTill
	 * @param int $iCategoryId
	 * @param string $sType
	 */
	public function __construct($from, $until, $iCategoryId, $sView, \Ext_Thebing_School $oSchool = null) {
		
		$this->oSmarty = new \SmartyWrapper();

		$this->oFrom = new Carbon($from);

		$this->oTill = new Carbon($until);

		$this->oTill->add('1 week');

		$this->iCategoryId = $iCategoryId;

		$this->sView = $sView;

		$this->oSchool = $oSchool;

		if ($oSchool) {
			$this->sDefaultLang = $this->oSchool->getLanguage();
		} else {
			$oSchool = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$this->sDefaultLang = $oSchool->getLanguage();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Exchange the dates

		if($this->oFrom > $this->oTill)	{
			
			$oTemp = clone $this->oTill;

			$this->oTill = clone $this->oFrom;

			$this->oFrom = $oTemp;
		}

		$this->aDays = $this->getDays($this->oFrom, $this->oTill);

		$this->aAllCategories = self::getCategories();
		
	}

	/* ==================================================================================================== */

	/**
	 * Get HTML formated results
	 *
	 * @return array
	 */
	public function getTemplateData() {
		
		$this->loadAvailabilities();
		$this->loadReservations();
		#$this->loadBookings();
		$this->loadBookingsWithAllocations();

		$aReturn = $this->getResults();

		return $aReturn;
	}

	/* ==================================================================================================== */

	/**
	 * Get the categories
	 *
	 * @return array
	 */
	public static function getCategories() {
		$aCategories = \Ext_Thebing_Accommodation_Category::getSelectOptions(false);
		return $aCategories;
	}

	/**
	 * Get the weeks
	 *
	 * @param bool $bCurrent
	 * @return array
	 */
	public static function getWeeks($bCurrent = false) {

		$aWeeks = array();

		$oSchool = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();

		$iWeekday = \Ext_Thebing_Util::convertWeekdayToInt($oSchool->accommodation_start);

		$oDate = new WDDate();

		$oDate->set('00:00:00', WDDate::TIMES);

		if($bCurrent) {
			$oDate->set($iWeekday, WDDate::WEEKDAY);

			$iTime = $oDate->get(WDDate::DB_DATE);

			return $iTime;
		} else {
			$oDate->sub(18, WDDate::MONTH);
			$oDate->set($iWeekday, WDDate::WEEKDAY);

			while($oDate->get(WDDate::YEAR) < date('Y') + 2) {
				$iTime = $oDate->get(WDDate::DB_DATE);

				$oTemp = new WDDate($oDate);
				$oTemp->add(1, WDDate::WEEK)->sub(12, WDDate::HOUR);

				$aWeeks[$iTime] = \Ext_Thebing_Format::LocalDate($oDate->get(WDDate::TIMESTAMP), $oSchool->id).' – '.\Ext_Thebing_Format::LocalDate($oTemp->get(WDDate::TIMESTAMP), $oSchool->id);

				$oDate->add(1, WDDate::WEEK);
			}

			return $aWeeks;
		}
	}

	/* ==================================================================================================== */

	/**
	 * Get default sum values
	 * 
	 * @param bool $bSingle
	 * @return array
	 */
	protected function addDefaults($bSingle = false)
	{
		$aDefaults = array(
			'rooms'	=> array(),
			'sums'	=> array(
				'rooms'	=> 0,
				'beds'	=> 0,
				'break'	=> 1
			)
		);

		return $aDefaults;
	}


	/**
	 * Check the existence of category and roomtype
	 *
	 * @param int &$iCategoryID
	 * @param int &$iRoomtypeID
	 */
	protected function checkNA(&$iCategoryID, &$iRoomtypeID)
	{
		if(!isset($this->aCategories[$iCategoryID]) && !empty($iCategoryID))
		{
			$category = \Ext_Thebing_Accommodation_Category::getInstance($iCategoryID);
			$this->aCategories[$iCategoryID] = $category->getName();
		}
		if(!isset($this->aRoomtypes[$iRoomtypeID]) && !empty($iRoomtypeID))
		{
			$roomtype = \Ext_Thebing_Accommodation_Roomtype::getInstance($iRoomtypeID);
			$this->aRoomtypes[$iRoomtypeID] = $roomtype->getName();
		}
	}


	/**
	 * Format available rooms data into the $this->aDays
	 *
	 * @param array $aData
	 */
	protected function formatAvailabilities($aData) {

		$aBlocked = array();

		foreach((array)$aData as $iKey=>$aEntry) {
			
			$aCategoryIds = (array)explode(',', $aEntry['category_ids']);
			
			foreach($aCategoryIds as $iCategoryId) {

				$aTemp = $aBlocked = [];

				if(!empty($aEntry['absence'])) {
					
					$aAbsence = explode(',', $aEntry['absence']);
					
					foreach($aAbsence as $sAbsense) {
						list($sFrom, $sUntil) = explode('_', $sAbsense);

						$aBlocked += (array)$this->getDays(
							new Carbon($sFrom),
							new Carbon($sUntil)
						);
					}
				}

				if(
					!empty($aEntry['valid_until']) &&
					$aEntry['valid_until'] !== '0000-00-00'
				) {

					$aBlocked += (array)$this->getDays(
						new Carbon($aEntry['valid_until']),
						clone $this->oTill
					);

				}

				$iCategoryID = $iCategoryId;
				$iRoomtypeID = $aEntry['roomtype_id'];

				$this->aCategories[$iCategoryID] = $this->aAllCategories[$iCategoryID];
				$this->aRoomtypes[$iRoomtypeID]	= $aEntry['roomtype'];

				$sSex		= $aEntry['sex'];
				$iRoomID	= $aEntry['room_id'];
				$iBeds		= $aEntry['single_beds'] + ($aEntry['double_beds'] * 2);

				// Jeden Tag einzeln checken
				foreach((array)$this->aDays as $sDay => $a) {

					if(!array_key_exists($sDay, $aBlocked)) {

						$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['rooms'][$iRoomID]++;
						$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['beds'][$iRoomID] = $iBeds;

						$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['rooms'] = (int)count($this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['rooms']);
						$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['beds'] = (int)array_sum($this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['beds']);

						if(
							$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['break'] == 0 ||
							$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['break'] > $iBeds
						) {
							// Set the break point like smallest number of beds
							$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['break'] = $iBeds;
						}

					} else {

						unset(
							$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['rooms'][$iRoomID],
							$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['beds'][$iRoomID]
						);

						$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['rooms'] = (int)count($this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['rooms']??[]);
						$this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['sums']['beds'] = (int)array_sum($this->aDays[$sDay][$iCategoryID][$iRoomtypeID]['beds']??[]);
					}
				}
			}
		}

	}

	/**
	 * Format the bookings data into the $this->aDays
	 *
	 * @param array $aData
	 */
	protected function formatBookings($aData) {

		$aAllocated = $aUnAllocated = array();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get allocated user

		foreach((array)$aData as $iKey => $aBooking) {

			$iCategoryId = $aBooking['default_category_id'];
			$aAccommodationCategoryIds = (array)explode(',', $aBooking['accommodation_category_ids']);

			if(
				in_array($aBooking['booked_category'], $aAccommodationCategoryIds)
			) {
				$iCategoryId = $aBooking['booked_category'];
			}

			$iRoomtypeID = $aBooking['alloc_roomtype_id'];

			$sSex		= $this->getSexKey($aBooking['sex']);
			$iRoomID	= $aBooking['room_id'];
			$iKI_ID		= $aBooking['ki_id'];

			$this->checkNA($iCategoryId, $iRoomtypeID);

			if($aBooking['allocation_id'] > 0) {

				$aDays = $this->getDays(
					new Carbon($aBooking['kaa_from']),
					new Carbon($aBooking['kaa_till'])
				);

				foreach((array)$aDays as $sDay => $a) {
					if(isset($this->aDays[$sDay])) {
						$aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID][$iKI_ID] = $aBooking;
					}
				}

				unset($aData[$iKey]);

			} else {

				$iCategoryId = $aBooking['category_id'];
				$iRoomtypeID = $aBooking['roomtype_id'];

				$aDays = $this->getDays(
					new Carbon($aBooking['kia_from']),
					new Carbon($aBooking['kia_till'])
				);

				foreach((array)$aDays as $sDay => $a) {
					if(isset($this->aDays[$sDay])) {
						$aUnAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iKI_ID] = $aBooking;
					}
				}

				unset($aData[$iKey]);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Assign allocated
		foreach((array)$aAllocated as $sDay => $aCategories) {
			
			foreach((array)$aCategories as $iCategoryId => $aRoomtypes) {
				
				foreach((array)$aRoomtypes as $iRoomtypeID => $aRooms) {
					
					foreach((array)$aRooms as $iRoomID => $aBookings) {
					
						foreach((array)$aBookings as $inquiryId => $aBooking) {

							if(!isset($this->aDays[$sDay][$iCategoryId])) // Category was deleted
							{
								$this->checkNA($iCategoryId, $iDummy);

								$this->aDays[$sDay][$iCategoryId] = array();

								#$this->aCategories[$iCategoryId] = \L10N::t('N/A', self::$sDescription);
							}

							if(isset($this->aDays[$sDay][$iCategoryId])) {

								if(!isset($this->aDays[$sDay][$iCategoryId][$iRoomtypeID])) // Roomtype was deleted
								{
									$this->checkNA($iCategoryId, $iRoomtypeID);

									$this->aDays[$sDay][$iCategoryId][$iRoomtypeID] = $this->addDefaults();

									//$this->aRoomtypes[$iRoomtypeID] = L10N::t('N/A', self::$sDescription);
								}

								if(isset($this->aDays[$sDay][$iCategoryId][$iRoomtypeID])) {

									$aDays = &$this->aDays[$sDay][$iCategoryId][$iRoomtypeID];

									if(
										$aBooking['booked_room_type'] == 0 && 
										$aBooking['allocated_room_type'] != 0
									) {
										$aDays['allocations']['beds'] += $aBooking['allocated_room_beds'];
									} else {
										$aDays['allocations']['beds']++;
									}

	//								$iCounter = count($aSexes);
	//
	//								if(in_array(1, $aSexes) && in_array(2, $aSexes)) // Both sex in a room
	//								{
	//									// The room for the number of people exists
	//									if($iCounter <= $aDays['rooms'][$iRoomID])
	//									{
	//										$aDays['sums']['rooms']--;
	//										$aDays['sums']['beds'] -= $aDays['rooms'][$iRoomID];
	//
	//										unset($aDays['rooms'][$iRoomID]);
	//										unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID]);
	//									}
	//									else // ERROR: No (more) rooms for the number of people available
	//									{
	//										$aDays['sums']['rooms'] -= ceil($iCounter / $aDays['sums']['break']);
	//										$aDays['sums']['beds'] -= $iCounter;
	//
	//										$aDays['rooms'][$iRoomID] = $aDays['sums']['beds'];
	//
	//										unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID]);
	//									}
	//								}
	//								else
	//								{
	//									if(in_array(1, $aSexes)) // Only male in a room
	//									{
	//										$sSex = '1_0';
	//									}
	//									else if(in_array(2, $aSexes)) // Only female in a room
	//									{
	//										$sSex = '0_1';
	//									}
	//
	//									if(isset($aDays['rooms'][$iRoomID]))
	//									{
	//										// The room for the number of people exists
	//										if($iCounter <= $aDays['rooms'][$iRoomID])
	//										{
	//											if($aDays['rooms'][$iRoomID] == $aDays['sets'][$iRoomID])
	//											{
	//												$aDays['sums']['rooms']--;
	//											}
	//
	//											$aDays['sums']['beds'] -= $iCounter;
	//											$aDays['rooms'][$iRoomID] -= $iCounter;
	//
	//											if($aDays['rooms'][$iRoomID] == 0)
	//											{
	//												unset($aDays['rooms'][$iRoomID]);
	//											}
	//
	//											unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID]);
	//										}
	//										else // ERROR: No (more) rooms for the number of people available
	//										{
	//											$aDays['sums']['rooms'] -= ceil($iCounter / $aDays['sets'][$iRoomID]);
	//											$aDays['sums']['beds'] -= $iCounter;
	//
	//											$aDays['rooms'][$iRoomID] -= $iCounter;
	//
	//											unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID]);
	//										}
	//									}
	//									else if(isset($aDays['rooms'][$iRoomID]))
	//									{
	//										// The room for the number of people exists
	//										if($iCounter <= $aDays['rooms'][$iRoomID])
	//										{
	//											$iSets = $aDays['sets'][$iRoomID];
	//											$iDiff = $iSets - $iCounter;
	//
	//											$aDays['sums']['rooms']--;
	//											$aDays['sums']['beds'] -= $aDays['rooms'][$iRoomID];
	//
	//											unset($aDays['rooms'][$iRoomID]);
	//											unset($aDays['sets'][$iRoomID]);
	//											unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID]);
	//
	//											if($iDiff > 0)
	//											{
	//											$aDays['rooms'][$iRoomID] = $iDiff;
	//											$aDays['sums']['beds'] += $iDiff;
	//											$aDays['sets'][$iRoomID] = $iSets;
	//										}
	//										}
	//										else // ERROR: No (more) rooms for the number of people available
	//										{
	//											$aDays['sets'][$iRoomID] = $aDays['sets'][$iRoomID];
	//											$aDays['rooms'][$iRoomID] = $aDays['rooms'][$iRoomID];
	//
	//											$aDays['sums']['rooms']--;
	//											$aDays['sums']['rooms']++;
	//											$aDays['sums']['beds'] -= $aDays['rooms'][$iRoomID];
	//											$aDays['sums']['beds'] += $aDays['rooms'][$iRoomID];
	//
	//											unset($aDays['sets'][$iRoomID]);
	//											unset($aDays['rooms'][$iRoomID]);
	//
	//											$aDays['sums']['rooms'] -= ceil($iCounter / $aDays['sets'][$iRoomID]);
	//											$aDays['sums']['beds'] -= $iCounter;
	//
	//											$aDays['rooms'][$iRoomID] -= $iCounter;
	//
	//											unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iRoomID]);
	//										}
	//									}
	//								}
								}
							}
						}
					
					}

					if(empty($aAllocated[$sDay][$iCategoryId][$iRoomtypeID]))
					{
						unset($aAllocated[$sDay][$iCategoryId][$iRoomtypeID]);
					}
				}

				if(empty($aAllocated[$sDay][$iCategoryId]))
				{
					unset($aAllocated[$sDay][$iCategoryId]);
				}
			}

			if(empty($aAllocated[$sDay]))
			{
				unset($aAllocated[$sDay]);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Assign unallocated

		foreach((array)$aUnAllocated as $sDay => $aCategories) {
			foreach((array)$aCategories as $iCategoryId => $aRoomtypes) {
				foreach((array)$aRoomtypes as $iRoomtypeID => $aUser) {
					foreach((array)$aUser as $iKI_ID => $iSex) {

						if(!isset($this->aDays[$sDay][$iCategoryId])) {// Category was deleted
							$this->checkNA($iCategoryId, $iDummy);

							$this->aDays[$sDay][$iCategoryId] = [];

							if(isset($this->aAllCategories[$iCategoryId])) {
								$this->aCategories[$iCategoryId] = $this->aAllCategories[$iCategoryId];
							} else {
#__pout($iCategoryId);
#__pout(\Ext_Thebing_Accommodation_Category::getInstance()->getName($iCategoryId));
								#$this->aCategories[$iCategoryId] = \L10N::t('N/A', self::$sDescription);
							} 
						}

						if(!isset($this->aDays[$sDay][$iCategoryId][$iRoomtypeID])) { // Roomtype was deleted
							$this->checkNA($iCategoryId, $iRoomtypeID);

							$this->aDays[$sDay][$iCategoryId][$iRoomtypeID] = $this->addDefaults();

							//$this->aRoomtypes[$iRoomtypeID] = L10N::t('N/A', self::$sDescription);
						}

						if(isset($this->aDays[$sDay][$iCategoryId][$iRoomtypeID])) {
							
							$aDays = &$this->aDays[$sDay][$iCategoryId][$iRoomtypeID];

//								if($iSex == 1) // Only male in a room
//								{
//									$sSex = '1_0';
//								}
//								else if($iSex == 2) // Only female in a room
//								{
//									$sSex = '0_1';
//								}

							$aDays['bookings']['beds']++;
							
//							if($aDays['sums']['beds'] > 0) {
//
//								$iBeds		= min($aDays['rooms']);
//								$iRoomID	= array_search($iBeds, $aDays['rooms']);
//
//								if($aDays['rooms'][$iRoomID] == $aDays['sets'][$iRoomID])
//								{
//									$aDays['sums']['rooms']--;
//								}
//
//								$aDays['sums']['beds']--;
//								$aDays['rooms'][$iRoomID]--;
//
//								if($aDays['rooms'][$iRoomID] == 0)
//								{
//									unset($aDays['rooms'][$iRoomID]);
//								}
//
//								unset($aUnAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iKI_ID]);
//							}
//							else
//							{
//								if($aDays['sums']['beds'] > 0)
//								{
//									$iBeds		= min($aDays['rooms']);
//									$iRoomID	= array_search($iBeds, $aDays['rooms']);
//									$iSets		= $aDays['sets'][$iRoomID];
//									$iDiff		= $iSets - 1;
//
//									$aDays['sums']['rooms']--;
//									$aDays['sums']['beds'] -= $aDays['rooms'][$iRoomID];
//
//									unset($aDays['rooms'][$iRoomID]);
//									unset($aUnAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iKI_ID]);
//
//									if($iDiff > 0)
//									{
//									$aDays['rooms'][$iRoomID] = $iDiff;
//									$aDays['sums']['beds'] += $iDiff;
//									$aDays['sets'][$iRoomID] = $iSets;
//								}
//								}
//								else // ERROR: No (more) rooms available
//								{
//									if(!isset($aDays['sums']['count']))
//									{
//										$aDays['sums']['count'] = $aDays['sums']['break'] - 1;
//										$aDays['sums']['rooms']--;
//									}
//									else
//									{
//										$aDays['sums']['count']--;
//
//										if($aDays['sums']['count'] == 0)
//										{
//											$aDays['sums']['count'] = $aDays['sums']['break'];
//											$aDays['sums']['rooms']--;
//										}
//									}
//
//									$aDays['sums']['beds']--;
//
//									unset($aUnAllocated[$sDay][$iCategoryId][$iRoomtypeID][$iKI_ID]);
//								}
//							}
						}
						
					}

					if(empty($aUnAllocated[$sDay][$iCategoryId][$iRoomtypeID])) {
						unset($aUnAllocated[$sDay][$iCategoryId][$iRoomtypeID]);
					}
				}

				if(empty($aUnAllocated[$sDay][$iCategoryId])) {
					unset($aUnAllocated[$sDay][$iCategoryId]);
				}
			}

			if(empty($aUnAllocated[$sDay])) {
				unset($aUnAllocated[$sDay]);
			}
		}

	}

	/**
	 * Prepare the results an get the HTML code
	 * 
	 * @return string
	 */
	protected function getResults() {

		$aResults = $aTotals = array();
		$oDate = clone $this->oFrom;
		
		foreach($this->aAllCategories as $categoryId=>$categoryName) {
			
			if(
				is_numeric($this->iCategoryId) &&
				$this->iCategoryId > 0 &&
				$categoryId != $this->iCategoryId
			) {
				continue;
			}
			
			$aResults[$categoryId] = [];
			
		}
		
		$oSchool = ($this->oSchool !== null) ? $this->oSchool : \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();

		foreach((array)$this->aDays as $sDay => $aCategories) {

			if(
				$oDate->toDateString() == $sDay &&
				$oDate < $this->oTill
			) {

				$sKey = \Ext_Thebing_Format::LocalDate($oDate, $oSchool->id);

				switch($this->sView) {
					case 'total':
					{
						$oDate = clone $this->oTill;

						#$oDate->sub('1 hour');

						$sKey .= ' – '.\Ext_Thebing_Format::LocalDate($oDate, $oSchool->id);

						break;
					}
					case 'problems':
					case 'all':
					{
						$oDate->add('1 week');#->sub('1 hour');

						$sKey .= ' – '.\Ext_Thebing_Format::LocalDate($oDate, $oSchool->id);

						#$oDate->add('1 hour');

						break;
					}
					case 'days':
					{
						$oDate->add('1 day');

						#$sKey .= \Ext_Thebing_Format::LocalDate($oDate, $oSchool->id);

						break;
					}
				}
			}

			foreach((array)$this->aCategories as $iCategoryID => $sCategory) {
				
				if(is_numeric($this->iCategoryId) && $this->iCategoryId > 0 && $iCategoryID != $this->iCategoryId) {
					continue;
				}

				$aRoomtypes = $this->aDays[$sDay][$iCategoryID];

				foreach((array)$this->aRoomtypes as $iRoomtypeID => $sRoomtype) {

					$aValues = $aRoomtypes[$iRoomtypeID];

					if(!empty($aValues)) {

						if(
							!isset($aResults[$iCategoryID][$sKey][$iRoomtypeID]['beds']) ||
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['beds'] > $aValues['sums']['beds']
						) {
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['beds'] = $aValues['sums']['beds'];
						}
						
						if(
							!isset($aResults[$iCategoryID][$sKey][$iRoomtypeID]['rooms']) ||
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['rooms'] > $aValues['sums']['rooms']
						) {
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['rooms'] = $aValues['sums']['rooms'];
						}

						if(
							!isset($aResults[$iCategoryID][$sKey][$iRoomtypeID]['reservation_beds']) ||
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['reservation_beds'] < $aValues['reservations']['beds']
						) {
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['reservation_beds'] = (int)$aValues['reservations']['beds'];
						}

						if(
							!isset($aResults[$iCategoryID][$sKey][$iRoomtypeID]['allocation_beds']) ||
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['allocation_beds'] < $aValues['allocations']['beds']
						) {
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['allocation_beds'] = (int)$aValues['allocations']['beds'];
						}

						if(
							!isset($aResults[$iCategoryID][$sKey][$iRoomtypeID]['booking_beds']) ||
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['booking_beds'] < $aValues['bookings']['beds']
						) {
							$aResults[$iCategoryID][$sKey][$iRoomtypeID]['booking_beds'] = (int)$aValues['bookings']['beds'];
						}

					}
				}
			}
		}
#__pout($aResults[5]);
		// Calculate the total line
		foreach((array)$aResults as $iCategoryID => $aCategories) {
			foreach((array)$aCategories as $sDate => $aRoomtypes) {
				foreach((array)$aRoomtypes as $iRoomtypeID => $aValues) {
					
					(int)$aTotals[$iCategoryID][$sDate]['beds'] += $aValues['beds'];
					(int)$aTotals[$iCategoryID][$sDate]['rooms'] += $aValues['rooms'];
					(int)$aTotals[$iCategoryID][$sDate]['reservation_beds'] += $aValues['reservation_beds'];
					(int)$aTotals[$iCategoryID][$sDate]['allocation_beds'] += $aValues['allocation_beds'];
					(int)$aTotals[$iCategoryID][$sDate]['booking_beds'] += $aValues['booking_beds'];

					(int)$aTotals[$iCategoryID][$sDate]['available_incl'] += ($aValues['beds'] - $aValues['allocation_beds'] - $aValues['booking_beds'] - $aValues['reservation_beds']);
					(int)$aTotals[$iCategoryID][$sDate]['available_excl'] += ($aValues['beds'] - $aValues['allocation_beds'] - $aValues['booking_beds']);
					
				}
				
				if($this->sView == 'problems') {
					if($aTotals[$iCategoryID][$sDate]['available_incl'] >= 0) {
						unset($aResults[$iCategoryID][$sDate]);

						if(empty($aResults[$iCategoryID])) {
							unset($aResults[$iCategoryID]);
						}
					}
				}
				
			}
		}

		return $this->writeResults($aResults, $aTotals);
	}


	/**
	 * Explode the dates on single days
	 *
	 * @param object $oFrom
	 * @param object $oTill
	 * @return array
	 */
	protected function getDays($oFrom, $oTill) {
		
		$aDays = [];

		$oTemp = clone $oFrom;

		while($oTill > $oTemp) {
			$aDays[$oTemp->toDateString()] = array();
			$oTemp->add('1 day');
		}

		return $aDays;
	}


	/**
	 * Create the sex key by sex number
	 *
	 * @param int $iSex
	 * @return string
	 */
	protected function getSexKey($iSex) {

		switch($iSex) {
			case 1:
				return '1_0';
			case 2:
				return '0_1';
			case 0:
				return '1_1';
		}
	}

	protected function loadReservations() {
		
		$sWhere = "";

		/*
		 * Add additional filter, increasing query speed
		 * Das geht leider nicht, da die gebuchte Kategorie nicht immer der zugewiesenen entspricht.
		 */
//		if(
//			is_numeric($this->mCategory) && 
//			$this->mCategory > 0
//		) {
//			$sWhere = " AND json_value(`kaa`.`reservation`, '$.category') = :iCategoryID ";
//		}

		$sSQL = "
			SELECT
				json_value(`kaa`.`reservation`, '$.category') `category_id`,
				`kar`.`id`				`roomtype_id`,
				`kaa`.`room_id`			`room_id`,
				`kaa`.`from`			`kaa_from`,
				`kaa`.`until`			`kaa_till`,
				`_kar`.`id`				`alloc_roomtype_id`,
				json_value(`kaa`.`reservation`, '$.category') `booked_category`,
				`_cdb4`.`default_category_id`,
				GROUP_CONCAT(DISTINCT `ts_actap`.`accommodation_category_id`) `accommodation_category_ids`
			FROM
				`kolumbus_accommodations_allocations` AS `kaa` LEFT JOIN
				`kolumbus_rooms` AS `kr` ON
					`kaa`.`room_id` = `kr`.`id` AND
					`kr`.`active` = 1 LEFT JOIN
				`kolumbus_accommodations_roomtypes` AS `kar` ON
					`kr`.`type_id` = `kar`.`id` LEFT JOIN
				`customer_db_4` AS `_cdb4` ON
					`kr`.`accommodation_id` = `_cdb4`.`id` AND
					`_cdb4`.`active` = 1 LEFT OUTER JOIN
				`ts_accommodation_categories_to_accommodation_providers` `ts_actap` ON
					`_cdb4`.`id` = `ts_actap`.`accommodation_provider_id` LEFT JOIN
				`kolumbus_accommodations_roomtypes` AS `_kar`		ON
					`kr`.`type_id` = `_kar`.`id` AND
					`_kar`.`active` = 1
			WHERE
				`kaa`.`active` = 1 AND
				`kaa`.`status` = 0 AND
				`kaa`.`room_id` > 0 AND
				`kaa`.`from` < :iTill AND
				`kaa`.`until` > :iFrom AND
				`kaa`.`reservation` IS NOT NULL
				" . $sWhere . "
			GROUP BY
				`kaa`.`id`
			ORDER BY
				`kaa`.`from` DESC,
				`kaa`.`until` DESC
		";
		$aSQL = array(
			'iFrom'			=> $this->oFrom->toDateString(),
			'iTill'			=> $this->oTill->toDateString(),
			'iCategoryID'	=> (int)$this->iCategoryId
		);
		$reservations = \DB::getQueryRows($sSQL, $aSQL);

		foreach($reservations as $reservation) {

			// Wenn die Reservierung ohne Kategorie-Filter gespeichert wurde, gibt es da keinen Wert
			if(empty($reservation['category_id'])) {
				$reservation['category_id'] = $reservation['default_category_id'];
			}

			$days = $this->getDays(new Carbon($reservation['kaa_from']), new Carbon($reservation['kaa_till']));

			foreach($days as $day=>$dummy) {
				
				if(isset($this->aDays[$day])) {
					$this->aDays[$day][$reservation['category_id']][$reservation['roomtype_id']]['reservations']['beds']++;
			
					$this->aDays[$day][$reservation['category_id']][$reservation['roomtype_id']]['reservations']['room_ids'][$reservation['room_id']] = $reservation['room_id'];
					$this->aDays[$day][$reservation['category_id']][$reservation['roomtype_id']]['reservations']['rooms'] = count($this->aDays[$day][$reservation['category_id']][$reservation['roomtype_id']]['reservations']['room_ids']);
				}
				
			}
			
			
		}
		
	}

	/**
	 * Load availabilities
	 *
	 * @return array
	 */
	protected function loadAvailabilities() {

		$sWhere = "";

		// Add additional filter, increasing query speed
		// Das geht leider nicht, da die gebuchte Kategorie nicht immer der zugewiesenen entspricht.
//		if(
//			is_numeric($this->mCategory) &&
//			$this->mCategory > 0
//		) {
//			$sWhere = " AND `ts_actap`.`accommodation_category_id` = :iCategoryID ";
//		}

		$sSQL = "
			SELECT
				CONCAT(`kr`.`male`, '_', `kr`.`female`) `sex`,
				`kr`.`single_beds`,
				`kr`.`double_beds`,
				`kr`.`id` `room_id`,
				IF(`kar`.`id` IS NULL, '-', `kar`.`id`) `roomtype_id`,
				GROUP_CONCAT(DISTINCT `ts_actap`.`accommodation_category_id`) `category_ids`,
				GROUP_CONCAT(DISTINCT CONCAT(`ka`.`from`, '_', `ka`.`until`)) `absence`,
				`kar`.`name_".$this->sDefaultLang."` `roomtype`,
				`kr`.`valid_until`
			FROM
				`kolumbus_rooms` `kr` JOIN
				`customer_db_4` `cdb4` ON
					`kr`.`accommodation_id` = `cdb4`.`id` AND
					(
						`cdb4`.`valid_until` >= :sFrom OR
						`cdb4`.`valid_until` = '0000-00-00' 
					) JOIN
				`ts_accommodation_providers_schools` `ts_aps` ON
					`ts_aps`.`accommodation_provider_id` = `cdb4`.`id`
					".$this->getSchoolQueryPart('ts_aps', 'school_id')." INNER JOIN
				`ts_accommodation_categories_to_accommodation_providers` `ts_actap` ON
					`cdb4`.`id` = `ts_actap`.`accommodation_provider_id` JOIN
				`kolumbus_accommodations_categories` `kac` ON
					`ts_actap`.`accommodation_category_id` = `kac`.`id` AND
					(
						`kac`.`valid_until` >= :sFrom OR
						`kac`.`valid_until` = '0000-00-00' 
					) JOIN
				`kolumbus_accommodations_roomtypes` `kar` ON
					`kr`.`type_id` = `kar`.`id` AND
					`kar`.`active` = 1 AND
					(
						`kar`.`valid_until` >= :sFrom OR
						`kar`.`valid_until` = '0000-00-00' 
					) LEFT JOIN
				`kolumbus_absence` `ka` ON
					`ka`.`item` = 'accommodation' AND
					`ka`.`item_id` = `kr`.`id` AND
					`ka`.`from` <= :sTill AND
					`ka`.`until` >= :sFrom AND
					`ka`.`active` = 1
			WHERE
				`kr`.`active` = 1 AND
				(
					`kr`.`valid_until` >= :sFrom OR
					`kr`.`valid_until` = '0000-00-00'
				) AND
				`cdb4`.`active` = 1 AND
				`kr`.`include_availability_report` = 1
				".$sWhere."
			GROUP BY
				`kr`.`id`
			ORDER BY
				`kr`.`id`
		";
		$aSQL = [
			'sFrom' => $this->oFrom->toDateString(),
			'sTill' => $this->oTill->toDateString(),
			'iSchoolID' => (int)$this->oSchool?->getId(),
			'iCategoryID' => (int)$this->iCategoryId,
		];
		$aAvailabilities = \DB::getQueryRows($sSQL, $aSQL);

		$this->formatAvailabilities($aAvailabilities);

	}


	/**
	 * Load the bookings
	 *
	 * @return array
	 */
	protected function loadBookings() {

		$sWhere = "";

		// Add additional filter, increasing query speed
		// Das geht leider nicht, da die gebuchte Kategorie nicht immer der zugewiesenen entspricht.
//		if(
//			is_numeric($this->mCategory) && 
//			$this->mCategory > 0
//		) {
//			$sWhere = " AND `ts_i_j_a`.`accommodation_id` = :iCategoryID ";
//		}

		$sSQL = "
			SELECT
				`ts_i`.`id` `ki_id`,
				`tc_c`.`gender` `sex`,
				`ts_i_j_a`.`from` `kia_from`,
				`ts_i_j_a`.`until` `kia_till`,
				`ts_i_j_a`.`accommodation_id` `category_id`,
				`ts_i_j_a`.`roomtype_id` `roomtype_id`,
				`ts_i_j_a`.`accommodation_id` `booked_category`
			FROM
				`ts_inquiries` AS `ts_i`									INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c`							ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'traveller'						INNER JOIN
				`tc_contacts` `tc_c`											ON
					`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
					`tc_c`.`active` = 1										INNER JOIN
				`ts_inquiries_journeys` `ts_i_j`								ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 
					".$this->getSchoolQueryPart('ts_i_j', 'school_id')." INNER JOIN
				`ts_inquiries_journeys_accommodations` AS `ts_i_j_a`			ON
					`ts_i_j`.`id` = `ts_i_j_a`.`journey_id`					INNER JOIN
				`kolumbus_accommodations_categories` AS `kac`										ON
					`ts_i_j_a`.`accommodation_id` = `kac`.`id`				INNER JOIN
				`kolumbus_accommodations_roomtypes` AS `kar`				ON
					`ts_i_j_a`.`roomtype_id` = `kar`.`id`
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`canceled` = 0 AND
				`ts_i`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING." AND
				`ts_i`.`confirmed` > 0 AND
				`ts_i_j_a`.`active` = 1 AND
				`ts_i_j_a`.`from` < :iTill AND
				`ts_i_j_a`.`until` >= :iFrom
				" . $sWhere . "
			GROUP BY
				`ts_i_j_a`.`id`
			ORDER BY
				`ts_i_j_a`.`from` DESC,
				`ts_i_j_a`.`until` DESC
		";

		$aSQL = array(
			'iFrom'			=> $this->oFrom->toDateString(),
			'iTill'			=> $this->oTill->toDateString(),
			'iSchoolID'	=> (int)$this->oSchool?->getId(),
			'iCategoryID'	=> (int)$this->iCategoryId
		);
		$aBookings = \DB::getQueryRows($sSQL, $aSQL);

		$this->formatBookings($aBookings);
		
	}
	
	protected function loadBookingsWithAllocations() {

		$sWhere = "";

		// Add additional filter, increasing query speed
		// Das geht leider nicht, da die gebuchte Kategorie nicht immer der zugewiesenen entspricht.
//		if(
//			is_numeric($this->mCategory) && 
//			$this->mCategory > 0
//		) {
//			$sWhere = " AND `ts_i_j_a`.`accommodation_id` = :iCategoryID ";
//		}

		$sSQL = "
			SELECT
				`tc_c`.`id`				`user_id`,
				`tc_c`.`gender`			`sex`,
				`ts_i`.`id`				`ki_id`,
				`ts_i_j_a`.`from`		`kia_from`,
				`ts_i_j_a`.`until`		`kia_till`,
				`kac`.`id`				`category_id`,
				`kar`.`id`				`roomtype_id`,
				`kaa`.`id` `allocation_id`,
				`kaa`.`room_id`			`room_id`,
				`kaa`.`from`			`kaa_from`,
				`kaa`.`until`			`kaa_till`,
				`_kar`.`id`				`alloc_roomtype_id`,
				`ts_i_j_a`.`accommodation_id` `booked_category`,
				`_cdb4`.`default_category_id`,
				GROUP_CONCAT(DISTINCT `ts_actap`.`accommodation_category_id`) `accommodation_category_ids`,
				(`kr`.`single_beds` + (`kr`.`double_beds` * 2)) `allocated_room_beds`,
				`kar`.`type` `booked_room_type`,
				`_kar`.`type` `allocated_room_type`
			FROM
				`ts_inquiries` AS `ts_i`									INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c`							ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'traveller'						INNER JOIN
				`tc_contacts` `tc_c`											ON
					`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
					`tc_c`.`active` = 1										INNER JOIN
				`ts_inquiries_journeys` `ts_i_j`								ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 
					".$this->getSchoolQueryPart('ts_i_j', 'school_id') ." INNER JOIN
				`ts_inquiries_journeys_accommodations` AS `ts_i_j_a`			ON
					`ts_i_j`.`id` = `ts_i_j_a`.`journey_id` AND
					`ts_i_j_a`.`visible` = 1 INNER JOIN
				`kolumbus_accommodations_categories` AS `kac`										ON
					`ts_i_j_a`.`accommodation_id` = `kac`.`id`				INNER JOIN
				`kolumbus_accommodations_roomtypes` AS `kar`				ON
					`ts_i_j_a`.`roomtype_id` = `kar`.`id`					LEFT OUTER JOIN
				`kolumbus_accommodations_allocations` AS `kaa`					ON
					`ts_i_j_a`.`id` = `kaa`.`inquiry_accommodation_id` AND
					`kaa`.`active` = 1 AND
					`kaa`.`status` = 0 AND
					`kaa`.`room_id` > 0 AND
					`kaa`.`from` < :iTill AND
					`kaa`.`until` > :iFrom							LEFT OUTER JOIN
				`kolumbus_rooms` AS `kr`								ON
					`kaa`.`room_id` = `kr`.`id` AND
					`kr`.`active` = 1								LEFT OUTER JOIN
				`customer_db_4` AS `_cdb4`								ON
					`kr`.`accommodation_id` = `_cdb4`.`id` AND
					`_cdb4`.`active` = 1							LEFT OUTER JOIN
				`ts_accommodation_categories_to_accommodation_providers` `ts_actap` ON
					`_cdb4`.`id` = `ts_actap`.`accommodation_provider_id` LEFT JOIN
				`kolumbus_accommodations_roomtypes` AS `_kar`		ON
					`kr`.`type_id` = `_kar`.`id` AND
					`_kar`.`active` = 1
			WHERE
				`ts_i`.`active` = 1 AND
				`ts_i`.`canceled` = 0 AND
				`ts_i_j_a`.`active` = 1 AND
				`ts_i_j_a`.`from` <= :iTill AND
				`ts_i_j_a`.`until` >= :iFrom
				" . $sWhere . "
			GROUP BY
				`ts_i_j_a`.`id`,
				`kaa`.`id`
			ORDER BY
				`kaa`.`from` DESC,
				`kaa`.`until` DESC
		";
		$aSQL = array(
			'iFrom'			=> $this->oFrom->toDateString(),
			'iTill'			=> $this->oTill->toDateString(),
			'iSchoolID'	=> (int)$this->oSchool?->getId(),
			'iCategoryID'	=> (int)$this->iCategoryId
		);
		$allocations = \DB::getQueryRows($sSQL, $aSQL);

		$this->formatBookings($allocations);
	}

	/**
	 * Create HTML code for output
	 *
	 * @param array $aResults
	 * @return string
	 */
	protected function writeResults($aResults, $aTotals) {

		$aColors = array(
			'green'		=> \Ext_Thebing_Util::getColor('green'),
			'red'		=> \Ext_Thebing_Util::getColor('red'),
			'yellow' 	=> \Ext_Thebing_Util::getColor('yellow')
		);

		$return = [
			'aData' => $aResults,
			'aTotals' => $aTotals,
			'aColors' => $aColors,
			'aCategoriesLabels' => $this->aCategories,
			'aRoomtypesLabels' => $this->aRoomtypes,
			'format' => new \Ext_Thebing_Gui2_Format_Int
		];

		return $return;
	}

	protected function getSchoolQueryPart(string $dbAlias, string $dbColumn): string {
		return ($this->oSchool !== null) ? ' AND `'.$dbAlias.'`.`'.$dbColumn.'` = :iSchoolID ' : '';
	}

	public function createTableData(array $data): Table
	{
		$table = new Table();
		foreach ($data['aData'] as $accommodationCategoryId => $accommodationCategoryData) {
			if (!$accommodationCategoryData) {
				continue;
			}
			$rows = [];

			$title = new Row();
			$title[] = new Cell($data['aCategoriesLabels'][$accommodationCategoryId]);

			$headRowTop = new Row();
			$cell = new Cell('');
			$cell->setColspan(2);
			$headRowTop[] = $cell;
			$cell = new Cell(\L10N::t('Gesamt', AvailabilityService::$sDescription));
			$cell->setColspan(2);
			$headRowTop[] = $cell;
			$headRowTop[] = new Cell(\L10N::t('Gebucht', AvailabilityService::$sDescription));
			$headRowTop[] = new Cell(\L10N::t('Zugewiesen', AvailabilityService::$sDescription));
			$headRowTop[] = new Cell(\L10N::t('Reserviert', AvailabilityService::$sDescription));
			$cell = new Cell(\L10N::t('Verfügbar', AvailabilityService::$sDescription));
			$cell->setColspan(2);
			$headRowTop[] = $cell;
			$cell = new Cell(\L10N::t('Auslastung', AvailabilityService::$sDescription));
			$cell->setColspan(2);
			$headRowTop[] = $cell;

			$headRowBottom = new Row();
			$cell = new Cell('');
			$cell->setColspan(2);
			$headRowBottom[] = $cell;
			$headRowBottom[] = new Cell(\L10N::t('Zimmer', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Bett', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Bett', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Bett', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Bett', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Inkl. Res.', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Exkl. Res.', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Inkl. Res.', AvailabilityService::$sDescription));
			$headRowBottom[] = new Cell(\L10N::t('Exkl. Res.', AvailabilityService::$sDescription));

			foreach ($accommodationCategoryData as $date => $dateData) {
				$sumRow = new Row();
				$sumRow[] = new Cell($date, true);
				$sumRow[] = new Cell(\L10N::t('Gesamt', AvailabilityService::$sDescription), true);
				$this->fillRowData($sumRow, $data['aTotals'][$accommodationCategoryId][$date], true);
				foreach ($dateData as $roomCategoryId => $roomData) {
					$row = new Row();
					$row[] = new Cell($date);
					$row[] = new Cell($data['aRoomtypesLabels'][$roomCategoryId]);
					$this->fillRowData($row, $roomData);
					$rows[] = $row;
				}
				$rows[] = $sumRow;
			}
			$table[] = new Row();
			$table[] = $title;
			$table[] = $headRowTop;
			$table[] = $headRowBottom;
			foreach ($rows as $row) {
				$table[] = $row;
			}
		}
		return $table;
	}

	private function fillRowData(Row &$row, array $rowData, $isHead = false): void
	{
		$fields = [
			'rooms',
			'beds',
			'booking_beds',
			'allocation_beds',
			'reservation_beds',
			'available_incl',
			'available_excl',
			'utilisation_incl',
			'utilisation_excl'
		];
		$format = new \Ext_Thebing_Gui2_Format_Int;
		foreach ($fields as $field) {
			$value = match($field) {
				'rooms' => $rowData['rooms'] ?? 0,
				'beds' => $rowData['beds'] ?? 0,
				'booking_beds' => $rowData['booking_beds'] ?? 0,
				'allocation_beds' => $rowData['allocation_beds'] ?? 0,
				'reservation_beds' => $rowData['reservations_beds'] ?? 0,
				'available_incl' => $rowData['beds'] - $rowData['allocation_beds'] - $rowData['booking_beds'] - $rowData['reservation_beds'],
				'available_excl' => $rowData['beds'] - $rowData['allocation_beds'] - $rowData['booking_beds'],
				'utilisation_incl' => ($rowData['beds'] > 0) ?
					$format->format((1-($rowData['beds'] - $rowData['booking_beds'] - $rowData['allocation_beds'] - $rowData['reservation_beds']) / $rowData['beds'])*100).'%' : '0%',
				'utilisation_excl' => ($rowData['beds'] > 0) ?
					$format->format((1-($rowData['beds'] - $rowData['booking_beds'] - $rowData['allocation_beds'] ) / $rowData['beds']) * 100).'%' : '0%',
			};
			$row[] = new Cell($value, $isHead);
		}
	}

}