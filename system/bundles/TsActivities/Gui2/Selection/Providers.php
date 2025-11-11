<?php

namespace TsActivities\Gui2\Selection;

class Providers extends \Ext_Gui2_View_Selection_Abstract
{

	public function getOptions(mixed $aSelectedIds, mixed $aSaveField, mixed &$oWDBasic): array
	{
		$activities = empty($oWDBasic) ? [] : $oWDBasic->getActivities();
		$providers = array_map(fn ($activity) => $activity->getProviders(), $activities);
		$providers = !empty($providers) ? array_intersect_key(...$providers) : [];

		return \Ext_Thebing_Util::addEmptyItem(
			collect($providers)
				->keyBy('id')
				->map(fn ($provider) => $provider->getName())
		)->toArray();
	}

}