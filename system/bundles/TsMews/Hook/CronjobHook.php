<?php

namespace TsMews\Hook;

use Illuminate\Support\Collection;
use Core\DTO\DateRange;
use TsMews\Api;
use TsMews\Handler\ExternalApp;
use TsMews\Service\Synchronization;

/**
 * Gleicht Mews-Reservierung ab um diese als Blockierungen in unserem System anzulegen (damit keine Überschneidungen kommen)
 *
 * Class CronjobHook
 * @package TsMews\Hook
 */
class CronjobHook extends AbstractMewsHook {

	private $fromCLI = false;

	public function fromCLI() {
		$this->fromCLI = true;
		return $this;
	}

	public function run() {

		if (!$this->hasApp()) {
			return;
		}

		$now = new \DateTime('now', new \DateTimeZone('UTC'));

		// In diesem Zeitraum Reservierungen suchen
		$from = (clone $now)->modify('-1 month')->modify('first day of this month');
		$until = (clone $now)->modify('+2 years')->modify('last day of this month');

		// Existierende Abwesenheiten
		$existingAbsences = $this->getExistingAbsences();

		do {
			// Mews möchte maximal 3 Monate verarbeiten
			$searchUntil = (clone $from)->modify('+3 month');

			$from->setTime(0, 0, 0);
			$searchUntil->setTime(23, 59, 59);

			$this->sync(new DateRange($from, $searchUntil), $existingAbsences);

			$from = $searchUntil;

		} while($from <= $until);

		// Blockierungen die nicht mehr benötigt werden löschen
		$existingAbsences->each(function (\Ext_Thebing_Absence $absence) {
			if($absence->isActive()) {
				$absence->delete();
			}
		});

	}

	/**
	 * Mews-Reservierungen für den Zeitraum abfragen und abgleichen
	 *
	 * @param DateRange $dateRange
	 * @param Collection $existingAbsences
	 */
	private function sync(DateRange $dateRange, Collection $existingAbsences) {

		$search = Api::default()->searchReservations($dateRange);

		$reservations = collect($search->get('Reservations'));
		$customers = collect($search->get('Customers'));

		Api::getLogger()->info('Sync', ['from' => $dateRange->from->format('Y-m-d H:i:s'), 'until' => $dateRange->until->format('Y-m-d H:i:s'), 'reservations' => $reservations->count(), 'customers' => $customers->count()]);

		foreach ($reservations as $reservation) {

			$customer = $customers->first(function($customer) use($reservation) {
				return ($customer['Id'] === $reservation['CustomerId']);
			});

			$absence = Synchronization::syncMewsReservationToFidelo($reservation, $customer, $this->fromCLI);

			if(
				!is_null($absence) &&
				$existingAbsences->has($reservation['Id'])
			) {
				// Entfernen damit die Abwesenheit im weiteren Verlauf nicht gelöscht wird
				$existingAbsences->forget($reservation['Id']);
			}
		}

	}

	/**
	 * Existierende Blockierungen die über diesen Cronjob angelegt wurden
	 *
	 * @return Collection|\Ext_Thebing_Absence[]
	 */
	private function getExistingAbsences() {

		$sql = "
            SELECT
                `k_a`.*,
                `attr`.`value` as `mews_id`
            FROM
                `kolumbus_absence` `k_a` LEFT JOIN
				`wdbasic_attributes` `attr` ON
					`attr`.`entity_id` = `k_a`.`id` AND 
					`attr`.`entity` = 'kolumbus_absence'
            WHERE
                `k_a`.`active` = 1 AND 
                `k_a`.`item` = 'accommodation' AND 
                `k_a`.`category_id` = :category_id
            GROUP BY
            	`k_a`.`id`
        ";

		$data = (array)\DB::getPreparedQueryData($sql, [
			'category_id' => ExternalApp::getBlockCategory()
		]);

		$entities = collect([]);
		foreach ($data as $entry) {

			$mewsId = $entry['mews_id'];
			unset($entry['mews_id']);

			$entity = \Ext_Thebing_Absence::getObjectFromArray($entry);

			// Mews-Id faken damit oben gelöscht wird
			if (empty($mewsId) || $entities->has($mewsId)) {
				do {
					$mewsId = 'd-'.\Util::generateRandomString(10);
				} while($entities->has($mewsId));
			}

			$entities->put($mewsId, $entity);
		}

		return $entities;
	}
}
