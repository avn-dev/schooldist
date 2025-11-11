<?php

namespace Communication\Interfaces;

use Illuminate\Http\Request;

/**
 * @deprecated
 */
interface MessageAllocationAction {

	public function isValid(\Ext_TC_Communication_Message $message): bool;

	public function save(\Ext_Gui2 $gui2, \Ext_TC_Communication_Message $message, Request $request): bool|array;

}
