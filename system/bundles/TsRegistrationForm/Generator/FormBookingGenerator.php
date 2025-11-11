<?php

namespace TsRegistrationForm\Generator;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TsFrontend\Entity\BookingTemplate;
use TsFrontend\Entity\InquiryFormProcess;

class FormBookingGenerator
{
	private CombinationGenerator $combination;

	private InquiryFormProcess|BookingTemplate|null $process = null;

	public function __construct(CombinationGenerator $combination)
	{
		$this->combination = $combination;
	}

	/**
	 * Basis-Struktur für Buchung (im Cache), damit Vuex State-Management mit verschachtelten Objekten korrekt funktioniert
	 */
	public function createBookingStructure(array $fields)
	{
		$services = collect($fields['services'])->keys()->mapWithKeys(function ($key) {
			return [$key => []];
		});

		$fieldValues = collect($fields['fields'])->map(function ($field) {
			return $field['value'];
		});

		$selectionValues = collect($fields['selections'])->map(function () {
			return null;
		});

		if ($fieldValues->has('school')) {
			// Eigentlich sollte die WDBasic aus ID immer int machen, aber manchmal ist es dann einfach ein String
			// Das Feld benötigt aber einen Integer, sonst wird der Wert rausgeschmissen, da exakt verglichen wird
			$fieldValues['school'] = (int)$this->combination->getSchool()->id;
		}

		return [
			'services' => $services,
			'fields' => $fieldValues,
			'selections' => $selectionValues
		];
	}

	public function mergeBooking(Collection $data, array &$settings)
	{
		$booking = $data['booking']->toArray();

		if (Arr::has($booking, 'fields.school')) {
			$booking['fields']['school'] = $this->combination->getSchool()->id;
		}

		// Buchung wurde durch Request-Parameter (initCombination) gesetzt
		$inquiry = $this->combination->getInquiry();
		if ($inquiry === null) {
			return $booking;
		}

		$process = $this->combination->getBookingGenerator()->getProcess();

		// Konkrete Buchung (Prozess)
		if ($process instanceof InquiryFormProcess) {
			$process->seen = Carbon::now()->toDateTimeString();
			$process->save();

			// Zusätzliche Daten (z.B. Leistungen)
			$process->mergePayloadIntoFormBooking($booking, $settings['blocks']);
		}

		// Buchungsvorlage
		if (
			$process instanceof BookingTemplate &&
			$this->combination->getForm()->isCreatingBooking()
		) {
			// TODO Muss man mal generell umstellen, da das eine mit JSON arbeitet und das andere mit DB-Feldern, die ein Objekt (Kurs) erzeugen
			foreach ($inquiry->getCourses() as $journeyCourse) {
				$course = collect($data['courses'])->firstWhere('key', $journeyCourse->course_id);
				if ($course) {
					$blockKey = Arr::first($course['blocks']); // Erstbester Block, wo der Kurs reinpasst
					$booking['services'][$blockKey][] = $journeyCourse->getRegistrationFormData();
				}
			}
		}

		$this->setFields($booking, $data);

		// TODO Verbessern wenn mehr Use-Cases bekannt sind
		$transferBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);
		if (
			$transferBlock !== null &&
			$process instanceof InquiryFormProcess
		) {
			if (!empty($inquiry->getAccommodations())) {
				$settings['transfer_force_accommodation_option'] = true;
			}

			$transfers = $inquiry->getTransfers('', true);
			foreach ($transfers as $transfer) {
				if ((int)$transfer->transfer_type !== \Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL) {
					$booking['services'][$transferBlock->getServiceBlockKey()][] = $transfer->getRegistrationFormData();
				}
			}
		}

		if ($process) {
			$booking['fields']['booking'] = $process->key;
		}

		return $booking;
	}

	private function setFields(array &$booking, Collection $data)
	{
		$fields = collect($data->get('fields'))->get('fields');

		// Alle Felder
		foreach ($fields as $field) {
			$value = null;
			if (empty($field['internal'])) {
				[$alias, $column] = $field['mapping'];
				$object = $this->combination->getInquiry()->getObjectByAlias($alias, $column);
				$value = !empty($object->$column) ? $object->$column : null;
			}

			// Sonderfall Flex-Mehrsprachigkeit: Array mit Sprachen
			if (
				!empty($field['flex_i18n']) &&
				is_array($value)
			) {
				$value = $value[$this->combination->getLanguage()->getLanguage()] ?? null;
			}

			// Werte müssen für JS identische Typen haben
			if (
				$value !== null && (
					$field['type'] === \Ext_Thebing_Form_Page_Block::TYPE_SELECT ||
					$field['type'] === \Ext_Thebing_Form_Page_Block::TYPE_MULTISELECT
				) && in_array('integer', Arr::get($field, 'validation', []))
			) {
				$value = (int)$value;
			} elseif($field['type'] === \Ext_Thebing_Form_Page_Block::TYPE_CHECKBOX) {
				$value = (bool)$value;
			} elseif(
				$value !== null &&
				$field['type'] === \Ext_Thebing_Form_Page_Block::TYPE_DATE
			) {
				$date = \Ext_Thebing_Util::convertDateStringToDateOrNull($value);
				$value = $date !== null ? 'date:'.$date->toDateString() : null;
			}

			$booking['fields'][$field['key']] = $value;
		}
	}

	/**
	 * Fehlende Werte müssen weiterhin vorhanden sein, da diese ansonsten im Formular gelöscht werden
	 *
	 * @TODO Felder (Selects) sind bisher nicht implementiert.
	 */
	public function mergeMissingValues(Collection $data)
	{
		/** @var Collection $transferLocations */
		$transferLocations = $data['transfer_locations'];

		$transferBlock = $this->combination->getForm()->getFixedBlock(\Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);
		if ($transferBlock === null) {
			return;
		}

		// Transfers: Transferorte
		$transfers = $this->combination->getInquiry()->getTransfers('', true);
		foreach ($transfers as $transferObject) {
			if ((int)$transferObject->transfer_type !== \Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL) {
				$transfer = $transferObject->getRegistrationFormData();

				// Erster Fall von: Was passiert wenn der Wert der Buchung nicht im Formular verfügbar ist? Antwort: Formular schmeißt den Wert raus.
				foreach (['origin', 'destination'] as $type) {
					$key = $transfer[$type] ?? null;
					$location = $transferLocations->firstWhere('key', $key);
					if (
						!empty($key) &&
						$location === null
					) {
						$formType = Str::startsWith($key, 'location') ? 'origin' : 'destination';
						$targetLocation = $transfer[$type === 'origin' ? 'destination' : 'origin'];
						[$locationType, $locationId] = explode('_', $key);
						$transferLocations->push([
							'key' => $key,
							'label' => \Ext_TS_Transfer_Location::getLabel($locationType, $locationId, $this->combination->getLanguage()->getLanguage()),
							'type' => $formType,
							'locations' => [$targetLocation]
						]);

						// Orte miteinander verknüfen
//						$transferLocations->transform(function ($transferLocation) use ($key, $targetLocation) {
//							if (
//								$transferLocation['key'] === $targetLocation &&
//								!in_array($key, $transferLocation['locations'])
//							) {
//								$transferLocation['locations'][] = $key;
//							}
//							return $transferLocation;
//						});
					}
				}
			}
		}
	}

	public function getProcess(): InquiryFormProcess|BookingTemplate|null
	{
		return $this->process;
	}

	public function setProcess(InquiryFormProcess|BookingTemplate $process): void
	{
		$this->process = $process;
	}
}