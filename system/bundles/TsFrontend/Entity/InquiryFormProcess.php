<?php

namespace TsFrontend\Entity;

use Core\Traits\UniqueKeyTrait;

/**
 * @property $id
 * @property $created
 * @property $changed
 * @property $active
 * @property $valid_until
 * @property $inquiry_id
 * @property $combination_id
 * @property $key
 * @property $multiple
 * @property $seen
 * @property $submitted
 * @property $payload
 */
class InquiryFormProcess extends \WDBasic {

	use UniqueKeyTrait;

	protected $_sTable = 'ts_inquiries_form_processes';

	protected $_aFormat = [
		'inquiry_id' => [
			'required' => true
		],
		'combination_id' => [
			'required' => true
		],
		'key' => [
			'required' => true
		]
	];

	public function save() {

		if (empty($this->key)) {
			$this->uniqueKeyLength = 64;
			$this->key = strtolower($this->getUniqueKey());
		}

		return parent::save();

	}


	public function getInquiry(): \Ext_TS_Inquiry {

		return \Ext_TS_Inquiry::getInstance($this->inquiry_id);

	}

	public function buildUrl(string $url): string {

		$uri = new \GuzzleHttp\Psr7\Uri($url);

		return \GuzzleHttp\Psr7\Uri::withQueryValue($uri, 'booking', $this->key);

	}

	public function mergePayloadIntoFormBooking(array &$booking, array $mapping): void {

		// Zusätzliche Daten (z.B. Leistungen)
		foreach ((array)json_decode($this->payload, true) as $namespace => $data) {
			if ($namespace !== 'services') {
				throw new \DomainException('Namespace not implemented: '.$namespace);
			}
			foreach ($data as $blockKey => $services) {
				$blockKey2 = array_search($blockKey, $mapping);
				if ($blockKey2 === false) {
					throw new \DomainException('Block for service type does not exist: '.$blockKey);
				}
				$booking['services'][$blockKey2] = $services;
			}
		}

	}

}
