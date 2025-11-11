<?php

namespace TsPrivacy\Service;

use TsPrivacy\Interfaces\Entity;

class Notification {

	const TRANSLATION_PATH = 'Thebing » Admin » Privacy';

	/**
	 * @var \Ext_Thebing_School
	 */
	private $oSchool;

	public function __construct() {
		$this->oSchool = \Ext_Thebing_School::getFirstSchool();
	}

	/**
	 * @param Entity $sEntityClass
	 * @return string
	 */
	private function getEntityActionTranslation($sEntityClass) {

		$sAction = $sEntityClass::getPurgeSettings()['action'];

		switch($sAction) {
			case 'delete':
				return \L10N::t('unwiderruflich GELÖSCHT', self::TRANSLATION_PATH);
			case 'anonymize':
				return \L10N::t('anonymisiert', self::TRANSLATION_PATH);
			default:
				return '';
		}

	}

	/**
	 * @param array $aEntity
	 * @return mixed|string
	 */
	private function formatEntity(array $aEntity) {

		$sLabel = $aEntity['label'];
		$sLabel .= '; '. \L10N::t('letzte Aktion', self::TRANSLATION_PATH).': ';
		$sLabel .= \Ext_Thebing_Format::LocalDate($aEntity['date'], $this->oSchool->id);
		$sLabel .= ' ('.\L10N::t('interne ID', self::TRANSLATION_PATH).': '.$aEntity['id'].')';

		return $sLabel;

	}

	/**
	 * @param array $aEntities
	 * @return string
	 */
	private function createMailText(array $aEntities) {

		$aEntitiesLabels = [];
		$aEntitiesFormatted = [];

		foreach(array_keys($aEntities) as $sEntity) {

			if(empty($aEntities[$sEntity])) {
				continue;
			}

			/** @var Entity $sEntityClass */
			$sEntityClass = $sEntity;

			$aEntitiesLabels[$sEntity] = [
				'label' => $sEntityClass::getPurgeLabel(),
				'action_label' => $this->getEntityActionTranslation($sEntityClass),
				'count' => count($aEntities[$sEntity])
			];

			$aEntitiesFormatted[$sEntity] = array_map(function(array $aEntity) {
				return $this->formatEntity($aEntity);
			}, $aEntities[$sEntity]);

			$aEntitiesFormatted[$sEntity] = array_reverse($aEntitiesFormatted[$sEntity]);

		}

		$oSmarty = new \SmartyWrapper();
		$oSmarty->assign('sTranslationPath', self::TRANSLATION_PATH);
		$oSmarty->assign('aEntitiesLabels', $aEntitiesLabels);
		$oSmarty->assign('aEntitiesFormatted', $aEntitiesFormatted);
		$sText = $oSmarty->fetch('system/bundles/TsPrivacy/Resources/views/emails/notification.tpl');

		return $sText;

	}

	/**
	 * @param array $aEntities
	 * @return bool
	 */
	public function send(array $aEntities) {

		$oMail = new \WDMail();
		$oMail->subject = \L10N::t('Fidelo School - Datenbereinigung', self::TRANSLATION_PATH);
		$oMail->text = $this->createMailText($aEntities);

		$bSuccess = $oMail->send($this->oSchool->email);

		if(!$bSuccess) {
			\Ext_TC_Util::reportError('Could not send privacy depuration mail!', $this->oSchool->email."\n\n".$oMail->text);
		}

		return $bSuccess;

	}

}
