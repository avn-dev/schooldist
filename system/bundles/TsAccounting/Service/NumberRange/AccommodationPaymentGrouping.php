<?php

namespace TsAccounting\Service\NumberRange;

class AccommodationPaymentGrouping extends \Ext_TS_NumberRange
{

	protected $_sNumberTable = 'ts_accommodations_payments_groupings';

	protected $_sNumberField = 'number';

	protected \Ext_TS_Accounting_Provider_Grouping_Accommodation $entity;

	public function bindEntity(\Ext_TS_Accounting_Provider_Grouping_Accommodation $entity): static
	{
		$this->entity = $entity;
		return $this;
	}

	public function getEntity(): ?\Ext_TS_Accounting_Provider_Grouping_Accommodation
	{
		return $this->entity;
	}

	public static function getObject(\Ext_TS_Accounting_Provider_Grouping_Accommodation $entity = null): ?static
	{
		$numberRangeId = (new \Ext_TS_Config)->getValue('ts_accommodations_payments_groupings_numbers');

		// Auf Ziffer prüfen
		if(is_numeric($numberRangeId)) {

			$numberRange = self::getInstance($numberRangeId);

			if($numberRange->exist()) {
				if ($entity) {
					$numberRange->bindEntity($entity);

					$bindPlaceholder = function ($numberRange, string $placeholder) {
						$numberRange->bindPlaceholder($placeholder, [AccommodationPaymentGrouping::class, 'replacePlaceholder']);
						$numberRange->bindPlaceholder(strtoupper($placeholder), [AccommodationPaymentGrouping::class, 'replacePlaceholder']);
					};

					// firstname und lastname von 1 - n als Platzhalter hinzufügen
					foreach (['firstname', 'lastname'] as $placeholder) {
						$bindPlaceholder($numberRange, $placeholder);
						for ($i = 1; $i <= 3; $i++) {
							$bindPlaceholder($numberRange, $placeholder.$i);
						}
					}
				}

				return $numberRange;
			}
		}

		return null;

	}

	public static function replacePlaceholder(string $placeholder, self $numberRange): string
	{
		if(null === $entity = $numberRange->getEntity()) {
			throw new \RuntimeException(sprintf('No entity object given [%s]', $numberRange::class));
		}

		$cutOff = null;

		preg_match('/[0-9]/', $placeholder, $parts);

		if ($parts[0]) {
			$cutOff = (int)$parts[0];
			$placeholder = str_replace($cutOff, '', $placeholder);
		}

		$accommodationProvider = $entity->getAccommodationProvider();

		$value = match (strtolower($placeholder)) {
			'%firstname' => $accommodationProvider->firstname,
			'%lastname' => $accommodationProvider->lastname,
		};

		if (!empty($value)) {
			if ($cutOff) {
				$value = substr($value, 0, $cutOff);
			}
			if (strtoupper($placeholder) === $placeholder) {
				$value = strtoupper($value);
			}
			return $value;
		}

		return '';
	}

}