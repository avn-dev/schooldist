<?php

namespace Communication\Notifications;

use Communication\Interfaces\Model\CommunicationSender;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Notifications\Channels\Messages\AppMessage;
use Communication\Notifications\Channels\Messages\MailMessage;
use Communication\Notifications\Channels\Messages\SmsMessage;
use Communication\Services\Communication;
use Core\Interfaces\Notification\Queueable;
use Core\Notifications\Attachment;
use Core\Notifications\Recipient;
use Core\Traits\Notification\WithQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CommunicationNotification extends Notification implements Queueable
{
	use WithQueue;

	public function __construct(
		private Communication $communication,
		private \Ext_TC_Communication_Message $message,
		private ?HasCommunication $basedOn = null,
	) {}

	public function toMail(): MailMessage
	{
		$message = (new MailMessage())
			->log($this->message);

		/**
		 * @var \Ext_TC_Communication_EmailAccount $account
		 * @var \User $user
		 */
		[$user, $account, $senderName] = $this->getUserAndAccount();

		if ($account) {
			$message->from($account, $user, $senderName ?? $this->getSenderName('mail', $user));
		}

		foreach(['to', 'cc', 'bcc'] as $receiver) {
			$addresses = $this->message->getAddresses($receiver);
			foreach ($addresses as $address) {
				// $model kann man sich hier sparen da die Nachricht ja bereits gespeichert ist
				$message->$receiver(new Recipient($address->address, $address->name));
			}
		}

		$message->subject($this->message->subject);
		$message->content($this->message->content, $this->message->content_type);

		$attachments = $this->message->getJoinedObjectChilds('files', true);

		foreach ($attachments as $attachment) {
			if (file_exists($filePath = storage_path(Str::after($attachment->file, 'storage/')))) {
				$message->attach(new Attachment($filePath, $attachment->name));
			}
		}

		return $message;
	}

	public function toSms()
	{
		$message = (new SmsMessage())
			->log($this->message);

		/**
		 * @var \Ext_TC_Communication_EmailAccount $account
		 * @var \User $user
		 */
		[$user, $account, $senderName] = $this->getUserAndAccount();

		if ($user) {
			$message->from($user, $senderName ?? $this->getSenderName('sms', $user));
		}

		$address = Arr::first($this->message->getAddresses('to'));

		// $model kann man sich hier sparen da die Nachricht ja bereits gespeichert ist
		$message->to(new Recipient($address->address, $address->name));
		$message->content($this->message->content);

		return $message;
	}

	public function toApp()
	{
		$message = (new AppMessage())
			->log($this->message);

		/**
		 * @var \User $user
		 */
		[$user, $account, $senderName] = $this->getUserAndAccount();

		if ($user) {
			$message->from($user, $senderName ?? $this->getSenderName('app', $user));
		}

		$to = $this->message->getAddresses('to');

		foreach ($to as $address) {
			// $model kann man sich hier sparen da die Nachricht ja bereits gespeichert ist
			$message->to(new Recipient($address->address, $address->name));
		}

		$message->content($this->message->content);

		return $message;
	}

	/*public function __call($name, $arguments)
	{
		if (substr($name, 0, 2) == 'to') {
			$channel = strtolower(substr($name, 2));
			return $this->communication->getChannel($channel)
				;
		}
	}*/

	private function getUserAndAccount(): array
	{
		$addresses = $this->message->getAddresses('from');

		$user = $account = $senderName = null;
		foreach($addresses as $address) {

			if (!empty($address->name)) {
				$senderName = $address->name;
			}

			foreach ($address->relations as $relation) {
				if (is_a($relation['relation'], \Ext_TC_Communication_EmailAccount::class, true)) {
					$account = \Factory::getInstance($relation['relation'], $relation['relation_id']);
				} else if (is_a($relation['relation'], \User::class, true)) {
					$user = \Factory::getInstance($relation['relation'], $relation['relation_id']);
				}
			}
		}

		return [$user, $account, $senderName];
	}

	private function getSenderName(string $channel, \User $user = null): ?string
	{
		if ($this->basedOn) {

			$subObject = $this->basedOn->getCommunicationSubObject();

			if ($user instanceof CommunicationSender) {
				return $user->getCommunicationSenderName($channel, $subObject);
			}

			if ($subObject instanceof CommunicationSender) {
				return $subObject->getCommunicationSenderName($channel, $subObject);
			}
		}

		return null;
	}

}