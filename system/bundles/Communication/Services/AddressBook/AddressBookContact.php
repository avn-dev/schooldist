<?php

namespace Communication\Services\AddressBook;

use Communication\Dto\Message\Recipient;
use Communication\Interfaces\Model\CommunicationContact;
use Communication\Interfaces\Model\HasCommunication;
use Communication\Interfaces\Notifications\NotificationRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class AddressBookContact
{
	private array $groups = [];

	private array $recipientKeys = [];

	private ?HasCommunication $source = null;

	private bool $allSelection = true;

	public function __construct(
		private string|int $key,
		private CommunicationContact $contact
	) {}

	public function groups(array|string $groups): static
	{
		$this->groups = array_unique(Arr::wrap($groups));
		return $this;
	}

	public function recipients(array|string $keys): static
	{
		$this->recipientKeys = array_unique(Arr::wrap($keys));
		return $this;
	}

	public function source(HasCommunication $source): static
	{
		$this->key = sprintf('%s::%s', $source->id, $this->key);
		$this->source = $source;
		return $this;
	}

	public function disableAllSelection(): static
	{
		$this->allSelection = false;
		return $this;
	}

	public function getKey(): string|int
	{
		return $this->key;
	}

	public function getContact(): CommunicationContact
	{
		return $this->contact;
	}

	public function getRoutes($channel): ?Collection
	{
		return $this->contact->getCommunicationRoutes($channel);
	}

	public function getSource(): ?HasCommunication
	{
		return $this->source;
	}

	public function getGroups(): array
	{
		return $this->groups;
	}

	public function getRecipientKeys(): array
	{
		return $this->recipientKeys;
	}

	public function getCorrespondenceLanguages(): array
	{
		return $this->contact->getCorrespondenceLanguages();
	}

	public function isEnabledForAllSelection(): bool
	{
		return $this->allSelection;
	}

	public function toRecipient(string $channel, $routeIndex = null): array
	{
		$routes = $this->getRoutes($channel);

		if ($routeIndex && $routes->has($routeIndex)) {
			$routes = collect([$routes->get($routeIndex)]);
		}

		$recipients = [];
		foreach ($routes as $route) {
			if ($route instanceof NotificationRoute) {
				$route = $route->toNotificationRoute($channel);
			}

			[$finalRoute, $name] = Arr::wrap($route);

			$recipient = new Recipient($finalRoute, $name, $this->contact);

			if ($this->source) {
				$recipient->source($this->source);
			}

			$recipients[] = $recipient;
		}

		return $recipients;
	}

	public function toArray(LanguageAbstract $l10n, string $channel): array
	{
		return [
			'model' => $this->getSource()?->getCommunicationLabel($l10n) ?? $l10n->translate('Sonstige'),
			'value' => $this->key,
			'text' => $this->contact->getCommunicationName($channel),
			'groups' => $this->getGroups(),
			'allSelection' => $this->allSelection,
			'routes' => $this->getRoutes($channel)
				->map(function ($route, $index) use ( $channel) {
					if ($route instanceof NotificationRoute) {
						$text = $route->getNotificationName($channel);
					} else if (is_array($route) && is_scalar($route[0])) {
						$text = $route[0];
					} else {
						$text = $this->contact->getCommunicationName($channel);
					}
					return ['value' => $this->key.'::'.$index, 'text' => $text];
				})
				->values()
		];
	}

}