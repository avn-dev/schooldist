<?php

namespace Ts\Service\Inquiry;

class Special {
	
	/**
	 * @var \Ext_TS_Inquiry
	 */
	private $inquiry;
	
	public function __construct(\Ext_TS_Inquiry $inquiry) {
		$this->inquiry = $inquiry;
	}
	
	/*
	 * Die Funktion sucht ALLE specials die zu dieser Buchung gefunden werden
	 */
	public function find() {

		// Array mit Positionen auf die specials passen
		$aFoundSpecials = array();

		$oGroup = $this->inquiry->getGroup();
		
		// Gruppenmitglieder haben kein Special
		if(is_object($oGroup)) {
			return;
		}

		$oJourney = $this->inquiry->getJourney();
		$oSchool = $oJourney->getSchool();
		$oCustomer				= $this->inquiry->getCustomer();

		$oAddressContact		= $oCustomer->getAddress('contact', false);

		/*
		 * @todo Warum werden hier inaktive Specials auch zurückgegeben? Ist das Absicht?
		 */
		$aSpecials				= $oSchool->getSpecials();

		$aInquiryCourses		= $this->inquiry->getCourses();
		$aInquiryAccommodations = $this->inquiry->getAccommodations();

		$oTransferArrival		= $this->inquiry->getTransfers('arrival');
		$oTransferDeparture		= $this->inquiry->getTransfers('departure');

		$aSpecialCourses		= array();
		$aSpecialAccommodations = array();
		$aSpecialTransfer		= array();

		// Vorab-Filtern, da der Müll unten sehr langsam ist bei vielen Specials
		$aSpecials = array_filter($aSpecials, function ($oSpecial) {

			if (!$oSpecial->visible) {
				return false;
			}

			$matchPeriods = true;
			
			if(
				$oSpecial->service_from !== null &&
				$oSpecial->service_until !== null
			) {

				$oPeriod = \Spatie\Period\Period::make(new \DateTime($oSpecial->service_from), new \DateTime($oSpecial->service_until));
				
				// Wenn die Buchung noch nicht gespeichert wurde, gibt es noch kein service_from und service_until
				if($this->inquiry->service_from === null) {
					$this->inquiry->refreshServicePeriod();
				}
				
				// Beim ersten Speichern einer Buchung werden Specials vmtl. nicht erkannt, wird aber eh bei jeder Rechnungserstellung ausgeführt
				if (
					!\Core\Helper\DateTime::isDate($this->inquiry->service_from, 'Y-m-d') ||
					!\Core\Helper\DateTime::isDate($this->inquiry->service_until, 'Y-m-d')
				) {
					$matchPeriods = false;
				}

				$oPeriod2 = \Spatie\Period\Period::make(new \DateTime($this->inquiry->service_from), new \DateTime($this->inquiry->service_until));
				
				$overlapsWith = $oPeriod->overlapsWith($oPeriod2);
				
				if(!$overlapsWith) {
					$matchPeriods = false;
				}
				
			}

			if (
				$oSpecial->created_from !== null &&
				$oSpecial->created_until !== null
			) {
				
				$oPeriod = \Spatie\Period\Period::make(new \DateTime($oSpecial->created_from), new \DateTime($oSpecial->created_until));
				
				$containsCreated = $oPeriod->contains(\Core\Helper\DateTime::createFromLocalTimestamp($this->inquiry->created));
				
				if(!$containsCreated) {
					$matchPeriods = false;
				}
				
			}

			return $matchPeriods;

		});

		foreach($aSpecials as $oSpecial) {

			// Inaktive Specials ausschließen
			if($oSpecial->visible < 1){
				continue;
			}

			// Zeitraum auschließen
			$oDate = new \WDDate();

			// TODO Warum werden hier nicht erst Dinge wie Direktbucher/Agentur überprüft, bevor Leistungszeitraum dran kommt?
			if(
				$oSpecial->created_from !== null &&
				$oSpecial->created_until !== null
			){

			
			}

			if(
				$oSpecial->service_from !== null &&
				$oSpecial->service_until !== null
			) {
				// Prüfen ob ein Kurs den Specialzeitraum schneidet
				$bContinue = true;

				foreach((array)$aInquiryCourses as $oInquiryCourse){
					if($oInquiryCourse->from != "0000-00-00" && $oInquiryCourse->until != "0000-00-00"){
                        $oDate->set($oSpecial->service_until, \WDDate::DB_DATE);
                        $iDiffTemp1 = (int)$oDate->getDiff(\WDDate::DAY, $oInquiryCourse->from, \WDDate::DB_DATE);

                        $oDate->set($oInquiryCourse->until, \WDDate::DB_DATE);
                        $iDiffTemp2 = (int)$oDate->getDiff(\WDDate::DAY, $oSpecial->service_from, \WDDate::DB_DATE);

                        if(
                            $iDiffTemp1 >= 0 &&
                            $iDiffTemp2 >= 0
                        ){
                            // Kurse die in diesem Special liegen
                            $aSpecialCourses[] = $oInquiryCourse;
                            $bContinue = false;
                        }
                    }
				}

				// Auch Unterkünfte prüfen welche Unterkünfte im Special liegen
				foreach((array)$aInquiryAccommodations as $oAccommodation){
					$oDate->set($oSpecial->service_until, \WDDate::DB_DATE);
					$iDiffTemp1 = (int)$oDate->getDiff(\WDDate::DAY, $oAccommodation->from, \WDDate::DB_DATE);

					$oDate->set($oAccommodation->until, \WDDate::DB_DATE);
					$iDiffTemp2 = (int)$oDate->getDiff(\WDDate::DAY, $oSpecial->service_from, \WDDate::DB_DATE);

					if(
						$iDiffTemp1 >= 0 &&
						$iDiffTemp2 >= 0
					){
						// Kurse die in diesem Special liegen
						$aSpecialAccommodations[] = $oAccommodation;
						$bContinue = false;
					}
				}

				// Tranfer prüfen
				if($oTransferArrival instanceof \Ext_TS_Service_Interface_Transfer){
	
					$oDate->set($oTransferArrival->transfer_date, \WDDate::DB_DATE);
					$bCheck = $oDate->isBetween(\WDDate::DB_DATE, $oSpecial->service_from, $oSpecial->service_until);

					if(
						$bCheck &&
						$oJourney->transfer_mode & \Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE
					){
						// Transfer die in diesem Special liegen
						$aSpecialTransfer[] = $oTransferArrival;
						$bContinue = false;
					}
				}

				if($oTransferDeparture instanceof \Ext_TS_Service_Interface_Transfer){
					
					$oDate->set($oTransferDeparture->transfer_date, \WDDate::DB_DATE);
					$bCheck = $oDate->isBetween(\WDDate::DB_DATE, $oSpecial->service_from, $oSpecial->service_until);
					
					if(
						$bCheck &&
						$oJourney->transfer_mode & \Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE
					){
						// Transfer die in diesem Special liegen
						$aSpecialTransfer[] = $oTransferDeparture;
						$bContinue = false;
					}
				}

				// Kein Kurs/Unterkunft fällt in den Zeitraum
				if($bContinue){
					\Ext_Thebing_Util::debug('Kurs/Unterk./Transfer fällt nicht in Zeitraum: ' . $oSpecial->name, 'findSpecial');
					continue;
				}
				
			} else {
				
				// Alle Transferobjekte setzen, da ohne Zeitraumfilter alle berücksichtigt werden müssen
				if($oTransferArrival instanceof \Ext_TS_Service_Interface_Transfer){
					$aSpecialTransfer[] = $oTransferArrival;
				}
				if($oTransferDeparture instanceof \Ext_TS_Service_Interface_Transfer){
					$aSpecialTransfer[] = $oTransferDeparture;
				}
				
			}

			// Gutscheincode
			$discountCode = null;
			if($oSpecial->discount_code_enabled) {
				$discountCode = $oSpecial->getDiscountCode($this->inquiry->promotion);
				if(empty($discountCode)) {
					\Ext_Thebing_Util::debug('Discount code passt nicht ' . $oSpecial->name, 'findSpecial');
					continue;
				}
			}

			// Mindestanzahl Kurse
			if(!empty($oSpecial->minimum_courses)) {
				if(count($aInquiryCourses) < $oSpecial->minimum_courses) {
					\Ext_Thebing_Util::debug('Zu wenig Kurse ' . $oSpecial->name, 'findSpecial');
					continue;
				}
			}
			
			// prüfen wieviele specials noch verfügbar sind
			$mAvailableSpacials = $oSpecial->getAvailable();
			if($mAvailableSpacials < 1){
				\Ext_Thebing_Util::debug('Kein Special übrig: ' . $oSpecial->name, 'findSpecial');
				continue;
			}

			// prüfen ob für Agenturen oder Direktbucher gültig ist
			if($this->inquiry->agency_id > 0){
				// Agenturen
				if(!$oSpecial->agency_bookings){
					\Ext_Thebing_Util::debug('Special nicht für Agenturkunden: ' . $oSpecial->name, 'findSpecial');
					continue;
				}

				$oAgency = $this->inquiry->getAgency();

				$aAgencyGroups			= (array)$oAgency->groups;

				switch($oSpecial->agency_grouping){
					case 1: //alle
						break;
					case 2: //länder
						$bContinue = true;
						foreach((array)$oSpecial->join_agency_countries as $sCountryId){
							if($sCountryId == $oAgency->ext_6){
								$bContinue = false;
								break;
							}
						}
						if($bContinue){
							\Ext_Thebing_Util::debug('Agenturländer falsch: ' . $oSpecial->name, 'findSpecial');
							continue 2;
						}
						break;
					case 3: // Agenturgruppen
						$bContinue = true;
						foreach((array)$oSpecial->join_agency_groups as $iGroupId){
							if(in_array($iGroupId, $aAgencyGroups)){
								$bContinue = false;
								break;
							}
						}
						if($bContinue){
							\Ext_Thebing_Util::debug('Agenturgruppe falsch: ' . $oSpecial->name, 'findSpecial');
							continue 2;
						}
						break;
					case 4: //kategorien
						$bContinue = true;
						foreach((array)$oSpecial->join_agency_categories as $iCategory){
							if($iCategory == $oAgency->ext_39){
								$bContinue = false;
								break;
							}
						}
						if($bContinue){
							\Ext_Thebing_Util::debug('Agenturkategorie falsch: ' . $oSpecial->name, 'findSpecial');
							continue 2;
						}
						break;
					case 5: //agenturen
						$bContinue = true;
						foreach((array)$oSpecial->join_agencies as $iCategory){
							if($iCategory == $oAgency->getId()){
								$bContinue = false;
								break;
							}
						}
						if($bContinue){
							\Ext_Thebing_Util::debug('Agentur falsch: ' . $oSpecial->name, 'findSpecial');
							continue 2;
						}
						break;
					case 6: // Ländergruppen
						$bContinue = true;
						foreach($oSpecial->join_agency_country_groups as $countryGroupId) {

							$countryGroup = \Ext_TC_Countrygroup::getInstance($countryGroupId);
							$countryGroupObjects = $countryGroup->getJoinedObjectChilds('SubObjects');
							$countryIsos = reset($countryGroupObjects)->countries;

							if(in_array($oAgency->ext_6, $countryIsos)) {
								$bContinue = false;
								break;
							}
						}
						if($bContinue) {
							\Ext_Thebing_Util::debug('Ländergruppe falsch: ' . $oSpecial->name, 'findSpecial');
							continue 2;
						}
						break;
				}

			} else {

				// Direktbucher
				if(!$oSpecial->direct_bookings){
					\Ext_Thebing_Util::debug('Special nicht für Direktbucher: ' . $oSpecial->name, 'findSpecial');
					continue;
				}

			}

			// leer = alles
			$aSpecialCountries = (array)$oSpecial->join_countries;
			if ($oSpecial->join_country_groups) {
				foreach((array)$oSpecial->join_country_groups as $countryGroupId) {
					$countryGroup = \Ext_TC_Countrygroup::getInstance($countryGroupId);
					$countryGroupObjects = $countryGroup->getJoinedObjectChilds('SubObjects');
					$aSpecialCountries = array_merge($aSpecialCountries, reset($countryGroupObjects)->countries);
				}
			}
			if(!empty($aSpecialCountries) && !in_array($oAddressContact->country_iso, $aSpecialCountries)){
				\Ext_Thebing_Util::debug('Falsches Land: ' . $oSpecial->name, 'findSpecial');
				continue;
			}

			// leer = alles
			$aSpecialNationalities = (array)$oSpecial->nationalities;
			if(!empty($aSpecialNationalities) && !in_array($oCustomer->nationality, $aSpecialNationalities)){
				\Ext_Thebing_Util::debug('Falsche Nationalität: ' . $oSpecial->name, 'findSpecial');
				continue;
			}

			// Auf Schülerstatus prüfen
			if($oSpecial->use_student_status == 1) {
				$aStudentStatus = $oSpecial->student_status;
				if(!in_array($this->inquiry->status_id, $aStudentStatus)) {
					continue;
				}
			}

			// Hauptbedingungen sind erfüllt jetzt nach den Buchungspositionen gucken
			\Ext_Thebing_Util::debug('Hauptkriterien erfüllt für: ' . $oSpecial->name, 'findSpecial');

			// SpecialBlöcke holen
			$aSpecialBlocks = $oSpecial->getBlocks();

			switch($oSpecial->amount_type){
				case 1: //prozent
					
					/** @var \Ext_Thebing_Special_Block_Block $oBlock */
					foreach((array)$aSpecialBlocks as $oBlock){
						switch($oBlock->option_id){
							case \Ext_Thebing_Form_Page_Block::TYPE_COURSES:
								$blockCourseIds = $oBlock->getAdditionalDataForSelect();

								// Alle gebuchten Kurse prüfen
								foreach((array)$aInquiryCourses as $oInquiryCourse){

									// Beim Leistungsspecial dürfen nur die Kurse vermerkt werden die in dem Zeitraum liegen
									if(
										(
											!empty($aSpecialCourses) &&
											!in_array($oInquiryCourse, $aSpecialCourses)
										) ||
										!$oBlock->validateWeeks($oInquiryCourse->weeks)
									){
										continue;
									}

									if(in_array($oInquiryCourse->course_id, $blockCourseIds)){
										$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
										$inquirySpecial->inquiry = $this->inquiry;
										$inquirySpecial->special = $oSpecial;
										$inquirySpecial->special_block = $oBlock;
										$inquirySpecial->object = $oInquiryCourse;
										$inquirySpecial->code = $discountCode;
										$aFoundSpecials[] = $inquirySpecial;
									}
								}

								break;
							case \Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
								$aAdditionalData = $oBlock->additional_data;

								// Alle Special Unterkünfte prüfen
								foreach((array)$aAdditionalData as $aData){

									$aAccommodationInfo = explode('_', $aData['value']);
									// Alle gebuchten Unterkünfte prüfen
									foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){

										// Beim Leistungsspecial dürfen nur die Unterkünfte vermerkt werden die in dem Zeitraum liegen
										if(
											!empty($aSpecialAccommodations) &&
											!in_array($oInquiryAccommodation, $aSpecialAccommodations)
										){
											continue;
										}

										if(
											$aAccommodationInfo[0] == $oInquiryAccommodation->accommodation_id &&
											$aAccommodationInfo[1] == $oInquiryAccommodation->roomtype_id &&
											$aAccommodationInfo[2] == $oInquiryAccommodation->meal_id &&
											$oBlock->validateWeeks($oInquiryAccommodation->weeks)
										){
											$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
											$inquirySpecial->inquiry = $this->inquiry;
											$inquirySpecial->special = $oSpecial;
											$inquirySpecial->special_block = $oBlock;
											$inquirySpecial->object = $oInquiryAccommodation;
											$inquirySpecial->code = $discountCode;
											$aFoundSpecials[] = $inquirySpecial;
											
										}
									}
								}
								break;
							case \Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS:

								$aAdditionalData		= $oBlock->additional_data;
								$aTransferPackages		= array();

								// Anreise prüfen
								if(
									count($aSpecialTransfer) == 2
								){
									// Hin und rückreise
									$oPackage = \Ext_Thebing_Transfer_Package::searchPackageByTwoWayTransfer($aSpecialTransfer[0], $aSpecialTransfer[1]);
			
									if(is_object($oPackage)){
										$aTransferPackages[] = $oPackage;
									}
								}

								if(empty($aTransferPackages)){
									// Gibt es keinen An/Abreise zusammen, dann wird einzeln geguckt.
									foreach($aSpecialTransfer as $oTransfer){
										
										$oPackage = \Ext_Thebing_Transfer_Package::searchPackageByTransfer($oTransfer); 

										if(is_object($oPackage)){
											$aTransferPackages[] = $oPackage;
										}
									}	
								}
								
								foreach($aTransferPackages as $oTransferPackage){
									if(is_object($oTransferPackage)){
										// Packet gefunden
										foreach((array)$aAdditionalData as $aData){
											if(
												$aData['value'] == $oTransferPackage->id
											) {
												
												$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
												$inquirySpecial->inquiry = $this->inquiry;
												$inquirySpecial->special = $oSpecial;
												$inquirySpecial->special_block = $oBlock;
												$inquirySpecial->object = $oTransferPackage;
												$inquirySpecial->code = $discountCode;
												$aFoundSpecials[] = $inquirySpecial;
												
											}
										}
									}
										
								}
				
								break;
							case 4: // Zusätzliche Gebühren
								$aAdditionalData = $oBlock->additional_data;
								foreach((array)$aAdditionalData as $aData){
									// Prüfen welche Zusatzkosten zum Kurs gehören
									foreach((array)$aInquiryCourses as $oInquiryCourse){
										
										$oCourse = $oInquiryCourse->getCourse();
										// Kurszusatzkosten
										$aCosts = $oCourse->getAdditionalCosts();
										
										foreach((array)$aCosts as $oCost){
											if($oCost->id == $aData['value']){
												
												$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
												$inquirySpecial->inquiry = $this->inquiry;
												$inquirySpecial->special = $oSpecial;
												$inquirySpecial->special_block = $oBlock;
												$inquirySpecial->object = $oCost;
												$inquirySpecial->code = $discountCode;
												$aFoundSpecials[] = $inquirySpecial;
												
											}
										}
									}
									// Prüfen welche Zusatzkosten zur Unterkunft gehören
									foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){
										
										#$oCategory = $oInquiryAccommodation->getJoinedObject('category');
										
										// Unterkunftzusatzkosten
										$aCosts = $oInquiryAccommodation->getAdditionalCosts();

										foreach((array)$aCosts as $oCost){
											if($oCost->id == $aData['value']){
												
												$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
												$inquirySpecial->inquiry = $this->inquiry;
												$inquirySpecial->special = $oSpecial;
												$inquirySpecial->special_block = $oBlock;
												$inquirySpecial->object = $oCost;
												$inquirySpecial->code = $discountCode;
												$aFoundSpecials[] = $inquirySpecial;
												
											}
										}
									}
								}
								break;
						}
					}
					break;
				case 2: //absolut
					foreach((array)$aSpecialBlocks as $oBlock){
						switch($oBlock->option_id){
							case 1: //einmalig
									// In dieses Special fällt jeder rein! (einmaliger Rabatt)

									$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
									$inquirySpecial->inquiry = $this->inquiry;
									$inquirySpecial->special = $oSpecial;
									$inquirySpecial->special_block = $oBlock;
									$inquirySpecial->code = $discountCode;
									$aFoundSpecials[] = $inquirySpecial;
									
								break;
							case 2: // pro Kurswoche
							case 4: // pro Kurs
								$aAdditionalData = $oBlock->additional_data;

								// Alle Special Kurse prüfen
								foreach((array)$aAdditionalData as $aData){
									// Alle gebuchten Kurse prüfen
									foreach((array)$aInquiryCourses as $oInquiryCourse){

										// Beim Leistungsspecial dürfen nur die Kurse vermerkt werden die in dem Zeitraum liegen
										if(
											!empty($aSpecialCourses) &&
											!in_array($oInquiryCourse, $aSpecialCourses)
										){
											continue;
										}

										if(
											$oInquiryCourse->course_id == $aData['value'] &&
											$oBlock->validateWeeks($oInquiryCourse->weeks)
										){

											$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
											$inquirySpecial->inquiry = $this->inquiry;
											$inquirySpecial->special = $oSpecial;
											$inquirySpecial->special_block = $oBlock;
											$inquirySpecial->object = $oInquiryCourse;
											$inquirySpecial->code = $discountCode;
											$aFoundSpecials[] = $inquirySpecial;
											
										}
									}
								}
								break;
							case 3: // pro Unterkunftswoche
							case 5: // pro Unterkunft
								$aAdditionalData = $oBlock->additional_data;

								// Alle Special Unterkünfte prüfen
								foreach((array)$aAdditionalData as $aData){

									$aAccommodationInfo = explode('_', $aData['value']);
									// Alle gebuchten Unterkünfte prüfen
									foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){

										// Beim Leistungsspecial dürfen nur die Unterkünfte vermerkt werden die in dem Zeitraum liegen
										if(
											!empty($aSpecialAccommodations) &&
											!in_array($oInquiryAccommodation, $aSpecialAccommodations)
										){
											continue;
										}

										if(
											$aAccommodationInfo[0] == $oInquiryAccommodation->accommodation_id &&
											$aAccommodationInfo[1] == $oInquiryAccommodation->roomtype_id &&
											$aAccommodationInfo[2] == $oInquiryAccommodation->meal_id &&
											$oBlock->validateWeeks($oInquiryAccommodation->weeks)
										){
											
											$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
											$inquirySpecial->inquiry = $this->inquiry;
											$inquirySpecial->special = $oSpecial;
											$inquirySpecial->special_block = $oBlock;
											$inquirySpecial->object = $oInquiryAccommodation;
											$inquirySpecial->code = $discountCode;
											$aFoundSpecials[] = $inquirySpecial;
											
										}
									}
								}
								break;
						}
					}
					break;
				case 3: //woche
					foreach((array)$aSpecialBlocks as $oBlock){
						switch($oBlock->option_id){
							case \Ext_Thebing_Form_Page_Block::TYPE_COURSES:
								$aAdditionalData = $oBlock->additional_data;

								// Alle Special Kurse prüfen
								foreach((array)$aAdditionalData as $aData){
									// Alle gebuchten Kurse prüfen
									foreach((array)$aInquiryCourses as $oInquiryCourse){

										// Beim Leistungsspecial dürfen nur die Kurse vermerkt werden die in dem Zeitraum liegen
										if(
											!empty($aSpecialCourses) &&
											!in_array($oInquiryCourse, $aSpecialCourses)
										){
											continue;
										}

										if(
											$oInquiryCourse->course_id == $aData['value'] &&
											$oInquiryCourse->weeks == $oBlock->weeks
										){
											
											$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
											$inquirySpecial->inquiry = $this->inquiry;
											$inquirySpecial->special = $oSpecial;
											$inquirySpecial->special_block = $oBlock;
											$inquirySpecial->object = $oInquiryCourse;
											$inquirySpecial->code = $discountCode;
											$aFoundSpecials[] = $inquirySpecial;
																						
										}
									}
								}
								break;
							case \Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS:
								$aAdditionalData = $oBlock->additional_data;

								// Alle Special Unterkünfte prüfen
								foreach((array)$aAdditionalData as $aData){

									$aAccommodationInfo = explode('_', $aData['value']);
									// Alle gebuchten Unterkünfte prüfen
									foreach((array)$aInquiryAccommodations as $oInquiryAccommodation){

										// Beim Leistungsspecial dürfen nur die Unterkünfte vermerkt werden die in dem Zeitraum liegen
										if(
											!empty($aSpecialAccommodations) &&
											!in_array($oInquiryAccommodation, $aSpecialAccommodations)
										){
											continue;
										}

										if(
											$aAccommodationInfo[0] == $oInquiryAccommodation->accommodation_id &&
											$aAccommodationInfo[1] == $oInquiryAccommodation->roomtype_id &&
											$aAccommodationInfo[2] == $oInquiryAccommodation->meal_id &&
											$oInquiryAccommodation->weeks == $oBlock->weeks
										) {

											$inquirySpecial = new \Ts\Model\Special\InquirySpecial;
											$inquirySpecial->inquiry = $this->inquiry;
											$inquirySpecial->special = $oSpecial;
											$inquirySpecial->special_block = $oBlock;
											$inquirySpecial->object = $oInquiryAccommodation;
											$inquirySpecial->code = $discountCode;
											$aFoundSpecials[] = $inquirySpecial;
											
										}
									}
								}
								break;
						}
					}
					break;
			}

		}

		// Exklusivität checken
		$exklusiveSpecialId = null;
		foreach($aFoundSpecials as $foundSpecial) {
			if($foundSpecial->special->exclusive) {
				$exklusiveSpecialId = $foundSpecial->special->id;
				break;
			}
		}
		
		// Nur Einträge von dem exklusiven Special behalten
		if($exklusiveSpecialId !== null) {
			$tmpFoundSpecials = $aFoundSpecials;
			$aFoundSpecials = [];
			foreach($tmpFoundSpecials as $foundSpecial) {
				if($foundSpecial->special->id === $exklusiveSpecialId) {
					$aFoundSpecials[] = $foundSpecial;
				}
			}
		}

		\Ext_Thebing_Util::debug('GEFUNDEN: ' . count($aFoundSpecials), 'findSpecial');

		return $aFoundSpecials;
	}

}
