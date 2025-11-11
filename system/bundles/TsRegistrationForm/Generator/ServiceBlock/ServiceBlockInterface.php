<?php

namespace TsRegistrationForm\Generator\ServiceBlock;

use Illuminate\Support\Collection;
use TsRegistrationForm\Dto\FrontendService;
use TsRegistrationForm\Generator\CombinationGenerator;
use TsRegistrationForm\Service\InquiryBuilder;

interface ServiceBlockInterface
{
	public function __construct(CombinationGenerator $combination, Collection $data);

	public function generateCacheData(\Ext_TS_Frontend_Combination_Inquiry_Helper_Services $helper, array &$additionalServices): void;

	public function generateData(): void;

	public function check(\Ext_TS_Inquiry $inquiry, string $trigger, array &$actions): void;

	public function transform(InquiryBuilder $builder, int $level): void;

	public function buildValidationRules(int $level, FrontendService $service = null): array;
}