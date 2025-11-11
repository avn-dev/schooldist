<?php

namespace TsStatistic\Generator\Tool\Columns;

use TsStatistic\Generator\Tool\Bases;
use TsStatistic\Generator\Tool\Groupings;
use TsStatistic\Traits\ColumnConfigTrait;

/**
 * Diese Spalte zählt die (Traveller-)Kontakte, nicht die Anzahl der Buchungen!
 *
 * @TODO Hinweis, dass bei Leistungszeitraum jeder Kontakt einmal gezählt wird, Summenbildung nicht sinnvoll.
 */
class StudentCount extends AbstractColumn {

	use ColumnConfigTrait;

	protected $type = 'absolute';

	protected $basedOn = 'booking';

	protected $inquiryFilter = 'all';

	public function __construct($grouping = null, $headGrouping = null, $configuration = null) {

		parent::__construct($grouping, $headGrouping, $configuration);

		$this->parseConfig($configuration);

	}

	public function getTitle() {

		$title = self::t('Anzahl der Schüler');
		$title .= $this->formatConfig();

		return $title;

	}

	public function getAvailableBases() {
		return [
			Bases\Booking::class,
			Bases\BookingServicePeriod::class
		];
	}

	public function getAvailableGroupings() {
		return [
			Groupings\Course\Category::class,
			Groupings\Course\Course::class, // TODO Für Leistungsraum könnte es sein, dass eine andere Wochenbasis (Einstellung) her muss
		];
	}

	/**
	 * @inheritdoc
	 *
	 * DISTINCT ist wichtig, damit ein Kunde mit mehreren Kursen
	 * bei Gruppierungen nicht auf einmal mehrfach angezeigt wird.
	 */
	public function getSelect() {

		if (
			$this->base instanceof Bases\BookingServicePeriod &&
			$this->type === 'absolute_once'
		) {
			$select = " IF(`ts_i`.`service_from` BETWEEN :from AND :until, COUNT(DISTINCT `tc_c`.`id`), 0) `result` ";
		} else {
			$select = " COUNT(DISTINCT `tc_c`.`id`) `result` ";
		}

		$select .= " , GROUP_CONCAT(DISTINCT `tc_cn`.`number`) `label` ";

		return $select;

	}

	public function getColumnColor() {

		if ($this->basedOn === 'course') {
			return 'service';
		}

		return 'booking';

	}

	public function getJoinParts() {

		$parts = ['contact_number'];

		if ($this->basedOn === 'course') {
			$parts[] = 'course';
		}

		return $parts;

	}

	public function getJoinPartsAdditions() {

		$additions = [];

		// Leistungszeitraum vom Kurs muss in den Zeitraum fallen
		if ($this->basedOn === 'course') {
			$additions['JOIN_JOURNEY_COURSES'] = " AND
				`ts_ijc`.`from` <= :until AND
				`ts_ijc`.`until` >= :from
			";
		}

		return $additions;

	}

	public function getResult($sql, $values) {

		$result = parent::getResult($sql, $values);

		if ($this->type === 'percentage') {
			$result = $this->buildPercentSum($result);
		}

		return $result;

	}

	public function isSummable() {

		if (
			// Macht bei Leistungszeitraum keinen Sinn, da Schüler über mehrere Zeiträume immer wieder auftaucht
			($this->base instanceof Bases\BookingServicePeriod && $this->type !== 'absolute_once') ||
			$this->type === 'percentage'
		) {
			return false;
		}

		return true;

	}

	public function getFormat() {

		if ($this->type === 'percentage') {
			return 'number_percent';
		}

		return 'number_int';

	}

	public function getConfigurationOptions() {

		return [
			[
				'key' => 'type',
				'label' => self::t('Typ'),
				'type' => 'select',
				'options' => [
					'absolute' => self::t('absolut'),
					'absolute_once' => self::t('absolut (einmalig gezählt)'),
					'percentage' => self::t('prozentual (Gruppierung)')
				]
			],
			[
				'key' => 'based_on',
				'label' => self::t('Basierend auf'),
				'type' => 'select',
				'options' => [
					'booking' => self::t('Buchung'),
					'course' => self::t('Kurs')
				]
			],
			[
				'key' => 'inquiry_filter',
				'label' => self::t('Art der Buchung'),
				'type' => 'select',
				'options' => [
					'all' => self::t('Alle Buchungen'),
					'individual' => self::t('Buchungen ohne Gruppe'),
					'group' => self::t('Gruppenbuchungen')
				]
			]
		];

	}

	public function getSqlWherePart() {

		$where = parent::getSqlWherePart();

		if ($this->inquiryFilter === 'individual') {
			$where .= " AND `ts_i`.`group_id` = 0 ";
		} elseif ($this->inquiryFilter === 'group') {
			$where .= " AND `ts_i`.`group_id` != 0 ";
		}

		return $where;

	}


}
