<?php

namespace Core\Middleware;

use Access_Frontend;

final class Frontend extends AbstractInterface {

	protected string $interface = 'frontend';

	protected string $access = Access_Frontend::class;

	protected function setup(): void {

	}

}
