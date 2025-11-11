<?php

namespace TsStatistic\Generator\Tool\Columns\Tuition;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\Precision;
use TcStatistic\Exception\NoResultsException;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Bases\BookingServicePeriod;
use TsStatistic\Generator\Tool\Columns\AbstractColumn;
use TsStatistic\Generator\Tool\Groupings\Tuition\DefaultTimes;

/**
 * Ticket #15386 – GLS - Teilnehmer und Raumbedarf - Teil 2
 *
 * Anzahl der Räume, die pro Standardzeit komplett frei sind
 *
 * TODO Nur mit Gruppierung, nur pro Woche(?)
 * TODO Beschreibung:
 * Diese Spalte ignoriert alle Filter außer dem Schulfilter.
 * Diese Spalte zählt nur dann Räume als nicht zugewiesen, wenn keine der definierten Standardzeiten in der Woche zu einem Block zugewiesen wurden. Individuelle Zeiten werden nicht berücksichtigt.
 *
 * @property DefaultTimes $grouping
 */
class RoomAvailable extends AbstractColumn {

	/**
	 * @var Collection
	 */
	private $rooms;

	/**
	 * @var Collection
	 */
	private $roomAllocations;

	public function getTitle() {
		return self::t('Räume verfügbar');
	}

	public function getAvailableBases() {
		return [BookingServicePeriod::class];
	}

	public function getAvailableGroupings() {
		return [DefaultTimes::class];
	}

	public function getSelect() {
		return "0";
	}

	/**
	 * @see \Ext_Thebing_School_Tuition_Block::getAvailableRooms()
	 *
	 * @inheritdoc
	 */
	public function getResult($sql, $values) {

		$configRoomIds = (array)(new \Ext_TS_Config())->ts_statistic_tuition_rooms;
		if (empty($configRoomIds)) {
			throw new NoResultsException(sprintf(self::t('Es wurden keine Räume für die Spalte "%s" eingestellt.'), self::getTitle()));
		}

		$values['week'] = Carbon::instance(\Ext_Thebing_Util::getWeekFromCourseStartDate($values['from']));

		$labels = collect($this->grouping->getAllLabels());
		$this->rooms = $this->getRooms($values, $configRoomIds);
		$this->roomAllocations = $this->getRoomAllocations($values);

		$result = $labels->keys()->map(function ($tuitionTimeId) {
			$tuitionTime = \Ext_Thebing_Tuition_Template::getInstance($tuitionTimeId);
			return $this->getTuitionTimeResult($tuitionTime);
		});

		return $result->toArray();

	}

	private function getTuitionTimeResult(\Ext_Thebing_Tuition_Template $tuitionTime) {

		$tuitionTimeSetting = \Ext_Thebing_Management_Settings_TuitionTime::findOneByTuitionTime($tuitionTime->id);
		$period = Period::make(Carbon::parse($tuitionTime->from), Carbon::parse($tuitionTime->until), Precision::MINUTE());

		if ($tuitionTimeSetting === null) {
			// Da die Gruppierung die Labels aus dem gleichen Settings holen, sollte das nicht vorkommen
			throw new \LogicException('No setting found but tution time is configured');
		}

		$usedRooms = collect();
		$freeRooms = $this->rooms->filter(function ($roomLabel, $roomId) use ($tuitionTimeSetting, $usedRooms, $period) {

			$isAllocated = $this->roomAllocations->some(function (array $roomAllocation) use ($roomId, $tuitionTimeSetting, $period) {
				if (
					in_array($roomId, $roomAllocation['rooms']) &&
					!empty(array_intersect($roomAllocation['days'], $tuitionTimeSetting->days)) &&
					$period->overlapsWith($roomAllocation['period'])
				) {
					return true;
				}
				return false;
			});

			if ($isAllocated) {
				$usedRooms->put($roomId, $roomLabel);
				return false;
			}
			return true;

		});

//		// Macht keinen Sinn
//		if ($tuitionTime->id === 0) {
//			$freeRooms = collect();
//		}

		return [
			'result' => $freeRooms->count(),
			'label' => [vsprintf("%s: %s\n%s: %s", [
				self::t('Frei'),
				$this->formatRoomLabel($freeRooms),
				self::t('Belegt'),
				$this->formatRoomLabel($usedRooms)
			])],
			'grouping_id' => $tuitionTime->id
		];

	}

	private function getRooms(FilterValues $values, array $configRoomIds) {

		// Alle Räume der auswählten Schulen, welche konfiguriert wurden
		return collect($values->schools)
			->map(function ($id) use($values) {
				$school = \Ext_Thebing_School::getInstance($id);
				return $school->getClassRooms(true, $values->until->toDateString(), true);
			})
			->reduce(function (Collection $rooms, array $rooms2) {
				// Auf eine Ebene reduzieren und doppelte Räume rauswerfen
				return $rooms->union($rooms2);
			}, collect())
			->filter(function($label, $id) use ($configRoomIds) {
				return in_array($id, $configRoomIds);
			});

	}

	private function getRoomAllocations(FilterValues $values): Collection {

		$sql = "
			SELECT
			    `ktb`.`id` `block_id`,
				`ktt`.`from`,
				`ktt`.`until`,
			    GROUP_CONCAT(DISTINCT `ktbtr`.`room_id`) `rooms`,
			    GROUP_CONCAT(DISTINCT `ktbd`.`day`) `days`
			FROM
				`kolumbus_tuition_blocks` `ktb` INNER JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON 
					`ktbtr`.`block_id` = `ktb`.`id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktb`.`template_id` = `ktt`.`id` INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id`
			WHERE
				`ktb`.`week` = :week AND
			    `ktb`.`active` = 1
			GROUP BY
				`ktb`.`id`
		";

		return collect(\DB::getQueryRows($sql, $values->toSqlData()))->map(function (array $row) {
			$row['period'] = Period::make(Carbon::parse($row['from']), Carbon::parse($row['until']), Precision::MINUTE());
			$row['rooms'] = explode(',', $row['rooms']);
			$row['days'] = explode(',', $row['days']);
			return $row;
		});

	}

	private function formatRoomLabel(Collection $collection): string {

		if ($collection->isEmpty()) {
			return '–';
		}

		return $collection->join(', ');

	}

}