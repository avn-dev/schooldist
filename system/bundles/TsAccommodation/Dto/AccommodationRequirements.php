<?php

namespace TsAccommodation\Dto;

class AccommodationRequirements
{
	use \Tc\Traits\Placeholder;

	protected $_sPlaceholderClass = \TsAccommodation\Service\Placeholder\AccommodationRequirementsPlaceholder::class;

	public function __construct(
		private readonly ?\Ext_Thebing_Accommodation $accommodation = null,
		private readonly ?array $requirements = null
	) {}

	public function getAccommodation(): ?\Ext_Thebing_Accommodation
	{
		return $this->accommodation;
	}

	public function getRequirements(): ?array
	{
		return $this->requirements;
	}

}