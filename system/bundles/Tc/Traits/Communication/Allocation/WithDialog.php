<?php

namespace Tc\Traits\Communication\Allocation;

/**
 * @deprecated
 */
trait WithDialog {

	abstract public function prepareDialog(\Ext_Gui2 $gui2, \Ext_Gui2_Dialog $dialog, \Ext_TC_Communication_Message $message): void;

}
