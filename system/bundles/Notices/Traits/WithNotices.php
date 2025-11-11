<?php

namespace Notices\Traits;

use Illuminate\Support\Collection;
use Notices\Entity\Notice;

trait WithNotices
{
	public function getNotices(): Collection
	{
		return Notice::query()
			->where('entity', $this::class)
			->where('entity_id', $this->id)
			->orderByDesc('created')
			->get();
	}
}