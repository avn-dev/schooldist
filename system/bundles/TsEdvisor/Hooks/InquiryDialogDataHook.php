<?php

namespace TsEdvisor\Hooks;

use Core\Service\Hook\AbstractHook;
use Illuminate\Support\Str;

class InquiryDialogDataHook extends AbstractHook
{
	public function run(array &$data, \Ext_Gui2_Dialog $dialog, \Ext_TS_Inquiry $inquiry)
	{
		if (!\TcExternalApps\Service\AppService::hasApp(\TsEdvisor\Handler\ExternalApp::APP_NAME)) {
			return;
		}

		if ($inquiry->getMeta('edvisor_id')) {

			$title = \L10N::t('Edvisor-Buchung', \TcExternalApps\Interfaces\ExternalApp::L10N_PATH).' #'.$inquiry->getMeta('edvisor_id');
			$instructions = Str::of($inquiry->getMeta('edvisor_instructions'))
				->explode("\n")
				->map(fn($v) => "<li>$v</li>")
				->join('');

			$notification = $dialog->createNotification($title, '<ul>'.$instructions.'</ul>', 'info');
			$data['tabs'][0]['html'] = $notification->generateHTML().$data['tabs'][0]['html'];

		}
	}
}
