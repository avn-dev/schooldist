<?php

namespace TsTuition\Entity;

class AbsenceReason extends \Ext_Thebing_Basic
{
	protected $_sTable = 'ts_tuition_absence_reasons';

	protected $_sTableAlias = 'ts_tar';

	static function getOptions($forTeacherPortal=false, $language=null) {
		
		if($language === null) {
			$language = \Ext_Thebing_Util::getInterfaceLanguage();
		}
		
		$query = self::query()->orderBy('name_'.$language);
		
		if($forTeacherPortal) {
			$query->where('teacher_portal_available', 1);
		}
			
		$options = $query->get()->pluck('name_'.$language, 'id')->toArray();
		
		return $options;
	}
	
	static public function getKeys() {
		
		$query = self::query();
					
		$options = $query->get()->pluck('key', 'id')->toArray();
		
		return $options;
	}
	
}