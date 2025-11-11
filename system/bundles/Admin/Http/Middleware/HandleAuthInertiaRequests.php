<?php

namespace Admin\Http\Middleware;

use Admin\Enums\ColorScheme;
use Admin\Facades\Admin;
use Admin\Traits\WithColorScheme;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleAuthInertiaRequests extends Middleware
{
	use WithColorScheme;

	/**
	 * The root template that's loaded on the first page visit.
	 *
	 * @see https://inertiajs.com/server-side-setup#root-template
	 * @var string
	 */
	protected $rootView = 'auth';

	/**
	 * Determines the current asset version.
	 *
	 * @see https://inertiajs.com/asset-versioning
	 * @param  \Illuminate\Http\Request  $request
	 * @return string|null
	 */
	public function version(Request $request): ?string
	{
		return parent::version($request);
	}

	/**
	 * Defines the props that are shared by default.
	 *
	 * @see https://inertiajs.com/shared-data
	 * @param  \Illuminate\Http\Request  $request
	 * @return array
	 */
	public function share(Request $request): array
	{
		$logos = (new \Admin\Helper\Design)->getLogos();

		$copyright = '2001-'.date('Y');
		if (!empty($producer = \System::d('software_producer'))) {
			$copyright .= ' by '.$producer;
		}

		return array_merge(parent::share($request), [
			'title' => sprintf('%s - %s', \System::d('project_name'), Admin::translate('Login')),
			'interface' => [
				'color_scheme' => $this->getColorScheme(default: ColorScheme::LIGHT)->value,
				'logo' => [
					'light' => $logos['login_logo'],
					'dark' => $logos['dark:login_logo']
				],
				'copyright' => $copyright
			]
		]);
	}

}