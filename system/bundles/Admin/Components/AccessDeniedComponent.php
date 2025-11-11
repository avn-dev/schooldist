<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Facades\Admin;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Illuminate\Http\Request;

class AccessDeniedComponent implements VueComponent
{
	const KEY = 'access.denied';

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('AccessDenied', '@Admin/layouts/admin/AccessDenied.vue');
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		return (new InitialData())
			->l10n([
				'access.denied.heading' => $admin->translate('Zugriff verweigert'),
				'access.denied.text' => $admin->translate('Dieser Bereich ist für Sie nicht freigegeben.'),
			]);
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}
}