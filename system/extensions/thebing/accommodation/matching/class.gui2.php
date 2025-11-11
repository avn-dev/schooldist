<?php

/*
 * Data Klasse für Gastfamilien & Residenz Matching
 */
class Ext_Thebing_Accommodation_Matching_Gui2 extends Ext_Thebing_Gui2_Data {

	protected function _prepareTableQueryData(&$aSql, &$sSql) {

		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$oClient	= $oSchool->getClient();

		$sLang		=  $oSchool->fetchInterfaceLanguage();
		// Gui View setzen
		$aSql['view']		= $this->_oGui->sView;
		$aSql['school_id']	= (int)$oSchool->id;
		$aSql['client_id']	= (int)$oClient->id;
		$aSql['client_id']	= (int)$oClient->id;
		$aSql['short']		= 'short_' . $sLang;

        if($this->_oGui->sView === 'parking') {
            // Parkplätze
            $aSql['category_type_id'] = 2;
        } else if($this->_oGui->sView === 'matching_hostfamily') {
            // Gastfamilienmatching
            $aSql['category_type_id'] = 1;
        } else {
            // Residenz
            $aSql['category_type_id'] = 0;
        }

		return;
	}
	
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$sLang = Ext_Thebing_School::fetchInterfaceLanguage();
		
		$aData['no_result']				= $this->_oGui->t('Kein Ergebniss');
		$aData['family']				= $this->_oGui->t('Unterkunftsanbieter');
		if($this->_oGui->sView === 'parking') {
            $aData['room'] = $this->_oGui->t('Parkplatz');
        } else {
            $aData['room'] = $this->_oGui->t('Raum');
        }
		$aData['name']					= $this->_oGui->t('Name');
		$aData['contact_person'] = $this->_oGui->t('Ansprechpartner');
		$aData['phone']					= $this->_oGui->t('Telefon');
		$aData['phone2']				= $this->_oGui->t('Telefon 2');
		$aData['mobile']				= $this->_oGui->t('Handy');
		$aData['skype']					= $this->_oGui->t('Skype');
		$aData['comment']				= $this->_oGui->t('Kommentar');
		$aData['address']				= $this->_oGui->t('Adresse');
		$aData['zip']					= $this->_oGui->t('PLZ');
		$aData['city']					= $this->_oGui->t('Stadt');
		$aData['country']				= $this->_oGui->t('Land');
		$aData['reserve']				= $this->_oGui->t('reservieren');
		$aData['available']				= $this->_oGui->t('verfügbar');
		$aData['not_available']			= $this->_oGui->t('nicht verfügbar');
		$aData['cut_allocation']		= $this->_oGui->t('Zuordnung zerschneiden');
		$aData['delete_allocation']		= $this->_oGui->t('Zuordnung löschen');
		$aData['comment_allocation'] = $this->_oGui->t('Zuordnung kommentieren');
		$aData['move_allocation']		= $this->_oGui->t('Zuordnung verschieben');
		$aData['match']					= $this->_oGui->t('zuweisen');
		$aData['delete_question']		= $this->_oGui->t('Möchten Sie diese Zuweisung wirklich löschen?');
		$aData['allocation_confirm_wrong_roomtype']		= $this->_oGui->t('Möchten Sie den Schüler wirklich zu einem anderen Raumtypen zuweisen? Diese Änderung hat keine Auswirkung auf die Rechnung und die angezeigten Preise!');
		$aData['requirement_invalid']		= $this->_oGui->t('Voraussetzung ungültig.');

		$oLocaleService = new Core\Service\LocaleService;
		$aMonth = $oLocaleService->getLocaleData($sLang, 'months');
		$aDays = $oLocaleService->getLocaleData($sLang, 'days');

		$aData['monday']				= $aDays['stand-alone']['abbreviated']['mon'];
		$aData['thuesday']				= $aDays['stand-alone']['abbreviated']['tue'];
		$aData['wednesday']				= $aDays['stand-alone']['abbreviated']['wed'];
		$aData['thursday']				= $aDays['stand-alone']['abbreviated']['thu'];
		$aData['friday']				= $aDays['stand-alone']['abbreviated']['fri'];
		$aData['saturday']				= $aDays['stand-alone']['abbreviated']['sat'];
		$aData['sunday']				= $aDays['stand-alone']['abbreviated']['sun'];
		
		$aData['january']				= $aMonth['format']['wide'][1];
		$aData['february']				= $aMonth['format']['wide'][2];
		$aData['march']					= $aMonth['format']['wide'][3];
		$aData['april']					= $aMonth['format']['wide'][4];
		$aData['may']					= $aMonth['format']['wide'][5];
		$aData['june']					= $aMonth['format']['wide'][6];
		$aData['july']					= $aMonth['format']['wide'][7];
		$aData['august']				= $aMonth['format']['wide'][8];
		$aData['september']				= $aMonth['format']['wide'][9];
		$aData['october']				= $aMonth['format']['wide'][10];
		$aData['november']				= $aMonth['format']['wide'][11];
		$aData['december']				= $aMonth['format']['wide'][12];

		$aData['matching_criteria'] = $this->getLegend();

		return $aData;
	}
	
	protected function getLegend(){
		
		$aData = array();
		$aData['A'] = $this->_oGui->t('Katzen');
		$aData['B'] = $this->_oGui->t('Hunde');
		$aData['C'] = $this->_oGui->t('Tiere');
		$aData['D'] = $this->_oGui->t('Raucher');
		$aData['E'] = $this->_oGui->t('Entfernung zur Schule');
		$aData['F'] = $this->_oGui->t('Klimaanlage');
		$aData['G'] = $this->_oGui->t('Seperates Badezimmer');
		$aData['H'] = $this->_oGui->t('Familienalter');
		$aData['I'] = $this->_oGui->t('Wohnumgebung');
		$aData['J'] = $this->_oGui->t('Kinder (Familie)');
		$aData['K'] = $this->_oGui->t('Internet');
		
		return $aData;

//		$oTable = new Ext_Gui2_Html_Table;
//		foreach((array)$aData as $sKey => $sValue){
//			$oTr = new Ext_Gui2_Html_Table_tr();
//			
//			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
//			$oTd->setElement($sKey);
//			$oTr->setElement($oTd);
//			
//			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
//			$oTd->setElement('=');
//			$oTr->setElement($oTd);
//			
//			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
//			$oTd->setElement($sValue);
//			$oTr->setElement($oTd);
//			
//			$oTable->setElement($oTr);
//		}
//		
//		$sHtmlLegend = $oTable->generateHTML();
//	
//		return $sHtmlLegend;
	}
	
	protected function getCommentDialog() {

		$oDialog					= $this->_oGui->createDialog($this->_oGui->t('Kommentar'));
		$oDialog->width				= 650;
		$oDialog->height			= 200;
		$oDialog->sDialogIDTag		= 'COMMENT_';

		$allocationId = $this->request->input('iAllocation');
		
		if(!empty($allocationId)) {
			
			$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($allocationId);

			$oDialog->setElement(
				$oDialog->createRow( 
					$this->_oGui->t('Kommentar'), 
					'input',	
					[
						'db_column' => 'comment', 
						'db_alias' => $oAllocation->id,
						'value' => \Util::convertHtmlEntities($oAllocation->comment)
					]
				)
			);
			
		}
		
		return $oDialog;
	}

	protected function getCutDialog($aSelectedIds){
		

		$oDialog					= $this->_oGui->createDialog($this->_oGui->t('Zerschneiden'));
		$oDialog->width				= 950;
		$oDialog->height			= 550;
		$oDialog->sDialogIDTag		= 'CUT_';

		if(count($aSelectedIds) > 0){
			$iInquiryAccommodationId = (int)reset($aSelectedIds);
			
			$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodationId);
			

			$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($oInquiryAccommodation->inquiry_id, $oInquiryAccommodation->id,true,true);
			
			$i = 1;
			$iActiveAllocations = 0;
			
			$oFormat = new Ext_Thebing_Gui2_Format_Date();	

			foreach($aAllocations as $aAllocation){
				if($i == count($aAllocations)){
					continue;
				}

				// #12767: Dialog funktioniert bei mehr als einer aktiven Zuweisung nicht
				if($aAllocation['room_id'] > 1) {
					$iActiveAllocations++;
				}
				if($iActiveAllocations > 1) {
					$oDialog->bReadOnly = true;
				}

				//$aTemp = array();
				//$aTemp['from'] = Ext_Thebing_Format::LocalDate($aAllocation['from']);
				//$aTemp['to'] = Ext_Thebing_Format::LocalDate($aAllocation['to']);
				//$sCuttings .= $aTemp['to'] . ', ';
				//$aBack[] = $aTemp;
				$i++;
				

				
				$sDate = $oFormat->format((int)$aAllocation['to']);
				
				$oDeleteImg = new Ext_Gui2_Html_Button();
				$oDeleteImg->src = Ext_Thebing_Util::getIcon('delete');
				$oDeleteImg->id = 'clone_img_' . $i;
				//$oDeleteImg->style = 'cursor: pointer; padding: 2px';
				$oDeleteImg->class = 'delete_img btn btn-default btn-sm';
				$oDeleteImg->setElement('<i class="fa fa-minus-circle"></i>');
		
				$oDialog->setElement(
								$oDialog->createRow( 
											$this->_oGui->t('Zerschneidungsdatum'), 
											'calendar',	
											array(
													'db_column' => 'cutting_' . $i, 
													'db_alias' => '',
													'value' => $sDate,
													'format' => $oFormat,
													'input_div_addon' => $oDeleteImg
											)
								)
				);
			}
			
			$i++;
			$oCloneImg = new Ext_Gui2_Html_Button();
			$oCloneImg->id = 'clone_img_' . $i;
			//$oCloneImg->style = 'cursor: pointer; padding: 2px';
			$oCloneImg->class = 'clone_img btn btn-default btn-sm';
			$oCloneImg->setElement('<i class="fa fa-plus-circle"></i>');
				
			$oDialog->setElement(
								$oDialog->createRow( 
											$this->_oGui->t('Zerschneidungsdatum'), 
											'calendar',	
											array(
													'db_column' => 'cutting_' . $i, 
													'db_alias' => '',
													'value' => '',
													'format' => $oFormat,
													'input_div_addon' => $oCloneImg
											)
								)
				);
			
			
		}
		
		
		return $oDialog;
	}

	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional=false) {

		switch($sIconAction) {
			// TODO Methode kann nicht entfernt werden, da ansonsten JS-Fehler TypeError: oDialog.options is undefined?
			case 'matching_cut':
				$oDialogData = $this->getCutDialog($aSelectedIds);
				$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);

//				// Wochentage mitschicken
//				$aDays = array();
//				if(count($aSelectedIds) > 0){
//					$iInquiryAccommodationId = (int)reset($aSelectedIds);
//					$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodationId);
//					$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($oInquiryAccommodation->inquiry_id, $oInquiryAccommodation->id,true,true);
//
//
//					$oDate = new WDDate();
//					$i = 1;
//					foreach($aAllocations as $aAllocation){
//						if($i == count($aAllocations)){
//							continue;
//						}
//						$i++;
//						$oDate->set($aAllocation['to'], WDDate::TIMESTAMP);
//						$aDays[] = $oDate->get(WDDate::WEEKDAY);
//					}
//
//				}
//
//				$aData['days'] = $aDays;
				break;
			case 'comment_allocation':
				$oDialogData = $this->getCommentDialog();
				$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);
				break;
			default:
				// Dialogdaten
				$aData = parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);
				break;
		}

		return $aData;
	}

	#[\Override]
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {
		
		if($sAction === 'comment_allocation') {
			
			$allocationId = array_key_first($aData['comment']);
			$comment = reset($aData['comment']);
			
			$allocation = Ext_Thebing_Accommodation_Allocation::getInstance($allocationId);
			
			if($allocation->exist()) {
				$allocation->comment = strip_tags($comment);
				$allocation->save();
			}
			
			$return = [
				'action' => 'closeDialogAndReloadTable',
				'data' => ['id' => 'COMMENT_'.implode('_', (!empty($aSelectedIds)) ? $aSelectedIds : [0])],
				'error' => []
			];
			
		} else {		
			$return = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}
		
		return $return;
	}
	
	public function switchAjaxRequest($_VARS) {

		$aSelectedIds = (array)$_VARS['id'];

		// Buchungskategorie ignorieren
		$bIgnoreCategory = false;
		if($_VARS['filter']['ignore_category']) {
			$bIgnoreCategory = (bool)$_VARS['filter']['ignore_category'];			
		}

		$bIgnoreRoomtype = false;
		if($_VARS['filter']['ignore_roomtype']) {
			$bIgnoreRoomtype = (bool)$_VARS['filter']['ignore_roomtype'];
		}

		$aTransfer = $this->_switchAjaxRequest($_VARS);		

		if($_VARS['task'] == 'openMoveAllocation') {

			$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($_VARS['iAllocation']);
			$oInquiry	= $oAllocation->getInquiry();
			$oAccommodation = $oAllocation->getInquiryAccommodation();

			$bParking = $oAccommodation->getCategory()->isParking();

			$sFrom = $oAllocation->from;
			$sUntil = $oAllocation->until;

			$oFrom = new DateTime($sFrom);

			$oUntil = new DateTime($sUntil);
			// gastfamilie
			$iFamilie = $_VARS['iFamilie'];
			$aRoomData = [];
			$aBedData = []; // Eigenes Array, da altes JS JS-Arrays braucht
			$aSelectFamilies = [];

			$oMatching = new Ext_TS_Matching();
			$oMatching->setFrom($oFrom);
			$oMatching->setUntil($oUntil);
			$oMatching->bIgnoreCategory = $bIgnoreCategory;
			$oMatching->bIgnoreRoomtype = $bIgnoreRoomtype;
			$oMatching->oAccommodation = $oAccommodation;
			$oMatching->iIgnoreAllocation = $oAllocation->id;
			$oMatching->bSkipAllocationCheck = true;

			if(isset($_VARS['ignore_category'])) {
				$oMatching->bIgnoreCategory = true;
			}

			if(isset($_VARS['ignore_roomtype'])) {
				$oMatching->bIgnoreRoomtype = true;
			}

			if($iFamilie) {
				$aFamilies = $oMatching->getMatchedFamilie($oInquiry);
			} else {
				$aFamilies = $oMatching->getOtherMatched($oInquiry, 1, false, false, $bParking);
			}

			foreach($aFamilies as $key => $aFamily){
				$aSelectFamilies[$aFamily['id']] = $aFamily['ext_33'];
				foreach($aFamily['rooms'] as $aRoom) {
					if($aRoom['isAssignable'] == 1) {

						$aRoomData[$aFamily['id']][$aRoom['id']] = array(
							'text' => $aRoom['room_name'],
							'value' => $aRoom['id'],
						);

						// Eigentlich wird die Methode bereits tief im Matching aufgerufen, aber da kommt man ohne großen Umbau nicht ran…
						$aFreeBeds = Ext_Thebing_Matching::getFreeBeds($aRoom['id'], $oFrom, $oUntil, null, true);
						$aBedData[$aRoom['id']] = array_map(function($aBed) {
							return [
								// Format für GUI.updateSelectOptions()
								'text' => $aBed['bed_number'],
								'value' => $aBed['bed_number']
							];
						}, $aFreeBeds);
					}				
				}

				if(isset($aRoomData[$aFamily['id']])) {
					$aRoomData[$aFamily['id']] = array_values($aRoomData[$aFamily['id']]);
				}
				
			}
			
			$oDialog = $this->_oGui->createDialog($this->t('Zuweisung verschieben'), $this->t('Zuweisung verschieben'));
			$oDialog->height = 240;
			$oDialog->width = 700;

			$sLabel = ($bParking) ? 'Anbieter' : 'Familie';

			$oRow = $oDialog->createRow($this->t($sLabel), 'select', array(
				'select_options' => $aSelectFamilies,
				'name' => 'family_id',
				'id' => 'family_id'
			));
			$oDialog->setElement($oRow);

            $sLabel = ($bParking) ? 'Parkplatz' : 'Raum';

			$oRow = $oDialog->createRow($this->t($sLabel), 'select', array(
				'select_options' => [],
				'name' => 'iNewRoom',
				'id' => 'room_id'
			));
			$oDialog->setElement($oRow);

            $sLabel = ($bParking) ? 'Platz' : 'Bett';

			$oDialog->setElement($oDialog->createRow($this->t($sLabel), 'select', [
				'id' => 'dialog_move_bed_number',
				'name' => 'bed_number',
				'select_options' => []
			]));
			
			$oField = $oDialog->createSaveField('hidden', array('name' => 'idAllocation', 'value' => $oAllocation->id));
			$oDialog->setElement($oField);
			
			$oField = $oDialog->createSaveField('hidden', array('name' => 'idAccommodation', 'value' => $oAllocation->inquiry_accommodation_id));
			$oDialog->setElement($oField);
			
			$oField = $oDialog->createSaveField('hidden', array('name' => 'bOverview', 'value' => 0));
			$oDialog->setElement($oField);
			
			
			$aTransfer['data']			= $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
			$aTransfer['data']['task']	= 'moveAllocation';
			$aTransfer['action']		= 'openDialog';
			$aTransfer['roomData']		= $aRoomData;
			$aTransfer['bed_data'] = $aBedData;

		} elseif(
			$_VARS['task'] == 'updateIcons' &&
			$_VARS['bOverview'] == 0 && // Nur wenn kein Overview da ist laden
			(
				count($aSelectedIds) > 0 ||
				(int)$_VARS['idAccommodation'] > 0
			)
		) {
			// Matching laden
			$iInquiryAccommodationId = 0;
			if(count($aSelectedIds) > 0) {
				$iInquiryAccommodationId = reset($aSelectedIds);
			} else {
				$iInquiryAccommodationId = $_VARS['idAccommodation'];
			}

			$oInquiryAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodationId);

			$this->getMatchingData($aTransfer, $oInquiryAccommodation, $bIgnoreCategory, $bIgnoreRoomtype);

		} elseif($_VARS['task'] == 'saveAllocation') {
			
			$aAllocations = [];
			$oAllocation = new Ext_Thebing_Allocation();
			$oAllocation->setAccommodation((int)$_VARS['idAccommodation']);

			$oInquiryAccommodation = new Ext_TS_Inquiry_Journey_Accommodation((int)$_VARS['idAccommodation']);
			$oInquiry = $oInquiryAccommodation->getInquiry();

			$oSchool = $oInquiry->getSchool();
			
			// Prüfen, ob es vorhandene Zuweisungen dieses Schülers gibt
			if(
				is_object($oInquiry) &&
				!$oSchool->accommodation_parallel_assignment
			) {
			
				if(
					is_numeric($_VARS['iFrom']) ||
					is_numeric($_VARS['iTo'])
				) {
					throw new UnexpectedValueException('Nicht mehr aktuell, bitte DB_DATE übergeben!');
				}
				
				// Sicherheitsabfrage, so dass NIE 2 Zuweisungen zeitgleich vorliegen können	
				$aFilter = array();
				$aFilter['from']	= $_VARS['iFrom'];
				$aFilter['until']	= $_VARS['iTo'];

				if($oInquiryAccommodation->isParking()) {
                    $aFilter['category_type_id'] = [\Ext_Thebing_Accommodation_Category::TYPE_PARKING];
                } else {
                    $aFilter['category_type_id'] = [\Ext_Thebing_Accommodation_Category::TYPE_HOSTFAMILY, \Ext_Thebing_Accommodation_Category::TYPE_OTHERS];
                }

				// Alle Zuweisungen im Zuweisungszeitraum holen
				$aAllocations = $oInquiry->getAllocations($aFilter);

			}
			
			$aError = array();
		
			if(empty($aAllocations)) {

				if($_VARS['idRoom'] > 0) {
					$oAllocation->setRoom((int)$_VARS['idRoom']);
				} else {
					$oAllocation->setFamilie((int)$_VARS['idFamilie']);
				}
				
				$oDateFrom = new DateTime($_VARS['iFrom']);
				$oDateUntil = new DateTime($_VARS['iTo']);

				$bIgnoreRequirements = filter_input( INPUT_POST, 'ignore_matching_requirements', FILTER_VALIDATE_BOOLEAN);
				if($bIgnoreRequirements === false) {

					$oAccommodation = \Ext_Thebing_Accommodation::getInstance($_VARS['idFamily']);

					$bIsValidUntilEndDate = $oAccommodation->checkRequirementValidationDate($oDateUntil);

					if($bIsValidUntilEndDate === false) {
						// Es gibt Dokumente, die vor dem Enddatum der Zuweisung nicht mehr gültig sein werden.
						$aError[] = $this->_oGui->t('Dokumentgültigkeit endet vor dem Enddatum der Zuweisung!');

					}
				}

                $oAllocation->setFrom($oDateFrom);
                $oAllocation->setTo($oDateUntil);
                $oAllocation->setBed($_VARS['iBed']);

                \System::wd()->executeHook('ts_matching_check_allocation', $oAllocation, $aError);

				if(empty($aError)) {

					DB::begin('saveAllocation');

					$bSave = $oAllocation->save();

					$aTransfer['save'] = $bSave;

					// Zuweisungen zusammenreisender Schüler speichern
					$bSaveSharingSuccess = Ext_TS_Inquiry::saveRoomSharingAllocation (
						(int)$_VARS['idInvoice'],
						(int)$_VARS['idRoom'],
						$oDateFrom,
						$oDateUntil
					);

					// Sind die Zuweisungs-Zeiträume aller behandelten Unterkunftsbuchung korrekt?
					if(
						!$bSaveSharingSuccess ||
						!$oInquiryAccommodation->checkAllocationContext()
					) {
						DB::rollback('saveAllocation');
						$aError[] = $this->_oGui->t('Es besteht ein fataler Fehler bei den Zuweisungen dieser Unterkunftsbuchung. Bitte kontaktieren Sie den Thebing-Support.');
						Ext_Thebing_Log::w(get_class($oInquiryAccommodation), $oInquiryAccommodation->id, 'saveAllocation', array('rollback'));
						Ext_TC_Util::reportError('Fataler Fehler bei Unterkunftszuweisungen', 'saveAllocation, Inquiry-Accommodation: '.$oInquiryAccommodation->id);
					} else {
						DB::commit('saveAllocation');
						
						// Damit auch die weiteren Felder wie z.B. Flex-Felder der Anbieter aktualisiert werden
						Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 2);

						// Relevante Felder einzeln aktualisieren, nicht den kompletten Index-Eintrag direkt (dauert zu lange)
						$this->updateIndexInquiry($oInquiry);
					}

				}

			} else {
				// Es liegen schon Zuweisungen in dieser Zeit vor
				$aError[] = $this->_oGui->t('Es gibt bereits eine Zuweisung in diesem Zeitraum.');
			}

			if(!empty($aError)) {
				array_unshift($aError, $this->_oGui->t('Fehler'));
				$aTransfer['action'] = 'reloadMatching';
				$aTransfer['error'] = $aError;
			} else {
				$aTransfer['action'] = 'reloadMatchingAndTable';
			}

			$aTransfer['data']['selectedRows'] = array((int)$_VARS['idAccommodation']);
			$aTransfer['data']['idAccommodation'] = $_VARS['idAccommodation'];

		} elseif($_VARS['task'] == 'deleteAllocation') {
		
			// Vor dem Löschen Accommodation rausfinden, da bei delete aus der DB gelöscht wird!
			$oAccommodationAllocation = new Ext_Thebing_Accommodation_Allocation((int)$_VARS['idAllocation']);
			$oInquiryAccommodation = $oAccommodationAllocation->getInquiryAccommodation();

			$bSuccess = false;
			$bWrongContext = false;
			
			// Es Muss erst geprüft werden ob Zahlungen vorliegen
			$aPayments = $oAccommodationAllocation->checkPaymentStatus();

			DB::begin('deleteAllocation');

			if(empty($aPayments)) {
				$bSuccess = $oAccommodationAllocation->deleteMatching();

				if(
					$bSuccess &&
					empty($oAccommodationAllocation->reservation)
				) {
					$bSuccess = $oInquiryAccommodation->checkAllocationContext();
					if(!$bSuccess) {
						$bWrongContext = true;
					}
				}
			}

			$aError = array();

			if(!$bSuccess){
				DB::rollback('deleteAllocation');

				$aError[] = $this->_oGui->t('Zuweisung konnte nicht gelöscht werden.');

				// Zahlungen vorhanden
				if(!empty($aPayments)){
					$aError[] = $this->_oGui->t('Zahlungen müssen vorher gelöscht werden.');
					foreach((array)$aPayments as $oPayment){
						$aError[] = $oPayment->comment;
					}
				}

				if($bWrongContext) {
					$aError[] = $this->_oGui->t('Es besteht ein fataler Fehler bei den Zuweisungen dieser Unterkunftsbuchung. Bitte kontaktieren Sie den Thebing-Support.');
					Ext_Thebing_Log::w(get_class($oInquiryAccommodation), $oInquiryAccommodation->id, 'deleteAllocation', array('rollback'));
					Ext_TC_Util::reportError('Fataler Fehler bei Unterkunftszuweisungen', 'deleteAllocation, Inquiry-Accommodation: '.$oInquiryAccommodation->id);
				}
				
			} else {
				DB::commit('deleteAllocation');
				
				if($oInquiryAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
					
					$oInquiry = $oInquiryAccommodation->getInquiry();
					
					// Damit auch die weiteren Felder wie z.B. Flex-Felder der Anbieter aktualisiert werden
					Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 2);
					
					$this->updateIndexInquiry($oInquiry);
				}

				$aTransfer['success_message'] = $this->_oGui->t('Die Zuordnung wurde erfolgreich gelöscht.');
				$aTransfer['success_title'] = $this->_oGui->t('Löschen');
				$aTransfer['success'] = 1;
				#$aTransfer['data']['selectedRows'] = array($oInquiryAccommodation->id);

				//nach dem Löschen darf die eigentliche ID nicht verloren gehen, siehe T-2984
				$aTransfer['data']['selectedRows'] = array($oInquiryAccommodation->id);;
			}
			
			$aTransfer['error'] = $aError;	
						
			if($_VARS['bOverview'] == 1){
				// Overview neu laden
				$aTransfer['action'] = 'reloadOverview';
			}else{
				#$aTransfer['data']['idAccommodation'] = $oInquiryAccommodation->id;

				//nach dem Löschen darf die eigentliche ID nicht verloren gehen, siehe T-2984
				$aTransfer['data']['idAccommodation'] = array($oInquiryAccommodation->id);;
				$aTransfer['action'] = 'reloadMatchingAndTable';
			}
		
		} elseif($_VARS['task'] == 'cutAllocation') {

			$oAccommodationAllocation = new Ext_Thebing_Accommodation_Allocation((int)$_VARS['idAllocation']);
			$oInquiryAccommodation = $oAccommodationAllocation->getInquiryAccommodation();
            
			$bSuccess = false;
			
			// Ab jetzt über die Tages Zahl! um Rechnungen im JS zu vermeiden
			$iCutDay = intval($_VARS['iDay']);
			$oFrom = new DateTime($oAccommodationAllocation->from);
			for($i = 1; $i <= $iCutDay; $i++){
				$oFrom->add(new DateInterval('P1D'));
			}
			$sCutDBDateTime = $oFrom->format('Y-m-d H:i:s');
			$oUntil = new DateTime($oAccommodationAllocation->until);

			// Es Muss erst geprüft werden ob Zahlungen vorliegen in dem Zeitraum
			// der durch die Zerschneidung wieder zur Verfügung steht
			$aPayments = $oAccommodationAllocation->checkPaymentStatus($oFrom, $oUntil);

			if(empty($aPayments)) {
				DB::begin('cutAllocation');

				$oAllocation = $oAllocation = new Ext_Thebing_Accommodation_Allocation((int)$_VARS['idAllocation']);
				$bSuccess = $oAllocation->cut($sCutDBDateTime);

				// Prüfen, ob Zuweisungen noch korrekt sind
				if($bSuccess && $oAllocation->hasInquiryAccommodation()) {
					$oAccommodation = $oAllocation->getInquiryAccommodation();
					$bSuccess = $oAccommodation->checkAllocationContext();
				}

				if($bSuccess) {
					DB::commit('cutAllocation');
					//Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiryAccommodation->getInquiry()->id, 0);

                    if($oInquiryAccommodation) {
                        $this->updateIndexInquiry($oInquiryAccommodation->getInquiry());
                    }
				} else {
					$bRollback = true;
					DB::rollback('cutAllocation');
				}
			}
			
			$aError = array();
			if(!$bSuccess) {
				$aError[] = $this->_oGui->t('Zuweisung konnte nicht zerschnitten werden.');

				// Zahlungen vorhanden
				if(!empty($aPayments)) {
					$aError[] = $this->_oGui->t('Zahlungen müssen vorher gelöscht werden.');
					foreach((array)$aPayments as $oPayment){
						$aError[] = $oPayment->comment;
					}
				}

				// »Error« von GUI2 verhindern
				if(isset($bRollback)) {
					$aError[] = $this->_oGui->t('Es besteht ein fataler Fehler bei den Zuweisungen dieser Unterkunftsbuchung. Bitte löschen Sie alle Zuweisungen oder kontaktieren Sie den Thebing-Support.');
					Ext_Thebing_Log::w(get_class($oInquiryAccommodation), $oInquiryAccommodation->id, 'cutAllocation', array('rollback'));
					Ext_TC_Util::reportError('Fataler Fehler bei Unterkunftszuweisungen', 'cutAllocation, Inquiry-Accommodation: '.$oInquiryAccommodation->id);
				}

				$aTransfer['action'] = 'reloadMatching';
			} else {
				$aTransfer['success_message'] = $this->_oGui->t('Zuweisung wurde zerschnitten.');
				$aTransfer['success'] = 1;
				$aTransfer['data']['selectedRows'] = array($oInquiryAccommodation->id);
				
				$aTransfer['action'] = 'saveDialogCallback';
			}			
			
			$aTransfer['error'] = $aError;	

		} elseif($_VARS['task'] == 'moveAllocation') {

			$bCheck = true;
			$aError = array();

			$oAccommodationAllocation = new Ext_Thebing_Accommodation_Allocation((int)$_VARS['idAllocation']);

            $bParking = $oAccommodationAllocation->getInquiryAccommodation()->getCategory()->isParking();

			// #5168
			// Auf vorhandene Unterkunftszahlungen prüfen
			$aPayments = $oAccommodationAllocation->checkPaymentStatus();
			// Zahlungen müssen erst gelöscht werden
			if(!empty($aPayments)){
				$aError[] = $this->_oGui->t('Fehler');
				$aError[] = $this->_oGui->t('Zahlungen müssen vorher gelöscht werden.');
				$bCheck = false;
			}
			
			// Prüfen ob die RaumID dieselbe ist
			if(
			    !$bParking &&
				$_VARS['iNewRoom'] == $oAccommodationAllocation->room_id &&
				$bCheck == true
			){
				$aError[] = $this->_oGui->t('Fehler');
				$aError[] = $this->_oGui->t('Eine Verschiebung innerhalb eines Zimmers ist nicht möglich.');
				$bCheck = false;
			}

			// Wenn die Kategorie nicht passt, kann auch eine Familie ohne Raum und Bett angezeigt werden
			if(
				empty($_VARS['iNewRoom']) ||
				empty($_VARS['bed_number']) &&
				$bCheck
			) {
				$aError[] = $this->_oGui->t('Fehler');
				$aError[] = $this->_oGui->t('Bitte wählen Sie einen Raum und ein Bett aus.');
				$bCheck = false;
			}
			
			if($bCheck == true) {
				
				$updateRoomsharingIndexInquiries = [];
				
				DB::begin('moveAllocation');

				$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance((int)$_VARS['idAllocation']);
				$bSuccess = $oAllocation->moveToRoom((int)$_VARS['iNewRoom'], $_VARS['bed_number']);

				// Prüfen, ob Zuweisungen noch korrekt sind
				if($bSuccess) {
					$oAccommodation = $oAllocation->getInquiryAccommodation();
					$bSuccess = $oAccommodation->checkAllocationContext();
				}

				// Beim Verschieben müssen auch alle zusammenreisenden Schüler mit verschoben werden,
				// die exakt denselben Zeitraum haben
				if($bSuccess) {
					$aRoomSharingAllocations = $oAccommodationAllocation->getRoomSharingAllocations();
					foreach($aRoomSharingAllocations as $oRoomSharingAllocation) {
						$aFreeBeds = Ext_Thebing_Matching::getFreeBeds($_VARS['iNewRoom'], new DateTime($oRoomSharingAllocation->from), new DateTime($oRoomSharingAllocation->until), null, true);
						if(!empty($aFreeBeds)) {
							// Für zusammenreisende Schüler freie Betten herzaubern…
							$iFirstFreeBed = reset($aFreeBeds)['bed_number'];
							$bTmpSuccess = $oRoomSharingAllocation->moveToRoom((int)$_VARS['iNewRoom'], $iFirstFreeBed);
							$updateRoomsharingIndexInquiries[] = $oRoomSharingAllocation->getInquiry();
						} else {
							// Fehler ignorieren, aber auf jeden Fall nicht in einen vollen Raum verschieben
							$bTmpSuccess = true;
						}

						// Prüfen, ob Zuweisungen noch korrekt sind
						if($bTmpSuccess) {
							$oTmpAccommodation = $oRoomSharingAllocation->getInquiryAccommodation();
							$bTmpSuccess = $oTmpAccommodation->checkAllocationContext();
						}

						if(!$bTmpSuccess) {
							$bSuccess = false;
							break;
						}
					}
				}

				if($bSuccess) {
					
					DB::commit('moveAllocation');
					//Ext_Gui2_Index_Stack::add('ts_inquiry', $oAccommodationAllocation->getInquiry()->id, 0);
					$this->updateIndexInquiry($oAccommodationAllocation->getInquiry());
					$aTransfer['success_message'] = $this->_oGui->t('Die Zuordnung wurde erfolgreich verschoben.');
					$aTransfer['success_title'] = $this->_oGui->t('Verschieben');
					$aTransfer['success'] = 1;
					
					foreach($updateRoomsharingIndexInquiries as $updateRoomsharingIndexInquiry) {
						$this->updateIndexInquiry($updateRoomsharingIndexInquiry);
					}
					
				} else {
					DB::rollback('moveAllocation');
					$aError[] = $this->_oGui->t('Fehler');
					$aError[] = $this->_oGui->t('Es besteht ein fataler Fehler bei den Zuweisungen dieser Unterkunftsbuchung. Bitte löschen Sie alle Zuweisungen oder kontaktieren Sie den Thebing-Support.');
					$oAccommodation = $oAllocation->getInquiryAccommodation();
					Ext_Thebing_Log::w(get_class($oAccommodation), $oAccommodation->id, 'moveAllocation', array('rollback'));
					Ext_TC_Util::reportError('Fataler Fehler bei Unterkunftszuweisungen', 'moveAllocation, Inquiry-Accommodation: '.$oAccommodation->id);
				}

				$aTransfer['data']['selectedRows'] = array((int)$_VARS['idAccommodation']);
			}

			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['error'] = $aError;
			$aTransfer['data']['idAccommodation'] = $_VARS['idAccommodation'];
			$aTransfer['data']['reloadTableByError'] = 1;
			
		}elseif($_VARS['task'] == 'confirmMoveAllocation'){
			
			$aError = array();
			$mCheck = true;

			$oAccommodationAllocation = new Ext_Thebing_Accommodation_Allocation((int)$_VARS['idAllocation']);

			$oInquiryAccommodation = $oAccommodationAllocation->getInquiryAccommodation();

			if(
				$_VARS['idAllocation'] > 0 &&
				is_object($oInquiryAccommodation)
			){
				$oInquiry = $oInquiryAccommodation->getInquiry();
				$oCustomer = $oInquiry->getCustomer();
			}else{
				$aError[] = $this->_oGui->t('Fehler');
				$aError[] = $this->_oGui->t('Die Zuordnung konnte nicht verschoben werden. Familie nicht gefunden.');
				$mCheck = false;
			}


			$oRoom = new Ext_Thebing_Accommodation_Room((int)$_VARS['iNewRoom']);
			$oProvider = $oRoom->getProvider();
			
			// Prüfen ob die RaumID dieselbe ist
			if($_VARS['iNewRoom'] == $oAccommodationAllocation->room_id){
				$aError[] = $this->_oGui->t('Fehler');
				$aError[] = $this->_oGui->t('Eine Verschiebung innerhalb eines Zimmers ist nicht möglich.');
				$mCheck = false;
			}
	
			if($mCheck == true){
				$mCheck = Ext_Thebing_Allocation::checkAllocation((int)$_VARS['idAllocation'], (int)$_VARS['iNewRoom'], (int)$_VARS['other_matching'], 'move');
				// Infos sammeln
				if($mCheck === true) {
					
					// Auf vorhandene Unterkunftszahlungen prüfen
					$aPayments = $oAccommodationAllocation->checkPaymentStatus();
					
					// Wenn nur innerhalb einer Familie verschoben wird, ist das mögich da Bezahlungen
					// Familiengebunden sind
					foreach($aPayments as $iPaymentId => $oPayment){
						if($oPayment->accommodation_id == $oProvider->id){
							unset($aPayments[$iPaymentId]);
						}
					}
					
					if(!empty($aPayments)){
						$aError[] = $this->_oGui->t('Zahlungen müssen vorher gelöscht werden.');
						foreach((array)$aPayments as $oPayment){
							$aError[] = $oPayment->comment;
						}
					}

					
					$aTransfer['action'] = 'confirmMoveAllocation';
				}else{
					$aError[] = $this->_oGui->t('Fehler');
					$aError[] = $this->_oGui->t('Die Zuordnung konnte nicht verschoben werden. Bitte prüfen Sie die Vorgaben der Buchung.');
				}
			}
			
			$sQuestion = $this->_oGui->t('Möchten Sie den Schüler "%s" in die Unterkunft "%u (Raum: %r)" verschieben?');
			$aFind = array('%s', '%u', '%r');
			$aReplace = array($oCustomer->name, $oProvider->ext_33, $oRoom->name);

			$sQuestion = str_replace($aFind, $aReplace, $sQuestion);
			
			if(!empty($aError)){
				if($_VARS['bOverview'] == 1){
					$aTransfer['action'] = 'reloadOverview';
				}else{
					$aTransfer['action'] = 'reloadMatching';
				}
			}
			
			$aTransfer['error'] = $aError;	
			$aTransfer['data']['room_id'] = (int)$_VARS['iNewRoom'];
			$aTransfer['data']['move_question'] = $sQuestion;
			$aTransfer['data']['allocation_id'] = (int)$_VARS['idAllocation'];
		} elseif (
			$_VARS['task'] == 'request' &&	
			(
				$_VARS['action'] == 'overview' ||
				$_VARS['action'] == 'availability'
			)
		) {		
			
			if($_VARS['check_availability']) {

				$bIgnoreCategory = false;
				$bIgnoreRoomtype = false;

				$dFrom = Ext_Thebing_Format::ConvertDate($_VARS['availability_from'], null, 3);
				$dTo = Ext_Thebing_Format::ConvertDate($_VARS['availability_to'], null, 3);
				
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
				
				$oInquiryAccommodation = new Ext_TS_Inquiry_Journey_Accommodation;
				$oInquiryAccommodation->visible = 1;
				$oInquiryAccommodation->accommodation_id = (int)$_VARS['availability_category'];
				$oInquiryAccommodation->roomtype_id = (int)$_VARS['availability_roomtype'];
				$oInquiryAccommodation->meal_id = (int)$_VARS['availability_board'];
				if($dFrom && $dTo) {
					$oInquiryAccommodation->from = $dFrom->format('Y-m-d');
					$oInquiryAccommodation->until = $dTo->format('Y-m-d');				
					$oInquiryAccommodation->weeks = \Ext_Thebing_Util::countWeeks($dFrom, $dTo);
				}			
				
//				$mValidate = $oInquiryAccommodation->validate();
//
//				if($mValidate === true) {
				if(empty($_VARS['availability_category'])) {
					$bIgnoreCategory = true;
				}

				if(empty($_VARS['availability_roomtype'])) {
					$bIgnoreRoomtype = true;
				}

				$oInquiry = $oInquiryAccommodation->getInquiry();

				if(!empty($_VARS['availability_criteria'])) {
					$oInquiryMatching = $oInquiry->getMatchingData();
					foreach($_VARS['availability_criteria'] as $sField=>$mValue) {
						if(!empty($mValue)) {
							$oInquiryMatching->$sField = $mValue;
						}
					}
				}

				$oCustomer = $oInquiry->getTraveller();

				$oBirthday = new Core\Helper\DateTime();
				if($_VARS['availability_age'] !== 'minor') {
					$oBirthday->modify('- '.($oSchool->getGrownAge()+1).' years');
				}

				$oCustomer->birthday = $oBirthday->format('Y-m-d');
				$oCustomer->gender = (int)$_VARS['availability_gender'];

				$this->getMatchingData($aTransfer, $oInquiryAccommodation, $bIgnoreCategory, $bIgnoreRoomtype);
				$aTransfer['action'] = 'showOverview';

//				} else {
//					$aTransfer = [
//						'action' => 'showError',
//						'error' => [$this->t('Bitte füllen Sie alle benötigen Felder aus!')]
//					];
//				}
				
			} else {
				$aFilter = $_VARS['filter'];		
				$aTransfer = $this->getOverviewData($aFilter, true);
				$aTransfer['data']['action'] = $_VARS['action'];
			}			
			
		}elseif(
			$_VARS['task'] == 'saveDialog' &&
			$_VARS['action'] == 'matching_cut'
		) {
			
			$aInquiryAccommodationIds = $_VARS['id'];
			$aError = array();

			if(count($aInquiryAccommodationIds) > 0){
				$iInquiryAccommodation = reset($aInquiryAccommodationIds);
				
				// Zerschneidungsdaten
				$aCuttingDates = array();
				foreach($_VARS['save'] as $sKey => $sDate){
					if(
						strpos($sKey, 'cutting_') !== false &&
						!empty($sDate)
					){
						$aCuttingDates[] = $sDate;
					}
				}

				// Prüfen, ob irgendein Datum doppelt ist – muss abgefangen werden, da ansonsten komische Dinge passieren
				if(count($aCuttingDates) !== count(array_unique($aCuttingDates))) {
					$aError[] = $this->_oGui->t('Es ist ein Fehler aufgetreten');
					$aError[] = $this->_oGui->t('Es ist nicht möglich, Zuweisungen einer Unterkunftsbuchung mehr als einmal am selben Tag zu zerschneiden.');
				}

				$oAllocation = new Ext_Thebing_Allocation();
				$oAllocation->setAccommodation($iInquiryAccommodation);

				$aCuttingEnds = array();
				foreach((array)$aCuttingDates as $sCuttingDate){
					$sCuttingDate = Ext_Thebing_Format::ConvertDate($sCuttingDate);
					if($sCuttingDate > 0){
						$aCuttingEnds[] = new WDDate($sCuttingDate);
					}
				}
				sort($aCuttingEnds);

				$oInquiryAccommodation = new Ext_TS_Inquiry_Journey_Accommodation($iInquiryAccommodation);

				$oFrom		= new WDDate($oInquiryAccommodation->from, WDDate::DB_DATE);
				$oUntil		= new WDDate($oInquiryAccommodation->until, WDDate::DB_DATE);
				
				$aCutting = array();
								
				if(
					!empty($aCuttingEnds) &&
					$oFrom->compare($aCuttingEnds[0]) < 0
				) {
					foreach((array)$aCuttingEnds as $oCuttingDate) {
						if($oUntil->compare($oCuttingDate) > 0) {
							$aTemp = array();
							$aTemp['from'] = $oFrom->get(WDDate::DB_DATE);
							$aTemp['to'] = $oCuttingDate->get(WDDate::DB_DATE);
							$aCutting[] = $aTemp;
							$oFrom = $oCuttingDate;
						}

					}

					$aTemp = array();
					$aTemp['from'] = $oFrom->get(WDDate::DB_DATE);
					$aTemp['to'] = $oUntil->get(WDDate::DB_DATE);
					$aCutting[] = $aTemp;				
				}

				DB::begin('saveDialogMatchingCut');

				if(empty($aError)) {

					// Eigentliches Zerschneiden
					$oAllocation->deleteAllAllocations(true);
					foreach((array)$aCutting as $aCutt) {
						$oAllocation->saveInactiveAllocation($aCutt['from'], $aCutt['to'], $iInquiryAccommodation);
					}

					// Prüfen, ob Zuweisungen noch korrekt sind
					$bSuccess = $oInquiryAccommodation->checkAllocationContext();
					if(!$bSuccess) {
						$aError[] = $this->_oGui->t('Es besteht ein fataler Fehler bei den Zuweisungen dieser Unterkunftsbuchung. Bitte löschen Sie alle Zuweisungen oder kontaktieren Sie den Thebing-Support.');
					}

				} else {
					$bSuccess = false;
				}

				if($bSuccess) {
					DB::commit('saveDialogMatchingCut');
					$aTransfer['success_message'] = $this->_oGui->t('Zuweisung wurde erfolgreich zerschnitten.');
					$aTransfer['success'] = 1;
					$aTransfer['data']['selectedRows'] = array($iInquiryAccommodation);
				} else {
					DB::rollback('saveDialogMatchingCut');
					Ext_Thebing_Log::w('Ext_TS_Inquiry_Journey_Accommodation', $iInquiryAccommodation, 'matching_cut', array('rollback'));
					Ext_TC_Util::reportError('Fataler Fehler bei Unterkunftszuweisungen', 'matching_cut, Inquiry-Accommodation: '.$iInquiryAccommodation);
				}

			} else {
				$aError[] = $this->_oGui->t('Kein Eintrag gewählt.');
			}			
			
			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['error'] = $aError;		
		} elseif(
			$_VARS['task'] === 'requestAsUrl' &&
			$_VARS['action'] === 'room_cleaning_schedule'
		) {
			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
			$oDateRange = \Core\Helper\DateTime::createDatesFromTimefilterInput($_VARS['filter']['search_time_from_1'], $_VARS['filter']['search_time_until_1'], $oDateFormat);

			if($oDateRange instanceof \Core\DTO\DateRange) {
				$bResidentalMatching = $_VARS['additional'] !== 'matching_hostfamily';
				$oService = new \TsAccommodation\Generator\RoomCleaningSchedule($oDateRange, Closure::fromCallable([$this, 't']), $bResidentalMatching);
				$oService->render();
			} else {
				throw new RuntimeException('Invalid dates!');
			}

			return;
		}

		echo json_encode($aTransfer);
	}
	
	private function getMatchingData(&$aTransfer, Ext_TS_Inquiry_Journey_Accommodation $oInquiryAccommodation, $bIgnoreCategory=false, $bIgnoreRoomtype=false) {
		
		$aBack = [];

		$iInquiryId = $oInquiryAccommodation->inquiry_id;

		$oFrom = new DateTime($oInquiryAccommodation->from);
		$oUntil = new DateTime($oInquiryAccommodation->until);
		$oFrom->setTime(0, 0, 0);
		$oUntil->setTime(0, 0, 0);

		$oMatch = new Ext_TS_Matching();
		$oMatch->setFrom($oFrom);
		$oMatch->setUntil($oUntil);

		$oInquiry = $oInquiryAccommodation->getInquiry();
		$oCustomer = $oInquiry->getCustomer();
		$oMatch->oAccommodation = $oInquiryAccommodation;
		$oMatch->bIgnoreCategory = $bIgnoreCategory;
		$oMatch->bIgnoreRoomtype = $bIgnoreRoomtype;

		if($this->_oGui->sView == 'matching_hostfamily') {
			$iHaveAllocation = $oInquiry->checkForAllcoation($oInquiryAccommodation->id);
			$aFamilies = $oMatch->getMatchedFamilie($oInquiry);
		} else if($this->_oGui->sView == 'parking') {
			$iHaveAllocation = $oInquiry->checkForAllcoation();
			$aFamilies = $oMatch->getOtherMatched($oInquiry,1, false, false, true);
        } else {
            $iHaveAllocation = $oInquiry->checkForAllcoation();
            $aFamilies = $oMatch->getOtherMatched($oInquiry);
        }

		// Zeitraum der angezeigt wird festlegen!
		$aTransfer['data']['displayData']['days'] = $oMatch->getDisplayDays(true);
		$aTransfer['data']['displayData']['day_from'] = reset($aTransfer['data']['displayData']['days']);
		$aTransfer['data']['displayData']['day_until'] = end($aTransfer['data']['displayData']['days']);

		$aBack['data'] = $aFamilies;
		$aBack['inquiry'] = $oInquiry->getShortArray();
		$aBack['inquiry']['haveAllocation'] = $iHaveAllocation;
		$aBack['customer'] = $oCustomer->getShortArray();
		$aBack['inquiry']['acc_time_from'] = $oMatch->iFrom;
		$aBack['inquiry']['acc_time_to'] = $oMatch->iTo;
		$aBack['inquiry']['acc_day_from'] = $oMatch->getDayData($oFrom);
		$aBack['inquiry']['acc_day_until'] = $oMatch->getDayData($oUntil);
		$aBack['inquiry']['inactive_allocations'] = $oInquiry->getInactiveAllocations($oInquiryAccommodation->id);

		foreach($aBack['inquiry']['inactive_allocations'] as $key => &$aAllocation){

			$oAlloFrom = new DateTime(date('Y-m-d', $aAllocation['from']));
			$oAlloUntil = new DateTime(date('Y-m-d', $aAllocation['to']));

			$aAllocation['from'] = Ext_Thebing_Util::convertUTCDate($aAllocation['from']);
			$aAllocation['to'] = Ext_Thebing_Util::convertUTCDate($aAllocation['to']);

			$aBack['inquiry']['inactive_allocations'][$key]['day_from'] = $oMatch->getDayData($oAlloFrom);
			$aBack['inquiry']['inactive_allocations'][$key]['day_until'] = $oMatch->getDayData($oAlloUntil);

		}

		$aBack['view'] = $this->_oGui->sView;
		$aBack['bOverwiew'] = 0;
		$aTransfer['data']['matching_data'] = $aBack;
		$aTransfer['data']['idAccommodation'] = $oInquiryAccommodation->id;

		return $aTransfer;
	}
	
	/*
	 * Liefert die Overview Daten
	 */
	private function getOverviewData($aFilter) {
		
		$aBack = array();
		$aBack['iFrom'] = Ext_Thebing_Format::ConvertDate($aFilter['search_time_from_1']);
		if($aBack['iFrom'] <= 0){
			$aBack['iFrom'] = time();
		}
		$aBack['iTo'] = Ext_Thebing_Format::ConvertDate($aFilter['search_time_until_1']);
		if($aBack['iTo'] <= 0){
			$aBack['iTo'] = time();
		}
		
		$oFrom	= new DateTime(date('Y-m-d', $aBack['iFrom']));
		$oUntil = new DateTime(date('Y-m-d', $aBack['iTo']));
		
		$aBack['iFrom'] = gmmktime(0, 0, 0, date('m', $aBack['iFrom']), date('d', $aBack['iFrom']), date('Y', $aBack['iFrom']));
		$aBack['iTo'] = gmmktime(23, 59, 59, date('m', $aBack['iTo']), date('d', $aBack['iTo']), date('Y', $aBack['iTo']));
		$aBack['data'] = array();

		if($this->_oGui->sView === 'parking') {
			$iOverviewType = 2; // Parkplatz
		} else if($this->_oGui->sView === 'matching_hostfamily') {
            $iOverviewType = 1; // Gastfamilie
        } else {
            $iOverviewType = 0; // Residenz
        }

		$oMatching = new Ext_TS_Matching();
		$oMatching->setFrom($oFrom);
		$oMatching->setUntil($oUntil);

		$aBack['data'] = $oMatching->getAllFamiliesWithBeds($oFrom, $oUntil, $iOverviewType, (int)$aFilter['matched_category'], true);
		$aBack['inquiry'] = null;
		$aBack['customer'] = null; 
		$aBack['view'] = $this->_oGui->sView;
		$aBack['bOverwiew'] = 1;

		$aTransfer['data']['displayData']['days']		= $oMatching->getDisplayDays();
		$aTransfer['data']['displayData']['day_from']	= reset($aTransfer['data']['displayData']['days']);
		$aTransfer['data']['displayData']['day_until']	= end($aTransfer['data']['displayData']['days']);
		$aTransfer['data']['matching_data'] = $aBack;
		$aTransfer['action'] = 'showOverview';

		return $aTransfer;
	}

	/**
	 * Nur benötigte Felder für Matchingveränderungen aktualisieren, nicht komplette Buchung
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 */
	private function updateIndexInquiry(Ext_TS_Inquiry $oInquiry) {

		// Dämlicher statischer Cache wird bereits aufgebaut, bevor Änderungen im Matching geschrieben wurden
		Ext_Thebing_Allocation::resetStaticCache();

		Ext_Gui2_Index_Stack::update('ts_inquiry', $oInquiry->id, [
			'accommodation_provider_id',
			'accommodation_name',
			'accommodation_room',
			'accommodation_street',
			'accommodation_address_addon',
			'accommodation_share_with',
			'accommodation_zip',
			'accommodation_city',
			'accommodation_tel',
			'accommodation_tel2',
			'accommodation_mobile',
			'accommodation_email',
			'accommodation_contact',
			'accommodation_description',
			'accommodation_bed', 
			'accommodation_room_bed',
			'allocated_accommodations'
		]);

	}

	public function requestReservationDialog($aVars) {
		
		$oDialog = $this->_oGui->createDialog($this->t('Reservierung eintragen'), $this->t('Reservierung eintragen'));
		$oDialog->height = 300;
		$oDialog->width = 700;
		$oDialog->sDialogIDTag = 'RESERVATION_';

		$dValidUntil = new DateTime();
		$dValidUntil->modify('+7 days');
		
		$oRow = $oDialog->createRow($this->t('Gültig bis'), 'calendar', array(
			'value' => Ext_Thebing_Format::LocalDate($dValidUntil),
			'name' => 'valid_until',
			'id' => 'valid_until',
			'required' => true
		));
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Reserviert für'), 'input', array(
			'name' => 'comment',
			'id' => 'comment',
			'required' => true
		));
		$oDialog->setElement($oRow);

		$oField = $oDialog->createSaveField('hidden', array('name' => 'accommodation_provider_id', 'value' => (int)$aVars['accommodation_provider_id']));
		$oDialog->setElement($oField);

		$oField = $oDialog->createSaveField('hidden', array('name' => 'room_id', 'value' => (int)$aVars['room_id']));
		$oDialog->setElement($oField);

		$oField = $oDialog->createSaveField('hidden', array('name' => 'bed', 'value' => (int)$aVars['bed']));
		$oDialog->setElement($oField);

		$this->aIconData['availability']['dialog_data'] = $oDialog;
		
		$aTransfer['data'] = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aTransfer['data']['task'] = 'save';
		$aTransfer['data']['action'] = 'Reservation';
		$aTransfer['action'] = 'openDialog';

		return $aTransfer;
	}

	public function saveReservation($aVars) {

		$dFrom = Ext_Thebing_Format::ConvertDate($aVars['availability_from'], null, 3);
		$dTo = Ext_Thebing_Format::ConvertDate($aVars['availability_to'], null, 3);

		$dValidUntil = Ext_Thebing_Format::ConvertDate($aVars['valid_until'], null, 3);

		$oReservation = Ext_Thebing_Accommodation_Allocation::getInstance();
		$oReservation->active = 1;
		$oReservation->room_id = (int)$aVars['room_id'];
		$oReservation->bed = (int)$aVars['bed'];
		$oReservation->from = $dFrom->format('Y-m-d H:i:s');
		$oReservation->until = $dTo->format('Y-m-d H:i:s');

		$aReservation = [
			'comment' => (string)$aVars['comment'],
			'date' => $dValidUntil->format('Y-m-d'),
			'category' => $aVars['availability_category'],
			'roomtype' => $aVars['availability_roomtype'],
			'board' => $aVars['availability_board'],
			'age' => $aVars['availability_age'],
			'gender' => $aVars['availability_gender'],
			'criteria' => $aVars['availability_criteria'],
		];
		$oReservation->setReservationData($aReservation);

		$oReservation->reservation_date = $dValidUntil->format('Y-m-d');
		
		$mValidate = $oReservation->validate();
		$aErrors = [];

		if($mValidate === true) {

		    \DB::begin(__METHOD__);

		    try {
                $oReservation->save();

                DB::commit(__METHOD__);

            } catch (\Exception $ex) {
                $aErrors[] = $ex->getMessage();
                DB::rollback(__METHOD__);
            }

		} else {
			$aErrors = $this->getErrorData($mValidate, 'availability', 'error', true);
		}
		
		if(!empty($aErrors)) {
			$aTransfer = [
				'action' => 'closeDialog',
				'data' => [
					'id' => 'RESERVATION_0'
				],
				'error' => $aErrors
			];
		} else {

			$aFilter = $aVars['filter'];		
			$aTransfer = $this->getOverviewData($aFilter, true);
			$aTransfer['data']['id'] = 'RESERVATION_0';

		}
		
		return $aTransfer;
	}
	
}
