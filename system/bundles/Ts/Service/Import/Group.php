<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;

class Group extends AbstractImport {

	protected $sEntity = \Ext_Thebing_Inquiry_Group::class;

	public function getFields() {

		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[] = ['field'=> 'Name der Agentur', 'key' => 'agency_id'];
		$aFields[] = ['field'=> 'Name der Gruppe', 'key' => 'name', 'mandatory' => true];
		$aFields[] = ['field'=> 'Kürzel der Gruppe', 'key' => 'short', 'mandatory' => true];
		$aFields[] = ['field'=> 'Muttersprache der Gruppe', 'key' => 'language_id', 'mandatory' => true];
		// Die Nationalität der Gruppe wird durch die Nationalität des ersten Schülers gesetzt (ist ein Pflichtfeld)
		$aFields[] = ['field'=> 'Vorname des Schülers', 'key' => 'firstname', 'mandatory' => true];
		$aFields[] = ['field'=> 'Nachname des Schülers', 'key' => 'lastname', 'mandatory' => true];
		$aFields[] = ['field'=> 'Geburtstag des Schülers', 'key' => 'birthday', 'mandatory' => true];
		$aFields[] = ['field'=> 'Geschlecht des Schülers', 'key' => 'gender', 'mandatory' => true];
		$aFields[] = ['field'=> 'Nationalität des Schülers', 'key' => 'nationality', 'mandatory' => true];
		$aFields[] = ['field'=> 'Anreisedatum (Transfer)', 'key' => 'arrival'];
		$aFields[] = ['field'=> 'Abreisedatum (Transfer)', 'key' => 'departure'];
		// Die Korrespondenzsprache der Gruppe wird durch die Schulsprache gesetzt (ist ein Pflichtfeld)

		$aFields = array_values($aFields);

		return $aFields;
	}

	public function getFlexibleFields() {

		$groupEntity = new $this->sEntity;
		$courseFlexFields = \Ext_TC_Flexibility::query()->where('section_id', 4)->get()->toArray();

		$aFlexFields['Main'] = array_merge($groupEntity->getFlexibleFields(), $courseFlexFields);

		return $aFlexFields;
	}

	protected function getBackupTables() {
		return [];
	}

	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {
			// Unnötige Ebene entfernen -> musste da sein wegen der parent execute.
			$groupArray = array_merge($aItem['group']);
			unset($aItem['group']);
			$aItem = array_merge($groupArray, $aItem);

			$school = \Ext_Thebing_School::getSchoolFromSession();
			$schoolLanguage = $school->getLanguage();
			$client = \Ext_Thebing_Client::getFirstClient();

			$inboxList = $client->getInboxList();
			// Erstbeste Inbox und Währung erstmal
			$inbox = reset($inboxList);
			$currencyId = array_key_first($client->getAllSchoolsCurrencies());

			if (!empty($aItem['agency_id'])) {

				$aItem['agency_id'] = \Ext_Thebing_Agency::query()
					->where('ext_1', $aItem['agency_id'])
					->get()
					->pluck('id')
					->first();

				if (empty($aItem['agency_id'])) {
					throw new \Exception(\L10N::t('Agentur nicht gefunden!'));
				}
			} else {
				$aItem['agency_id'] = 0;
			}

			$allLanguages = \Ext_TC_Language::getSelectOptions('en');
			if (empty($allLanguages[$aItem['language_id']])) {
				throw new \Exception(\L10N::t('Sprache nicht gefunden!'));
			}

			$nationalities = \Ext_Thebing_Nationality::getNationalities(true, 'en', 0);

//			$installedLanguages = \Ext_Thebing_Client::getLangList();;
//			if (empty($installedLanguages[$aItem['correspondence_id']])) {
//				throw new \Exception(\L10N::t('Sprache nicht gefunden!'));
//			}

			$fields = $this->getFields();

			$group = new \Ext_Thebing_Inquiry_Group();
			foreach($aItem as $field=>$value) {
				if (empty($value)) {
					$temp = array_filter($fields, fn($f) => $f['key'] == $field);
					$fieldInformation = reset($temp);
					if ($fieldInformation['mandatory']) {
						throw new \Exception(sprintf(\L10N::t('Feld "%s" hat keinen Wert, ist aber ein Pflichtfeld!'), \L10N::t($fieldInformation['field'])));
					}
				}
				if ($field == 'members') {
					$inquiries = [];
					foreach ($value as $member) {
						$inquiry = new \Ext_TS_Inquiry();

						if(\System::d('booking_auto_confirm') > 0) {
							$inquiry->confirm();
						}

						$customer = $inquiry->getCustomer();

						$journey = $inquiry->getJourney();

						$arrivalDate = $aItem['transfer']['arrival'];
						$departureDate = $aItem['transfer']['departure'];
						$departureDateTime = \DateTime::createFromFormat('Y-m-d', $departureDate);
						$arrivalDateTime = \DateTime::createFromFormat('Y-m-d', $arrivalDate);
						if (
							$departureDateTime &&
							$departureDateTime->format('Y-m-d') === $departureDate
						) {
							// Bei einem validen Wert
							$journeyDepartureTransfer = new \Ext_TS_Inquiry_Journey_Transfer();
							$journeyDepartureTransfer->transfer_type = \Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE;
							$journeyDepartureTransfer->transfer_date = $departureDateTime->format('Y-m-d');
							$journeyDepartureTransfer->booked = 1;
							$journeyDepartureTransfer->journey_id = $journey->id;
							$journey->setJoinedObjectChild('transfers', $journeyDepartureTransfer);
							$journeyDepartureTransfer->validate();
							$journeyDepartureTransfer->save();
						}

						if (
							$arrivalDateTime &&
							$arrivalDateTime->format('Y-m-d') === $arrivalDate
						) {
							// Bei einem validen Wert
							$journeyArrivalTransfer = new \Ext_TS_Inquiry_Journey_Transfer();
							$journeyArrivalTransfer->transfer_type = \Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL;
							$journeyArrivalTransfer->transfer_date = $arrivalDateTime->format('Y-m-d');
							$journeyArrivalTransfer->booked = 1;
							$journeyArrivalTransfer->journey_id = $journey->id;
							$journey->setJoinedObjectChild('transfers', $journeyArrivalTransfer);
							$journeyArrivalTransfer->validate();
							$journeyArrivalTransfer->save();
						}

						foreach ($member as $memberField => $memberValue) {
							if (empty($memberValue)) {
								$temp = array_filter($fields, fn($f) => $f['key'] == $memberField);
								$fieldInformation = reset($temp);
								if ($fieldInformation['mandatory']) {
									throw new \Exception(sprintf(\L10N::t('Feld "%s" hat keinen Wert, ist aber ein Pflichtfeld!'), \L10N::t($fieldInformation['field'])));
								}
							}

							if ($memberField == 'birthday') {
								$birthdayDate = $memberValue;
								$birthdayDateTime = \DateTime::createFromFormat('Y-m-d', $birthdayDate);
								if (
									$birthdayDateTime &&
									$birthdayDateTime->format('Y-m-d') === $birthdayDate
								) {
									// Valides Datum
									$customer->birthday = $birthdayDateTime->format('Y-m-d');
								} else {
									// Pflichtfeld, also Fehlermeldung
									throw new \Exception(\L10N::t('Falsches Datumsformat bei dem Geburtstagsfeld!'));
								}
							} elseif ($memberField == 'gender') {
								if ($memberValue == 'M') {
									$gender = 1;
								} elseif ($memberValue == 'F') {
									$gender = 2;
								} elseif ($memberValue == 'D') {
									$gender = 3;
								} else {
									throw new \Exception(\L10N::t('Geschlecht nicht gefunden!'));
								}
								$customer->gender = $gender;
							} elseif ($memberField == 'nationality') {

								if (!in_array($memberValue, $nationalities)) {
									throw new \Exception(sprintf(\L10N::t('Nationalität nicht gefunden!').' ("%s")', $memberValue));
								}
								$customer->nationality = mb_strtoupper(array_search($memberValue, $nationalities));
							} elseif (str_contains($memberField, 'field')) {
								// Flex- bzw Custom Felder
								$temp = explode('_', $memberField);
								$fieldNumber = end($temp);
								$customFieldId = $this->aFlexFields['Main'][$fieldNumber]['target'];
								$flexField = \Ext_TC_Flexibility::getInstance($customFieldId);

								// Das müsste eigentlich in der saveFlexValues() passieren oder? Ich will aber auch nichts
								// kaputt machen, falls das schon wo anders genau so wie hier abgefangen wurde.
								if ($flexField->type == \Ext_TC_Flexibility::TYPE_SELECT) {
									$options = \Ext_TC_Flexibility::getOptions($flexField->id, $schoolLanguage);
									foreach ($options as $optionId => $optionValue) {
										if ($optionValue == $memberValue) {
											$memberValue = $optionId;
										}
									}
								}

								$section = $flexField->getSection();
								if ($section->type == 'student_record_course') {
									$inquiry->setFlexValue($customFieldId, $memberValue);
								} else {
									// Group Customfields
									$group->setFlexValue($customFieldId, $memberValue);
								}
							} else {
								$customer->$memberField = $memberValue;
							}
							$customer->language = $aItem['language_id'];
							$customer->corresponding_language = $schoolLanguage;
						}

						$customer->validate();
						$customer->save();

						$inquiry->currency_id = $currencyId;
						$inquiry->inbox = $inbox['short'];
						$inquiry->payment_method = 1;
						$inquiry->agency_id = $aItem['agency_id'];
						$oCustomerNumber = new \Ext_Thebing_Customer_CustomerNumber($inquiry);
						$oCustomerNumber->saveCustomerNumber();

						$journey->school_id = $school->id;
						$journey->validate();
						$journey->save();

						$saveInquiry = true;
						// In ein Array um später alle zu speichern nach dem Gruppenspeichern für die Gruppen-ID
						$inquiries[] = $inquiry;
					}

				} elseif ($field == 'transfer') {
					$arrivalDate = $value['arrival'];
					$departureDate = $value['departure'];
					$departureDateTime = \DateTime::createFromFormat('Y-m-d', $departureDate);
					$arrivalDateTime = \DateTime::createFromFormat('Y-m-d', $arrivalDate);
					if (
						$departureDateTime &&
						$departureDateTime->format('Y-m-d') === $departureDate
					) {
						// Bei einem validen Wert
						$departureTransfer = new \Ext_Thebing_Inquiry_Group_Transfer();
						$departureTransfer->transfer_type = \Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE;
						$departureTransfer->transfer_date = $departureDateTime->format('Y-m-d');
						$departureTransfer->booked = 1;
						$saveDepartureTransfer = true;
					}

					if (
						$arrivalDateTime &&
						$arrivalDateTime->format('Y-m-d') === $arrivalDate
					) {
						// Bei einem validen Wert
						$arrivalTransfer = new \Ext_Thebing_Inquiry_Group_Transfer();
						$arrivalTransfer->transfer_type = \Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL;
						$arrivalTransfer->transfer_date = $arrivalDateTime->format('Y-m-d');
						$arrivalTransfer->booked = 1;
						$saveArrivalTransfer = true;
					}
				}  else {
					$group->$field = $value;
				}
			}

			// Erstmal so einfach
			$group->correspondence_id = $schoolLanguage;

			$firstMember = reset($aItem['members']);
			$group->nationality_id = mb_strtoupper(array_search($firstMember['nationality'], $nationalities));

			$group->inbox_id = $inbox['id'];
			$group->currency_id = $currencyId;
			$group->school_id = $school->id;

			$group->validate();
			$group->save();
			$group->saveFlexValues();

			// Jetzt erst Transfer validieren und speichern wegen der GruppenID
			if ($saveDepartureTransfer) {
				$departureTransfer->group_id = $group->id;
				$departureTransfer->validate();
				$departureTransfer->save();
			}

			if ($saveArrivalTransfer) {
				$arrivalTransfer->group_id = $group->id;
				$arrivalTransfer->validate();
				$arrivalTransfer->save();
			}

			if ($saveInquiry) {
				foreach ($inquiries as $inquiry) {
					$inquiry->group_id = $group->id;
					$inquiry->validate();
					$inquiry->save();
					$inquiry->saveFlexValues();
				}
			}

			$this->aReport['insert']++;

			return $group->id;

		} catch(\Exception $e) {

			$pointer = ($e instanceof ImportRowException && $e->hasPointer())
				? $e->getPointer()
				: new ErrorPointer(null, $iItem);

			$this->aErrors[$iItem] = [['message'=> $e->getMessage(), 'pointer' => $pointer]];

			$this->aReport['error']++;

			if(empty($this->aSettings['skip_errors'])) {
				throw new \Exception('Terminate import');
			}

		}

	}

	public function setItems($aItems, bool $bSkipFirstRow)
	{
		parent::setItems($aItems, $bSkipFirstRow);

		$groupNames = [];
		$items = [];
		// Wird bei jeder neuen Gruppe einen hochgezählt und direkt auch am Anfang einmal für die 1. Gruppe, deswegen
		// mit -1 starten. Wird gebraucht für die parent execute.
		$index = -1;

		$fields = $this->getFields();

		$memberIndex = 0;
		foreach ($this->aItems as $item) {
			$groupName = $item[1];
			if (!in_array($groupName, $groupNames)) {
				$index++;
				$items[$index]['group'] = [
					$fields[0]['key'] => $item[0],
					$fields[1]['key'] => $groupName,
					$fields[2]['key'] => $item[2],
					$fields[3]['key'] => $item[3],
					'transfer' => [
						$fields[9]['key'] => $item[9],
						$fields[10]['key'] => $item[10],
					],
				];
			}

			$items[$index]['members'][$memberIndex] = [
				$fields[4]['key'] => $item[4],
				$fields[5]['key'] => $item[5],
				$fields[6]['key'] => $item[6],
				$fields[7]['key'] => $item[7],
				$fields[8]['key'] => $item[8],
			];

			$customFieldValues = array_slice($item, 11);
			$fieldIndex = 11;
			foreach ($customFieldValues as $customFieldValue) {
				// $this->aFlexFields['Main'] hat man erst in der processItems und deswegen so, will auch nichts umstellen
				// in core
				$items[$index]['members'][$memberIndex]['field_'.$fieldIndex] = $customFieldValue;
				$fieldIndex++;
			}

			if (empty(array_filter($items[$index]['members'][$memberIndex]))) {
				// Wenn keine Gruppenmitglieder erstellt werden sollen -> unsetten für später
				unset($items[$index]['members'][$memberIndex]);
			}
			$groupNames[] = $groupName;

			$memberIndex++;
		}

		$this->aItems = $items;
	}

	protected function getCheckItemFields(array $aPreparedData) {

	}

}
