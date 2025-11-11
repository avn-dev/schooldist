<?php

namespace Core\Interfaces\Http;

use Illuminate\Http\Request;

// TODO \Illuminate\Contracts\Support\Responsable
interface HttpResponse
{
	public function toResponse(Request $request);
}
