<?php

namespace Communication\Traits\Gui2;

use Illuminate\Support\Collection;

trait WithCommunication
{
	protected function readCommunicationAccessFromIconData(string $application): string|array|null
	{
		if (empty($this->aIconData)) {
			// TODO korrekt? Zb. Klassenplanung
			return null;
		}

		$icon = $this->aIconData['communication_'.$application];
		if (!$icon) {
			throw new \RuntimeException(sprintf('Missing communication icon in $aIconData [communication_%s]', $application));
		}

		$access = !empty($icon['access']) ? $icon['access'] : null;

		return $access;
	}

	protected function openCommunication(Collection $notifiables, string $application = null, string|array|null $access = null, array $additional = [], bool $initialize = true)
	{
		if ($notifiables->isEmpty()) {
			throw new \RuntimeException('No notifiables found for communication');
		}

		$action = \Admin\Facades\Router::openCommunication($notifiables, application: $application, access: $access, additional: $additional, initialize: $initialize);

		$transfer = [
			'action' => 'executeRouterAction',
			'router_action' => (new \Admin\Http\Resources\RouterActionResource($action, \Admin\Facades\Admin::instance()))->toArray(app('request')),
			'local' => true
		];

		echo \Util::encodeJson($transfer);
		$this->_oGui->save();
		die();
	}
}