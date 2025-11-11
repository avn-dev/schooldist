<?
interface Ext_Gui2_Html_Interface {

	public function __set($sOption, $mValue);
	public function __get($sOption);

	public function setElement($mElement);

	public function generateHTML($bReadOnly = false);

}