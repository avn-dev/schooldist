<?php

namespace Admin\Interfaces\Component;

use Admin\Dto\Component\InitialData;
use Admin\Dto\Component\VueComponentDto;
use Admin\Instance;
use Admin\Interfaces\Component;
use Illuminate\Http\Request;

interface VueComponent extends Component
{
	public static function getVueComponent(Instance $admin): VueComponentDto;

	public function init(Request $request, Instance $admin): ?InitialData;
}