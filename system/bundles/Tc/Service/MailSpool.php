<?php

namespace Tc\Service;

use Communication\Enums\MessageStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

// TODO Endlich mal eine vernünftige Kommunikationsstruktur
class MailSpool
{
	private ?Collection $messages;

	public function for(Collection $messages): static
	{
		$this->messages = $messages;
		return $this;
	}

	public function run(): array
	{
		if ($this->messages !== null) {
			$messages = $this->messages;
		} else {
			$messages = \Ext_TC_Communication_Message::query()
				->where('sent', 0)
				->orderBy('date')
				->get();
		}

		$sent = $failed = [];

		foreach ($messages as $message) {

			[$success, $errors] = $this->send($message);

			if ($success) {
				$sent[] = $message;
			} else {
				$failed[] = [$message, $errors];
			}

		}

		return [$sent, $failed];
	}

	public function send(\Ext_TC_Communication_Message $message): array {

		if ((int)$message->sent === 1) {
			return [false, ['ALREADY_SENT']];
		}

		try {
			[$success, $errors] = match ($message->type) {
				'app' => $this->sendApp($message),
				'email' => $this->sendMail($message),
				default => [false, ['UNKNOWN_MESSAGE_TYPE']]
			};
		} catch (\Throwable $e) {
			$errors = [$e->getMessage()];
			$success = false;
		}

		if ($success) {
			$message->sent = 1;
			$message->status = MessageStatus::SENT->value;
		} else {
			$message->status = MessageStatus::FAILED->value;
		}

		$message->save();

		return [$success, $errors];
	}

	private function sendMail(\Ext_TC_Communication_Message $message): array
	{
		$fromAddress = Arr::first($message->getAddresses('from'));

		$mail = new \Ext_TC_Communication_WDMail();
		$mail->subject = $message->subject;
		$mail->from = $fromAddress->address;

		$userRelation = Arr::first($fromAddress->relations, fn($relation) => is_a($relation['relation'], \User::class, true));
		if ($userRelation) {
			// Wichtig für den richtigen Absender
			$mail->from_user = \Factory::getInstance($userRelation['relation'], $userRelation['relation_id']);
		}

		if($message->content_type == 'html') {
			$mail->html = $message->content;
		} else {
			$mail->text = $message->content;
		}

		$attachments = [];
		foreach ($message->files as $file) {
			$path = \Util::getDocumentRoot(false).$file['file'];
			if (file_exists($path)) {
				$attachments[$path] = $file['name'];
			}
		}
		$mail->attachments = $attachments;

		$to = $message->getAddresses('to');
		$cc = $message->getAddresses('cc');
		$bcc = $message->getAddresses('bcc');

		$to = array_map(fn ($address) => $address->address, $to);

		if(!empty($cc)) {
			$mail->cc = array_map(fn ($address) => $address->address, $cc);
		}
		if(!empty($aRecipients['bcc'])) {
			$mail->bcc = array_map(fn ($address) => $address->address, $bcc);
		}

		// TODO Fehler?
		return [$mail->send($to), []];
	}

	private function sendApp(\Ext_TC_Communication_Message $message): array
	{
		$success = false;
		$errors = [];

		// TODO anders lösen
		\System::wd()->executeHook('tc_mailspool_send_app', $message, $success, $errors);

		return [$success, $errors];
	}

}