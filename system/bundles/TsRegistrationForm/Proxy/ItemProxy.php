<?php

namespace TsRegistrationForm\Proxy;

class ItemProxy {

	private array $item;

	private \Tc\Service\Language\Frontend $language;

	public function __construct(array $item, \Tc\Service\Language\Frontend $language) {
		$this->item = $item;
		$this->language = $language;
	}

	public function getAmount(): float {
		return (float)$this->item['amount_with_tax'];
	}

	public function getType(): string {
		return $this->item['type'];
	}

	public function getDuration(): int {
		return match ($this->getType()) {
			'course' => (int)$this->item['additional_info']['course_weeks'],
			'accommodation' => (int)$this->item['additional_info']['accommodation_weeks'],
			default => 0,
		};
	}

	public function getDescription(): string {
		return $this->item['description'];
	}

	public function getServiceName(): string {
		$item = \Ext_Thebing_Inquiry_Document_Version_Item::createFromArray($this->item);
		$service = $item->getService();
		return (string)$service?->getName($this->language->getLanguage());
	}

	public function getServiceCategory(): string {
		$item = \Ext_Thebing_Inquiry_Document_Version_Item::createFromArray($this->item);
		return (string)$item->getTypeName($this->language);
	}

}