<?php

namespace TsStatistic\Generator\Tool\Columns;

use Core\DTO\DateRange;
use TsStatistic\Dto\FilterValues;
use TsStatistic\Generator\Tool\Bases;
use TsStatistic\Generator\Tool\Groupings;
use TsStatistic\Service\DocumentItemAmount;

/**
 * @TODO Spalte so abstrahieren, dass man die Logik für die Items generell hat (siehe Ableitungen)
 *
 * Generelle Spalte für Umsätze
 */
class Revenue extends AbstractColumn {

	/**
	 * @var bool
	 */
	protected $bOverwriteGroupingColumn = true;

	/**
	 * @var string
	 */
	protected $sAmountType;

	/**
	 * @var string
	 */
	protected $sServiceType;

	/**
	 * @TODO Auf Config-Trait umstellen (siehe StudentCount)
	 *
	 * @inheritdoc
	 */
	public function __construct($grouping = null, $headGrouping = null, $configuration = null) {
		parent::__construct($grouping, $headGrouping, $configuration);

		$aConfig = explode('_', $this->sConfiguration, 2);
		$this->sAmountType = $aConfig[0];

		if(!empty($aConfig[1])) {
			$this->sServiceType = $aConfig[1];
		} else {
			// Typ setzen, damit das weniger verwirrend wird
			if($this->hasGrouping()) {
				$this->sServiceType = $this->getServiceTypeByGrouping();
				if($this->sServiceType !== null) {
					$this->sConfiguration = $this->sAmountType.'_'.$this->sServiceType; // Titel
				}

			}
		}
	}

	public function getTitle() {
		return $this->getConfigurationOptions()[$this->sConfiguration];
	}

	public function getAvailableBases() {
		return [
			Bases\Booking::class,
			Bases\BookingServicePeriod::class
		];
	}

	public function getAvailableGroupings() {
		return [
			Groupings\InquiryChannel::class,
			//Groupings\Nationality::class,
			Groupings\Revenue\AccommodationCategory::class,
			Groupings\Revenue\AccommodationFees::class,
			Groupings\Revenue\Course::class,
			Groupings\Revenue\CourseFees::class,
			Groupings\Revenue\GeneralFees::class
		];
	}

	public function getSelect() {

		// TODO Umstellen auf \TsStatistic\Service\DocumentItemAmount::getFieldsSqlSelect()
		$sSelect = "
			`kid`.`document_number` `label`,
			`kid`.`type` `document_type`,
			`kidv`.`tax` `item_tax_type`,
			`kidvi`.`id` `item_id`,
			`kidvi`.`type` `item_type`,
			`kidvi`.`index_from` `item_from`,
			`kidvi`.`index_until` `item_until`,
			`kidvi`.`amount` `item_amount`,
			`kidvi`.`amount_net` `item_amount_net`,
			`kidvi`.`amount_discount` `item_amount_discount`,
			`kidvi`.`amount_provision` `item_amount_commission`,
			`kidvi`.`tax` `item_tax`,
			`kidvi`.`index_special_amount_gross` `item_index_special_amount_gross`,
			`kidvi`.`index_special_amount_net` `item_index_special_amount_net`,
			`kidvi`.`index_special_amount_gross_vat` `item_index_special_amount_gross_vat`,
			`kidvi`.`index_special_amount_net_vat` `item_index_special_amount_net_vat`,
			`kidvi`.`additional_info` `item_additional_info`,
			`cdb2`.`course_startday`
		";

		// Join ist nur bei entsprechender Gruppierung vorhanden
		// TODO Ist das korrekt so? Ohne den Join dürften doch Gebühren pro Woche immer nur auf den ersten Tag gebucht werden und mit Join dann gesplittet?
		if($this->grouping instanceof Groupings\Revenue\GeneralFees) {
			$sSelect .= ",
				`kc_items`.`calculate` `item_costs_calculation`,
				`kc_items`.`timepoint` `item_costs_booking_timepoint`
			";
		} else {
			$sSelect .= ",
				NULL `item_costs_calculation`,
				NULL `item_costs_booking_timepoint`
			";
		}

		return $sSelect;

	}

	public function getJoinParts() {

		return ['document'];

	}

	public function getJoinPartsAdditions() {

		$additions = [];
		$additions['JOIN_ITEMS'] = $this->getServiceTypeJoin();

		// Hat auf die Berechnung eigentlich keine Auswirkung und dient nur der Performance bzw. der Anzeige der Gruppierungen (bei z.B. sehr langen Buchungen)
		if ($this->base instanceof Bases\BookingServicePeriod) {
			// Da der Zeitraum bei Kursen durch DocumentItemAmount korrigiert wird, pauschal jeweils eine Woche ergänzen, damit Zeitraum immer voll enthalten ist
			$fromAddition = $this->sServiceType === 'course' ? '- INTERVAL 1 WEEK' : '';
			$untilAddition = $this->sServiceType === 'course' ? '+ INTERVAL 1 WEEK' : '';

			$additions['JOIN_ITEMS'] .= "
				AND `kidvi`.`index_from` <= :until $untilAddition
				AND `kidvi`.`index_until` >= :from $fromAddition
			";
		}

		return $additions;

	}

	private function getServiceTypeJoin() {

		switch($this->sServiceType) {
			case 'course':
				return " AND `kidvi`.`type` = 'course' ";
			case 'coursefees':
				return " AND `kidvi`.`type` = 'additional_course' ";
			case 'accommodation':
				return " AND `kidvi`.`type` IN('accommodation', 'extra_nights', 'extra_weeks') ";
			case 'accommodationfees':
				return " AND `kidvi`.`type` = 'additional_accommodation' ";
			case 'transfer':
				return " AND `kidvi`.`type` = 'transfer' ";
			case 'insurance':
				return " AND `kidvi`.`type` = 'insurance' ";
			case 'generalfees':
				return " AND `kidvi`.`type` = 'additional_general' ";
			case 'extraposition':
				return " AND `kidvi`.`type` = 'extraPosition' ";
		}

		return '';

	}

	public function getGroupBy() {
		return ['`kidvi`.`id`'];
	}

	final protected function createDocumentItemAmountHelper(FilterValues $values): DocumentItemAmount {

		$amountHelper = new DocumentItemAmount();
		$amountHelper->sAmountType = $this->sAmountType;

		if ($this->base instanceof Bases\BookingServicePeriod) {
			$amountHelper->bSplitByServicePeriod = true;
			$amountHelper->oServicePeriodSplitDateRange = new DateRange($values['from'], $values['until']);
		}

		return $amountHelper;

	}

	public function prepareResult(array $result, FilterValues $values) {

		$amountHelper = $this->createDocumentItemAmountHelper($values);

		foreach($result as &$item) {
			$item['result'] = $amountHelper->calculate($item);
		}

		// Da die Items nicht (mehr) im Query summiert werden, muss das manuell passieren
		return $this->buildSum($result);

	}

	public function isSummable() {
		return true;
	}

	public function getFormat() {
		return 'number_amount';
	}

	public function getColumnColor() {
		return 'revenue';
	}

	public function getConfigurationOptions() {
		return [
			#'gross' => self::t('Umsatz - gesamt (brutto, exkl. Steuern)'),
			'net' => self::t('Umsatz - gesamt (netto, exkl. Steuern)'),
			'net_course' => self::t('Umsatz - Kurs (netto, exkl. Steuern)'),
			'net_accommodation' => self::t('Umsatz - Unterkunft (netto, exkl. Steuern)'),
			'net_transfer' => self::t('Umsatz - Transfer (netto, exkl. Steuern)'),
			'net_insurance' => self::t('Umsatz - Versicherung (netto, exkl. Steuern)'),
			'net_generalfees' => self::t('Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)'),
			'net_coursefees' => self::t('Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)'),
			'net_accommodationfees' => self::t('Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)'),
			'net_extraposition' => self::t('Umsatz - manuelle Positionen (netto, exkl. Steuern)'),
		];
	}

	/**
	 * @return null|string
	 */
	private function getServiceTypeByGrouping() {

		switch(get_class($this->grouping)) {
			case Groupings\Revenue\Course::class:
				return 'course';
			case Groupings\Revenue\CourseFees::class:
				return 'coursefees';
			case Groupings\Revenue\AccommodationCategory::class:
				return 'accommodation';
			case Groupings\Revenue\AccommodationFees::class:
				return 'accommodationfees';
			case Groupings\Revenue\GeneralFees::class:
				return 'generalfees';
		}

		return null;

	}

}
