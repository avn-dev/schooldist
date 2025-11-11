<?php

namespace Communication\Traits\Channel;

use Communication\Facades\Communication;
use Communication\Interfaces\Flag;
use Communication\Interfaces\Notifications\LoggableMessage;
use Core\Notifications\Channels\MessageTransport;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

trait WithCommunication
{
	private array $config = [];

	protected $communicationMode = false;

	public function enabledCommunicationMode(): static
	{
		$this->communicationMode = true;
		return $this;
	}

	public function setCommunicationConfig(array $config): static
	{
		$this->config = $config;
		return $this;
	}

	private function buildLoggerPayload(\Ext_TC_Communication_Message $log, array $additional = []): array
	{
		if ($log->exist()) {
			return [
				'log_id' => $log->id,
				...$additional
			];
		}
		return $additional;
	}

	private function buildLog(string $channel, LoggableMessage $message): \Ext_TC_Communication_Message
	{
		$log = new \Ext_TC_Communication_Message();
		$log->direction = 'out';
		$log->date = time();
		$log->type = $channel;

		if (!empty([$account, $user] = $message->getFrom())) {
			/* @var \Ext_TC_Communication_Message_Address $address */
			$address = $log->getJoinedObjectChild('addresses');
			$address->type = 'from';
			$address->address = $account->email;
			$address->name = $account->sFromName;
			$address->addRelation($account);
			if ($user && $user->exist()) {
				$log->creator_id = $user->id;
				$address->addRelation($user);
			}

			$log->account_id = $account->id;
		}

		$bindAsAddresses = function (string $type, array $recipients) use ($log) {
			$relations = [];
			foreach ($recipients as $recipient) {
				/* @var \Ext_TC_Communication_Message_Address $address */
				$address = $log->getJoinedObjectChild('addresses');
				$address->type = $type;
				$address->address = $recipient->getRoute();
				$address->name = $recipient->getName();
				if (
					!empty($model = $recipient->getModel()) &&
					$model instanceof \WDBasic
				) {
					$address->addRelation($model);
					$relations = [...$relations, ...$address->relations];
				}
			}
			return $relations;
		};

		if (!empty($relations = $message->getRelations())) {
			$log->addRelations($relations);
		}

		// Alle Relations + $notifiable als Relation für die Nachricht mitspeichern damit die Nachricht immer
		// in der History der Kontakte erscheint
		$allRelations = [
			...$bindAsAddresses('to', (array)$message->getTo()),
			...$bindAsAddresses('cc', (array)$message->getCc()),
			...$bindAsAddresses('bcc', (array)$message->getBcc()),
		];

		$log->addRelations($allRelations);

		[$content, $contentType] = $message->getContent();

		$log->subject = (string)$message->getSubject();
		$log->content = $content;
		$log->content_type = $contentType;

		$attachments = $message->getAttachments();
		foreach ($attachments as $attachment) {
			/* @var \Ext_TC_Communication_Message_File $file */
			$file = $log->getJoinedObjectChild('files');
			$file->file = $attachment->getUrl();
			$file->name = $attachment->getFileName();
			if (!empty($source = $attachment->getSource())) {
				$file->addRelation($source);
			}
		}

		$this->appendConversationCode($log);

		return $log;
	}

	private function finishFlags(\Ext_TC_Communication_Message $log, LoggerInterface $logger): void
	{
		$flags = $log->getJoinedObjectChilds('flags', true);

		foreach ($flags as $model) {
			/* @var \Ext_TC_Communication_Message_Flag $model */
			/* @var Flag $flag */
			if (empty($flag = Communication::getFlag($model->flag))) {
				$logger->warning('Unknown flag', ['log_id' => $log->id, 'flag' => $model->flag]);
				continue;
			}

			$logger->info('Executing flag', ['log_id' => $log->id, 'flag' => $flag::class]);

			try {

				$flag->save($log);

				$logger->info('Flag saved', ['log_id' => $log->id, 'flag' => $flag::class]);

			} catch (\Throwable $e) {
				$logger->info('Saving flag failed', ['log_id' => $log->id, 'flag' => $flag::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
			}
		}
	}

	private function finishLog(\Ext_TC_Communication_Message $log, MessageTransport $transport, LoggerInterface $logger): void
	{
		if (
			// Im Dialog nur speichern wenn erfolgreich versendet wurde
			($this->communicationMode && ($log->exist() || ($transport->successfully() && !$log->exist()))) ||
			// Ansonsten immer speichern
			(!$this->communicationMode && $log->status !== $log->getOriginalData('status'))
		) {
			$log->save();
		}

		if ($transport->successfully() && !$transport->isQueued() && $log->exist()) {
			$logger->info('Message sent successfully', $this->buildLoggerPayload($log));
			// Markierungen final speichern
			$this->finishFlags($log, $logger);
		}
	}

	private function appendConversationCode(\Ext_TC_Communication_Message $log): void
	{
		$code = Arr::first($log->codes, default: \Ext_TC_Communication::generateCode());

		$tmc = sprintf('[TMC:%s]', $code);

		$log->subject = str_replace(['[#]', '[TMC]'], $tmc, $log->subject);
		$log->content = str_replace(['[#]', '[TMC]'], $tmc, $log->content);

		$log->codes = [$code];
	}

}