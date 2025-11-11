<?php

namespace TsStudentApp\Pages;

use Illuminate\Support\Collection;
use TsStudentApp\AppInterface;

class Accommodation extends AbstractPage {

	private $appInterface;

	private $inquiry;

	public function __construct(AppInterface $appInterface, \Ext_TS_Inquiry $inquiry) {
		$this->appInterface = $appInterface;
		$this->inquiry = $inquiry;
	}

	public function init(): array {

		$inquiryAllocations = \Ext_Thebing_Allocation::getAllocationByInquiryId($this->inquiry->id, 0, true, false);

		$grouped = collect($inquiryAllocations)
			->mapToGroups(fn ($allocation) => [$allocation['family_id'] => $allocation]);

		$providersData = [];
		foreach ($grouped as $providerId => $allocations) {
			$provider = \Ext_Thebing_Accommodation::getInstance($providerId);
			$providersData[$provider->getId()] = $this->buildProviderArray($provider, $allocations);
		}

		return [
			'providers' => array_values($providersData)
		];
	}

	private function buildProviderArray(\Ext_Thebing_Accommodation $provider, Collection $allocations) {

		$shortDescription = sprintf('%s %s', $provider->ext_64, $provider->ext_65); // Zip + City
		if(!empty($provider->ext_66)) $shortDescription .= ', '.$provider->ext_66; // Country

		$providerData = [];
		$providerData['icon'] = 'home-outline';
		$providerData['name'] = $provider->ext_33;
		$providerData['phone'] = $provider->ext_67;
		$providerData['phone_mobile'] = $provider->ext_77;
		$providerData['email'] = $provider->email;
		$providerData['address'] = [
			'label' => '',
			'address' => $provider->ext_63,
			'address_addon' => $provider->address_addon,
			'zip' => $provider->ext_64,
			'city' => $provider->ext_65,
			'country' => $provider->ext_66
		];
		$providerData['short_description'] = $shortDescription;
		$providerData['description'] = $provider->getFamilyDescription($this->appInterface->getLanguage());
		$providerData['way_description'] = $provider->getWayDescription($this->appInterface->getLanguage());
		$providerData['main_image'] = null;
		$providerData['images'] = [];

		$providerData['email_link'] = $this->appInterface->formatEmailLink($provider->email);
		$providerData['phone_link'] = $this->appInterface->formatPhoneNumberLink($provider->ext_67);
		$providerData['phone_mobile_link'] = $this->appInterface->formatPhoneNumberLink($provider->ext_77);

		$providerData['dates'] = [];

		$lastDateEnd = null;
		foreach ($allocations as $allocation) {

			$from = new \DateTime($allocation['date_from']);
			$until = new \DateTime($allocation['date_until']);

			if (
				$lastDateEnd !== null &&
				$from->format('Y-m-d') === $lastDateEnd->format('Y-m-d')
			) {
				// Nur einen Zimmer-/Bettenwechsel
				$providerData['dates'][(count($providerData['dates']) - 1)]['until'] = $this->appInterface->formatDate2($until, 'll');
			} else {
				$providerData['dates'][] = [
					'from' => $this->appInterface->formatDate2($from, 'll'),
					'until' => $this->appInterface->formatDate2($until, 'll')
				];
			}

			$lastDateEnd = $until;
		}

		$images = $this->getAccommodationImages($provider);

		if(!empty($images)) {
			$firstImage = array_shift($images);
			$providerData['main_image'] = $this->appInterface->image('accommodation', $firstImage->getId());
			foreach($images as $image) {
				$providerData['images'][] = [
					'url' => $this->appInterface->image('accommodation', $image->getId()),
					'description' => strip_tags($image->getDescription($this->appInterface->getLanguage()))
				];
			}
		}

		return $providerData;
	}

	/**
	 * Liefert alle freigegebenen Bilder des Unterkunftsanbieters
	 *
	 * @param \Ext_Thebing_Accommodation $provider
	 * @return \Ext_Thebing_Accommodation_Upload[]
	 */
	protected function getAccommodationImages(\Ext_Thebing_Accommodation $provider) {

		$uploads = \Ext_Thebing_Accommodation_Upload::getList($provider->getId(), 'picture', 'released_student_login');
		foreach($uploads as $index => $upload) {
			if(!$upload->isFileExisting()) {
				unset($uploads[$index]);
			}
		}

		return $uploads;
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.accommodation.provider.phone' => $appInterface->t('Phone'),
			'tab.accommodation.provider.phone_mobile' => $appInterface->t('Cell phone'),
			'tab.accommodation.provider.email' => $appInterface->t('Email'),
			'tab.accommodation.provider.description' => $appInterface->t('About'),
			'tab.accommodation.provider.way_description' => $appInterface->t('Directions'),
			'tab.accommodation.provider.images' => $appInterface->t('Images'),
		];
	}
}
