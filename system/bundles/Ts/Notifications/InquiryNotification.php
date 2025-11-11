<?php

namespace Ts\Notifications;

use Communication\Interfaces\Model\CommunicationContact;
use Communication\Notifications\Channels\Messages\AppMessage;
use Communication\Notifications\Channels\Messages\MailMessage;
use Communication\Services\Builder\MessageBuilder;
use Core\Interfaces\HasAttachments;
use Core\Interfaces\Notification\Queueable;
use Core\Service\NotificationService;
use Core\Traits\Notification\WithAttachments;
use Core\Traits\Notification\WithModelsRelations;
use Core\Traits\Notification\WithQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Tc\Interfaces\EventManager\ManageableNotification;
use Tc\Traits\Events\ManageableNotificationTrait;
use Ts\Communication\Application\Booking;

class InquiryNotification extends Notification implements ManageableNotification, HasAttachments, Queueable
{
	use ManageableNotificationTrait,
		WithModelsRelations,
		WithAttachments,
		WithQueue;

	protected array $additionalPlaceholders = [];

	public function __construct(
		protected \Ext_TS_Inquiry $inquiry,
		protected \Ext_TC_Communication_Template $template,
		protected string $sendMode
	) {}

	public function additionalPlaceholders(array $placeholders): static
	{
		$this->additionalPlaceholders = [
			...$this->additionalPlaceholders,
			...$placeholders
		];
		return $this;
	}

	public function via(): array
	{
		return ['mail', 'app'];
	}

	public function toMail($notifiable): ?MailMessage
	{
		[$log, $errors] = $this->builder($notifiable, 'mail');

		if ($log && empty($errors)) {
			$message = (new MailMessage())
				->log($log)
				->subject($log->subject)
				->content($log->content, $log->content_type)
				->attach($this->getAttachments())
				->sendMode($this->sendMode);

			return $message;
		}

		return null;
	}

	public function toApp($notifiable): ?AppMessage
	{
		[$log, $errors] = $this->builder($notifiable, 'app');

		if ($log && empty($errors)) {
			$message = (new AppMessage())
				->log($log)
				->content($log->content)
				->sendMode($this->sendMode);

			return $message;
		}

		return null;
	}

	private function builder($notifiable, string $channel): array {

		$languages = [];

		if ($notifiable instanceof CommunicationContact) {
			$languages = $notifiable->getCorrespondenceLanguages();
		}

		if (empty($languages)) {
			$languages = $this->inquiry->getSchool()->getCorrespondenceLanguages();
		}

		$language = Arr::first($languages);

		/* @var MessageBuilder $builder */
		$builder = \Communication\Facades\Communication::basedOn($this->inquiry)
			->application(Booking::class)
			->new($channel);


		$builder->additionalPlaceholders($this->additionalPlaceholders);
		$builder->template($this->template, $language);

		foreach ($this->getAttachments() as $attachment) {
			$entity = $attachment->getEntity();
			$builder->attachment(new \Communication\Dto\Message\Attachment(
				'document.'.$entity->id,
				filePath: $attachment->getFilePath(),
				fileName: $attachment->getFileName(),
				entity: $entity
			));
		}

		if (empty($builder->getFrom())) {
			// Schule als Absender nehmen falls bisher noch kein Absender gesetzt (z.b. durch Template)
			$builder->from($this->inquiry->getSchool());
		}

		[$messages, ] = $builder->build();

		/* @var \Ext_TC_Communication_Message $log */
		[$log, $errors, ] = $messages->first();

		if ($log) {
			$relations = $this->relations;
			$relations[] = $this->inquiry;
			if ($this->process instanceof \WDBasic) {
				$relations[] = $this->process;
			}
			$log->addRelations($relations);
		}

		if (!empty($errors)) {
			NotificationService::getLogger('InquiryNotification')->error('Message builder failed', ['inquiry_id' => $this->inquiry->id, 'template_id' => $this->template->id, 'language' => $language, 'errors' => $errors]);
		}

		return [$log, $errors];
	}

	/**
	 * @deprecated Arbeitet noch mit der Template-Struktur der Schule
	 * @return array|null
	 */
	/*private function generateMessagePayload(): ?array
	{
		$attachments = $this->template->buildMailAttachmentArray();

		foreach ($this->attachments as $attachment) {

			if (!file_exists($attachment->getFilePath())) {
				continue;
			}

			$payload = [
				'path' => $attachment->getFilePath(),
				'name' => $attachment->getFileName(),
				'relation' => null,
				'relation_id' => null,
			];

			if (!empty($entity = $attachment->getEntity())) {
				$payload['relation'] = $entity::class;
				$payload['relation_id'] = $entity->id;
			}

			$attachments['_'][] = $payload;
		}

		$mailData = \Ext_Thebing_Mail::createMailDataArray($this->inquiry, $this->inquiry->getCustomer(), $this->inquiry->getSchool(), $this->template, $attachments);

		$mailData['to'] = [new Recipient($mailData['to'], $this->inquiry->getCustomer()->getName(), $this->inquiry->getCustomer())];
		$mailData['cc'] = array_map(fn ($address) => new Recipient($address), array_filter($mailData['cc'] ?? [], fn ($address) => !empty($address)));
		$mailData['bcc'] = array_map(fn ($address) => new Recipient($address), array_filter($mailData['bcc'] ?? [], fn ($address) => !empty($address)));

		return $mailData;
	}*/
}