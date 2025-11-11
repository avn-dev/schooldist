<?php

namespace Communication\Services\Builder;

use Communication\Dto\ChannelConfig;
use Communication\Dto\Message\Attachment;
use Communication\Dto\Message\Recipient;
use Communication\Enums\MessageOutput;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Interfaces\Model\CommunicationSender;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Notifications\Channels\MailChannel;
use Communication\Services\AddressBook\AddressBookContact;
use Communication\Services\Communication;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MessageBuilder
{
	private ?string $language = null;
	private ?CommunicationSender $from = null;

	private array $to = [];
	private array $cc = [];
	private array $bcc = [];
	private string $subject = '';
	private string $contentType = '';
	private array $content = [];
	private array $history = [];
	private array $flags = [];
	private array $attachments = [];
	private array $confirmedErrors = [];

	protected array $additionalPlaceholders = [];

	private ?ChannelConfig $config = null;

	private ?\Ext_TC_Communication_Message $relatedMessage = null;
	private ?\Ext_TC_Communication_Template_Email_Layout $layout = null;
	private ?\Ext_TC_Communication_Template $template = null;

	public function __construct(
		private readonly Communication $communication,
		private readonly string $channel
	) {
		$this->config = $this->communication->getChannel($this->channel)->getCommunicationConfig();
		$this->contentType = Arr::first($this->config->getContentTypes(), default: ChannelConfig::CONTENT_TEXT);
	}

	public function related(\Ext_TC_Communication_Message $message): static
	{
		$this->relatedMessage = $message;
		return $this;
	}

	public function reply(\Ext_TC_Communication_Message $message): static
	{
		$this->related($message);

		$this->subject = sprintf($this->communication->l10n()->translate('AW: %s'), $message->subject);
		if ($this->config->get('actions.reply.history', false) && !empty($message->content)) {
			$this->history[] = [$message->content, \Factory::getObject(\Ext_TC_Gui2_Format_Date_Time::class)->formatByValue($message->date)];
		}

		if (empty($replyTo = $message->getAddresses('reply_to'))) {
			$replyTo = $message->getAddresses('from');
		}

		$this->to = $this->syncLogRecipients($replyTo, true);

		return $this;
	}

	public function replyAll(\Ext_TC_Communication_Message $message): static
	{
		$this->reply($message);

		$this->cc = $this->syncLogRecipients($message->getAddresses('cc'));

		return $this;
	}

	public function forward(\Ext_TC_Communication_Message $message, bool $withAttachments = true): static
	{
		$this->related($message);

		$this->subject = sprintf($this->communication->l10n()->translate('WG: %s'), $message->subject);

		if (!empty($message->content)) {
			$this->history[] = [$message->content, \Factory::getObject(\Ext_TC_Gui2_Format_Date_Time::class)->formatByValue($message->date)];
		}

		if ($withAttachments) {
			$files = $message->getJoinedObjectChilds('files', true);
			$this->attachments = $this->syncLogAttachments($files);
		}

		return $this;
	}

	public function from(CommunicationSender $sender): static
	{
		$this->from = $sender;
		return $this;
	}

	public function to(AddressBookContact|Recipient|array $recipient): static
	{
		$this->to = [...$this->to, ...Arr::wrap($recipient)];
		return $this;
	}

	public function cc(AddressBookContact|Recipient|array $recipient): static
	{
		$this->cc = [...$this->cc, ...Arr::wrap($recipient)];
		return $this;
	}

	public function bcc(AddressBookContact|Recipient|array $recipient): static
	{
		$this->bcc = [...$this->bcc, ...Arr::wrap($recipient)];
		return $this;
	}

	public function layout(\Ext_TC_Communication_Template_Email_Layout $layout, bool $adoptContent = true): static
	{
		$this->layout = $layout;

		if ($adoptContent) {
			$this->contentType('html');
			$this->content($layout->generateContent(''));
		}

		return $this;
	}

	public function template(\Ext_TC_Communication_Template $template, string $language = null, bool $adoptContent = true): static
	{
		$this->template = $template;

		if ($language) {
			$this->language = $language;
		}

		if ($adoptContent) {

			if (empty($this->language)) {
				$this->language = Arr::first($template->languages, 'en');
			}

			$templateContent = $template->getContentObjectByIso($this->language);

			if ($templateContent) {
				if ($template->shipping_method === 'html') {
					if ($this->layout) {
						$content = $this->layout->generateContent((string)$templateContent->content);
					} else if ($templateContent->layout_id > 0) {
						$this->layout($templateContent->getLayout(), false);
						$content = $this->layout->generateContent((string)$templateContent->content);
					} else {
						$content = $templateContent->content;
					}
				} else {
					$content = $templateContent->content;
				}

				if ($template->default_identity_id > 0) {
					/* @var \Ext_TC_User $sender */
					$sender = \Factory::getInstance(\Ext_TC_User::class, $template->default_identity_id);
					$this->from($sender);
				}

				$this->cc(array_map(fn ($recipient) => new Recipient($recipient), $template->getCC()));
				$this->bcc(array_map(fn ($recipient) => new Recipient($recipient), $template->getBCC()));
				$this->subject((string)$templateContent->subject);
				$this->contentType($template->shipping_method);
				$this->content((string)$content);
				$this->flag($template->flags);

				if ($this->config->hasAttachments()) {
					$manualUploads = $templateContent->getUploadFilePaths();
					foreach ($manualUploads as $filePath) {
						$dto = new Attachment('template::' . 'storage/'.Str::after($filePath, 'storage/'), $filePath, basename($filePath));
						$this->attachment($dto);
					}

					$uploads = $templateContent->to_uploads;

					foreach ($uploads as $uploadId) {
						/* @var \Ext_TC_Upload $upload */
						$upload = \Factory::getInstance(\Ext_TC_Upload::class, $uploadId);
						if ($upload->isActive() && file_exists($filePath = $upload->getPath(true))) {
							$dto = new Attachment('tc.upload.'.$upload->id, filePath: $filePath, fileName: $upload->description, entity: $upload);
							$this->attachment($dto);
						}
					}
				}
			} else if ($this->layout) {
				$this->content($this->layout->generateContent(''));
			}
		}

		return $this;
	}

	public function language(string $language): static
	{
		$this->language = $language;
		return $this;
	}

	public function subject(string $subject): static
	{
		$this->subject = strip_tags($subject);
		return $this;
	}

	public function contentType(string $contentType): static
	{
		// TODO macht Probleme bei der Ereignissteuerung
		/*if (!in_array($contentType, $this->config->getContentTypes())) {
			throw new \InvalidArgumentException(sprintf('Content type not allowed for channel [channel: %s, content_type: %s, allowed: %s]', $this->channel, $contentType, implode('|', $this->config->getContentTypes())));
		}*/

		$this->contentType = $contentType;
		return $this;
	}

	public function content(string $content, string $separator = null): static
	{
		$clean = preg_replace('/<!--placeholder\s+name=["\'][^"\']+["\']\s*-->([\s\S]*?)<!--\/placeholder-->/i', '$1', $content);

		$this->content[] = [$clean, $separator];
		return $this;
	}

	public function flag(string|array $flag): static
	{
		$this->flags = [...$this->flags, ...Arr::wrap($flag)];
		return $this;
	}

	public function attachment(AttachmentsCollection|Attachment|array $attachment): static
	{
		// TODO Nur mit AttachmentsCollection arbeiten?
		if ($attachment instanceof AttachmentsCollection) {
			$attachment = $attachment->toArray();
		}

		$this->attachments = [...$this->attachments, ...Arr::wrap($attachment)];
		return $this;
	}

	public function additionalPlaceholders(array $placeholders): static
	{
		$this->additionalPlaceholders = [
			...$this->additionalPlaceholders,
			...$placeholders
		];

		return $this;
	}

	public function confirmError(string|array $error): static
	{
		$this->confirmedErrors = [
			...$this->confirmedErrors,
			...Arr::wrap($error),
		];
		return $this;
	}

	public function getFrom(): ?CommunicationSender
	{
		return $this->from;
	}

	public function getTo(): Collection
	{
		return collect($this->to);
	}

	public function getCc(): Collection
	{
		return collect($this->cc);
	}

	public function getBcc(): Collection
	{
		return collect($this->bcc);
	}

	public function getSubject(): string
	{
		return $this->subject;
	}

	public function getLayout(): ?\Ext_TC_Communication_Template_Email_Layout
	{
		return $this->layout;
	}

	public function hasContent(): bool
	{
		return !empty([...$this->content, ...$this->history]);
	}

	public function getContentType(): string
	{
		return $this->contentType;
	}

	public function getContent(bool $onlyBody = false): string
	{
		if ($this->contentType === 'html' && empty($this->content) && !empty($this->history)) {
			$this->content('<br/><br/>');
		}

		$allContents = [...$this->content, ...$this->history];

		if ($this->contentType === 'html') {

			if (!$onlyBody) {
				$skeleton = '<html><head></head><body></body></html>';
				if ($this->template) {
					$templateContent = $this->template->getJoinedObjectChildByValue('contents', 'language_iso', $this->language);
					if ($templateContent && $templateContent->layout_id > 0) {
						$skeleton = $this->communication->contentManager()->extractHtmlSkeleton($templateContent->getLayout()->html);
						// <body>-Attribute entfernen
						$skeleton = preg_replace('/<body[^>]*>/i', '<body>', $skeleton);
					}
				}
			} else {
				$skeleton = '<body></body>';
			}

			$content = $this->communication->contentManager()->combineAsHtml($skeleton, ...$allContents);
		} else {
			$content = $this->communication->contentManager()->combineAsText(...$allContents);
			
			// Nach fälschlicherweise umgewandelten Zeichen suchen "->" und diese korrigieren, aber nur innerhalb von {}
			if(
				str_contains($content, '-&gt;') ||
				str_contains($content, '&gt;') ||
				str_contains($content, '&lt;') ||
				str_contains($content, '&amp;')
			) {
				$content = preg_replace_callback(
					'/\{(.*?)\}/',
					function ($matches) {
						$final = str_replace(['-&gt;', '&gt;', '&lt;', '&amp;'], ['->', '>', '<', '&'], $matches[0]);
						return $final;
					},
					$content
				);
			}
			
		}

		return $content;
	}

	public function getAttachments(): Collection
	{
		return collect($this->attachments);
	}

	public function buildIndividually(MessageOutput $output = MessageOutput::FINAL): array
	{
		$models = $this->communication->getBasedOnModels();

		if ($models->isEmpty()) {
			// Wenn die Kommunikation auf keiner bestimmten Entität basiert (z.b. globale Kommunikation)
			$models = collect([null]);
		}

		$allMessages = collect();
		$allInvalidRecipients = collect();
		foreach ($models as $model) {
			$to = $this->filterRecipientsForModel($this->to, $model);
			$cc = $this->filterRecipientsForModel($this->cc, $model);
			$bcc = $this->filterRecipientsForModel($this->bcc, $model);
			$attachments = $this->filterAttachmentsForModel($this->attachments, $model);

			[$messages, $invalidRecipients] = $this->buildMessages($model, $to, $cc, $bcc, $attachments, $output);

			$allMessages = $allMessages->merge($messages);

			$invalidRecipients->each(fn ($value, $index) => $allInvalidRecipients->put($index, $value));
		}

		return [$allMessages, $allInvalidRecipients];
	}

	/**
	 * @param MessageOutput $output
	 * @return array
	 * @throws \Throwable
	 */
	public function build(MessageOutput $output = MessageOutput::FINAL): array
	{
		$models = $this->communication->getBasedOnModels();
		return $this->buildMessages($models->first(), $this->to, $this->cc, $this->bcc, $this->attachments, $output);
	}

	private function buildMessages(?HasCommunication $model, array $to,  array $cc, array $bcc, array $attachments, MessageOutput $output)
	{
		// Sichergehen dass für die Nachricht ein TO enthalten ist
		$this->ensureTo($to, $cc, $bcc);

		// In der Ereignissteuerung wird der Empfänger erst später gesetzt
		/*if (empty($to)) {
			return [collect(), collect()];
		}*/

		[$validTo, $invalidTo] = $this->validateRecipients($to);
		[$validCc, $invalidCc] = $this->validateRecipients($cc);
		[$validBcc, $invalidBcc] = $this->validateRecipients($bcc);

		$invalidRecipients = collect()
			->merge(!empty($invalidTo) ? ['to' => $invalidTo] : [])
			->merge(!empty($invalidCc) ? ['cc' => $invalidCc] : [])
			->merge(!empty($invalidBcc) ? ['bcc' => $invalidBcc] : []);

		$buildMessage = function (array $to, array $cc, array $bcc) use ($model, $attachments, $output) {

			$transaction = 'communication.message.build.'.\Util::generateRandomString(5);

			// Transaktion starten für Markierungen bzw. Platzhalter, dort werden teilweise Objekte erzeugt und
			// gespeichert. Diese müssen rückgängig gemacht werden wenn Fehler auftreten
			\DB::begin($transaction);

			$content = $this->getContent();

			[$content, $contentErrors] = $model ? $this->replacePlaceholders($model, $content, $output) : [$content, []];
			[$subject, $subjectErrors] = $model ? $this->replacePlaceholders($model, $this->subject, $output) : [$this->subject, []];

			if (empty($content)) {
				$contentErrors[] = $this->communication->l10n()->translate('Die Nachricht darf nicht leer sein.');
			}

			$log = new \Ext_TC_Communication_Message();
			$log->date = time();
			$log->direction = 'out';
			$log->subject = $subject;
			$log->content_type = $this->contentType;
			$log->content = $content;
			$log->type = $this->channel === 'mail' ? 'email' : $this->channel;

			if ($this->relatedMessage) {
				$log->codes = $this->relatedMessage->codes;
				$log->addRelations($this->relatedMessage->relations);
			}

			if ($this->template) {
				$template = $log->getJoinedObjectChild('templates');
				$template->template_id = $this->template->id;
			}

			$from = null;

			if ($this->from instanceof \Ext_TC_User) {
				$log->creator_id = $this->from ->id;
			}

			$subObject = $model->getCommunicationSubObject();

			if ($this->channel === MailChannel::CHANNEL_KEY) {

				$account = null;

				if ($this->from) {
					$account = $this->from->getCommunicationEmailAccount($subObject);
				}

				// Fallback
				// TODO: was hier passiert ist grausam
				if (!$account || !$account->exist()) {
					$user = ($this->from instanceof \Ext_TC_User) ? $this->from : null;

					$wdmail = new \Ext_TC_Communication_WDMail();
					if ($user) {
						$wdmail->from_user = $user;
					}

					$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'getUserOptions', [$wdmail, $user]);
				}

				if (!$account instanceof \Ext_TC_Communication_EmailAccount) {
					throw new \RuntimeException('No email account');
				}

				$senderName = (empty($account->sFromName))
					? $this->from->getCommunicationSenderName($this->channel, $subObject)
					: $account->sFromName;

				$from = [$account->email, $senderName, [$account, $this->from]];

			} else if ($this->from) {

				$from = [null, $this->from->getCommunicationSenderName($this->channel, $subObject), [$this->from]];

			}

			if (!empty($from)) {
				[$route, $name, $relations] = $from;

				/* @var \Ext_TC_Communication_Message_Address $addressChild */
				$addressChild = $log->getJoinedObjectChild('addresses');
				$addressChild->type = 'from';
				$addressChild->address = (string)$route;
				$addressChild->name = (string)$name;

				foreach ($relations as $relation) {
					if ($relation instanceof \WDBasic && $relation->exist()) {
						$log->addRelation($relation);
						$addressChild->addRelation($relation);
					}
				}
			}

			if ($model instanceof \WDBasic) {
				$log->addRelation($model);

				// Weitere Entitäten automatisch mit dieser Nachricht verknüpfen
				if (method_exists($model, 'getCommunicationAdditionalRelations')) {
					foreach ($model->getCommunicationAdditionalRelations() as $relationModel) {
						$log->addRelation($relationModel);
					}
				}
			}

			$bindAsAddresses = function (string $type, array $recipients) use ($log) {
				$relations = [];
				foreach ($recipients as $recipient) {

					if ($recipient instanceof AddressBookContact) {
						$loop = $recipient->toRecipient($this->channel);
					} else if (is_array($recipient) && $recipient[0] instanceof AddressBookContact) {
						$loop = $recipient[0]->toRecipient($this->channel, $recipient[1]);
					} else {
						$loop = [$recipient];
					}

					foreach ($loop as $resolvedRecipient) {
						/* @var Recipient $resolvedRecipient */
						/* @var \Ext_TC_Communication_Message_Address $addressChild */
						$addressChild = $log->getJoinedObjectChild('addresses');
						$addressChild->type = $type;
						$addressChild->address = $resolvedRecipient->getRoute();
						$addressChild->name = $resolvedRecipient->getName();
						if (!empty($recipientModel = $resolvedRecipient->getModel())) {
							$addressChild->addRelation($recipientModel);
							if (method_exists($recipientModel, 'getCommunicationAdditionalRelations')) {
								foreach ($recipientModel->getCommunicationAdditionalRelations() as $relationModel) {
									$relations[] = ['relation' => $relationModel::class, 'relation_id' => (int)$relationModel->id];
								}
							}

							$relations = [...$relations, ...$addressChild->relations];
						}
					}
				}

				return $relations;
			};

			// Alle Relations + $basedOn als Relation für die Nachricht mitspeichern damit die Nachricht immer
			// in der History der Kontakte erscheint
			$allRelations = [
				...$bindAsAddresses('to', $to),
				...$bindAsAddresses('cc', $cc),
				...$bindAsAddresses('bcc', $bcc),
			];

			$log->addRelations($allRelations);

			// Markierungen

			$usedFlags = $this->communication->getFlags()->intersect($this->flags);

			if ($model) {
				foreach ($usedFlags->keys() as $flag) {
					/* @var $flagChild \Ext_TC_Communication_Message_Flag */
					$flagChild = $log->getJoinedObjectChild('flags');
					$flagChild->flag = $flag;
				}
			}

			// Anhänge

			foreach ($attachments as $attachment) {
				/* @var Attachment $attachment */
				/* @var $file \Ext_TC_Communication_Message_File */
				$file = $log->getJoinedObjectChild('files');
				$file->file = $attachment->getUrl();
				$file->name = basename($attachment->getFilePath());

				// TODO die Datenbank erlaub nur eine Relation für das File (Primary-Key). Das sollte man umstellen können.
				// An sich denke ich braucht man das $source-Model aber nicht zu verknüpfen
				/*if (!empty($attachmentSource = $attachment->getSource()) && $attachmentSource instanceof \WDBasic) {
					$file->addRelation($attachmentSource);
				}*/

				if (!empty($attachmentModel = $attachment->getModel()) && $attachmentModel instanceof \WDBasic) {
					$file->addRelation($attachmentModel);
				}
			}

			$applicationErrors = $flagErrors = [];
			if ($model) {
				$flagErrors = $this->communication->flagManager()->handleFlags($model, $usedFlags->values(), $log, $output->isFinal(), $this->confirmedErrors);

				if (
					!empty($application = $this->communication->getApplication()) &&
					method_exists($application, 'validate')
				) {
					$applicationErrors = $application->validate($this->communication->l10n(), $model, $log, $output->isFinal(), $this->confirmedErrors);
				}
			}

			//$validationErrors = (is_array($validation = $message->validate())) ? $validation : [];
			$validationErrors = [];

			$errors = [...$validationErrors, ...$subjectErrors, ...$contentErrors, ...$flagErrors, ...$applicationErrors];

			if (!empty($errors)) {
				// Umwandeln damit alle errors dieselbe Struktur haben
				$errors = array_map(fn ($error) => is_string($error) ? ['type' => 'error', 'message' => $error] : $error, $errors);
				// Falls es Fehler gibt müssen die bei den Markierungen erstellen Objekte wieder rückgängig gemacht werden
				\DB::rollback($transaction);
			} else {
				\DB::commit($transaction);
			}

			return [$log, $errors, $model];
		};

		// Jeder Empfänger erhält eine eigene Nachricht (z.b. SMS)
		//$messagePerRecipient = $channelObject->getCommunicationConfig()->get('message_per_recipient', false);

		try {
			$messages = [];
			//if ($messagePerRecipient) {
			//	foreach ([...$validTo, ...$validCc, ...$validBcc] as $recipient) {
			//		$messages[] = $buildMessage([$recipient], [], []);
			//	}
			//} else if (!empty($validTo)) {
				$messages[] = $buildMessage($validTo, $validCc, $validBcc);
			//}

		} catch (\Throwable $e) {

			if (!empty($transaction = \DB::getLastTransactionPoint()) && str_starts_with($transaction, 'communication.message.build.')) {
				\DB::rollback($transaction);
			}

			throw $e;
		}

		return [collect($messages), $invalidRecipients];
	}

	private function syncLogRecipients(array $addresses, $debug = false): array
	{
		$addressBook = $this->communication->addressBook();

		$recipients = [];
		foreach ($addresses as $address) {
			$match = [];

			$relations = $address->relations;
			if (empty($relations)) {
				$relations[] = ['relation' => null, 'relation_id' => null];
			}

			foreach ($relations as $relation) {
				// Adresse im Adressbuch suchen um den Kontakt in der Auswahl zu highlighten
				$found = $addressBook->search($this->channel, $relation['relation'], (int)$relation['relation_id'], $address->address);

				if ($found->isNotEmpty()) {
					/* @var AddressBookContact $addressBookContact */
					[$addressBookContact, $routeIndex] = $found->first();
					if ($routeIndex) {
						$match = [$addressBookContact, $routeIndex];
					} else {
						$match = $addressBookContact;
					}
					break;
				}
			}

			if (empty($match)) {
				$match = new Recipient($address->address, !empty($address->name) ? $address->name : $address->address);
			}

			$recipients[] = $match;
		}

		return $recipients;
	}

	private function syncLogAttachments(array $files): array
	{
		$attachments = [];

		foreach ($files as $file) {
			$completePath = storage_path(Str::after($file->file, 'storage/'));

			$attachment = $this->communication->fileManager($this->channel)->search($completePath);

			if (!$attachment && file_exists($completePath)) {
				$attachment = new Attachment('custom::'.$file->file.'::'.$file->name, $completePath, $file->name);
			}

			if ($attachment) {
				$attachments[] = $attachment;
			}
		}

		return $attachments;
	}

	public function validateRecipients(array $recipients): array
	{
		$channelObject = $this->communication->getChannel($this->channel);

		$valid = $invalid = [];
		foreach ($recipients as $index => $recipient) {

			$isValid = $channelObject->validateRoute($recipient);

			if ($isValid) {
				$valid[$index] = $recipient;
			} else {
				$invalid[$index] = $recipient->getRoute();
			}
		}

		return [$valid, $invalid];
	}

	private function filterRecipientsForModel(array $recipients, HasCommunication $model): array
	{
		return array_filter($recipients, function ($recipient) use ($model) {
			if (
				($recipient instanceof Recipient && !empty($source = $recipient->getSource())) ||
				($recipient instanceof AddressBookContact && !empty($source = $recipient->getSource())) ||
				(is_array($recipient) && $recipient[0] instanceof AddressBookContact && !empty($source = $recipient[0]->getSource()))
			) {
				return is_a($model, $source::class) && (int)$source->id === (int)$model->id;
			}
			return true;
		});
	}

	private function filterAttachmentsForModel(array $attachments, HasCommunication $model): array
	{
		return array_filter($attachments, function (Attachment $attachment) use ($model) {
			if (!empty($source = $attachment->getSource())) {
				return is_a($model, $source::class) && (int)$source->id === (int)$model->id;
			}
			return true;
		});
	}

	private function ensureTo(array &$to, array &$cc, array &$bcc): void
	{
		if (empty($to) && !empty($cc)) {
			$to = $cc;
			$cc = [];
		}

		if (empty($to) && !empty($bcc)) {
			$to = $bcc;
			$bcc = [];
		}
	}

	public function replacePlaceholders(HasCommunication $basedOn, string $content, MessageOutput $output)
	{
		if (empty($content) || !$basedOn instanceof \Ext_TC_Basic) {
			return [$content, []];
		}

		foreach ($this->additionalPlaceholders as $placeholder => $value) {
			$content = str_replace('{'.$placeholder.'}', $value, $content);
		}

		$userSignatureWrapped = false;

		// Signatur des Absenders nur setzen wenn generell HTML benutzt wird oder man sich in der Vorschau oder beim finalen Senden
		// befindet. D.h. bei Text-Nachrichten wird die Signatur erst später (Vorschau/Senden) gesetzt da man sie dort nicht "live"
		// austauschen kann wenn man den Absender wechselt
		if ($this->contentType === 'html' || $output->isPreview() || $output->isFinal()) {
			$content = $this->replaceUserSignature($this->contentType, $this->from, $content, $basedOn, $this->language, $output);
		} else {
			// Platzhalter für Signatur maskieren damit diese im weiteren Verlauf nicht ersetzt werden
			$content = $this->wrapUserSignaturePlaceholders($content, true);
			$userSignatureWrapped = true;
		}

		$application = $this->communication->getApplication();

		$placeholderObject = (method_exists($application, 'getPlaceholderObject'))
			? $application->getPlaceholderObject($basedOn, $this->template, collect($this->to), $this->language, $output->isFinal())
			: null;

		if (!$placeholderObject) {
			// Fallback
			$placeholderObject = $basedOn->getPlaceholderObject();
		}

		$errors = [];

		if ($placeholderObject) {

			try {

				if ($placeholderObject instanceof \Ext_TC_Placeholder_Abstract) {
					$placeholderObject->setDisplayLanguage($this->language);
					$placeholderObject->setType('communication');
					if ($this->from instanceof \Ext_TC_User) {
						$placeholderObject->setCommunicationSender($this->from);
					}

					$replaced = $placeholderObject->replace($content, $output->isFinal());

					$errors = array_map(fn($error) => \Util::getEscapedString($error, 'htmlall'), Arr::flatten($placeholderObject->getErrors(), 1));

				} else {
					$replaced = $placeholderObject->replace($content);

					if ($output->isFinal() && method_exists($placeholderObject, 'replaceFinalOutput')) {
						// @deprecated Alte Platzhalterklassen in der Schule
						$replaced = $placeholderObject->replaceFinalOutput($replaced);
					}
				}

				if (empty($errors)) {
					$content = $replaced;
				}

			} catch (\Throwable $e) {

				if (
					\Util::isDebugIP() || \System::d('debugmode') > 0 ||
					// Platzhalter Schule
					str_contains($e->getMessage(), 'Missing closing if')
				) {
					$errors[] = $e->getMessage();
				} else {
					$errors[] = $this->communication->l10n()->translate('Beim Ersetzen der Fehler ist ein Fehler aufgetreten.');
				}
			}
		}

		if ($userSignatureWrapped) {
			// Platzhalter für Signatur maskieren wieder im Original einfügen
			$content = $this->wrapUserSignaturePlaceholders($content, false);
		}

		return [$content, array_map(fn ($error) => ['type' => 'error', 'message' => $error], $errors)];
	}

	private function replaceUserSignature(string $contentType, CommunicationSender $sender, string $text, HasCommunication $basedOn, string $language, MessageOutput $output)
	{
		if (mb_strpos($text, '<!--placeholder') !== false) {
			// Platzhalter für Signatur wieder umwandeln damit man die Werte wieder neu setzen kann
			$text = preg_replace('/<!--placeholder\s+name=["\']([^"\']+)["\']\s*-->([\s\S]*?)<!--\/placeholder-->/i', '{$1}', $text);
		}

		$user = ($sender instanceof \Ext_TC_User) ? $sender : null;

		/* @var \WDBasic $subObject */
		$subObject = $basedOn->getCommunicationSubObject();

		// Die Signaturen-Platzhalter sollten auf jeden Fall ersetzt werden da ansonsten das Template die Platzhalter nicht
		// kennt und Fehler wirft.
		/* @var \Ext_TC_User_Signature $userSignature */
		$userSignature = $user?->getSignatureForObject($subObject) ?? \Factory::getObject(\Ext_TC_User_Signature::class);

		$signaturePlaceholder = $userSignature->getPlaceholderObject();
		$signaturePlaceholder->setDisplayLanguage($language);

		$definedPlaceholders = $signaturePlaceholder->getPlaceholders();

		foreach (array_keys($definedPlaceholders) as $placeholderKey) {
			$placeholder = '{'.$placeholderKey.'}';
			if (mb_strpos($text, $placeholder) !== false) {
				$signature = $signaturePlaceholder->replace($placeholder, $output->isFinal());

				if ($output->isEdit() && $contentType === 'html') {
					// Wert innerhalb einer Markierung setzen um beim Wechsel des Absenders diese mit den Daten des neuen
					// Absenders zu überschreiben. Dadurch dass TinyMCE einem hier viele Steine in den Weg legt ist es
					// nicht innerhalb eines HTML-Tags sondern innerhalb eines HTML-Kommentares
					$text = str_replace($placeholder, '<!--placeholder name="'.$placeholderKey.'"-->'.$signature.'<!--/placeholder-->', $text);
				} else {
					$text = str_replace($placeholder, $signature, $text);
				}
			}
		}

		// --- Feste E-Mail Signatur ---
		if (mb_strpos($text, '{email_signature}') !== false) {

			$signature = $user?->getEmailSignatureContent($contentType, $language, $subObject) ?? '';

			if (!empty($signature)) {
				$signaturePlaceholder = $userSignature->getPlaceholderObject();
				$signature = $signaturePlaceholder->replace($signature, $output->isFinal());

				// TODO schauen ob das noch benötigt wird
				// Schauen ob noch Platzhalter in dem Signature-Template vorhanden sind
				$signaturePlaceholders = (new \Ext_TC_Placeholder_Util())->getPlaceholdersInTemplate($signature);
				// Wenn es noch Platzhalter gibt dann müssen diese mit "" ersetzt werden. Ansonsten werden diese
				// mit in das Placeholder-Objekt unten geschleift und dieses wirft dann Fehler. Im Frontend gibt es nie
				// ein User-Objekt mit dem Platzhalter ersetzt werden könnten
				if (!empty($signaturePlaceholders)) {
					$signature = '';
				}
			}

			if ($output->isEdit() && $contentType === 'html') {
				$text = str_replace('{email_signature}', '<!--placeholder name="email_signature"-->'.$signature.'<!--/placeholder-->', $text);
			} else {
				$text = str_replace('{email_signature}', $signature, $text);
			}
		}

		return $text;
	}

	private function wrapUserSignaturePlaceholders(string $text, bool $mask): string
	{
		$signaturePlaceholders = \Factory::getObject(\Ext_TC_User_Signature::class)
			->getPlaceholderObject()
			->getPlaceholders();

		$placeholders = [...array_keys($signaturePlaceholders), 'email_signature'];

		foreach ($placeholders as $placeholder) {
			if ($mask) {
				$text = str_replace('{'.$placeholder.'}', '['.$placeholder.']', $text);
			} else {
				$text = str_replace('['.$placeholder.']', '{'.$placeholder.'}', $text);
			}
		}

		return $text;
	}

}