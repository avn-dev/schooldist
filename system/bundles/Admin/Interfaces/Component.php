<?php

namespace Admin\Interfaces;

interface Component {

	public function isAccessible(\Access $access): bool;

}