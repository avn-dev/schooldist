<?php

namespace Tc\Gui2\Format\Contact;

use Tc\Entity\SystemTypeMapping;

class SystemType extends \Ext_Gui2_View_Format_Abstract
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$systemTypeMappings = SystemTypeMapping::query()
			->where('type', \Ext_TC_Contact::MAPPING_TYPE)
			->pluck('name', 'id');

		$contactMappingIds = explode('{||}', $mValue);

		return $systemTypeMappings->intersectByKeys(array_flip($contactMappingIds))
			->map(fn ($name) => sprintf('<span class="badge badge-default">%s</span>', $name))
			->implode('');
	}
}
