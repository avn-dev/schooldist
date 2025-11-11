<?php

namespace Communication\Admin\Components;

use Carbon\Carbon;
use Communication\Dto\Message\Attachment;
use Communication\Dto\Message\Recipient;
use Communication\Enums\MessageOutput;
use Communication\Enums\MessageStatus;
use Communication\Events\MessagesSent;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\CommunicationChannel;
use Communication\Interfaces\Model\CommunicationSubObject;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Services\AddressBook;
use Communication\Services\AddressBook\AddressBookContact;
use Communication\Services\Communication;
use Communication\Services\Communication as CommunicationService;
use Admin\Attributes\Component\Parameter;
use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Enums\Size;
use Admin\Facades\InterfaceResponse;
use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Component\HasParameters;
use Admin\Interfaces\Component\VueComponent;
use Admin\Traits\Component\WithParameters;
use Core\Collection\MessageTransportCollection;
use Core\Exception\ValidatorException;
use Core\Factory\ValidatorFactory;
use Core\Notifications\Channels\MessageTransport;
use Core\Service\NotificationService;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Tc\Entity\EventManagement;
use Tc\Service\Language\Backend;
use Tc\Service\LanguageAbstract;

#[Parameter(name: 'models')]
#[Parameter(name: 'ids')]
#[Parameter(name: 'application')]
#[Parameter(name: 'access')]
#[Parameter(name: 'additional')]
class CommunicationComponent implements VueComponent, HasParameters
{
	use WithParameters;

	const KEY = 'communication';
	const ALLOCATE_COMPONENT_KEY = 'communication.allocate.{message}';
	const MAX_LOADING_MESSAGES = 150;
	// TODO: Je mehr Messages desto mehr Queries werden auch benötigt. Pro Message ca. 7 Queries (Betreff, Adressen, ...)
	const MESSAGES_EACH_REQUEST = 10;
	const MESSAGE_COUNT_BEFORE_QUEUE = 3;
	const MESSAGE_PREVIEW_LIMIT = 20;

	private ?CommunicationService $communication = null;

	private ?LanguageAbstract $l10n = null;

	private static array $recipientsCache = [];

	public static function getVueComponent(Instance $admin): VueComponentDto
	{
		return new VueComponentDto('Communication', '@Communication/admin/components/Communication.vue');
	}

	public function __construct(
		private \Access_Backend $access,
		private Container $container,
		private Instance $admin
	) {}

	public function isAccessible(\Access $access): bool
	{
		$right = $this->parameters->get('access');

		if (!empty($right)) {
			return $access->hasRight($right);
		}

		return true;
	}

	public function rules(): array
	{
		$applications = (new CommunicationService($this->container))->getAllApplications($this->access)->keys();

		return [
			'application' => [Rule::in($applications)],
			'access' => [
				function ($attribute, $value, $fail) {
					if (!is_string($value) && !is_array($value)) {
						$fail(sprintf("The {$attribute} is invalid. [%s]", $value));
					}
				}
			],
			'models' => 'array',
			'models.*' => [
				'string',
				function (string $attribute, mixed $value, \Closure $fail) {
					if (!is_a($value, HasCommunication::class, true)) {
						$fail(sprintf("The {$attribute} is invalid. [%s]", $value));
					}
				}
			],
			'ids' => ['required_with:models', 'array'],
			'ids.*' => 'int',
			'additional' => 'array'
		];
	}

	public function init(Request $request, Instance $admin): ?InitialData
	{
		$communication = $this->communication($request);

		$messages = $communication->messages()
			// TODO: Achtung! pro weiterer Message kommen nochmal ca. 10 weitere Queries
			->limit(self::MESSAGES_EACH_REQUEST)
			->get();

		$thousands = '.';
		$decimal = ',';
		\Ext_TC_Number::class::createNumberFormatPoints(
			\Factory::executeStatic(\Ext_TC_Number::class, 'getNumberFormatSettings'),
			$decimal,
			$thousands
		);

		return (new InitialData([
			'multiple' => $communication->isMassCommunication(),
			'pingInterval' => 2000,
			// Maximale Anzahl an Nachrichten die geladen werden können
			'maxLoading' => \System::d('admin.communication.max_loading', self::MAX_LOADING_MESSAGES),
			'channels' => $communication->getChannels()
				->forget(['notice']) // TODO
				->map(fn(CommunicationChannel $channel) => $channel->getCommunicationConfig()),
			'categories' => \Ext_TC_Communication_Category::query()->get()
				->map(fn($category) => ['id' => (int)$category->id, 'text' => $category->getName(), 'color' => $category->code])
				->values(),
			'status' => collect(MessageStatus::cases())
				->filter(fn($enum) => $enum !== MessageStatus::NULL)
				->map(fn($enum) => ['value' => $enum->value, 'text' => $enum->getLabelText($this->l10n()), 'icon' => $enum->getIcon()]),
			'flags' => collect($communication->getFlags())
				->map(fn($class, $key) => ['value' => $key, 'text' => \Factory::executeStatic($class, 'getTitle', [$this->l10n()])]),
			'accounts' => collect(\Ext_TC_Communication_EmailAccount::getSelectOptions(true, $this->access->getUser()->id))
				->map(fn($text, $key) => ['value' => $key, 'text' => $text])
				->values(),
			'numberFormat' => ['decimal' => $decimal, 'thousands' => $thousands],
			'dateFormat' => strtoupper(\Factory::getObject(\Ext_TC_Gui2_Format_Date::class)->format_js),
			'total' => $communication->total()->count(),
			'messages' => $messages
				->map(fn($message) => $this->buildMessagePreviewPayload($request, $message))
				->values()
		]))
			// TODO aufsplitten, manche Übersetzungen werden erst bei weiteren Aktionen benötigt und können dort mitgeschickt werden
			->l10n([
				'communication.filters' => $this->l10n()->translate('Nachrichten filtern'),
				'communication.filters.search' => $this->l10n()->translate('Suche') . '…',
				'communication.filters.direction' => $this->l10n()->translate('Richtung'),
				'communication.filters.unseen' => $this->l10n()->translate('Ungelesen'),
				'communication.filters.drafts' => $this->l10n()->translate('Entwürfe'),
				'communication.filters.categorized' => $this->l10n()->translate('Kategorien'),
				'communication.filters.flags' => $this->l10n()->translate('Markierungen'),
				'communication.filters.flags.empty' => $this->l10n()->translate('Markierung'),
				'communication.filters.attachments' => $this->l10n()->translate('Anhänge'),
				'communication.filters.status' => $this->l10n()->translate('Status'),
				'communication.filters.channel' => $this->l10n()->translate('Kanal'),
				'communication.filters.account' => $this->l10n()->translate('E-Mail-Konto'),
				'communication.filters.period' => $this->l10n()->translate('Zeitraum'),
				'communication.refresh' => $this->l10n()->translate('Aktualisieren'),
				'communication.messages' => $this->l10n()->translate('Nachrichten'),
				'communication.messages.empty' => $this->l10n()->translate('Keine Nachrichten gefunden.'),
				'communication.messages.loading_limit_reached' => $this->l10n()->translate('Sie haben die maximale Anzahl an Nachrichten, die geladen werden können, erreicht. Es existieren eventuell noch mehr Nachrichten, bitte verfeinern Sie Ihre Anfrage über die Filter.'),
				'communication.messages.selection' => $this->l10n()->translate('%d Nachrichten ausgewählt'),
				'communication.messages.no_selection' => $this->l10n()->translate('Bitte wählen Sie etwas aus'),
				'communication.message.new' => $this->l10n()->translate('Neue Nachricht'),
				'communication.message.new.attachments.take_over.heading' => $this->l10n()->translate('Anhänge übernehmen'),
				'communication.message.new.attachments.take_over.text' => $this->l10n()->translate('Möchten Sie die Anhänge für die neue Nachricht übernehmen?'),
				'communication.message.new.attachments.take_over.yes' => $this->l10n()->translate('Ja, übernehmen'),
				'communication.message.new.attachments.take_over.no' => $this->l10n()->translate('Nein'),
				'communication.message.new.uploads.placeholder' => $this->l10n()->translate('Dateien hierher ziehen oder klicken zum Hochladen.'),
				'communication.message.draft' => $this->l10n()->translate('Entwurf'),
				'communication.message.direction.in' => $this->l10n()->translate('Eingegangene Nachricht'),
				'communication.message.direction.out' => $this->l10n()->translate('Gesendete Nachricht'),
				'communication.message.event' => $this->l10n()->translate('Ereignis'),
				'communication.message.reply' => $this->l10n()->translate('Antworten'),
				'communication.message.reply_all' => $this->l10n()->translate('Allen antworten'),
				'communication.message.forward' => $this->l10n()->translate('Weiterleiten'),
				'communication.message.resend' => $this->l10n()->translate('Erneut senden'),
				'communication.message.assign' => $this->l10n()->translate('Zuweisen'),
				'communication.message.delete' => $this->l10n()->translate('Löschen'),
				'communication.message.discard' => $this->l10n()->translate('Verwerfen'),
				'communication.message.observe' => $this->l10n()->translate('Abonnieren'),
				'communication.message.from' => $this->l10n()->translate('Von'),
				'communication.message.recipient.placeholder' => $this->l10n()->translate('Empfänger suchen'),
				'communication.message.recipient.group_selection' => $this->l10n()->translate('Alle "%s" auswählen'),
				'communication.message.recipient.empty' => $this->l10n()->translate('Keine Empfänger vorhanden'),
				'communication.message.recipient.all' => $this->l10n()->translate('Alle'),
				'communication.message.recipient.select_all' => $this->l10n()->translate('Alle auswählen'),
				'communication.message.to' => $this->l10n()->translate('An'),
				'communication.message.cc' => $this->l10n()->translate('Cc'),
				'communication.message.bcc' => $this->l10n()->translate('Bcc'),
				'communication.message.subject' => $this->l10n()->translate('Betreff'),
				'communication.message.no_subject' => $this->l10n()->translate('Kein Betreff'),
				'communication.message.no_content' => $this->l10n()->translate('Kein Inhalt'),
				'communication.message.subject.intern' => $this->l10n()->translate('Der Inhalt aus dem Betreff wird nur intern verwendet und nicht an die Empfänger gesendet.'),
				'communication.message.add_attachments' => $this->l10n()->translate('Dateien anhängen'),
				'communication.message.attachments.placeholder' => $this->l10n()->translate('Anhänge suchen'),
				'communication.message.attachments.empty' => $this->l10n()->translate('Keine Anhänge vorhanden'),
				'communication.message.add_flags' => $this->l10n()->translate('Markierungen setzen'),
				'communication.message.no_template' => $this->l10n()->translate('Leere Vorlage'),
				'communication.message.delete.confirm.title' => $this->l10n()->translate('Möchten Sie die Nachricht(en) wirklich löschen?'),
				'communication.message.delete.confirm.text' => $this->l10n()->translate('Durch diese Aktion werden die Nachricht(en) und sämtliche Anhänge unwiderruflich gelöscht. Möchten Sie wirklich fortfahren?'),
				'communication.message.resend.confirm.title' => $this->l10n()->translate('Möchten Sie die Nachricht(en) wirklich erneut senden?'),
				'communication.message.resend.confirm.text' => $this->l10n()->translate('Durch das erneute Senden werden die gewählten Nachrichten inklusive eventueller Anhänge noch einmal an alle ursprünglichen Empfänger versendet.'),
				'communication.message.send_all' => $this->l10n()->translate('Dieselbe Nachricht an alle Empfänger senden'),
				'communication.message.send_all.warning' => $this->l10n()->translate('Achtung! Alle ausgewählten Empfänger erhalten dieselbe Nachricht basierend auf dem ersten ausgewählten Eintrag.'),
				'communication.message.btn.back' => $this->l10n()->translate('Zurück'),
				'communication.message.btn.send' => $this->l10n()->translate('Senden'),
				'communication.message.btn.preview' => $this->l10n()->translate('Vorschau'),
				'communication.message.preview.message_label' => $this->l10n()->translate('Nachricht'),
				'communication.message.send.confirm.title' => $this->l10n()->translate('Nachricht senden'),
				'communication.message.send.confirm.empty_subject' => $this->l10n()->translate('Möchten Sie die Nachricht(en) ohne Betreff versenden?')
			]);
	}

	public function loadMessages(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'sync' => 'integer',
			'last_id' => 'integer',
			'search' => 'string',
			'direction' => Rule::in(['in', 'out']),
			'unseen' => 'boolean',
			'drafts' => 'boolean',
			'status' => 'array',
			'status.*' => [new Enum(MessageStatus::class)],
			'channels' => 'array',
			'channels.*' => Rule::in($this->communication($request)->getChannels()->keys()),
			'categories' => 'array',
			'categories.*' => 'integer',
			'account' => 'integer',
			'attachments' => 'boolean',
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		if ((bool)$validated['sync']) {
			(new \Ext_TC_System_CronJob_Update_Imap)->executeUpdate();
		}

		$query = $this->communication($request)->messages()
			->limit(self::MESSAGES_EACH_REQUEST);

		if ($validated['last_id']) {
			$query->where('tc_cm.id', '<', $validated['last_id']);
			$totalQuery = null;
		} else {
			$totalQuery = $this->communication($request)->total();
		}

		// Filter im Query ergänzen
		$filters = function ($query) use ($request) {
			if (!$query) return null;

			$query
				// Suche
				->when(!empty($search = $request->input('search')), function ($query) use ($search) {
					//$query->where('tc_cms.subject', 'like', '%' . $search . '%');
					$query->whereExists(function ($query) use ($search) {
						$query->select('tc_cms_filter.subject')
							->from('tc_communication_messages_subjects as tc_cms_filter')
							->whereColumn('tc_cms_filter.message_id', 'tc_cm.id')
							->where('tc_cms_filter.subject', 'like', '%' . $search . '%');
					});
				})
				// Richtung
				->when(!empty($direction = $request->input('direction')), function ($query) use ($direction) {
					$query->where('tc_cm.direction', $direction);
				})
				// Datum: Von
				->when(!empty($dateStart = $request->input('date_start')), function ($query) use ($dateStart) {
					$date = Carbon::createFromFormat('Y-m-d', $dateStart)->startOfDay();
					if ($date) {
						$query->whereDate('tc_cm.date', '>=', $date);
					}
				})
				// Datum: Bis
				->when(!empty($dateEnd = $request->input('date_end')), function ($query) use ($dateEnd) {
					$date = Carbon::createFromFormat('Y-m-d', $dateEnd)->endOfDay();
					if ($date) {
						$query->whereDate('tc_cm.date', '<=', $date);
					}
				})
				// Gelesen
				->when((bool)$request->input('unseen', false) === true, function ($query) {
					$query->whereNull('tc_cm.seen_at');
					//$query->where('tc_cm.direction', 'in');
				})
				// Entwürfe
				->when((bool)$request->input('drafts', false) === true, function ($query) {
					$query->where(function ($where) {
						$where->whereNull('tc_cm.sent')
							->orWhere('tc_cm.sent', 0);
					});
					$query->whereNull('tc_cm.status');
					$query->where('tc_cm.direction', 'out');
				})
				// Kategorien
				->when(!empty($categories = $request->input('categories', [])), function ($query) use ($categories) {
					//$query->whereIn('tc_cmc.category_id', $categories);
					$query->whereExists(function ($query) use ($categories) {
						$query->select('tc_cmc_filter.category_id')
							->from('tc_communication_messages_to_categories as tc_cmc_filter')
							->whereColumn('tc_cmc_filter.message_id', 'tc_cm.id')
							->whereIn('tc_cmc_filter.category_id', $categories);
					});
				})
				// Markierungen
				->when(!empty($flag = $request->input('flags', null)), function ($query) use ($flag) {
					//$query->where('tc_cmfl.flag', $flag);
					$query->whereExists(function ($query) use ($flag) {
						$query->select('tc_cmfl_filter.id')
							->from('tc_communication_messages_flags as tc_cmfl_filter')
							->whereColumn('tc_cmfl_filter.message_id', 'tc_cm.id')
							->where('tc_cmfl_filter.flag', $flag);
					});
				})
				// Anhänge
				->when((bool)$request->input('attachments', false) === true, function ($query) {
					//$query->whereNotNull('tc_cmf.id');
					$query->whereExists(function ($query) {
						$query->select('tc_cmf.id')
							->from('tc_communication_messages_files as tc_cmf')
							->whereColumn('tc_cmf.message_id', 'tc_cm.id');
					});
				})
				// Status
				->when(!empty($status = $request->input('status')), function ($query) use ($status) {
					$query->whereIn('tc_cm.status', $status);
				})
				// Channel
				->when(!empty($channels = $request->input('channels', [])), function ($query) use ($channels) {
					$query->whereIn('tc_cm.type', array_map(fn($channel) => CommunicationService::MESSAGE_TYPE_CHANNEL_MAPPING[$channel] ?? $channel, $channels));
				})
				// E-Mail-Konto
				->when(($accountId = (int)$request->input('account', 0)) > 0, function ($query) use ($accountId) {
					$query->whereExists(function ($query) use ($accountId) {
						$classes = collect([\Ext_TC_Communication_EmailAccount::class, \Factory::getClassName(\Ext_TC_Communication_EmailAccount::class)])->unique();

						$query->select('tc_cmr_filter.*')
							->from('tc_communication_messages_relations as tc_cmr_filter')
							->whereColumn('tc_cmr_filter.message_id', 'tc_cm.id')
							->whereIn('tc_cmr_filter.relation', $classes)
							->where('tc_cmr_filter.relation_id', $accountId)
						;
					});
				})
			;

			return $query;
		};

		$total = $filters($totalQuery)?->count();
		$messages = $filters($query)->get();

		return response()->json([
			'total' => $total,
			'messages' => $messages->map(fn($message) => $this->buildMessagePreviewPayload($request, $message))
		]);
	}

	public function delete(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'message_ids' => ['required', 'array'],
			'message_ids.*' => 'integer'
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$this->communication($request)->delete(collect($validated['message_ids']));

		return response()->json(['success' => true]);
	}

	public function categorize(Request $request)
	{
		$availableCategories = \Ext_TC_Communication_Category::query()->pluck('id');

		$validator = (new ValidatorFactory())->make($request->all(), [
			'action' => ['required', Rule::in(['add', 'remove'])],
			'category_id' => ['required', Rule::in($availableCategories)],
			'message_ids' => ['required', 'array'],
			'message_ids.*' => 'integer',
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$messageIds = collect($validated['message_ids']);

		if ($validated['action'] === 'add') {
			$this->communication($request)->categorize($messageIds, $validated['category_id']);
		} else if ($validated['action'] === 'remove') {
			$this->communication($request)->decategorize($messageIds, $validated['category_id']);
		}

		return response()->json(['success' => true]);
	}

	public function viewMessage(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'message_id' => ['required', 'integer']
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		/* @var \Ext_TC_Communication_Message $message */
		$message = $this->communication($request)->messages()->findOrFail($validated['message_id']);

		if ($message->isUnseen()) {
			// Nachricht als "Gelesen" markieren
			$message->status = MessageStatus::SEEN->value;
			$message->unseen = 0;
			$message->seen_at = time();
			$message->save();
		}

		// Nachricht zugewiesen zu
		$basedOnRelations = $message->searchRelations(HasCommunication::class);

		return response()
			->json([
				'assignable' => $basedOnRelations->isEmpty(),
				'message' => $this->buildMessageViewPayload($request, $message)
			]);
	}

	public function allocateMessage(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'message_id' => ['required', 'integer']
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$message = $this->communication($request)->messages()->findOrFail((int)$validated['message_id']);

		$action = Router::modal($this->l10n()->translate('Nachricht zuweisen'), self::ALLOCATE_COMPONENT_KEY, ['message' => $message->id], initialize: false)
			->size(Size::LARGE);

		return InterfaceResponse::json(['action' => $action]);
	}

	public function newMessage(Request $request, \Access_Backend $access)
	{
		$communication = $this->communication($request);

		$validator = (new ValidatorFactory())->make($request->all(), [
			'channel' => ['required', Rule::in($communication->getChannels()->keys())],
			// Basierend auf einer anderen Nachricht (antworten, weiterleiten, ...)
			'message_id' => ['integer'],
			'action' => ['required_with:message_id', 'string'],
			'message_attachments' => ['required_with:message_id', Rule::in(0, 1)],
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		/* @var \Ext_TC_Communication_Message $relatedMessage */
		$relatedMessage = ($validated['message_id'] > 0)
			? $communication->messages()->findOrFail($validated['message_id'])
			: null;

		$addressBook = $communication->addressBook();

		$builder = match ($validated['action']) {
			'reply' => $communication->reply($validated['channel'], $relatedMessage),
			'reply_all' => $communication->replyAll($validated['channel'], $relatedMessage),
			'forward' => $communication->forward($validated['channel'], $relatedMessage, (bool)$validated['message_attachments'] ?? false),
			default => $communication->new($validated['channel']),
		};

		/* @var \Ext_TC_User $user */
		$user = $access->getUser();

		// Absender
		$identities = $this->getIdentities($communication, $user, $validated['channel']);

		$builder->from($user);

		if (
			$builder->getContentType() === 'html' &&
			!empty($defaultLayout = $this->getDefaultLayout($communication->getSubObject()))
		) {
			$builder->layout($defaultLayout);
		}

		$interface = $this->buildDefaultNewMessagePayload($request);

		$builder->language($interface['languages']?->first() ?? 'en');

		$resolveRecipients = function (Collection $recipients) use ($addressBook, $validated) {
			$final = [];

			foreach ($recipients as $recipient) {
				if ($recipient instanceof AddressBookContact) {
					$final[] = Arr::only($recipient->toArray($this->l10n(), $validated['channel']), ['value', 'text']);
				} else if (is_array($recipient) && $recipient[0] instanceof AddressBookContact) {
					$payload = $recipient[0]->toArray($this->l10n(), $validated['channel']);
					$routeIndex = $recipient[1];
					if (isset($payload['routes'][$routeIndex])) {
						$final[] = $payload['routes'][$routeIndex];
					} else {
						$final[] = Arr::only($payload, ['value', 'text']);
					}
				} else {
					$final[] = ['value' => $recipient->getRoute(), 'text' => sprintf('%s <%s>', $recipient->getName(), $recipient->getRoute())];
				}
			}
			return $final;
		};

		$attachments = $builder->getAttachments();
		$subject = $builder->getSubject();
		$content = $builder->getContent();

		$errors = [];

		// In der einfachen Kommunikation Platzhalter direkt ersetzen
		if (!$communication->isMassCommunication()) {
			$basedOn = $this->communication($request)->getBasedOnModels()->first();

			[$subject, $subjectErrors] = $builder->replacePlaceholders($basedOn, $subject, MessageOutput::EDIT);
			[$content, $contentErrors] = $builder->replacePlaceholders($basedOn, $content, MessageOutput::EDIT);

			$errors = [...$subjectErrors, ...$contentErrors];
		}

		return response()
			->json(array_merge_recursive(
					[
						'multiple' => $communication->getBasedOnModels()->count() > 1,
						'identities' => collect($identities)
							->map(fn($text, $key) => ['value' => $key, 'text' => $text])
							->values()
							->toArray(),
						'contacts' => $addressBook->getContacts($validated['channel'])
							->map(fn(AddressBookContact $addressBookContact) => $addressBookContact->toArray($this->l10n(), $validated['channel']))
							->toArray(),
						'attachments' => $attachments
							->map(fn(Attachment $attachment) => $attachment->toArray($this->l10n()))
							->values()
							->toArray(),
						'alerts' => array_map(fn ($error) => is_string($error) ? ['type' => 'error', 'message' => $error] : $error, $errors),
						'message' => [
							'id' => Str::random(6),
							'content_type' => $builder->getContentType(),
							'from' => (int)$builder->getFrom()->id,
							'to' => $resolveRecipients($builder->getTo()),
							'cc' => $resolveRecipients($builder->getCc()),
							'bcc' => $resolveRecipients($builder->getBcc()),
							'send_individually' => $communication->getBasedOnModels()->count() > 1,
							'subject' => $subject,
							'content' => $content,
							'attachments' => $attachments
								->map(fn(Attachment $attachment) => ['value' => $attachment->getKey(), 'text' => $attachment->getFileName()])
								->toArray(),
						],
					], $interface)
			);
	}

	public function newNotice(Request $request, \Access_Backend $access)
	{
		// TODO

		$l10n = $this->l10n();

		return InterfaceResponse::json([
			'types' => [
				['value' => 'call', 'text' => $l10n->translate('Anruf')],
				['value' => 'email', 'text' => $l10n->translate('E-Mail')],
				['value' => 'fax', 'text' => $l10n->translate('Fax')],
				['value' => 'conversation', 'text' => $l10n->translate('Persönliches Gespräch')],
			],
			'contacts' => []
		])
			->l10n([
				'communication.notice.type' => $l10n->translate('Art'),
				'communication.notice.to' => $l10n->translate('Gesprächspartner'),
				'communication.notice.direction.in' => $l10n->translate('Eingehend'),
				'communication.notice.direction.out' => $l10n->translate('Ausgehend'),
				'communication.notice.subject' => $l10n->translate('Betreff'),
				'communication.notice.date_time' => $l10n->translate('Datum/Uhrzeit'),
			]);
	}

	// TODO Wird nicht benutzt
	/*public function searchContacts(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'channel' => ['required', Rule::in($this->communication()->getChannels()->keys())],
			// Basierend auf einer anderen Nachricht (antworten, weiterleiten, ...)
			'message_id' => ['integer']
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$channel = $request->input('channel');
		$query = (string)$request->input('query', '');

		if (!empty($query)) {
			$contacts = $this->communication()->addressBook($channel)->search($query, $this->l10n());
		} else {
			$contacts = $this->communication()->addressBook($channel)->all($this->l10n());
		}

		[$contacts, $groups] = $this->buildAddressCollectionPayload($channel, $contacts);

		return response()
			->json([
				'groups' => $groups,
				'contacts' => $contacts
			]);
	}*/

	public function prepareSending(Request $request)
	{
		$invalidRecipients = [];
		$count = 0;

		if (!empty($request->input('to')) || !empty($request->input('cc')) || !empty($request->input('bcc'))) {
			[$messages, $invalidRecipients] = $this->buildSendingMessages($request, MessageOutput::PREVIEW);
			$count = $messages->count();
		}

		$interface = $this->buildDefaultNewMessagePayload($request);

		return response()
			->json(array_merge_recursive(
				[
					'messages' => $count,
					'invalid_recipients' => $invalidRecipients
				],
				$interface
			));
	}

	public function previewSending(Request $request)
	{
		[$messages, ] = $this->buildSendingMessages($request, MessageOutput::PREVIEW);

		return response()->json([
			'total' => $messages->count(),
			'messages' => $messages
				// Payload in der Massenkommunikation verringern
				->slice(0, self::MESSAGE_PREVIEW_LIMIT)
				->map(fn($message) => $this->buildMessageViewPayload($request, $message[0], $message[1])),
		]);
	}

	public function send(Request $request)
	{
		[$messages, $invalidRecipients] = $this->buildSendingMessages($request, MessageOutput::FINAL);

		$transports = new MessageTransportCollection();

		if ($invalidRecipients->isNotEmpty()) {
			$transports->push(
				new MessageTransport(false, [
					$this->l10n()->translate('Die Nachricht enthält ungültige Empfänger.')
				])
			);
		} else {
			// Ab einer bestimmten Anzahl an Nachrichten über das PP gehen
			$prio = $messages->count() >= (int)\System::d('admin.communication.message_count_before_queue', self::MESSAGE_COUNT_BEFORE_QUEUE) ? 1 : 0;
			//$prio = 1; // PP

			foreach ($messages as $index => $messagePayload) {
				/**
				 * @var \Ext_TC_Communication_Message $message
				 * @var HasCommunication $basedOn
				 */
				[$message, $errors, $basedOn] = $messagePayload;

				if (empty($errors)) {
					$transport = $this->communication($request)->send($message, $basedOn, $prio);
					//$transport = new MessageTransport((bool)random_int(0, 1), ['Test Fehler']);
				} else {
					$transport = new MessageTransport(false, $errors);
				}

				$transports->put($index, $transport);
			}
		}

		MessagesSent::dispatch($transports);

		return $this->buildMessageTransportResponse($request, $transports);
	}

	public function resend(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'message_ids' => ['required', 'array'],
			'message_ids.*' => ['required', 'integer']
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		/* @var \Ext_TC_Communication_Message $message */
		$messages = $this->communication($request)->messages()
			->where('direction', 'out')
			->findMany($validated['message_ids']);

		$modelClasses = $this->parameters->get('models', [HasCommunication::class]);

		$transports = new MessageTransportCollection();

		foreach ($messages as $index => $message) {

			\DB::begin('communication-resend-' . $message->id);

			try {

				$basedOn = $message->searchRelations($modelClasses)->first();

				/* @var \Ext_TC_Communication_Message $send */
				$send = ($message->isDraft() || $message->status === MessageStatus::FAILED) ? $message : $message->createCopy();
				$transport = $this->communication($request)->send($send, $basedOn);

				//$transport = new MessageTransport((bool)random_int(0, 1), ['Test Fehler']);

			} catch (\Throwable $e) {
				NotificationService::getLogger()->error('Resending communication message failed', ['log_id' => $message->id, 'exception' => $e, 'file' => $e->getFile(), 'line' => $e->getLine()]);
				$transport = new MessageTransport(false, [$e]);
			}

			if (!$transport->successfully()) {
				\DB::rollback('communication-resend-' . $message->id);
			} else {
				\DB::commit('communication-resend-' . $message->id);
			}

			$transports->put($index, $transport);
		}

		MessagesSent::dispatch($transports);

		return $this->buildMessageTransportResponse($request, $transports);
	}

	private function buildMessageTransportResponse(Request $request, MessageTransportCollection $transports)
	{
		[$successfully, $failed] = $transports->partition(fn(MessageTransport $transport) => $transport->successfully());

		[$sent, $queued] = $successfully->partition(fn(MessageTransport $transport) => !$transport->isQueued());

		$buildSuccessfullyEntry = function (MessageTransportCollection $transports, string $message) use ($request) {
			$payload = [];
			$payload['success'] = true;
			$payload['messages'] = $transports
				->filter(fn(MessageTransport $transport) => !empty($log = $transport->getLog()) && $log->exist())
				->map(fn(MessageTransport $transport) => $this->buildMessagePreviewPayload($request, $transport->getLog()));
			$payload['alerts'] = [
				[
					'type' => 'success',
					'heading' => $transports->keys()->map(fn($index) => sprintf($this->l10n()->translate('Nachricht %d'), $index + 1))->implode(', '),
					'message' => $message
				]
			];
			return $payload;
		};

		$successfullyPayload = [];
		if ($sent->isNotEmpty()) {
			$successfullyPayload[] = $buildSuccessfullyEntry($sent, $this->l10n()->translate('Erfolgreich gesendet.'));
		}

		if ($queued->isNotEmpty()) {
			$successfullyPayload[] = $buildSuccessfullyEntry($queued, $this->l10n()->translate('Nachricht wird im Hintergrund versendet.'));
		}

		return response()->json([
			'status' => $transports->getStatus(),
			'messages' => $failed
				->map(function (MessageTransport $transport, $index) use ($request) {
					$payload = [];
					$payload['success'] = $transport->successfully();
					$payload['messages'] = (!empty($log = $transport->getLog()) && $log->exist())
						? [$this->buildMessagePreviewPayload($request, $log)]
						: [];

					$payload['alerts'] = array_map(function ($message) use ($index) {
						$heading = sprintf($this->l10n()->translate('Nachricht %d'), $index + 1);
						if (is_array($message)) {
							$message['heading'] ??= $heading;
							return $message;
						}
						return ['type' => 'error', 'heading' => $heading, 'message' => $message];
					}, $transport->getErrorMessages($this->l10n()));

					return $payload;
				})
				->merge($successfullyPayload)
		]);
	}

	public function reloadSignature(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'channel' => ['required', Rule::in($this->communication($request)->getChannels()->keys())],
			'from' => ['required', 'integer'],
			'language' => ['string', 'size:2'],
			'content_type' => ['required', Rule::in(['html', 'text'])],
			'content' => ['required', 'string'],
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$communication = $this->communication($request);

		if (!$communication->isMassCommunication()) {
			$builder = $communication->new($validated['channel']);
			$builder->language($validated['language']);
			$builder->contentType($validated['content_type']);

			/* @var \Ext_TC_User $sender */
			$sender = \Factory::getInstance(\Ext_TC_User::class, $validated['from']);
			$builder->from($sender);

			$basedOn = $communication->getBasedOnModels()->first();

			[$content, ] = $builder->replacePlaceholders($basedOn, $validated['content'], MessageOutput::EDIT);
		} else {
			$content = $validated['content'];
		}

		return response()
			->json(['content' => $content]);

	}

	public function loadTemplate(Request $request, \Access_Backend $access)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'channel' => ['required', Rule::in($this->communication($request)->getChannels()->keys())],
			'from' => ['required', 'integer'],
			'content_type' => ['required', Rule::in(['html', 'text'])],
			'template_id' => ['required', 'integer'],
			'language' => ['string', 'size:2'],
			// Basierend auf einer anderen Nachricht (antworten, weiterleiten, ...)
			'message_id' => ['integer'],
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$communication = $this->communication($request);

		$builder = $communication->new($validated['channel']);
		$builder->language($validated['language']);
		$builder->contentType($validated['content_type']);

		$interface = $this->buildDefaultNewMessagePayload($request);

		if ($interface['message']['template_id'] > 0) {
			$template = \Ext_TC_Communication_Template::getInstance($interface['message']['template_id']);
			$builder->template($template);
		} else if (
			$validated['content_type'] === 'html' &&
			!empty($defaultLayout = $this->getDefaultLayout($communication->getSubObject()))
		) {
			$builder->layout($defaultLayout);
		}

		/* @var \Ext_TC_Communication_Message $relatedMessage */
		$relatedMessage = ($validated['message_id'] > 0)
			? $communication->messages()->findOrFail($validated['message_id'])
			: null;

		if ($relatedMessage) {
			$builder->content($relatedMessage->content, \Factory::getObject(\Ext_TC_Gui2_Format_Date_Time::class)->formatByValue($relatedMessage->date));
		}

		$identities = $this->getIdentities($communication, $access->getUser(), $validated['channel']);

		$from = $validated['from'];

		// Defaultabsender des Templates
		if (
			!empty($sender = $builder->getFrom()) &&
			$sender instanceof \Ext_TC_User
		) {
			if (!isset($identities[$sender->id])) {
				$identity = $this->getIdentities($communication, $sender, $validated['channel'])[$sender->id];
				$identities[$sender->id] = $identity;
			}

			$from = $sender->id;
		} else {
			/* @var \Ext_TC_User $sender */
			$sender = \Factory::getInstance(\Ext_TC_User::class, $validated['from']);
			$builder->from($sender);
		}

		$subject = $builder->getSubject();
		$content = $builder->getContent(true);

		$errors = [];

		// In der einfachen Kommunikation Platzhalter direkt ersetzen
		if (!$communication->isMassCommunication()) {
			$basedOn = $this->communication($request)->getBasedOnModels()->first();

			[$subject, $subjectErrors] = $builder->replacePlaceholders($basedOn, $subject, MessageOutput::EDIT);
			[$content, $contentErrors] = $builder->replacePlaceholders($basedOn, $content, MessageOutput::EDIT);

			$errors = [...$subjectErrors, ...$contentErrors];
		}

		return response()
			->json(array_merge_recursive([
				'identities' => collect($identities)
					->map(fn($text, $key) => ['value' => $key, 'text' => $text])
					->values()
					->toArray(),
				'alerts' => array_map(fn ($error) => is_string($error) ? ['type' => 'error', 'message' => $error] : $error, $errors),
				'message' => [
					'from' => $from,
					'cc' => $builder->getCc()->map(fn(Recipient $recipient, $index) => ['value' => $index, 'text' => $recipient->getRoute(), 'additional' => ['template' => true]]),
					'bcc' => $builder->getBcc()->map(fn(Recipient $recipient, $index) => ['value' => $index, 'text' => $recipient->getRoute(), 'additional' => ['template' => true]]),
					'content_type' => $builder->getContentType(),
					'subject' => $subject,
					'content' => $content,
				]
			], $interface));
	}

	public function pingMessageStatus(Request $request)
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'message_ids' => ['required', 'array'],
			'message_ids.*' => 'integer'
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$messages = $this->communication($request)->messages()
			->whereIn('tc_cm.id', $validated['message_ids'])
			->get();

		$status = $messages
			->map(function (\Ext_TC_Communication_Message $message) {
				$status = null;
				if (!empty($message->status)) {
					$enum = MessageStatus::from($message->status);
					$status = ['value' => $enum->value, 'icon' => $enum->getIcon(), 'text' => $enum->getLabelText($this->l10n())];
				}
				return ['message_id' => $message->id, 'status' => $status];
			})
			->values();

		return response()->json(['status' => $status]);
	}

	private function communication(Request $request = null): CommunicationService
	{
		$application = $this->parameters->get('application');

		if (!$this->communication) {
			$models = null;
			if (!empty($ids = $this->parameters->get('ids'))) {
				$modelClasses = $this->parameters->get('models');
				$models = collect($ids)
					->mapToGroups(function ($id, $index) use ($modelClasses) {
						// Falls es nur eine Modelklasse gibt muss immer diese genommen werden
						$class = $modelClasses[$index] ?? Arr::first($modelClasses);
						return [$class => $id];
					})
					->map(fn($classIds, $class) => $class::query()->whereIn('id', $ids)->get())
					->flatten();
			}

			$this->communication = (new CommunicationService($this->container))
				->basedOn($models ?? collect())
				->setL10n($this->l10n())
				->additional($this->parameters->get('additional', []));

			if (!empty($application)) {
				$this->communication->application($application);
			}
		}

		$communication = $this->communication;

		if ($request) {

			/**
			 * Basierend auf einer anderen Nachricht
			 */

			$relatedMessageId = (int)$request->input('message_id');

			if ($relatedMessageId > 0) {
				/* @var \Ext_TC_Communication_Message $relatedMessage */
				$relatedMessage = $communication->messages()->findOrFail($relatedMessageId);

				$modelClasses = $this->parameters->get('models', [HasCommunication::class]);

				$models = $relatedMessage->searchRelations($modelClasses);

				if ($models->isNotEmpty()) {
					$baseModel = $models->first();

					$models = $models->filter(fn ($loop) => $loop::class === $baseModel::class);

					$communication = $communication->basedOn($models);

					if (empty($application)) {
						$communication->application($baseModel->getCommunicationDefaultApplication());
					}
				}
			}

			/**
			 * Basierend auf Empfänger
			 */

			if ($request->has('channel') && $request->has('to')) {
				$channel = $request->input('channel');

				$addressBook = $communication->addressBook($channel);

				// Frontend-Values mit Adressbuch abgleichen
				[$addressBookContacts,] = $this->matchRecipientsAgainstAddressBookContacts($channel, $request->input('to', []), $addressBook);

				$basedOn = $addressBookContacts
					->map(function ($recipient) {
						$addressBookContact = is_array($recipient) ? $recipient[0] : $recipient;
						return $addressBookContact->getSource();
					})
					->filter(fn($source) => !empty($source))
					->mapWithKeys(fn($source) => [$source->id => $source]);

				if ($basedOn->isNotEmpty()) {
					$communication = $communication->basedOn($basedOn->values());
				}
			}
		}

		return $communication;
	}

	/**
	 * TODO das gefällt mir noch nicht, funktioniert aber fürs Erste
	 *
	 * @param Request $request
	 * @return array
	 * @throws \Exception
	 */
	private function buildDefaultNewMessagePayload(Request $request): array
	{
		$validator = (new ValidatorFactory())->make($request->all(), [
			'channel' => ['required', Rule::in($this->communication($request)->getChannels()->keys())],
			'to' => ['array'],
			'to.*' => ['string'],
			'cc' => ['array'],
			'cc.*' => ['string'],
			'bcc' => ['array'],
			'bcc.*' => ['string'],
			'template_id' => ['integer'],
			'language' => ['string', 'size:2'],
			// Basierend auf einer anderen Nachricht (antworten, weiterleiten, ...)
			'message_id' => ['integer'],
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$channel = $request->input('channel');
		$chosenTemplateId = (int)$request->input('template_id');
		$chosenLanguage = $request->input('language');

		$communication = $this->communication($request);

		$addressBook = $communication->addressBook();

		$recipientKeys = $languages = collect([]);
		if ($request->has('to') || $request->has('cc') || $request->has('bcc')) {
			// Frontend-Values mit Adressbuch abgleichen
			[$addressBookContacts,] = $this->matchRecipientsAgainstAddressBookContacts($channel, $request->input('to', []), $addressBook);
			//$matchedCc = $this->matchRecipientsAgainstContacts($request->input('cc'), $contacts);
			//$matchedBcc = $this->matchRecipientsAgainstContacts($request->input('bcc'), $contacts);

			/* @var AddressContactsCollection $addressBookContacts */
			$languages = $addressBookContacts->getCorrespondingLanguages();
			$recipientKeys = $addressBookContacts->getRecipientKeys();
		}

		if ($languages->isEmpty() || $recipientKeys->isEmpty()) {
			$allContacts = $addressBook->getContacts($channel, false);

			if ($languages->isEmpty()) {
				// Fallback auf alle Kontakte im Adressbuch
				$languages = $allContacts->getCorrespondingLanguages();
			}
			if ($recipientKeys->isEmpty()) {
				$recipientKeys = $allContacts->getRecipientKeys();
			}
		}

		if ($languages->isEmpty()) {
			// Fallback auf zentrale Einstellungen
			$languages = collect(\Factory::executeStatic('Ext_TC_Object', 'getLanguages', [true]))
				->keys();
		}

		$templates = $this->searchTemplates($communication, $channel, $languages, $recipientKeys);

		$chosenFlags = $chosenAttachments = [];

		$template = null;
		if ($chosenTemplateId > 0 && $templates->has($chosenTemplateId)) {
			$template = \Ext_TC_Communication_Template::getInstance($chosenTemplateId);
			$languages = $languages->intersect($template->languages);
			$chosenFlags = $template->flags;
		} else {
			$chosenTemplateId = 0;
		}

		if (empty($chosenLanguage) || !$languages->contains($chosenLanguage)) {
			$chosenLanguage = $languages->first();
		}

		[$attachments, $selected] = $communication->fileManager($channel)
			->setTemplate($template)
			->getFiles($chosenLanguage);

		foreach ($selected as $templateFile) {
			$chosenAttachments[] = ['value' => $templateFile->getKey(), 'text' => $templateFile->getFileName(), 'additional' => ['source' => 'template']];
		}

		$flags = $communication->getFlags()
			->filter(fn($class) => $recipientKeys->intersect(\Factory::executeStatic($class, 'getRecipientKeys'))->isNotEmpty());

		return [
			'templates' => $templates
				->map(fn($text, $key) => ['value' => $key, 'text' => $text])
				->values()
				->toArray(),
			'languages' => $languages,
			'attachments' => $attachments
				->map(fn(Attachment $attachment) => $attachment->toArray($this->l10n()))
				->values()
				->toArray(),
			'flags' => $flags
				->map(fn($class, $flag) => [
					'value' => $flag,
					'text' => \Factory::executeStatic($class, 'getTitle', [$this->l10n()]),
					'icon' => 'fas fa-thumbtack'
				])
				->values()
				->toArray(),
			'message' => [
				'template_id' => $chosenTemplateId,
				'language' => $chosenLanguage,
				'flags' => $chosenFlags,
				'attachments' => $chosenAttachments,
			]
		];
	}

	private function buildMessagePreviewPayload(Request $request, \Ext_TC_Communication_Message $message)
	{
		$content = ($message->content_type === 'html')
			? strip_tags($this->communication($request)->contentManager()->extractBody($message->content, true))
			: strip_tags($message->content);

		$date = Carbon::createFromTimestamp($message->date);

		$group = match ($date->toDateString()) {
			Carbon::today()->toDateString() => $this->l10n()->translate('Heute'),
			Carbon::yesterday()->toDateString() => $this->l10n()->translate('Gestern'),
			default => $this->l10n()->translate('Älter'),
		};

		$status = null;
		if (!in_array($message->type, ['notice']) && $message->direction === 'out') {
			if ($message->seen_at !== null) {
				$text = sprintf('%s: %s', $this->l10n()->translate('Gelesen'), \Factory::getObject(\Ext_TC_Gui2_Format_Date::class)->formatByValue($message->seen_at));
				$status = ['value' => MessageStatus::SEEN->value, 'icon' => 'fas fa-check-double', 'text' => $text];
			} else if ($message->status !== null) {
				$enum = MessageStatus::from($message->status);
				$status = ['value' => $enum->value, 'icon' => $enum->getIcon(), 'text' => $enum->getLabelText($this->l10n())];
			}
		}

		$event = $message->searchRelations(EventManagement::class)->first();

		return [
			'id' => $message->id,
			'contact' => ($message->direction === 'in')
				? strip_tags($message->getFormattedContacts('from'))
				: strip_tags($message->getFormattedContacts('to')),
			'direction' => $message->direction,
			'draft' => $message->isDraft(),
			'unseen' => $message->isUnseen(),
			'subject' => strip_tags($message->subject),
			'date' => \Factory::getObject(\Ext_Gui2_View_Format_Date_Time::class)->formatByValue($message->date),
			'content' => strlen($content > 100) ? mb_substr($content, 0, 100) : $content,
			'has_attachments' => !empty($message->files),
			'has_flags' => !empty($message->flags),
			'status' => $status,
			'categories' => array_map('intval', $message->categories),
			'channel' => $message->getChannel(),
			'group' => $group,
			'event'	=> $event?->name,
		];
	}

	private function buildMessageViewPayload(Request $request, \Ext_TC_Communication_Message $message, array $errors = [])
	{
		$flagKeys = array_map(fn($flag) => $flag->flag, $message->getJoinedObjectChilds('flags', true));

		$flags = [];
		if (!empty($flagKeys)) {
			$flags = $this->communication($request)->getAllFlags()
				->only($flagKeys)
				->map(fn($class) => \Factory::executeStatic($class, 'getTitle', [$this->l10n()]))
				->values();
		}

		$attachments = collect($message->getJoinedObjectChilds('files', true));

		$event = $message->searchRelations(EventManagement::class)->first();

		return [
			'id' => (int)$message->id,
			'direction' => $message->direction,
			'draft' => $message->isDraft(),
			'from' => strip_tags($message->getFormattedContacts('from')),
			'to' => strip_tags($message->getFormattedContacts('to')),
			'cc' => strip_tags($message->getFormattedContacts('cc')),
			'bcc' => strip_tags($message->getFormattedContacts('bcc')),
			'date' => \Factory::getObject(\Ext_TC_Gui2_Format_Date_Time::class)->formatByValue($message->date),
			'subject' => strip_tags($message->subject),
			'content' => $message->getContent(),
			'categories' => array_map('intval', $message->categories),
			'has_attachments' => $attachments->isNotEmpty(),
			'attachments' => $attachments->map(function ($attachment) {
				$filePath = storage_path(Str::after($attachment->file, 'storage/'));
				return ['icon' => 'fas fa-paperclip', 'file' => $attachment->file, 'file_name' => $attachment->name, 'file_size' => \Util::formatFilesize(filesize($filePath))];
			})
				->values(),
			'flags' => $flags,
			'channel' => $message->getChannel(),
			'event'	=> $event?->name,
			'errors' => $errors
		];
	}

	private function buildSendingMessages(Request $request, MessageOutput $output): array
	{
		$required = $output->isFinal() ? ['required'] : [];

		$validator = (new ValidatorFactory())->make($request->all(), [
			'channel' => ['required', Rule::in($this->communication($request)->getChannels()->keys())],
			// Basierend auf einer anderen Nachricht (antworten, weiterleiten, ...)
			'message_id' => ['integer'],
			'action' => ['required_with:message_id', 'string'],
			'id' => ['required'],
			'from' => ['integer', ...$required],
			'to' => ['array'],
			'to.*' => ['string'],
			'cc' => ['array'],
			'cc.*' => ['string'],
			'bcc' => ['array'],
			'bcc.*' => ['string'],
			'subject' => ['string'],
			'content_type' => ['string', ...$required],
			'content' => ['string'],
			'template_id' => ['integer'],
			'language' => ['required_with:template_id', 'string', 'size:2'],
			'flags' => ['array'],
			'flags.*' => ['string'],
			'attachments' => ['array'],
			'attachments.*' => ['string'],
			'files' => ['array'],
			'files.*' => ['file'],
			'send_individually' => ['required', Rule::in(0, 1)],
			'confirmed_errors' => ['array'],
			'confirmed_errors.*' => ['string'],
		]);

		if ($validator->fails()) {
			throw new ValidatorException($validator);
		}

		$validated = $validator->validated();

		$communication = $this->communication($request);

		/* @var \Ext_TC_Communication_Message $relatedMessage */
		$relatedMessage = ($validated['message_id'] > 0)
			? $communication->messages()->findOrFail($validated['message_id'])
			: null;

		$addressBook = $communication->addressBook();

		$builder = $communication->new($validated['channel']);
		$builder->language($validated['language']);

		// Absender
		/* @var \Ext_TC_User $sender */
		$sender = \Factory::getInstance(\Ext_TC_User::class, $validated['from']);
		$builder->from($sender);

		if ($relatedMessage) {
			$builder->related($relatedMessage);
		}

		$template = ($validated['template_id'] > 0)
			? \Ext_TC_Communication_Template::getInstance($validated['template_id'])
			: null;

		// Empfänger
		$recipientsTo = $this->resolveRecipients($validated['channel'], $validated['to'] ?? [], $addressBook);
		$recipientsCc = $this->resolveRecipients($validated['channel'], $validated['cc'] ?? [], $addressBook);
		$recipientsBcc = $this->resolveRecipients($validated['channel'], $validated['bcc'] ?? [], $addressBook);

		if ($recipientsTo->isEmpty() && $recipientsCc->isEmpty() && $recipientsBcc->isEmpty()) {
			return [collect(), collect()];
		}

		$builder->to($recipientsTo->toArray());
		$builder->cc($recipientsCc->toArray());
		$builder->bcc($recipientsBcc->toArray());

		// Inhalt
		if ($template) {
			$builder->template($template, adoptContent: false);
		}

		$builder->subject($validated['subject'] ?? '');
		$builder->contentType($validated['content_type'] ?? '');
		$builder->content($validated['content'] ?? '');
		$builder->confirmError($validated['confirmed_errors'] ?? []);

		// Markierungen
		if (!empty($validated['flags'])) {
			$flags = $communication->getAllFlags()->only($validated['flags'])->values();
			$builder->flag($flags->toArray());
		}

		// Anhänge
		[$allAttachments, ] = $communication->fileManager($validated['channel'])
			->setTemplate($template)
			->getFiles($validated['language']);

		if (!empty($validated['attachments'])) {
			foreach ($validated['attachments'] as $attachmentKey) {
				$attachments = [];
				if ($attachmentKey === 'all') {
					$attachments = $allAttachments;
				} else if ($allAttachments->getGroups()->contains($attachmentKey)) {
					$attachments = $allAttachments->getByGroup($attachmentKey);
				} else if (
					str_starts_with($attachmentKey, 'custom::') ||
					str_starts_with($attachmentKey, 'template::')
				) {
					[, $filePath, $fileName] = explode('::', $attachmentKey);
					if (!empty($filePath)) {
						$filePath = storage_path(Str::after($filePath, 'storage/'));
						if (file_exists($filePath)) {
							$attachments[] = new Attachment($attachmentKey, $filePath, $fileName);
						}
					}
				} else {
					$attachment = $allAttachments->first(fn(Attachment $loop) => $loop->getKey() === $attachmentKey);
					if ($attachment) {
						$attachments = [$attachment];
					}
				}

				$builder->attachment($attachments);
			}
		}

		// Uploads - temporäres Verzeichnis (siehe \Ext_TC_Communication_Message_File::save())
		if (!empty($validated['files'])) {
			$uploadDirectory = storage_path('tc/communication/out/tmp/' . \Util::getCleanPath($validated['id']));
			\Util::checkDir($uploadDirectory);

			foreach ($validated['files'] as $file) {
				/* @var \Illuminate\Http\UploadedFile $file */
				$moved = $file->move($uploadDirectory, \Util::getCleanFilename($file->getClientOriginalName()));
				$builder->attachment(new Attachment('upload:' . md5($moved->getRealPath()), $moved->getRealPath(), $moved->getFilename()));
			}
		}

		if ((bool)$validated['send_individually'] ?? true) {
			[$messages, $invalidRecipients] = $builder->buildIndividually($output);
		} else {
			[$messages, $invalidRecipients] = $builder->build($output);
		}

		foreach (['to', 'cc', 'bcc'] as $key) {
			if ($invalidRecipients->has($key)) {
				$recipientKeys = array_keys($invalidRecipients->get($key));
				$invalidRecipients->put($key, array_values(array_map(fn($recipientKey) => Str::before($recipientKey, '{||}'), $recipientKeys)));
			}
		}

		return [$messages, $invalidRecipients];
	}

	/**
	 * @param string $channel
	 * @param array $recipients
	 * @param AddressBook $addressBook
	 * @return array [$addressBookContacts, $unknown]
	 */
	private function matchRecipientsAgainstAddressBookContacts(string $channel, array $recipients, AddressBook $addressBook): array
	{
		$contacts = $addressBook->getContacts($channel);

		$groups = $contacts->getGroups();

		$resolvedContacts = $contacts->mapWithKeys(function (\Communication\Services\AddressBook\AddressBookContact $address) use ($channel) {
			$key = $address->getKey();
			$routes = $address->getRoutes($channel);

			$return = [$key => $address];
			foreach ($routes as $index => $route) {
				$return[$key . '::' . $index] = [$address, $index];
			}

			return $return;
		});

		$addressBookContacts = $unknown = [];
		foreach ($recipients as $index => $recipientKey) {
			if (isset(self::$recipientsCache[$recipientKey])) {
				$payload = self::$recipientsCache[$recipientKey];
			} else {
				$matches = [];
				preg_match('/^(.*?)(?:\[(.+)\])?$/', $recipientKey, $matches);

				$recipient = $matches[1];

				if ($recipient === 'all') {
					$payload = $contacts->filter(fn(AddressBookContact $contact) => $contact->isEnabledForAllSelection())->all();
				} else if ($groups->contains($recipient)) {
					$payload = $contacts->getByGroup($recipient);
				} else if ($resolvedContacts->has($recipient)) {
					$payload = [$resolvedContacts->get($recipient)];
				} else {
					$payload = [new Recipient($recipient)];
				}

				self::$recipientsCache[$recipientKey] = $payload;
			}

			foreach ($payload as $recipientIndex => $recipient) {
				if (
					$recipient instanceof AddressBookContact ||
					(is_array($recipient) && $recipient[0] instanceof AddressBookContact)
				) {
					$addressBookContacts[$recipientKey . '{||}' . $index . '{||}' . $recipientIndex] = $recipient;
				} else {
					$unknown[$recipientKey . '{||}' . $index . '{||}' . $recipientIndex] = $recipient;
				}
			}
		}

		return [new AddressContactsCollection($addressBookContacts), collect($unknown)];
	}

	private function resolveRecipients(string $channel, array $recipients, AddressBook $addressBook): Collection
	{
		[$addressBookContacts, $unknown] = $this->matchRecipientsAgainstAddressBookContacts($channel, $recipients, $addressBook);

		$final = [];
		foreach ([...$addressBookContacts, ...$unknown] as $recipientKey => $recipient) {
			if (is_array($recipient) && $recipient[0] instanceof AddressBookContact) {
				$resolved = $recipient[0]->toRecipient($channel, $recipient[1]);
			} else if ($recipient instanceof AddressBookContact) {
				$resolved = $recipient->toRecipient($channel);
			} else {
				$resolved = [$recipient];
			}

			foreach ($resolved as $index => $resolvedRecipient) {
				$final[$recipientKey . '{||}' . $index] = $resolvedRecipient;
			}
		}

		return collect($final);
	}

	private function searchTemplates(\Communication\Services\Communication $communication, string $channel, Collection $languages = null, Collection $recipientKeys = null): Collection
	{
		$basedOn = $communication->getBasedOnModels();

		if ($basedOn->isEmpty()) {
			// TODO
			$templates = collect();
		} else {
			$templates = \Ext_TC_Communication_Template::getSelectOptions($channel, [
				'application' => $this->parameters->get('application'),
				'languages' => (array)$languages?->toArray(),
				'recipient' => (array)$recipientKeys?->toArray(),
				'sub_objects' => $communication->getBasedOnModels()
					->map(fn (HasCommunication $model) => $model->getCommunicationSubObject()->id)
					->unique()
					->toArray()
			]);
		}

		return $templates->forget(0);
	}

	private function getDefaultLayout(CommunicationSubObject $subObject = null): ?\Ext_TC_Communication_Template_Email_Layout
	{
		if ($subObject && !empty($subObjectLayout = $subObject->getCommunicationDefaultLayout())) {
			return $subObjectLayout;
		}

		if (!empty($globalLayout = \System::d('admin.communication.message.default_layout'))) {
			return \Ext_TC_Communication_Template_Email_Layout::getInstance($globalLayout);
		}

		return null;
	}

	private function getIdentities(Communication $communication, \Ext_TC_User $user, string $channel): array
	{
		$subObject = (!$communication->isMassCommunication())
			? $communication->getBasedOnModels()->first()?->getCommunicationSubObject()
			: null;

		$identities = $user->getIdentities($channel, true, true, $subObject);

		asort($identities);

		return $identities;
	}

	private function l10n(): LanguageAbstract
	{
		if (!$this->l10n) {
			$this->l10n = (new Backend(\System::getInterfaceLanguage()))
				->setContext($this->admin->buildL10NContext('Communication'));
		}
		return $this->l10n;
	}
}