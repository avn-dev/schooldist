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

class StudentNationalitiesComponent implements VueComponent
{
	use WithColorScheme;

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('StudentNationalities', '@TsDashboard/admin/components/StudentNationalitiesChart.vue');
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$school = \Ext_Thebing_School::getSchoolFromSession();
		$interfaceLanguage = \System::getInterfaceLanguage();

		$colorScheme = $this->getRequestColorScheme($request);

		$cacheKey = implode('admin.dashboard.StudentNationalitiesChart_', [$school->id, $interfaceLanguage, $colorScheme->value]);

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
		$chartData = (new Data($school, $language))->getStudentsByNationality();

		// https://v3.tailwindcss.com/docs/customizing-colors
		$colors = ['#FCA5A5', '#FCA5A5','#FDBA74','#86EFAC','#F0ABFC','#A5B4FC','#BAE6FD','#F472B6', '#BEF264', '#FDA4AF', '#A78BFA', '#C084FC', '#5EEAD4', '#FEF08A', '#7C3AED', '#FB923C', '#F87171', '#10B981', '#0284C7', '#6D28D9'];
		$data = $options = [];

		$options['responsive'] = true;
		$options['maintainAspectRatio'] = false;
		$options['animations'] = ['tension' => ['easing' => 'easeOutBounce']];

		$color = match ($colorScheme) {
			ColorScheme::LIGHT, ColorScheme::AUTO => '#FFFFFF',
			ColorScheme::DARK => '#293338', // bg-gray-800
		};

		$data['labels'] = array_keys($chartData);
		$data['datasets'] = [
			[
				'dataset' => $admin->translate('Nationalitäten', ['Dashboard']),
				'data' => array_values($chartData),
				'backgroundColor' => array_slice($colors, 0, count($chartData)),
				'borderRadius' => 7,
				'borderColor' => $color
			]
		];

		return [
			'options' => $options,
			'data' => $data,
		];
	}
}