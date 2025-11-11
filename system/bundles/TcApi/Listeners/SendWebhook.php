<?php

namespace TcApi\Listeners;

use Core\Factory\ValidatorFactory;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\ManageableTrait;
use TcApi\Interfaces\Events\WebhookEvent;

class SendWebhook implements Manageable
{
	use ManageableTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Webhook auslösen');
	}

	private function logger(): LoggerInterface
	{
		return EventManager::logger('Webhook');
	}

	public function handle(WebhookEvent $event): void
	{
		if ($this->isManaged()) {
			$url = $this->getManagedObject()->getSetting('url');
		} else {
			$url = $event->getWebhookUrl();
		}

		$validator = (new ValidatorFactory())->make(['url' => $url], ['url' => 'url:http,https']);

		if (empty($url) || $validator->fails()) {
			$this->logger()->error('Missing or invalid webhook url', ['event' => $event::class, 'url' => $url]);
		}

		$payload = $event->getWebhookPayload();

		if (empty($payload)) {
			$this->logger()->error('Missing webhook payload', ['event' => $event::class]);
		}

		$webhook = [
			'action' => $event->getWebhookAction(),
			'payload' => $payload
		];

		if (
			($event instanceof ManageableEvent && $event->isManaged()) ||
			$this->isManaged()
		) {
			if ($event->isManaged()) {
				$webhook['webhook']['managed']['process_id'] = $event->getManagedObject()->getId();
			}
			if ($this->isManaged()) {
				$webhook['webhook']['managed']['task_id'] = $this->getManagedObject()->getId();
			}
		}

		\Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('api/webhook', ['url' => $url, 'payload' => $webhook]);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow($dataClass->t('Url (POST)'), 'input', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_url',
			'required' => true
		]));
	}
}