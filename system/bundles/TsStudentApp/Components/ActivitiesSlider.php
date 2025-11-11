<?php

namespace TsStudentApp\Components;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TsStudentApp\Http\Resources\ActivityBlockResource;

/*
 * TODO extends Slider
 * TODO Entfernen (Was ist hier anders im Gegensatz zum normalen Slider?)
 */
class ActivitiesSlider implements Component
{
	private string $title = '';

	private Collection $activities;

	public function __construct(private readonly Request $request) {}

	public function getKey(): string
	{
		return 'activities-slider';
	}

	/**
	 * @deprecated
	 */
	public function title(string $title): static
	{
		$this->title = $title;
		return $this;
	}

	public function activities(Collection $activities): static
	{
		$this->activities = $activities;
		return $this;
	}

	public function toArray(): array
	{
		return [
			'title' => $this->title,
			'activities' => ActivityBlockResource::collection($this->activities)->toArray($this->request),
		];
	}

}