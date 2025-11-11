<?php

namespace Admin\Http\Resources;

use Admin\Handler\System\Buttons\SystemButton;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SystemButton
 */
class SystemButtonResource extends JsonResource
{
	public function toArray($request)
	{
		return [
			'key' => $this->getKey(),
			'icon' => $this->getIcon(),
			'text' => $this->getTitle(),
			'options' => $this->getOptions(),
			'active' => $this->isActive(),
		];
	}
}