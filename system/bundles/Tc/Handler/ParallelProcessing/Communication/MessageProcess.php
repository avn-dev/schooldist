<?php

namespace Tc\Handler\ParallelProcessing\Communication;

use Communication\Exceptions\Mail\AccountConnectionLocked;
use Communication\Services\ConnectionLock;
use Core\Exception\ParallelProcessing\RewriteException;
use Core\Handler\ParallelProcessing\TypeHandler;
use Illuminate\Support\Arr;

/**
 * TODO das ganze muss auch ohne Fake-Gui2 etc. funktionieren können
 * @deprecated
 */
class MessageProcess extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Kommunikation: Nachricht verarbeiten');
	}

	/**
	 * Versand zweimal versuchen
	 * @return int
	 */
	public function getRewriteAttempts() {
		return 2;
	}

	public function execute(array $data, $debug = false) {
		
		/*
		 * @todo Fallback entfernen
		 */
		if(empty($data['language'])) {
			$data['language'] = 'de';
		}

		try {

			$result = $this->send($data, $debug);

			if (!$result['success']) {
				throw new \RuntimeException('Message could not be sent ('.implode(', ', $result['errors']).')');
			}

		} catch (\Throwable $e) {

			$this->tryRewrite($e);

			throw $e;
		}

		return $result['success'];
	}

	public function send(array $data, $debug = false): array
	{

		/* @var \Ext_TC_Basic $entity */
		$entity = \Factory::getInstance($data['entity_class'], $data['entity_id']);
		$communication = $this->createCommunicationObject($data['transfer_type'], $data['application'], $data['language'], $entity);

		/* @var \Ext_TC_Communication_Template $template */
		$template = \Factory::getInstance(\Ext_TC_Communication_Template::class, $data['template_id']);
		$content = $template->getJoinedObjectChildByValue('contents', 'language_iso', $data['language']);

		$recipients = $this->determineRecipients($communication, $entity, $data);

		// Sicherstellen, dass die Struktur stimmt
		if(!empty($data['attachments'])) {
			reset($data['attachments']);
			if(key($data['attachments']) === 0) {
				$data['attachments'] = [
					'documents' => $data['attachments']
				];
			}
		}

		$varsData = [
			'current_tab' => '0', // Tab: E-Mail
			'current_tabarea' => '0', // Tab: Kunden
			$data['transfer_type'] => [
				'send_mode' => $data['send_mode'] ?? \Ext_TC_Communication::SEND_MODE_AUTOMATIC,
				'identity_id' => '0', // Absender (Systembenutzer)
				'customer' => [
					'template_id' => $template->id,
					'recipients' => $recipients,
					'subject' => $content->subject,
					'content' => $content->content,
				],
				'attachments' => $data['attachments']
			]
		];

		$varsData = array_merge(
			$varsData,
			Arr::only($data, ['event_manager_process', 'event_manager_task', 'thread'])
		);

		$data = $communication->saveDialog($varsData);

		return $data;
	}

	protected function createCommunicationObject(string $transferType, string $application, string $language, \Ext_TC_Basic $entity): \Ext_TC_Communication
	{
		$fakeGui = new \Ext_TC_Gui2();
		$dialog = \Factory::executeStatic('Ext_TC_Communication', 'createDialogObject', [&$fakeGui, [], $application]);
		$communication = \Ext_TC_Communication::createCommunicationObject($dialog, $application, [$entity->getId()]);
		$communication->setDefaultLanguage($language);
		$communication->sTransferType = $transferType; // email, sms, app, etc.

		if ($communication->sTransferType !== 'app') {
			// Verhindern, dass Relationen für E-Mail-Adressen mit Objekten gespeichert werden
			$communication->bSkipAdressRelationSaving = true;
		}

		$tabs = [
			'show_history' => false,
			'show_placeholders' => false,
			'show_sms' => false,
			'show_app' => false,
			'show_notices' => false
		];

		if(isset($tabs['show_' . $communication->sTransferType])) {
			$tabs['show_' . $communication->sTransferType] = true;
		}

		// Dialog generieren, da die Kommunikation Daten daraus ausliest
		$communication->getDialogTabs($tabs, true);

		return $communication;
	}

	private function determineRecipients(\Ext_TC_Communication $communication, \Ext_TC_Basic $entity, array $data): array
	{
		if (empty($receivers = $data['receivers'])) {
			return [];
		}


		$recipientCache = [];
		foreach (array_values($receivers) as $index => $receiver) {

			$cacheData = [
				'name' => $receiver['name'],
				'address' => $receiver['email'],
				'selected_id' => $entity->getId(),
				'crc' => crc32(mt_rand())
			];

			if (isset($receiver['entity_class'])) {
				$cacheData['object'] = $receiver['entity_class'];
				$cacheData['object_id'] = $receiver['entity_id'];
			}

			$recipientCache[$index + 1] = $cacheData;
		}

		// Kontaktobjekt-Daten direkt in den Empfängercache der Kommunikation schreiben
		$communication->getDialogDataObject()->aRecipientCache = ['customer' => $recipientCache];

		// Empfänger-Eingabe für den Kommunikationsdialog zusammenbauen
		$sendTo = array_map(
			fn ($recipient) => '<span style="text-decoration: underline;" title="1">'.$recipient['name'].' ('.$recipient['address'].')</span>',
			$recipientCache
		);

		$recipients = [
			'to' => implode(';', $sendTo),
			'cc' => $data['cc'] ?? '',
			'bcc' => $data['bcc'] ?? ''
		];

		return $recipients;
	}

	private function tryRewrite(\Throwable $e): void
	{
		if (
			$e instanceof AccountConnectionLocked ||
			// Falls doch mal ein Microsoft-Fehler durchkommt
			str_contains($e->getMessage(), 'connections limit exceeded')
		) {
			sleep(ConnectionLock::LOCK_DURATION * 0.5);
			// Zu viele Verbindungen, den Task erneut in den Stack schreiben
			throw new RewriteException($e->getMessage());
		}
	}

}
