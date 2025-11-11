<?php

namespace TsStudentApp\Pages\Home\Boxes;

use TsStudentApp\Components\Component;

interface Box
{
	public function generate(): ?Component;
}