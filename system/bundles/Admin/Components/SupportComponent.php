<?php

namespace Admin\Components;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Facades\Admin;
use Admin\Instance;
use Admin\Interfaces\Component\VueComponent;
use Illuminate\Http\Request;

class SupportComponent implements VueComponent
{
	const KEY = 'support';

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('Support', '@Admin/layouts/admin/Support.vue');
	}

	public function isAccessible(\Access $access): bool
	{
		return true;
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		return (new InitialData())
			->l10n([
				'support.label.help_center' => $admin->translate('Hilfezentrum', 'Support'),
				'support.label.chat' => $admin->translate('Support Chat', 'Support'),
				'support.text.help_center' => $admin->translate('Hier finden Sie Antworten auf häufige Fragen, nützliche Anleitungen und schnelle Unterstützung für Ihre Anliegen – schauen Sie sich um!', 'Support'),
				'support.text.chat' => $admin->translate('Unser Support-Chat steht Ihnen zur Verfügung, um schnell und unkompliziert Hilfe zu erhalten. Schreiben Sie uns und wir kümmern uns um Ihr Anliegen!', 'Support'),
			]);
	}

}