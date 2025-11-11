<?php

namespace Tc\Interfaces\EventManager;

interface ManageableEvent extends Manageable
{
	public static function getManageableListeners(): array;

	public static function getManageableConditions(): array;

}