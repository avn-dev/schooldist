<?php

namespace TsDashboard\Helper;

abstract class Charts extends Box
{

	public function __construct(array $aBox)
	{

		parent::__construct($aBox);

		$this->aBox['function'] = [static::class, 'parse'];

	}

	public function getHandler(): \Admin\Components\Dashboard\Handler
	{
		return (new \Admin\Components\Dashboard\Handler(2, 6));
	}

	protected function updateParameter()
	{
		$this->aBox['parameter'] = [$this->oSchool->id];
	}

	public static function parse($iSchoolId)
	{
		$school = \Ext_Thebing_School::getInstance($iSchoolId);

		$data = new Charts\Data($school, \System::getInterfaceLanguage());

		$view = static::getView();

		$count = 0;

		if ($view === \TsDashboard\Helper\ConvertedEnquiries::VIEW) {
			$count = $data->getConvertedEnquiries();
		} else if ($view === \TsDashboard\Helper\CurrentStudents::VIEW) {
			$count = $data->getStudentsAtSchool();
		}

		return sprintf('<h1 class="text-3xl">%d</h1>', (int)$count);
	}
	
	public function printBox($sRefreshKey=null, $fStartTime=null, $bSkipCache=false) {
	
		$this->getContent($bSkipCache);
				
		echo $this->aBox['content'];
		
	}

	abstract static protected function getView(): string;
}