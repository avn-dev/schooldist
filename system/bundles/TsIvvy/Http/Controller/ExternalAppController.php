<?php

namespace TsIvvy\Http\Controller;

use Carbon\Carbon;
use Core\Entity\ParallelProcessing\Stack;
use Illuminate\Http\Request;
use TsIvvy\Api;

class ExternalAppController extends \Illuminate\Routing\Controller
{
	public function sync(Request $request, \Access_Backend $access)
	{
		$lastSync = \System::d('ivvy_last_sync', null);

		// Letzte Änderungen seit der letzten Abfrage (-5 Minutes)
		$lastSync = ($lastSync)
			? (new Carbon())->setTimestamp($lastSync)->modify('-5 minutes')
			: null;

		$start = match ($request->input('timeframe')) {
			'last_month' => Carbon::now()->firstOfMonth(),
			'last_3_months' => Carbon::now()->subMonths(2)->firstOfMonth(),
			'last_6_months' => Carbon::now()->subMonths(5)->firstOfMonth(),
			default => $lastSync,
		};

		Api::getLogger()->info('Controller: Sync', ['start' => $start->toDateString()]);

		if ($start) {
			do {
				$end = $start->clone()->endOfMonth();

				Stack::getRepository()->writeToStack('ts-ivvy/sync-timeframe', [
					// Beim letzten Eintrag den Benutzer benachrichtigen
					'user_id' => ($end >= Carbon::now()) ? $access->getUser()->id : null,
					'start' => $start->toDateString(),
					'end' => $end->toDateString()
				], 10);

				$start->addMonth()->firstOfMonth();

			} while ($end <= Carbon::now());

			\Core\Handler\SessionHandler::getInstance()->getFlashBag()->add('success', \L10N::t('Die Ivvy-Daten werden im Hintergrund aktualisiert'));
		}

		return back();
	}
}