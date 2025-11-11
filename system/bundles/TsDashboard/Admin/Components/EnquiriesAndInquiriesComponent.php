<?php

namespace TsDashboard\Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Enums\ColorScheme;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Admin\Traits\WithColorScheme;
use Carbon\Carbon;
use Core\Facade\Cache;
use Illuminate\Http\Request;
use TsDashboard\Helper\Charts\Data;

class EnquiriesAndInquiriesComponent implements VueComponent
{
	use WithColorScheme;

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('InquiriesAndEnquiries', '@TsDashboard/admin/components/EnquiriesInquiriesChart.vue');
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$school = \Ext_Thebing_School::getSchoolFromSession();
		$interfaceLanguage = \System::getInterfaceLanguage();

		$colorScheme = $this->getRequestColorScheme($request);

		$cacheKey = implode('admin.dashboard.EnquiriesInquiriesChart_', [$school->id, $interfaceLanguage, $colorScheme->value]);

		if ($request->boolean('force')) {
			Cache::forget($cacheKey);
		}

		[$dateAsOf, $data] = Cache::remember($cacheKey, 60*60*24, function () use ($admin, $school, $interfaceLanguage, $colorScheme) {
			$data = $this->buildData($admin, $school, $interfaceLanguage, $colorScheme);
			return [Carbon::now()->getTimestamp(), $data];
		});

		return (new InitialData($data))
			->dateAsOf(Carbon::createFromTimestamp($dateAsOf, date_default_timezone_get()));
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	private function buildData(Instance $admin, \Ext_Thebing_School $school, string $language, ColorScheme $colorScheme): array
	{
		$chartData = (new Data($school, $language))->getEnquiryAndBookingStats();

		$data = $options = [];

		$max = max([
			...array_map(fn ($entry) => $entry['enquiries'], $chartData),
			...array_map(fn ($entry) => $entry['inquiries'], $chartData)
		]);

		$color = match ($colorScheme) {
			ColorScheme::LIGHT, ColorScheme::AUTO => '#E2E7EA', // bg-gray-100/50
			ColorScheme::DARK => '#334046', // bg-gray-700
		};

		$options['responsive'] = true;
		$options['maintainAspectRatio'] = false;
		$options['interaction'] = ['mode' => 'index'];
		$options['scales'] = [
			'y' => ['beginAtZero' => true, 'suggestedMax' => (floor(($max + 50) / 10) * 10), 'ticks' => ['precision' => 0], 'border' => ['dash' => [5, 5]], 'grid' => ['color' => $color]], // bg-gray-100/50
			'x' => ['beginAtZero' => true, 'border' => ['dash' => [5, 5]], 'grid' => ['color' => $color]]
		];

		$data['labels'] = array_keys($chartData);
		$data['datasets'] = [
			[
				'label' => $admin->translate('Anfragen', ['Dashboard']),
				'data' => array_map(fn ($entry) => $entry['enquiries'], array_values($chartData)),
				'backgroundColor' => '#BAE6FD',
				'borderRadius' => 7,
				'borderSkipped' => false
			],
			[
				'label' => $admin->translate('Buchungen', ['Dashboard']),
				'data' => array_map(fn ($entry) => $entry['inquiries'], array_values($chartData)),
				'backgroundColor' => '#86EFAC',
				'borderRadius' => 7,
				'borderSkipped' => false
			]
		];

		return [
			'options' => $options,
			'data' => $data,
		];
	}

}