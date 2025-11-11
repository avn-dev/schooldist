<?php

namespace Admin\Router\Action;

use Admin\Enums\RouterAction as Target;
use Admin\Enums\Size;
use Admin\Instance;
use Admin\Interfaces\RouterAction;
use Admin\Interfaces\HasTranslations;
use Admin\Router\Content;
use Admin\Factory\Content as ContentFactory;
use Admin\Traits\RouterAction as RouterActionTrait;
use Illuminate\Support\Arr;

class OpenModal implements RouterAction, HasTranslations, RouterAction\StorableRouterAction
{
	use RouterActionTrait\Resizeable,
		RouterActionTrait\OuterClosable,
		RouterActionTrait\Moveable,
		RouterActionTrait\Storable;

	public function __construct(string $text, private Content $content)
	{
		$this->text = Arr::wrap($text);
	}

	public function getTarget(): Target
	{
		return Target::MODAL;
	}

	public function getPayload(Instance $admin): array
	{
		return [
			'title' => array_map('strip_tags', $this->text ?? []),
			'content' => $this->content->toArray(),
			'size' => $this->size->value,
			'moveable' => $this->moveable,
			'closable' => $this->closable,
			'outer_closable' => $this->outerClosable
		];
	}

	public static function fromPayload(Instance $admin, array $payload): ?static
	{
		$action = new self($payload['title'], ContentFactory::fromArray($payload['content']));

		if (!empty($payload['size'])) {
			$action->size(Size::from($payload['size']));
		}

		if (!empty($payload['moveable'])) {
			$action->moveable((bool)$payload['moveable']);
		}

		if (!empty($payload['closable'])) {
			$action->closable((bool)$payload['closable']);
		}

		if (!empty($payload['outer_closable'])) {
			$action->outerClosable((bool)$payload['outer_closable']);
		}

		return $action;
	}

	public function getTranslations(): array
	{
		return $this->content->getTranslations();
	}
}