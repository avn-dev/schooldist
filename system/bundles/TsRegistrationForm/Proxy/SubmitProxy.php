<?php

namespace TsRegistrationForm\Proxy;

use Carbon\Carbon;

/**
 * @property \Ext_TS_Inquiry $oEntity
 */
class SubmitProxy extends \Core\Proxy\WDBasicAbstract {

	protected $sEntityClass = 'Ext_TS_Inquiry';

	private \Tc\Service\Language\Frontend $language;

	private array $items = [];

	public function setLanguage(\Tc\Service\Language\Frontend|string $language) {
		if (is_string($language)) {
			$language = new \Tc\Service\Language\Frontend($language);
			$language->setContext($this->language->getContext());
		}

		$this->language = $language;
	}

	public function getLanguage(): \Tc\Service\Language\Frontend {
		return $this->language;
	}

	public function getType(): string {
		return $this->oEntity->type == \Ext_TS_Inquiry::TYPE_ENQUIRY ? 'request' : 'booking';
	}

	public function getCustomFieldValue(int $id): string {
		return (string)\Ext_TC_Flexibility::getInstance($id)->getFormattedValue($this->oEntity, $this->language->getLanguage());
	}

	public function getCurrencyCode(): string {
		return $this->oEntity->getCurrency(true)->iso4217;
	}

	public function getBookingNumber(): string {
		return (string)$this->oEntity->number;
	}

	public function getAge(): int {
		return (int)$this->oEntity->getCustomer()->getAge();
	}

	public function getReferrer(): string {
		return (string)$this->oEntity->getReferrer()?->getName($this->language->getLanguage());
	}

	public function getItems(): array {
		return $this->items;
	}

	public function setItems(array $items) {
		$this->items = array_map(function (array $item) {
			return new ItemProxy($item, $this->language);
		}, $items);
	}

	public function getTotalAmount(): float {
		return (float)collect($this->items)->sum(function (ItemProxy $item) {
			return $item->getAmount();
		});
	}

	public function getDaysUntilStart(): int {
		if (!\Core\Helper\DateTime::isDate($this->oEntity->service_from, 'Y-m-d')) {
			return 0;
		}
		return Carbon::parse($this->oEntity->service_from)->diffInDays(Carbon::now());
	}

	public function getSchoolName(): string {
		return $this->oEntity->getSchool()->ext_1;
	}

}