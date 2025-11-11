<?php

/**
 * Class Ext_TS_Marketing_Feedback_Question
 * 
 * @property string $dependency_on Weist die Abhängigkeit des aktuellen Objektes auf
 * @property string $accommodation_provider Beinhaltet den Namen des Unterkunftsanbieters
 */
class Ext_TS_Marketing_Feedback_Question extends Ext_TC_Marketing_Feedback_Question {

	/**
	 * @see \Ext_TS_Marketing_Feedback_Questionary_Generator::getSubWDBasic()
	 * @param $sType
	 * @param int $iId
	 * @return string
	 */
	public static function getDependencyLabel($sType, $iId) {

		switch($sType) {
			case '0':
				return L10N::t('ohne Abhängigkeit', 'Thebing » Feedback');
			case 'course':
				return \Ext_Thebing_Tuition_Course::getInstance($iId)->getName();
			case 'course_category':
				return \Ext_Thebing_Tuition_Course_Category::getInstance($iId)->getName();
			case 'accommodation_category':
				return \Ext_Thebing_Accommodation_Category::getInstance($iId)->getName();
			case 'meal':
				return \Ext_Thebing_Accommodation_Meal::getInstance($iId)->getName();
			case 'rooms':
				return \Ext_Thebing_Accommodation_Room::getInstance($iId)->getName();
			case 'accommodation_provider':
				return \Ext_Thebing_Accommodation::getInstance($iId)->getName();
			case 'transfer':
				$oGenerator = new \Ext_TS_Marketing_Feedback_Questionary_Generator(null, new \Ext_TC_Marketing_Feedback_Questionary(), \System::getInterfaceLanguage());
				return $oGenerator->getTransferName($iId);
			case 'teacher':
			case 'teacher_course':
				return \Ext_Thebing_Teacher::getInstance($iId)->getName();
			case 'booking_type':
				if($iId == 2) {
					return L10N::t('Agenturbuchungen', 'Thebing » Feedback');
				}
				return L10N::t('Direktbuchungen', 'Thebing » Feedback');
			default:
				throw new \InvalidArgumentException('Unknown type: '.$sType);
		}

	}

    /**
     * Liefert alle Abhänigkeiten
     *
     * @param string $sLanguage
     * @return array
     */
    public static function getDependencies($sLanguage = '') {
		
		$aReturn = parent::getDependencies($sLanguage);

		$aReturn['teacher'] = Ext_TC_L10N::t('Lehrer', $sLanguage);
		$aReturn['teacher_course'] = Ext_TC_L10N::t('Lehrer (abhängig vom Kurs)', $sLanguage);
		$aReturn['rooms'] = Ext_TC_L10N::t('Zimmer', $sLanguage);
        $aReturn['accommodation_provider'] = Ext_TC_L10N::t('Unterkunftsanbieter', $sLanguage);
	    $aReturn['booking_type'] = Ext_TC_L10N::t('Buchungstyp', $sLanguage);
				
		return $aReturn;
	}

	/**
	 * @param bool $bLog
	 * @return Ext_TC_Marketing_Feedback_Question
	 */
	public function save($bLog = true) {

		if($this->dependency_on != 'accommodation_provider') {
			$this->accommodation_provider = '';
		}

		return parent::save($bLog);
	}
	
}
