<?php

namespace Ts\Notifications\Buttons;

use Admin\Enums\Size;
use Admin\Facades\Admin;
use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Notification\AdminButton;
use Admin\Interfaces\RouterAction;
use Tc\Facades\EventManager;
use Ts\Admin\Components\TravellerComponent;

class OpenTravellerButton implements AdminButton
{
	public function __construct(
		private \Ext_TS_Inquiry_Contact_Traveller $traveller,
		private ?\Ext_TS_Inquiry $inquiry = null
	) {}

	public function getTitle(): string
	{
		return EventManager::l10n()->translate('Schüler öffnen');
	}

	public function isAccessible(\Access $access): bool
	{
		return $access->hasRight('thebing_invoice_edit_student');
	}

	public function action(): ?RouterAction
	{
		return \Ts\Admin\Router::openTraveller($this->traveller->id, $this->inquiry->id);
	}

	public function toArray(Instance $admin): array
	{
		return [
			'id' => (int)$this->traveller->id,
			'inquiry_id' => (int)$this->inquiry?->id
		];
	}

	public static function fromArray(Instance $admin, array $payload): ?static
	{
		$traveller = \Ext_TS_Inquiry_Contact_Traveller::getInstance($payload['id']);

		if ($traveller->exist() && $traveller->isActive()) {
			$inquiry = (!empty($payload['inquiry_id'])) ? \Ext_TS_Inquiry::getInstance($payload['inquiry_id']) : null;
			return new static($traveller, $inquiry);
		}

		return null;
	}
}