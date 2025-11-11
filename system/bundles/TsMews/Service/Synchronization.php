<?php

namespace TsMews\Service;

use TsMews\Api;
use TsMews\Api\Operations\ConfirmReservation;
use TsMews\Api\Operations\ProcessReservation;
use TsMews\Api\Operations\StartReservation;
use TsMews\Entity\Absence;
use TsMews\Entity\Allocation;
use TsMews\Entity\Customer;
use TsMews\Handler\ExternalApp;
use Illuminate\Support\Collection;

class Synchronization {

	private static $providerCheck = [];

	private static $absences = [];

	/**
	 * Gleicht Mews-Reservierung ab um diese als Blockierungen in unserem System anzulegen (damit keine Überschneidungen kommen)
	 *
	 * @param array $reservation
	 * @param array $customer
	 * @param bool $forceInternCheck
	 * @return \Ext_Thebing_Absence|null
	 */
	public static function syncMewsReservationToFidelo(array $reservation, $customer = null, $forceInternCheck = false) {

		// Reservierungen dir über unser Matching angelegt wurden brauchen nicht beachtet zu werden
		if (self::isInternReservation($reservation)) {
			//self::checkMewsReservationInFidelo($reservation, $forceInternCheck);
			return null;
		}

		// Schon eingetragene Abwesenheit zu der Mews-Id
		$absence = self::searchAbsence($reservation['Id']);

		$fideloRoom = self::searchRoom($reservation['AssignedResourceId']);

		// Blockierung eintragen
		if ($fideloRoom) {

			$needed = true;

			if ($reservation['State'] === Api::STATE_CANCELED) {
				$needed = false;
			} else {
				$provider = $fideloRoom->getProvider();
				if (!$provider || !self::checkProviderSync($provider)) {
					$needed = false;
				}
			}

			if ($needed) {

				// Mews arbeitet mit UTC
				$from = \DateTime::createFromFormat(DATE_ISO8601, $reservation['StartUtc'], new \DateTimeZone('UTC'));
				$until = \DateTime::createFromFormat(DATE_ISO8601, $reservation['EndUtc'], new \DateTimeZone('UTC'));

				// Lokale Zeitzone setzen
				$now = new \DateTime();
				$from->setTimezone($now->getTimezone());
				$until->setTimezone($now->getTimezone());

				// Tage für Abwesenheit berechnen

				$cloneFrom = (clone $from)->setTime(12,0,0);
				$cloneUntil = (clone $until)->setTime(12,0,0);

				$days = $cloneUntil->diff($cloneFrom)->days;
				$days++;

				if ($absence === null) {
					$absence = new \Ext_Thebing_Absence();
					$absence->active = 1;
					$absence->item = 'accommodation';
					$absence->category_id = ExternalApp::getBlockCategory();
					$absence->setMeta('mews_id', $reservation['Id']);
				}

				$absence->item_id = $fideloRoom->getId();
				$absence->days = $days;
				$absence->from = $from->format('Y-m-d');
				$absence->until = $until->format('Y-m-d');
				$absence->comment = "";

				if ($customer) {
					$absence->comment =
						#"ID: ".$reservation['Id']."<br/>".
						"Name: ".$customer['LastName'].', '.$customer['FirstName']."<br/>".
						"Status: ".$reservation['State']."<br/>".
						"Start: ".$from->format('d.m.Y H:i').'Uhr'."<br/>".
						"Ende: ".$until->format('d.m.Y H:i').'Uhr'
					;
				}

				$absence->save();

				self::$absences[$reservation['Id']] = $absence;

				return $absence;
			}
		}

		if ($absence) {
			// Abwesenheit existiert und hat eine Mews-Id, wird aber nicht mehr benötigt
			$absence->delete();
		}

		return null;
	}

	public static function syncCustomerToMews(\Ext_TS_Inquiry_Contact_Traveller $customer): bool {

		$mewsId = $customer->getMeta('mews_id');

		// Wenn der Kunde keine Mews-ID hat muss er auch nicht aktualisiert werden
		if ($mewsId === null) {
			return false;
		}

		$operation = new Api\Operations\UpdateCustomer($customer);
		Api::default()->request($operation);

		return true;
	}

	public static function syncAllocationToMews(\Ext_Thebing_Accommodation_Allocation $allocation): bool {

		if(
			$allocation->isActive() &&
			$allocation->hasRoom() &&
			$allocation->status == 0 &&
			$allocation->active_storno == 1
		) {
			$provider = $allocation->getAccommodationProvider();

			if ($provider && self::checkProviderSync($provider)) {

				$mewsId = $allocation->getMeta('mews_id');

				if ($mewsId !== null) {
					Api::default()->updateReservation($allocation);
				} else {
					if ($allocation->isReservation()) {
						Api::default()->addReservation($allocation);
					} else {
						Api::default()->addAllocation($allocation);
					}
				}

				return true;
			}
		}

		return self::deleteAllocationInMews($allocation);
	}

	public static function deleteAllocationInMews(\Ext_Thebing_Accommodation_Allocation $allocation): bool {

		$mewsId = $allocation->getMeta('mews_id');

		// Wenn die Zuweisung keine Mews-ID hat muss sie auch nicht aktualisiert werden
		if ($mewsId === null) {
			return false;
		}

		Api::default()->deleteReservation($allocation);

		return true;
	}

	public static function confirmAllocationInMews(\Ext_Thebing_Accommodation_Allocation $allocation): bool {

		$mewsId = $allocation->getMeta('mews_id');

		// Wenn die Zuweisung keine Mews-ID hat muss auch nichts bestätigt werden
		if ($mewsId === null) {
			return false;
		}

		$operation = new ConfirmReservation($allocation);
		Api::default()->request($operation);

		return true;
	}

	public static function confirmInquiryArrivalInMews(\Ext_TS_Inquiry $inquiry): bool {

		$allocations = $inquiry->getAllocations();

		$synced = false;
		foreach ($allocations as $allocation) {

			$mewsId = $allocation->getMeta('mews_id');

			if ($mewsId !== null) {
				$operation = new StartReservation($allocation);
				Api::default()->request($operation);

				$synced = true;
			}
		}

		return $synced;
	}

	public static function confirmInquiryDepartureInMews(\Ext_TS_Inquiry $inquiry): bool {

		$allocations = $inquiry->getAllocations();

		$synced = false;
		foreach($allocations as $allocation) {

			$mewsId = $allocation->getMeta('mews_id');

			if ($mewsId !== null) {
				$operation = new ProcessReservation($allocation);
				Api::default()->request($operation);

				$synced = true;
			}
		}

		return $synced;
	}

	/**
	 * Prüft, ob eine Mews-Reservierung über unser Matching angelegt wurde
	 *
	 * @param array $reservation
	 * @return bool
	 */
	public static function isInternReservation(array $reservation): bool {
		return !is_null(self::searchAllocation($reservation['Id']));
	}

	/**
	 * Prüfen ob die Mews-Reservierung mit der in Fidelo übereinstimmt (keine Abwesenheit)
	 *
	 * @param array $reservation
	 * @param bool $force
	 */
	public static function checkMewsReservationInFidelo(array $reservation, bool $force = false): void {

		// Allocation zu der Mews-Reservierung suchen
		$allocation = self::searchAllocation($reservation['Id']);

		if (!is_null($allocation) && $allocation->isActive()) {

			// nur alle 24h prüfen da sonst zu viele Logs geschrieben werden
			$checked = \WDCache::get('mews_allocation_'.(int)$allocation->getId());

			if (is_null($checked) || $force) {
				// Mews-IDs
				$mewsRoomId = self::searchMewsRoomId($allocation->room_id);

				// Differenz zwischen Fidelo und Mews
				if($mewsRoomId !== $reservation['AssignedResourceId']) {

					$mewsRoom = self::searchRoom($reservation['AssignedResourceId']);
					$inquiry = $allocation->getInquiry();

					Api::getLogger()->error('Invalid mews room id', [
						'customer' => $inquiry->getCustomer()->getName().' ('.$inquiry->getNumber().')',
						'allocation' => '('.$allocation->getId().') '.substr($allocation->from, 0, 10).' - '.substr($allocation->until, 0, 10),
						'fidelo_room' => $allocation->getRoom()->getName(),
						'mews_room' => ($mewsRoom) ? $mewsRoom->getName() : 'unknown'
					]);
				}
			}

			\WDCache::set('mews_allocation_'.(int)$allocation->getId(), 60*60*24, 'checked');
		}

	}

	/**
	 * Liefert die Allocation zu einer Mews-Reservierung
	 *
	 * @param string $mewsReservationId
	 * @return \Ext_Thebing_Accommodation_Allocation|null
	 */
	public static function searchAllocation(string $mewsReservationId): ?\Ext_Thebing_Accommodation_Allocation {

		$allocationId = self::searchAttributeEntityId(
			'mews_id',
			(new \Ext_Thebing_Accommodation_Allocation())->getTableName(),
			$mewsReservationId
		);

		if (!is_null($allocationId)) {
			return \Ext_Thebing_Accommodation_Allocation::getInstance((int)$allocationId);
		}

		return null;
	}

	/**
	 * Liefert die Allocation zu einer Mews-Reservierung
	 *
	 * @param string $mewsReservationId
	 * @return \Ext_Thebing_Absence|null
	 */
	public static function searchAbsence(string $mewsReservationId): ?\Ext_Thebing_Absence {

		if (isset(self::$absences[$mewsReservationId])) {
			return self::$absences[$mewsReservationId];
		}

		$absenceId = self::searchAttributeEntityId(
			'mews_id',
			(new \Ext_Thebing_Absence())->getTableName(),
			$mewsReservationId
		);

		if (!is_null($absenceId)) {
			$absence = \Ext_Thebing_Absence::getInstance((int)$absenceId);
			self::$absences[$mewsReservationId] = $absence;

			return $absence;
		}

		return null;
	}

	/**
	 * Sucht anhand einer Mews-Room-Id das richtige Zimmer raus
	 *
	 * @param $mewsRoomId
	 * @return \Ext_Thebing_Accommodation_Room|null
	 * @throws \Exception
	 */
	public static function searchRoom($mewsRoomId): ?\Ext_Thebing_Accommodation_Room {

		$roomIds = ExternalApp::getMewsRoomIds()->flip();

		if ($roomIds->has($mewsRoomId)) {
			return \Ext_Thebing_Accommodation_Room::getInstance($roomIds->get($mewsRoomId));
		}

		return null;
	}

	/**
	 * Sucht anhand einer Fidelo-Room-Id das richtige Zimmer raus
	 *
	 * @param $fideloRoomId
	 */
	public static function searchMewsRoomId($fideloRoomId)  {
		return ExternalApp::getMewsRoomIds()->get($fideloRoomId);
	}

	/**
	 * @param $attribute
	 * @param $class
	 * @param $mewsId
	 * @return int|null
	 */
	private static function searchAttributeEntityId($attribute, $table, $mewsId) {

		$sql = "
            SELECT 
                `entity_id`
            FROM
                `wdbasic_attributes`
            WHERE
                `key` = :attr_name AND    
                `entity` = :entity_table AND 
                `value` = :mews_id
			LIMIT 1
        ";

		return \DB::getQueryOne($sql, [
			'attr_name' => $attribute,
			'mews_id' => $mewsId,
			'entity_table' => $table
		]);
	}

	public static function checkProviderSync(\Ext_Thebing_Accommodation $provider): bool {

		if (!isset(self::$providerCheck[$provider->getId()])) {
			$categoryId = $provider->default_category_id;

			$categorySync = (int)\System::d(ExternalApp::CONFIG_CATEGORY.$categoryId, 0);
			$providerSync = (int)\System::d(ExternalApp::CONFIG_PROVIDER.$provider->getId(), 0);

			if(
				$categorySync === 1 &&
				$providerSync === 1
			) {
				self::$providerCheck[$provider->getId()] = true;
			} else {
				self::$providerCheck[$provider->getId()] = false;
			}
		}

		return self::$providerCheck[$provider->getId()];
	}

	public static function getAllocationStartDate(\Ext_Thebing_Accommodation_Allocation $allocation) {
		$category = $allocation->getAccommodationCategory();
		// Die Zuweisungen werden immer mit 00:00:00 gespeichert. Uhrzeiten werden hier von der Kategorie genommen
		return \DateTime::createFromFormat('Y-m-d H:i:s', str_replace('00:00:00', $category->arrival_time, $allocation->from));
	}

	public static function getAllocationEndDate(\Ext_Thebing_Accommodation_Allocation $allocation) {
		$category = $allocation->getAccommodationCategory();
		// Die Zuweisungen werden immer mit 00:00:00 gespeichert. Uhrzeiten werden hier von der Kategorie genommen
		return \DateTime::createFromFormat('Y-m-d H:i:s', str_replace('00:00:00', $category->departure_time, $allocation->until));
	}

}
