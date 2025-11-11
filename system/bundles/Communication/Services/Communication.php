<?php

namespace Communication\Services;

use Communication\Applications\GlobalApplication;
use Communication\Enums\MessageStatus;
use Communication\Interfaces\Application;
use Communication\Interfaces\CommunicationChannel;
use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\CommunicationSubObject;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Notifications\CommunicationNotification;
use Core\Database\Query\Builder as QueryBuilder;
use Core\Database\WDBasic\Builder as ModelQueryBuilder;
use Core\Facade\Cache;
use Core\Notifications\Channels\MessageTransport;
use Core\Service\NotificationService;
use Core\Traits\WithAdditionalData;
use Illuminate\Container\Container;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tc\Service\Language\Backend;
use Tc\Service\LanguageAbstract;

class Communication
{
	use WithAdditionalData;

	// $channel => message->type
	const MESSAGE_TYPE_CHANNEL_MAPPING = [
		'mail' => 'email'
	];

	private ?string $application = null;

	private ?LanguageAbstract $l10n = null;

	public function __construct(
		private Container   $container,
		private ?Collection $models = null
	) {}

	public function basedOn(Collection|HasCommunication $models): static
	{
		if ($models instanceof HasCommunication) {
			$models = collect([$models]);
		}

		$instance = $this->container->makeWith(static::class, ['models' => $models]);

		if ($this->application) {
			$instance->application($this->application);
		}

		if ($this->additionalData) {
			$instance->additional($this->additionalData);
		}

		if ($this->l10n) {
			$instance->setL10n($this->l10n);
		}

		return $instance;
	}

	public function application(string $application): static
	{
		$this->application = $application;
		return $this;
	}

	public function setL10n(LanguageAbstract $l10n): static
	{
		$this->l10n = $l10n;
		return $this;
	}

	public function messages(): ModelQueryBuilder
	{
		/* @var $query ModelQueryBuilder */
		$query = \Factory::getClassName(\Ext_TC_Communication_Message::class)::query()
			->select('tc_cm.*')
			->orderByDesc('tc_cm.date');

		return $this->fillQuery($query);
	}

	public function total(): QueryBuilder
	{
		$query = \DB::table('tc_communication_messages as tc_cm')
			->distinct('tc_cm.id')
			->where('tc_cm.active', 1);

		return $this->fillQuery($query);
	}

	public function delete(Collection $messageIds): Collection
	{
		$messages = $this->messages()
			->whereIn('tc_cm.id', $messageIds)
			->get();

		$messages->each(fn(\Ext_TC_Communication_Message $message) => $message->delete());

		return $messages
			->map(fn(\Ext_TC_Communication_Message $message) => $message->id);
	}

	public function categorize(Collection $messageIds, int $categoryId): Collection
	{
		return $this->executeCategorization($messageIds, $categoryId, 'add');
	}

	public function decategorize(Collection $messageIds, int $categoryId): Collection
	{
		return $this->executeCategorization($messageIds, $categoryId, 'remove');
	}

	public function addressBook(): AddressBook
	{
		return new AddressBook($this);
	}

	public function new(string $channel): Builder\MessageBuilder
	{
		return new Builder\MessageBuilder($this, $channel);
	}

	public function reply(string $channel, \Ext_TC_Communication_Message $message): Builder\MessageBuilder
	{
		return (new Builder\MessageBuilder($this, $channel))->reply($message);
	}

	public function replyAll(string $channel, \Ext_TC_Communication_Message $message): Builder\MessageBuilder
	{
		return (new Builder\MessageBuilder($this, $channel))->replyAll($message);
	}

	public function forward(string $channel, \Ext_TC_Communication_Message $message, bool $withAttachments = true): Builder\MessageBuilder
	{
		return (new Builder\MessageBuilder($this, $channel))->forward($message, $withAttachments);
	}

	public function builder(string $channel): Builder\MessageBuilder
	{
		return new Builder\MessageBuilder($this, $channel);
	}

	public function contentManager(): ContentManager
	{
		return new ContentManager();
	}

	public function fileManager(string $channel): FileManager
	{
		return new FileManager($this, $channel);
	}

	public function flagManager(): FlagManager
	{
		return new FlagManager($this);
	}

	public function send(\Ext_TC_Communication_Message $message, HasCommunication $basedOn = null, int $prio = 0): MessageTransport
	{
		$channel = $this->getChannel($message->getChannel());

		try {
			$transport = $channel
				->send(notifiable: null, notification: (new CommunicationNotification($this, $message, $basedOn))->queue($prio));

			// Fallback
			if (!$transport instanceof MessageTransport) {
				$transport = new MessageTransport((is_bool($transport)) ? $transport : true);
				NotificationService::getLogger()->warning('Invalid channel return value', ['channel' => $message->getChannel()]);
			}
		} catch (\Throwable $e) {

			if ($message->exist() && $message->status !== MessageStatus::FAILED->value) {
				$message->status = MessageStatus::FAILED->value;
				$message->save();
			}

			$transport = (new MessageTransport(success: false, errors: [$e]))->log($message, $message->isDraft());
		}

		return $transport;
	}

	public function getBasedOnModels(): Collection
	{
		return $this->models ?? collect();
	}

	public function isMassCommunication(): bool
	{
		return $this->models->count() !== 1;
	}

	public function getSubObject(): ?CommunicationSubObject
	{
		return $this->getBasedOnModels()->first()?->getCommunicationSubObject();
	}

	public function l10n(): LanguageAbstract
	{
		if (!$this->l10n) {
			$this->l10n = (new Backend(\System::getInterfaceLanguage()))
				->setContext('Communication');
		}

		return $this->l10n;
	}

	public function getChannel(string $channel): CommunicationChannel
	{
		$channelConfig = $this->getApplication()->getChannels($this->l10n());

		if (!isset($channelConfig[$channel])) {
			throw new \RuntimeException(sprintf('Channel is not available for this communication [%s]', $channel));
		}

		$instance = \Illuminate\Support\Facades\Notification::driver($channel);

		if (!$instance instanceof CommunicationChannel) {
			throw new \RuntimeException(sprintf('Channel must be an instance of "%s" [%s]', CommunicationChannel::class, $instance::class));
		}

		return $instance
			->setCommunicationConfig($channelConfig[$channel])
			->enabledCommunicationMode();
	}

	public function getChannels(): Collection
	{
		$availableChannels = array_keys($this->getApplication()->getChannels($this->l10n()));

		return collect($availableChannels)
			->mapWithKeys(fn($channel) => [$channel => $this->getChannel($channel)]);
	}

	public function getApplicationKey(): string
	{
		$application = $this->application ?? 'global';

		$applications = $this->getAllApplications();

		if (class_exists($application)) {
			$applicationKeys = $applications->filter(fn($class) => $application === $class)
				->keys();

			if ($applicationKeys->isEmpty()) {
				throw new \RuntimeException(sprintf('Application does not exist [%s]', $application));
			}

			$application = $applicationKeys->first();
		}

		return $application;
	}

	public function getApplication(): Application
	{
		$application = $this->getApplicationKey();

		$bindingKey = 'communication.application.' . $application . '.' . md5(serialize($this->additionalData));

		if ($this->container->has($bindingKey)) {
			return $this->container->get($bindingKey);
		}

		$applications = $this->getAllApplications();

		if ($applications->has($application)) {
			$class = $applications->get($application);
		} else {
			throw new \InvalidArgumentException(sprintf('Application unknown, perhaps you have forgotten to add it in config.php [%s]', $application));
		}

		$instance = $this->container->makeWith($class, ['additional' => $this->additionalData]);

		$this->container->instance($bindingKey, $instance);

		return $instance;
	}

	public function getAllApplications(\Access $access = null): Collection
	{
		$applications = $this->getConfigSetting('applications', []);

		$final = [];
		foreach ($applications as $application => $config) {
			if (is_array($config)) {
				[$class, $config] = $config;
				if (!$access || !isset($config['access']) || $access->hasRight($config['access'])) {
					$final[$application] = $class;
				}
			} else {
				$final[$application] = $config;
			}
		}

		if (!isset($final['global'])) {
			// Fallback
			$final['global'] = GlobalApplication::class;
		}

		return collect($final);
	}

	public function getAllRecipients(LanguageAbstract $l10n): Collection
	{
		$recipients = $this->getConfigSetting('recipients', []);

		return collect($recipients)
			->map(fn($recipient) => $l10n->translate($recipient))
			->sort();
	}

	public function getAllFlags(): Collection
	{
		$flags = $this->getConfigSetting('flags', []);
		return collect($flags);
	}

	public function getFlags(): Collection
	{
		$applicationClass = $this->getApplication()::class;

		return $this->getAllFlags()
			->intersect(\Factory::executeStatic($applicationClass, 'getFlags'));
	}

	public function getFlag(string $flag): ?Flag
	{
		$allFlags = $this->getAllFlags();

		if ($allFlags->has($flag)) {
			return $this->container->make($allFlags->get($flag));
		} else if ($allFlags->contains($flag)) {
			return $this->container->make($flag);
		}

		return null;
	}

	private function executeCategorization(Collection $messageIds, int $categoryId, string $action): Collection
	{
		$messages = $this->messages()
			->whereIn('tc_cm.id', $messageIds)
			->get();

		$ids = [];
		foreach ($messages as $message) {
			$categories = $message->categories;

			if ($action === 'add' && !in_array($categoryId, $categories)) {
				$categories[] = $categoryId;
			} else if ($action === 'remove') {
				$categories = array_diff($categories, [$categoryId]);
			}

			if (
				!empty(array_diff($message->categories, $categories)) ||
				!empty(array_diff($categories, $message->categories))
			) {
				$message->categories = $categories;
				$message->save();

				$ids[] = $message->id;
			}
		}

		return collect($ids);
	}

	private function fillQuery($query)
	{
		$models = $this->getBasedOnModels();

		$messageTypes = collect($this->getApplication()->getChannels($this->l10n()))->keys()
			->map(fn($channel) => self::MESSAGE_TYPE_CHANNEL_MAPPING[$channel] ?? $channel);

		$addMultipleWherePart = function ($query, string $column, Collection $values) {
			if ($values->count() > 1) {
				$query->whereIn($column, $values);
			} else {
				$query->where($column, $values->first());
			}
		};

		if (!$models->isEmpty()) {

			$query->where(function ($where) use ($addMultipleWherePart, $messageTypes) {
				$addMultipleWherePart($where, 'tc_cm.type', $messageTypes);
				$where->orWhereNull('tc_cm.type');
			});

			/*$query->from('tc_communication_messages_relations as relations');
			$query->join('tc_communication_messages as tc_cm', function (JoinClause $join) {
				$join->on('tc_cm.id', '=', 'relations.message_id')
					->where('tc_cm.active', 1);
			});*/

			$application = $this->getApplication();

			// Weitere E-Mails mit anzeigen
			if (method_exists($application, 'getAdditionalModelRelations')) {
				$models = $models->merge($application->getAdditionalModelRelations($models));
			}

			//$differentEntities = $models->first(fn ($loop) => $loop::class !== $models->first()::class) !== null;

			//$relationClasses = $models->map(fn($model) => [$model::class, \Factory::getClassName($model::class)])
			//	->flatten()
			//	->unique();

			//$relationIds = $models->map(fn($model) => $model->id);

			//$addMultipleWherePart($query, 'relations.relation', $relationClasses);
			//$addMultipleWherePart($query, 'relations.relation_id', $relationIds);

			$query->whereIn('tc_cm.id', function(QueryBuilder $query) use ($models, $addMultipleWherePart) {
				$query->select('relations.message_id')->distinct()
					->from('tc_communication_messages_relations as relations')
					->where(function ($query) use ($models, $addMultipleWherePart) {
						foreach ($models as $model) {
							$classes = collect([$model::class, \Factory::getClassName($model::class)])->unique();
							$query->orWhere(function ($query) use ($classes, $model, $addMultipleWherePart) {
								$addMultipleWherePart($query, 'relations.relation', $classes);
								$query->where('relations.relation_id', $model->id);
							});
						}
					});
			});

		} else {

			// Globale Kommunikation

			$query->leftJoin('tc_communication_messages_relations as account_relation', function (JoinClause $join) use ($addMultipleWherePart) {
				$classes = collect([\Ext_TC_Communication_EmailAccount::class, \Factory::getClassName(\Ext_TC_Communication_EmailAccount::class)])->unique();
				$accountIds = collect(\Ext_TC_Communication_EmailAccount::getSelectOptions(true))->keys();

				$join->on('account_relation.message_id', '=', 'tc_cm.id');
				$addMultipleWherePart($join, 'account_relation.relation', $classes);
				$addMultipleWherePart($join, 'account_relation.relation_id', $accountIds);
			});

			$query->where(function ($where) use ($messageTypes) {
				$where->whereIn('tc_cm.type', $messageTypes->reject(fn ($key) => $key === 'email'))
					->orWhereNotNull('account_relation.message_id');
			});
		}

		return $query;
	}

	private function getConfigSetting(string $key, $default = null): array
	{
		$all = Cache::get('communication.settings');

		if ($all === null || \System::d('debugmode') == 2) {
			$files = (new \Core\Helper\Config\FileCollector())->collectAllFileParts();
			$all = [];

			foreach ($files as $file) {
				if (!empty($fileConfig = $file->get('communication', []))) {
					$all = array_merge_recursive($all, $fileConfig);
				}
			}

			Cache::put('communication.settings', 60 * 60 * 24, $all);
		}

		return Arr::get($all, $key, $default);
	}
}