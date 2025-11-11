<?php

namespace TsMews\Interfaces;

use TsMews\Api\Request;

interface Operation {

    public function getUri(): string;

    public function manipulateRequest(Request $request) : Request;

}
