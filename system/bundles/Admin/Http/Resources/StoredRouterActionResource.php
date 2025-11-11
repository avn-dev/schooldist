<?php

namespace Admin\Http\Resources;

use Admin\Instance;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @mixin StorableRouterAction
 */
class StoredRouterActionResource extends JsonResource {

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
		$node = [
			'id' => $this->getStorableKey(),
			'text' => Arr::wrap($this->getStorableText()),
			'action' => (new RouterActionResource($this->resource, $this->admin))->toArray($request),
		];

		if (!empty($icon = $this->getStorableIcon())) {
			$node['icon'] = $icon;
		}

		return $node;
	}

}
