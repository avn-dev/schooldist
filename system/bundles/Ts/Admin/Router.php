<?php

namespace Ts\Admin;

use Admin\Dto\Component\Parameters;
use Admin\Enums\Size;
use Admin\Instance;
use Admin\Interfaces\Component\RouterActionSource;
use Admin\Interfaces\RouterAction;
use Ts\Admin\Components\TravellerComponent;

class Router implements RouterActionSource
{
	public static function openTraveller(int $travellerId, int $inquiryId = null, string|array $text = null, bool $initialize = true): RouterAction
	{
		//return Router::openGui2Dialog('ts_inquiry|inquiry', 'edit', [$this->inquiry->id], ['inbox_id' => $this->inquiry->getInbox()->id]);

		if (!$text) {
			$traveller = \Ext_TS_Inquiry_Contact_Traveller::getInstance($travellerId);
			$text = [$traveller->getCustomerNumber(), $traveller->getName()];
		}

		$optional = ($inquiryId) ? ['inquiry' => $inquiryId] : [];

		return \Admin\Facades\Router::slideOver(TravellerComponent::class, ['traveller' => $travellerId, ...$optional], initialize: $initialize)
			->storable('ts.traveller.'.$travellerId, 'fas fa-user-graduate', $text)
			->source(static::class, 'traveller.'.$travellerId)
			->size(Size::EXTRA_LARGE);
	}

	public static function getRouterActionByKey(Instance $admin, string $key, Parameters $parameters = null, bool $initialize = true): ?RouterAction
	{
		[$type, $additional] = explode('.', $key, 2);

		switch ($type) {
			case 'traveller':
				$traveller = \Ext_TS_Inquiry_Contact_Traveller::getInstance($additional);
				return static::openTraveller($traveller->id, $parameters?->get('inquiry'), initialize: $initialize);
		}

		return null;
	}
}