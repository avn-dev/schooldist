<?php


class Ext_Thebing_Provision_Gui2_Html {

	static public $sL10NDescription = '';
	static public $oCalendarFormat;
	static public $sGuiHash;

	/*
	 * Hier werden alle Tabs für die Provisionsgruppen generiert
	 */
	public static function getProvisionTabHtml($sType, Ext_Gui2_Dialog $oDialogData, $aSelectedIds){
		global $user_data;

		$aSelectedIds			= (array)$aSelectedIds;
		$iSelectedId			= reset($aSelectedIds);
		// Daten
		$sDialogId				= $oDialogData->sDialogIDTag . $iSelectedId;
		$sHash					= self::$sGuiHash;

		$oClient				= Ext_Thebing_Client::getInstance($user_data['client']);
		$aSchool				= $oClient->getSchools(true);

		$oDivContent = $oDialogData->create('div');

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement(L10N::t('Filter', self::$sL10NDescription));
		$oDivContent->setElement($oH3);
		
		## START Schul select
			$sLabel = L10N::t('Schule', self::$sL10NDescription);

			$oSelect		= $oDialogData->create('select');
			$oSelect->id	= $sType.'['.$sHash.']['.$sDialogId.'][school_id]';
			$oSelect->name	= $sType.'[school_id]';
			$oSelect->class = 'txt form-control school_select';

			$oOption		= $oDialogData->create('option');
			$oOption->value = (int)0;
			$oOption->setElement('');
			$oSelect->setElement($oOption);

			foreach((array)$aSchool as $iSchoolId => $sSchool){
				$oOption		= $oDialogData->create('option');
				$oOption->value = (int)$iSchoolId;
				$oOption->setElement($sSchool);
				$oSelect->setElement($oOption);
			}

			$oRow = $oDialogData->createRow($sLabel, $oSelect);
			$oDivContent->setElement($oRow);
		## ENDE

		## Jahr Select

			$oDivContent->setElement(
					$oDialogData->createRow(
						L10N::t('Jahre', self::$sL10NDescription),
						'select',
						array(
							'db_column' => 'year_select',
							'multiple' => 5,
							'select_options' => Ext_TC_Util::getYears(3,2),
							'jquery_multiple' => 1,
							'searchable' => 1,
							'default_value' => Ext_TC_Util::getYears(1,1),
							'class' => 'year_select',
							'no_savedata' => true
						)
					)
				);
		## ENDE

		// Inhalt
		$oDivTable			= $oDialogData->create('div');
		$oDivTable->id		= $sType . '_provision_table';
		$oDivTable->style	= 'display: none;';

		$oDivContent->setElement($oDivTable);

		$sHTML = $oDivContent->generateHTML();

		return $sHTML;
	}

	/**
	 * Für jedes Tab wird hier die "Tabelle" der Prosisionen gelasen
	 *
	 * @TODO Was ist das hier für ein Schrott, dass zwei Tabellen nebeneinander platziert und positioniert werden?
	 *
	 * @param $iSelectedId
	 * @param $iSchool
	 * @param $sType
	 * @param $aYears
	 * @return string
	 * @throws Exception
	 */
	public static function getProvisionTable($iSelectedId, $iSchool, $sType, $aYears) {

		$iSelectedId		= (int)$iSelectedId;

		$oProvisionGroup	= Ext_Thebing_Provision_Group::getInstance($iSelectedId);
		$oSchool			= Ext_Thebing_School::getInstance($iSchool);
		$aSeasonData		= $oSchool->getSaisonList(false, true);

		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();

		## START Saison Titel formatieren
			$aSeasonListTemp = array();
			foreach((array)$aSeasonData as $aData){
				
				$oPeriodFrom = new WDDate((int)$aData['valid_from']);
				$oPeriodUntil = new WDDate((int)$aData['valid_until']);
				
				// Rausfinden ob Saison in Filterzeitraum fällt
				foreach($aYears as $iYear){
					$oDate = new WDDate($iYear, WDDate::YEAR);
					$bInFilter = $oDate->isBetween(WDDate::YEAR, $oPeriodFrom->get(WDDate::YEAR), $oPeriodUntil->get(WDDate::YEAR));
					
					if($bInFilter){
				$aSeasonListTemp[$aData['id']] = $aData['title_en'];
			}
				}
			}
		## ENDE

		## START Aktuelle Saison Id
			$oSaison 		= new Ext_Thebing_Saison($oSchool->id);
			$oSaisonSearch 	= new Ext_Thebing_Saison_Search();
			$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp(
																	$oSchool->id,
																	null,
																	0,
																	'',
																	false,
																	true
																);
			$iSaisonId 		= $aSaisonData[0]['id'];
			$oSaison->setSaisonById((int)$iSaisonId);
			$iSaisonId = (int)$oSaison->getSaisonId();
		## ENDE


		// Saisonausschnitt bestimmen welche angezeigt werden sollen
		$aSeasonList = array();
		#$iOldKey = 0;
		#$iOldName = '';
		#$iSeasonCount = 0;
		#$iCurrentSeasonSet = false;
		/*
		if($iSaisonId > 0){
			foreach((array)$aSeasonListTemp as $iKey => $sName){

				if(
					$iKey == $iSaisonId &&
					count($aSeasonList) == 0
				){
					// prüfen ob vorheriger Key existiert
					if($iOldKey > 0){
						$aSeasonList[$iOldKey] = $iOldName;
						$iSeasonCount++;
					}
					// aktueller Key
					$aSeasonList[$iKey] = $sName;
					$iCurrentSeasonSet = true;
					$iSeasonCount++;
				}elseif(
					$iSeasonCount < $iMaxSeasons &&
					$iCurrentSeasonSet
				){
					// nächster Key
					$aSeasonList[$iKey] = $sName;
					$iSeasonCount++;
				}

				$iOldKey = $iKey;
				$iOldName = $sName;
			}
		}else{
			// Es gibt zZ keine aktuelle Saison also die letzten 4 nehmen
			$i = 0;
			foreach((array)$aSeasonListTemp as $iKey => $sName){
				if($i < $iMaxSeasons){
					$aSeasonList[$iKey] = $sName;
				}
				$i++;
			}
		}*/

		//immer alles anzeigen, da wird noch bald ein zeitfilter dazu kommen...
		foreach((array)$aSeasonListTemp as $iKey => $sName){
				$aSeasonList[$iKey] = $sName;
		}

		// Headdate vorbereiten
		$aHeadData = array();
		$aHeadData[0]['value'] = '';
		$aHeadData[0]['id'] = 0;
		$aHeadData[0]['width'] = 150;
		$aHeadData[0]['style'] = '';
		$i = 0;
		$iCountSeasons = count($aSeasonList) - 1;
		
		foreach((array)$aSeasonList as $iTempSeasonId => $sSeason){
			$sValue = $sSeason;
			if($i < $iCountSeasons){
				$sValue .= '<i class="copy_season fa fa-chevron-right" aria-hidden="true" id="copy_season_'.$sType.'_'.$iTempSeasonId.'"></i>';
			}
			$aTempData = array();
			$aTempData['value'] = $sValue;
			$aTempData['id'] = $iTempSeasonId;
			$aTempData['width'] = 135;
			$aTempData['cell_class'] = 'table_head';
			$aTempData['cell_id'] = 'table_head_'.$iTempSeasonId;
			if($iTempSeasonId == $iSaisonId){
				$aTempData['style'] = 'background-color: ' . Ext_Thebing_Util::getColor('marked') . '; ';
			}
			$aHeadData[] = $aTempData;
			$i++;
		}

		## START Daten Linke Tabelle holen
			$aLeftData = self::getLeftTableData($oSchool, $sType);
		## ENDE

		## START Tabellen Daten holen
			$aTableData = self::getTableData($oSchool, $oProvisionGroup, $sType, $aHeadData, $aLeftData, false);
		## ENDE

		## START Daten Mittlere Tabelle holen
			$aMainData = self::getMainData($oSchool, $sType, $aHeadData, $aLeftData, $iSaisonId, $aTableData);
		## ENDE

		// ------------------------------------------------

		// Zeilenhöhe px
		$iLineHeight = 43;

		// Korrektur der Maintabelle durch padding/border der LeftTable entstanden
		$iAdjustmentMainTable = 0;
		// Head
		$aHeadData = $aHeadData;

		$iHeaderTableWidth = 0;
		$iMainTableWidth = 0;

		$iAllWidth =

		$oDivContent = new Ext_Gui2_Html_Div();
		$oDivContent->id = 'table_' . $sType;


		$oH3 = new Ext_Gui2_Html_H3();
		$oH3->setElement($oSchool->getName());
		$oDivContent->setElement($oH3);

		$oDivTableHead = new Ext_Gui2_Html_Div();
		$oDivTableLeft = new Ext_Gui2_Html_Div();
		$oDivTableMain = new Ext_Gui2_Html_Div();

		## START Tabellen Kopf erstellen
			$oTable = new Ext_Gui2_Html_Table();
			$oTable->class = 'table tblDocumentTable ';
				$oTr = new Ext_Gui2_Html_Table_tr();
					$bFirst = true; // Erste Spalte
					foreach((array)$aHeadData as $iKey => $aData){
						if($iKey != 0){
							// Breite der Haupttabelle
							$iMainTableWidth += $aData['width'];
						}
						// Breite der HeaderTable
						$iHeaderTableWidth += $aData['width'];

						$sStyle = 'height: ' . $iLineHeight . 'px !important; ';
						$sStyle .= 'width: ' . $aData['width'] . 'px !important; ';
						//$sStyle .= 'white-space: nowrap; ';
						$sStyle .= 'border-bottom: none; ';
						$sStyle .= $aData['style'];

						$oTh = new Ext_Gui2_Html_Table_Tr_Th();
						$oTh->class = $aData['cell_class'];
						$oTh->id = $aData['cell_id'];
						$oTh->setElement($aData['value']);
						$oTh->style =   $sStyle;
						$oTr->setElement($oTh);
					}

			$oTable->setElement($oTr);

			$oTable->style = 'width: ' . $iHeaderTableWidth . 'px;';

			$iContentWidth = $iHeaderTableWidth + 60;
			$oDivContent->style = 'width: ' . $iContentWidth . 'px;';

			$oDivTableHead->setElement($oTable);
		## ENDE

		## START Tabelle links erstellen
			$oDivTableLeft->style = 'float: left; width: ' . ($aHeadData[0]['width']+5) . 'px; ';

			$oTable = new Ext_Gui2_Html_Table();
			$oTable->class = 'table tblDocumentTable ';
				foreach((array)$aLeftData as $iKey => $aData){
					$sStyle = 'height: ' . $iLineHeight . 'px !important; ';
					$sStyle .= 'width: ' . $aHeadData[0]['width'] . 'px !important; ';
					$sStyle .= 'white-space: nowrap; overflow: hidden;';

					$oTr = new Ext_Gui2_Html_Table_tr();

						$sStyle .= $aData['info']['style'];

						$oTh = new Ext_Gui2_Html_Table_Tr_Th();
						$oTh->setElement($aData['value']);
						$oTh->style = $sStyle;
					$oTr->setElement($oTh);
					$oTable->setElement($oTr);
				}

			$oDivTableLeft->setElement($oTable);
		## ENDE

		## START Tabelle main erstellen
			$oDivTableMain->style = 'float: left; width: ' . $iMainTableWidth . 'px; margin-left: ' . $iAdjustmentMainTable . 'px; ';

			$oTable = new Ext_Gui2_Html_Table();
			$oTable->class = 'table tblDocumentTable ';
			$oTable->style = 'width: ' . $iMainTableWidth . 'px;';

			$sStyle = 'height: ' . $iLineHeight . 'px !important; ';
			$sStyle .= 'white-space: nowrap; ';

			foreach((array)$aMainData as $iRow => $aData){
				$oTr = new Ext_Gui2_Html_Table_tr();
				foreach((array)$aData as $iCol => $aCell) {

					//Breite der Head verwenden
					$sCellStyle = $sStyle.'width: ' . $aHeadData[$iCol + 1]['width'] . 'px !important; ';
					$sCellStyle .= $aCell['info']['style'];

					$oTd = new Ext_Gui2_Html_Table_Tr_Td();
					$oTd->class = $aCell['info']['cell_class'];
					$oTd->setElement($aCell['value']);
					$oTd->style = $sCellStyle;
					$oTr->setElement($oTd);

				}
				$oTable->setElement($oTr);
			}
			$oDivTableMain->setElement($oTable);
		## ENDE
		
		$oDivContent->setElement($oDivTableHead);
		$oDivContent->setElement($oDivTableLeft);
		$oDivContent->setElement($oDivTableMain);

		$sHTML = $oDivContent->generateHTML();

		return $sHTML;

	}

	/**
	 * Daten für linke Tabelle holen
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @param string $sType
	 * @return array
	 */
	public static function getLeftTableData(Ext_Thebing_School $oSchool, $sType){

		$aBack = array();

		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();

		switch($sType){
			case 'course':
				## START Kurse
				/** @var $aCategories Ext_Thebing_Tuition_Course_Category[] */
				$aCategories = $oSchool->getCourseCategoriesList('object');
				$oCourseList = $oSchool->getCourseListObject();
				$aCourses = $oCourseList->getObjectList();

				foreach($aCategories as $oCategory){
					$aCategorieInfo = array();
					$aCategorieInfo['value'] = $oCategory->getName();
					$aCategorieInfo['id'] = $oCategory->id;
					$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
					$aCategorieInfo['info']['type'] = 'h2';
					$aBack[] = $aCategorieInfo;

					$bCoursesFound = false;
					//Passende Kurse zur Kategorie
					foreach($aCourses as $oCourse){
						if($oCourse->category_id == $oCategory->id){
							$aCourseInfo = array();
							$aCourseInfo['value'] = $oCourse->name_short;
							$aCourseInfo['id'] = $oCourse->id;
							$aCourseInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
							$aCourseInfo['info']['type'] = '';
							$aCourseInfo['info']['parent_id'] = $oCategory->id;
							$aCourseInfo['info']['save_prefix'] = 'course';
							$aBack[] = $aCourseInfo;
							$bCoursesFound = true;
						}
					}
					// wenn es kein Kurs gibt in der Kategorie dann nicht anzeigen
					if(!$bCoursesFound){
						array_pop($aBack);
					}
				}
				## ENDE

				$aHeadline = array();
				$aHeadline['value'] = L10N::t('Zusatzgebüren');
				$aHeadline['id'] = 0;
				$aHeadline['info']['style'] = 'background-color: #CCCCCC';
				$aHeadline['info']['type'] = 'h1';
				$aBack[] = $aHeadline;

				## START Kurszusatzkosten holen
				$aCosts = array();
				foreach($aCourses as $oCourse){
					$aAdditionalCosts = $oCourse->getAdditionalCosts(true);
					foreach((array)$aAdditionalCosts as $oCost){
						$aCosts[$oCost->id]['cost'] = $oCost;
						$aCosts[$oCost->id]['courses'][] = $oCourse;
					}
				}
				## ENDE

				## START Zusatzkosten
				foreach($aCosts as $iCostId => $aData){
					$aCategorieInfo = array();
					$aCategorieInfo['value'] = $aData['cost']->getName($sInterfaceLanguage);
					$aCategorieInfo['id'] = $aData['cost']->id;
					$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
					$aCategorieInfo['info']['type'] = 'h2';
					$aBack[] = $aCategorieInfo;

					foreach($aData['courses'] as $oCourse){
						$aCourseInfo = array();
						$aCourseInfo['value'] = $oCourse->name_short;
						$aCourseInfo['id'] = $oCourse->id;
						$aCourseInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
						$aCourseInfo['info']['type'] = '';
						$aCourseInfo['info']['parent_id'] = $aData['cost']->id;
						$aCourseInfo['info']['save_prefix'] = 'additional_course';
						$aBack[] = $aCourseInfo;
					}
				}
				## ENDE

				break;
			case 'accommodation':
				$oAccomUtil			= new Ext_Thebing_Accommodation_Util($oSchool);
				$aCategories		= $oSchool->getAccommodationCategoriesList();


				//$aTmpCats = array();
				## START Unterkünfte 2 Mal (!Normale Provision! UND !Extranächte!)
				for($i = 0; $i <2; $i++){
					if($i == 0){
						$sSavePrefix = 'accommodation';
					}else{
						$sSavePrefix = 'extra_night';
					}


					foreach((array)$aCategories as $oCategory){
						$aRoomMealCombi = array();

						$oAccomUtil->setAccommodationCategorie($oCategory->id);
						$aRooms = $oAccomUtil->getRoomtypeList();

						$sName = $oCategory->getName($sInterfaceLanguage);
						if($sName == ''){
							$sName = '?';
						}
						$aCategorieInfo = array();
						$aCategorieInfo['value'] = $sName;
						$aCategorieInfo['id'] = (int)$oCategory->id;
						$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
						$aCategorieInfo['info']['type'] = 'h2';
						$aBack[] = $aCategorieInfo;



						// $aTmpCats[$oCategory->id]['name'] = $oCategory->getName($oSchool->getLanguage());

						// Räume durchgehen
						foreach((array)$aRooms as $aRoom){
							$aMeals = explode(',', $aRoom['meal']);
							$oAccomUtil->setRoomtype($aRoom);

							// Malzeiten durchgehen
							foreach((array)$aMeals as $iMeal){

								$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($iMeal);

								if($aRoomMealCombi[$aRoom['id']][$oMeal->id] == 1){
									continue;
								}

								$aRoomMealCombi[$aRoom['id']][$oMeal->id] = 1;

								$oAccomUtil->setMealById($oMeal->id);

								if($oAccomUtil->getRoomtypeName() != ''){

									$sMealName = $oMeal->getName('', true);

									if($sMealName != ''){
										$aAccommodationInfo = array();
										$aAccommodationInfo['value'] = $oAccomUtil->getRoomtypeName() . '/' . $sMealName;
										$aAccommodationInfo['id'] = (int)$oMeal->id;
										$aAccommodationInfo['additional_id'] = (int)$aRoom['id'];
										$aAccommodationInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
										$aAccommodationInfo['info']['type'] = '';
										$aAccommodationInfo['info']['parent_id'] = $oCategory->id;
										$aAccommodationInfo['info']['save_prefix'] = $sSavePrefix;
										$aBack[] = $aAccommodationInfo;

									}
								}
							}
						}
					}

					if($i == 0){
						$aHeadline = array();
						$aHeadline['value'] = L10N::t('Extranächte');
						$aHeadline['id'] = 0;
						$aHeadline['info']['style'] = 'background-color: #CCCCCC';
						$aHeadline['info']['type'] = 'h1';
						$aBack[] = $aHeadline;
					}
				}
				## ENDE

				$aHeadline = array();
				$aHeadline['value'] = L10N::t('Zusatzgebüren');
				$aHeadline['id'] = 0;
				$aHeadline['info']['style'] = 'background-color: #CCCCCC';
				$aHeadline['info']['type'] = 'h1';
				$aBack[] = $aHeadline;

				## START UK-Zusatzkosten holen
				$aCosts = array();
				foreach($aCategories as $oCategory){
					$aAdditionalCosts = $oCategory->getAdditionalCosts($oSchool);
					foreach((array)$aAdditionalCosts as $oCost){
						$aCosts[$oCost->id]['cost'] = $oCost;
						$aCosts[$oCost->id]['accommodations'][$oCategory->id] = $oCategory;
					}
				}
				## ENDE

				## START Unterkunftskosten
					foreach($aCosts as $iCostId => $aData){
						$aCategorieInfo = array();
						$aCategorieInfo['value'] = $aData['cost']->getName($sInterfaceLanguage);
						$aCategorieInfo['id'] = $aData['cost']->id;
						$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
						$aCategorieInfo['info']['type'] = 'h2';
						$aBack[] = $aCategorieInfo;

						foreach($aData['accommodations'] as $oCategory){
							/** @var Ext_Thebing_Accommodation_Category $oCategory */
							$sName = $oCategory->getName($sInterfaceLanguage);
							if($sName == ''){
								$sName = '?';
							}

							$aAccommodationInfo = array();
							$aAccommodationInfo['value'] = $sName;
							$aAccommodationInfo['id'] = $oCategory->id;
							$aAccommodationInfo['info']['style'] = 'color: black; font-size: 10px; font-weight: normal';
							$aAccommodationInfo['info']['type'] = '';
							$aAccommodationInfo['info']['parent_id'] = $aData['cost']->id;
							$aAccommodationInfo['info']['save_prefix'] = 'additional_accommodation';
							$aBack[] = $aAccommodationInfo;
						}
					}
				## ENDE
				break;
			case 'general':
				
				$aCategorieInfo = array();
				$aCategorieInfo['value'] = L10N::t('Generelle Kosten'); 
				$aCategorieInfo['id'] = 0;
				$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
				$aCategorieInfo['info']['type'] = 'h2';
				$aBack[] = $aCategorieInfo;
				
				$aGeneralCosts = $oSchool->getGeneralCosts();

				foreach((array)$aGeneralCosts as $oCost){
					$aGeneralInfo = array();
					$aGeneralInfo['value'] = $oCost->getName($sInterfaceLanguage);
					$aGeneralInfo['id'] = $oCost->id;
					$aGeneralInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
					$aGeneralInfo['info']['type'] = '';
					$aGeneralInfo['info']['parent_id'] = 0;
					$aGeneralInfo['info']['save_prefix'] = 'general';
					$aBack[] = $aGeneralInfo;
				}
				break;
			case 'transfer':
				$aCategorieInfo = array();
				$aCategorieInfo['value'] = L10N::t('Transfer');
				$aCategorieInfo['id'] = 0;
				$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
				$aCategorieInfo['info']['type'] = 'h2';
				$aBack[] = $aCategorieInfo;
						
				$aTransferInfo = array();
				$aTransferInfo['value'] = L10N::t('An- und Abreise');
				$aTransferInfo['id'] = 0;
				$aTransferInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
				$aTransferInfo['info']['type'] = '';
				$aTransferInfo['info']['parent_id'] = 0;
				$aTransferInfo['info']['save_prefix'] = 'transfer';
				$aBack[] = $aTransferInfo;
					
				$aTransferInfo = array();
				$aTransferInfo['value'] = L10N::t('Anreise');
				$aTransferInfo['id'] = 1;
				$aTransferInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
				$aTransferInfo['info']['type'] = '';
				$aTransferInfo['info']['parent_id'] = 0;
				$aTransferInfo['info']['save_prefix'] = 'transfer';
				$aBack[] = $aTransferInfo;
				
				$aTransferInfo = array();
				$aTransferInfo['value'] = L10N::t('Abreise');
				$aTransferInfo['id'] = 2;
				$aTransferInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
				$aTransferInfo['info']['type'] = '';
				$aTransferInfo['info']['parent_id'] = 0;
				$aTransferInfo['info']['save_prefix'] = 'transfer';
				$aBack[] = $aTransferInfo;
				break;
			case 'activity':
				
				$aCategorieInfo = array();
				$aCategorieInfo['value'] = L10N::t('Aktivitäten'); 
				$aCategorieInfo['id'] = 0;
				$aCategorieInfo['info']['style'] = 'background-color: #DEDEDE';
				$aCategorieInfo['info']['type'] = 'h2';
				$aBack[] = $aCategorieInfo;
				
				$aActivities = TsActivities\Entity\Activity::getActivitiesForSelect();

				foreach($aActivities as $iActivityId=>$sActivity){
					$aGeneralInfo = array();
					$aGeneralInfo['value'] = $sActivity;
					$aGeneralInfo['id'] = $iActivityId;
					$aGeneralInfo['info']['style'] = 'color: black; /*font-size: 10px;*/ font-weight: normal';
					$aGeneralInfo['info']['type'] = '';
					$aGeneralInfo['info']['parent_id'] = 0;
					$aGeneralInfo['info']['save_prefix'] = 'activity';
					$aBack[] = $aGeneralInfo;
				}
				
				break;
		}

		return $aBack;
	}

	/*
	 * Daten für Main-Table holen
	 */
	public static function getMainData($oSchool, $sType, $aHeadData, $aLeftData, $iCurrentSeasonId, $aTableData){
		global $user_data;

		$aBack = array();

		$iSchool = $oSchool->id;

		// Erste Spalte löschen da schon gefüllt
		unset($aHeadData[0]);
		$aHeadData = array_values($aHeadData);

		// Arraydimensionen bestimmen
		//$iCols = count($aLeftData);
		//$iRows = count($aHeadData);

		// Nächste Zeile bekommt Pfeile nach unten wenn true
		$bFirstItemPerCategory = false;

		// Zeilen durchgehen
		foreach((array)$aLeftData as $iKey => $aLeft){
			$iItemId		= (int)$aLeft['id'];

			// Zusatz ID (falls vorhanden) -> Unterkünfte
			$iAdditionalId	= (int)$aLeft['additional_id'];

			$iCategoryId	= (int)$aLeft['info']['parent_id'];
			$sSavePrefix	= $aLeft['info']['save_prefix'];
            $sSavePrefix2   = $sSavePrefix;
			
			// "_" entfernen, da im js ein regex ausgeführt wird
			// durch das "_" im prefix würde dieser falsche Werte liefern
			//
			// z.B. extra_night, additional_*
			if(strpos($sSavePrefix2, '_') !== false){
                $sSavePrefix2   = str_replace('_', '', $sSavePrefix2);
            }			
			
			$bHeadline = 0;
			// überschriften bekommen kein Select
			if($aLeft['info']['type'] == 'h2'){
				$bHeadline = 2;
				$bFirstItemPerCategory = true;
			}elseif($aLeft['info']['type'] == 'h1'){
				$bHeadline = 1;
				$bFirstItemPerCategory = true;
			}

			$bResetFirstItemPerCategory = false;
			// Spalten durchgehen
			foreach((array)$aHeadData as $jKey => $aHead){

				$aInfo = array();
				$HighliteSeason = false;

				if($aHead['id'] == $iCurrentSeasonId){
					$HighliteSeason = true;
				}

				if($bHeadline == 0){
					$iSaisonId = (int)$aHead['id'];

					$oInput = new Ext_Gui2_Html_Input();
					$oInput->type = 'text';
					$oInput->class = 'txt amount w70 input_season_' . $iSaisonId.' input_category_'.$sSavePrefix2.'_'. $iSaisonId . '_' . $iCategoryId;

					// Value holen
					$oProvisionObj = Ext_Thebing_Provision_Group_Provision::getProvisionObject($iSchool, $iSaisonId, $iCategoryId, $iItemId, $iAdditionalId, $sSavePrefix, $aTableData);
					$oInput->value = Ext_Thebing_Format::Number($oProvisionObj->provision, null, $iSchool, true, 5);
					if($oProvisionObj->id > 0){
						// Name braucht nur die Object ID und ist somit eindeutig
						$oInput->name = $sSavePrefix.'['.$oProvisionObj->id.']';
					}else{
						// Name muss alle Informationen enthalten zur Provision
						$sName = $sSavePrefix.'['.$iSaisonId.']['.$iCategoryId.']['.$iItemId.']['.$iAdditionalId.']';


						$oInput->name = $sName;
					}


					$sHtml = $oInput->generateHTML();
					$sHtml .= ' %';
					if($bFirstItemPerCategory){
						$sHtml .= '<i class="copy_category fa fa-chevron-down" aria-hidden="true" id="copy_category_'.$sType.'_'.$sSavePrefix2.'_'.$iSaisonId.'_'.$iCategoryId.'"></i>';
						$bResetFirstItemPerCategory = true;
					}
					$aInfo['value'] = $sHtml;
					$aInfo['info']['cell_class'] = '';
					if($HighliteSeason){
						$aInfo['info']['cell_class'] = 'current-season';
						$aInfo['info']['style'] = 'background-color: ' . Ext_Thebing_Util::getColor('marked');
					}else{
						$aInfo['info']['style'] = 'background-color: #EEEEEE';
					}
				}elseif($bHeadline == 1){
					$aInfo['value'] = '';
					$aInfo['info']['style'] = 'background-color: #CCCCCC;';
				}elseif($bHeadline == 2){
					$aInfo['value'] = '';
					$aInfo['info']['style'] = 'background-color: #DEDEDE;';
				}


				$aBack[$iKey][$jKey] = $aInfo;
			}

			// Wieder zurücksetzen das der nächste eintrag kein Pfeil mehr erhält
			if($bResetFirstItemPerCategory){
				$bFirstItemPerCategory = false;
			}
		}



		return $aBack;
	}

	/*
	 * Daten für diese Tabelle holen
	 */
	public static function getTableData($oSchool, $oGroup, $sType, $aHeadData, $aLeftData, $bReturnObjects = true){

		$iSchool = (int)$oSchool->id;
		$iGroup = (int) $oGroup->id;

		$aTypes				= array();
		$aCategoryIds		= array();
		$aItemIds			= array();
		// Additional IDs z.B. Accommodation room_id
		$aAdditionalIds		= array();
		$aSeasons			= array();
		foreach((array)$aLeftData as $aData){
			if(
				isset($aData['info']['save_prefix']) &&
				$aData['info']['save_prefix'] != ''
			){
				$aTypes[]			= $aData['info']['save_prefix'];
				$aCategoryIds[]		= (int)$aData['info']['parent_id'];
				$aItemIds[]			= (int)$aData['id'];
				$aAdditionalIds[]	= (int)$aData['additional_id'];
			}
		}

		$aTypes				= array_unique($aTypes);
		$aCategoryIds		= array_unique($aCategoryIds);
		$aItemIds			= array_unique($aItemIds);
		$aAdditionalIds		= array_unique($aAdditionalIds);

		foreach((array)$aHeadData as $aData){
			if($aData['id'] > 0){
				$aSeasons[] = (int)$aData['id'];
			}
		}

		$aSeasons			= array_unique($aSeasons);

		// Daten holen
		$aResult = array();
		if(
			$iSchool > 0 &&
			!empty($aItemIds) &&
			!empty($aSeasons)
		){
			$sSql = "SELECT
						*
					FROM
						`ts_commission_categories_values_old`
					WHERE
						`season_id` IN (" . implode(', ', $aSeasons) . ") AND
						`type_id` IN (" . implode(', ', $aItemIds) . ") AND
						`category_id` IN (" . implode(', ', $aCategoryIds) . ") AND
						`type` IN ('" . implode("', '", $aTypes) . "') AND
						`additional_id` IN ('" . implode("', '", $aAdditionalIds) . "') AND
						`group_id` = :group_id AND
						`school_id` = :school_id AND
						`active` = 1
					";
			$aSql = array();
			$aSql['school_id'] = (int)$iSchool;
			$aSql['group_id'] = (int)$iGroup;

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			if($bReturnObjects) {
				$aBack = array();
				foreach((array)$aResult as $aData) {
					$aBack[] = new Ext_Thebing_Provision_Group_Provision($aData['id']);
				}
			} else {
				return $aResult;
			}
		} else {
			return $aResult;
		}

	}

}