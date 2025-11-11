<?php

/**
 * @TODO Sinnloses Interface
 * @deprecated
 */
interface Ext_Gui2_View_Selection_Interface {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic);

	public function prepareOptionsForGui($aSelectOptions);

}