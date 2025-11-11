<?php

namespace Communication\Services;

use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Model\CommunicationContact;
use Communication\Interfaces\Notifications\NotificationRoute;
use Communication\Services\AddressBook\AddressBookContact;
use Illuminate\Support\Collection;

class AddressBook
{
	private array $collection = [];

	public function __construct(
		private readonly Communication $communication
	) {}

	public function getContacts(string $channel, bool $onlyWithRoutes = true): AddressContactsCollection
	{
		$cacheKey = implode('_', [$channel, (int)$onlyWithRoutes]);

		if ($this->collection[$cacheKey] !== null) {
			return $this->collection[$cacheKey];
		}

		$models = $this->communication->getBasedOnModels();

		$recipients = $this->communication->getApplication()->getRecipients($this->communication->l10n(), $models, $channel);

		// Alle Benutzer hinzufügen (TODO Systemtyp abfragen)
		/* @var Collection<\Ext_TC_User> $users */
		$users = \Factory::getClassName(\Ext_TC_User::class)::query()
			->where('status', 1)
			->get();

		foreach ($users as $user) {
			$recipients->push(
				(new AddressBookContact('user.'.$user->id, $user))
					->groups($this->communication->l10n()->translate('Mitarbeiter'))
					->disableAllSelection()
			);
		}

		if ($onlyWithRoutes) {
			$recipients = $recipients
				->filter(fn(AddressBookContact $address) => $address->getRoutes($channel)?->isNotEmpty())
				->values();
		}

		// Sichergehen dass die Kontakte auch in der Reihenfolge erscheinen wie sie auch ausgewählt wurden und dass "Sonstige"
		// immer am Ende stehen
		[$withSource, $noSource] = $recipients->partition(fn (AddressBookContact $contact) => $contact->getSource() !== null);

		$sorted = $withSource->union($noSource)->values();

		$this->collection[$cacheKey] = new AddressContactsCollection($sorted);

		return $this->collection[$cacheKey];
	}

	public function search(string $channel, CommunicationContact|string $contact = null, int $contactId = null, string $route = null): AddressContactsCollection
	{
		$collection = $this->getContacts($channel);

		if ($contact instanceof CommunicationContact) {
			$contactId = $contact->id;
			$contact = $contact::class;
		}

		if (!empty($contact)) {
			$collection = $collection->filter(function (AddressBookContact $address) use ($contact, $contactId) {
				$addressContact = $address->getContact();
				return $addressContact::class === $contact && (int)$addressContact->id === (int)$contactId;
			});
		}

		$found = $collection->map(function (AddressBookContact $address) use ($contact, $route, $channel) {
			$found = null;
			if (!empty($route)) {
				$contactRoutes = $address->getContact()->getCommunicationRoutes($channel);

				foreach ($contactRoutes as $routeIndex => $contactRoute) {
					if ($contactRoute instanceof NotificationRoute) {
						$contactRoute = $contactRoute->toNotificationRoute($channel);
					}

					if (is_array($contactRoute) && is_scalar($contactRoute[0])) {
						if ($contactRoute[0] === $route) {
							$found = $routeIndex;
							break;
						}
					} else {
						if ($contactRoute === $route) {
							$found = $routeIndex;
							break;
						}
					}
				}

				if (empty($contact) && !$found) {
					return null;
				}
			}

			return [$address, $found];
		});

		return $found->filter(fn ($loop) => $loop !== null);
	}

	public function getCorrespondingLanguages(string $channel): Collection
	{
		return $this->getContacts($channel)
			->map(fn (AddressBookContact $address) => $address->getContact()->getCorrespondenceLanguages())
			->flatten()
			->unique()
			->values();
	}

	public function getRecipientKeys(string $channel): Collection
	{
		return $this->getContacts($channel)
			->map(fn (AddressBookContact $address) => $address->getRecipientKeys())
			->flatten()
			->unique()
			->values();
	}

}