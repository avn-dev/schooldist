<?php

namespace Admin\Router\Action;

use Admin\Dto\Component\Parameters;
use Admin\Enums\RouterAction as Target;
use Admin\Instance;
use Admin\Interfaces\RouterAction\StorableRouterAction;
use Admin\Router\ComponentContent;
use Admin\Router\Content;
use Admin\Factory\Content as ContentFactory;
use Admin\Traits\RouterAction\Storable;
use Admin\Traits\RouterAction\Closable;
use Illuminate\Support\Str;

class OpenTab implements StorableRouterAction
{
	use Closable, Storable;

	private bool $active = false;

	public function __construct(
		private string $id,
		string $icon,
		string|array $text,
		private Content $content
	) {
		$this->storable($id, $icon, $text);
	}

	public function getTarget(): Target
	{
		return Target::TAB;
	}

	public function active(bool $active = true): static
	{
		$this->active = $active;
		return $this;
	}

	public function getStorableParameters(Instance $admin): ?Parameters
	{
		if ($this->content instanceof ComponentContent) {
			return $this->content->getParameters();
		}
		return null;
	}

	public function getPayload(Instance $admin): array
	{
		$array = [
			'id' => $this->id,
			'icon' => $this->icon,
			'text' => array_map('strip_tags', $this->text),
			'content' => $this->content->toArray(),
			'active' => $this->active,
			'closable' => $this->closable
		];

		if ($this->hasSource()) {
			if (is_a($this->source[0], \Admin\Components\NavigationComponent::class, true)) {
				$array['navigationNodeId'] = $this->source[1];
			} else if (
				is_a($this->source[0], \Admin\Components\SearchComponent::class, true) &&
				str_starts_with($this->source[1], \Admin\Search\Navigation::KEY.'{|}')
			) {
				$array['navigationNodeId'] = Str::after($this->source[1], \Admin\Search\Navigation::KEY.'{|}');
			}
			$array['component'] = $this->source;
		}

		return $array;
	}

	public static function fromPayload(Instance $admin, array $payload): ?static
	{
		if (!empty($payload['component'])) {
			try {
				return $payload['component'][0]::getRouterActionByKey($payload['component'][1]);
			} catch (\Throwable $e) {
				return null;
			}
		}

		$action = new self($payload['id'], $payload['icon'], $payload['text'], ContentFactory::fromArray($payload['content']));

		if (!empty($payload['active'])) {
			$action->active($payload['active']);
		}

		if (!empty($payload['closeable'])) {
			$action->closable($payload['closeable']);
		}

		if (!empty($payload['storable'])) {
			$action->storable($payload['storable'], $payload['icon'], $payload['text']);
		}

		return $action;
	}
}