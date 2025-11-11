<?php

namespace Ts\Gui2\Data\PointOfSale;

class Stock extends \Ext_Thebing_Gui2_Data {
	
	public static function getOrderby() {
		return ['created'=>'DESC'];
	}
	
	public static function getDialog(\Ext_Gui2 $oGui) {

		$aGenders = \Ext_TC_Util::getPersonTitles();

		$oDialog = $oGui->createDialog($oGui->t('Bestandsänderung "{change}" editieren'), $oGui->t('
		Neue Bestandsänderung anlegen'));

		$oPos = new \Ts\Entity\PointOfSale;
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Verkaufsstelle'), 'select', array(
			'db_column' => 'pos_id',
			'db_alias' => 'ts_ps',
			'required' => 1,
			'select_options' => \Ext_Thebing_Util::addEmptyItem($oPos->getArrayList(true))
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Bestandsänderung'), 'input', array(
			'db_column' => 'change',
			'db_alias' => 'ts_ps',
			'required' => 1,
			'format' => new \Ext_Thebing_Gui2_Format_Int
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
			'db_column' => 'comment',
			'db_alias' => 'ts_ps'
		)));
		
		return $oDialog;
	}
	
}
