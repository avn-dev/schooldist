<?php

namespace Admin\Http\Resources;

use Admin\Instance;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\HasTranslations;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RouterAction
 */
class RouterActionResource extends JsonResource
{
	public function __construct($resource, private Instance $admin)
	{
		parent::__construct($resource);
	}

	/**
	 * @param $request
	 * @return array
	 * @throws \Exception
	 */
	public function toArray($request)
	{
		$payload = $this->resource->getPayload($this->admin);
		$payloadStorable = ($this->resource instanceof StorableRouterAction && $this->resource->isStorable())
			? ['payload_storable' => $this->resource->getStorablePayload($this->admin)]
			: [];

		$l10n = ($this->resource instanceof HasTranslations)
			? ['_l10n' => $this->resource->getTranslations()]
			: [];

		return [
			'target' => $this->getTarget()->value,
			'payload' => self::resolvePayloadInstances($payload, $this->admin, $request),
			...$payloadStorable,
			...$l10n
		];
	}

	public static function resolvePayloadInstances(array $payload, Instance $admin, $request): array
	{
		foreach ($payload as $key => $value) {
			if ($value instanceof JsonResource) {
				$value = $value->toArray($request);
			}

			if ($value instanceof RouterAction) {
				$value = (new RouterActionResource($value, $admin))->toArray($request);
			} else if ($value instanceof Arrayable) {
				$value = $value->toArray();
			}

			if (is_array($value)) {
				 $value = self::resolvePayloadInstances($value, $admin, $request);
			}

			$payload[$key] = $value;
		}

		return $payload;
	}
}
