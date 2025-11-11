<?php

namespace TsStudentApp\Components;

use Illuminate\Http\Request;
use TsStudentApp\AppInterface;
use TsStudentApp\Http\Resources\TeacherResource;

class TeacherBox implements Component
{
	private ?\Ext_Thebing_Teacher $teacher = null;

	public function __construct(private readonly Request $request) {}

	public function getKey(): string
	{
		return 'teacher';
	}

	public function teacher(\Ext_Thebing_Teacher $teacher): static
	{
		$this->teacher = $teacher;
		return $this;
	}

	public function toArray(): array
	{
		return [
			'teacher' => (new TeacherResource($this->teacher))->toArray($this->request),
		];
	}
}