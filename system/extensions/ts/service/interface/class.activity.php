<?php

interface Ext_TS_Service_Interface_Activity {

	/**
	 * @return TsActivities\Entity\Activity
	 */
	public function getActivity();

	public function getInfo($sLanguage);

}
