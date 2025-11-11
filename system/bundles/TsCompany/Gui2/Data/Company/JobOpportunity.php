<?php

namespace TsCompany\Gui2\Data\Company;

use TsCompany\Gui2\Selection\CompanyIndustries;

class JobOpportunity extends \Ext_Thebing_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $gui2): \Ext_Gui2_Dialog {

		$dialog = $gui2->createDialog($gui2->t('Arbeitsangebot "{name}" editieren'), $gui2->t('Neues Arbeitsangebot anlegen'));

		$dialog->setElement($dialog->createRow($gui2->t('Aktiv'), 'checkbox', [
			'db_column' => 'status',
			'db_alias' => 'ts_cjo'
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Bezeichnung'), 'input', [
			'db_column' => 'name',
			'db_alias' => 'ts_cjo',
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Abkürzung'), 'input', [
			'db_column' => 'short_name',
			'db_alias' => 'ts_cjo',
			'required' => true
		]));

		$h3 = $dialog->create('h4');
		$h3->setElement($gui2->t('Einstellungen'));
		$dialog->setElement($h3);

		$dialog->setElement($dialog->createRow($gui2->t('Firma'), 'select', [
			'db_column' => 'company_id',
			'db_alias' => 'ts_cjo',
			'select_options' => \Ext_Thebing_Util::addEmptyItem(\TsCompany\Entity\Company::getSelectOptions(true)),
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Branche'), 'select', [
			'db_column' => 'industry_id',
			'db_alias' => 'ts_cjo',
			'selection' => new CompanyIndustries(),
			'required' => true,
			'dependency' => [
				[
					'db_alias' => 'ts_cjo',
					'db_column' => 'company_id'
				]
			]
		]));

		$dialog->setElement($dialog->createMultiRow($gui2->t('Gehalt'), [
			'db_alias' => 'ts_cjo',
			'items' => [
				[
					'db_column' => 'wage',
					'input' => 'input',
					'text_after' => $gui2->t('pro'),
					'style' => 'width: 80px;',
					'format' => new \Ext_Thebing_Gui2_Format_Amount(),
				],
				[
					'db_column' => 'wage_per',
					'input' => 'select',
					'select_options' => [
						'hour' => $gui2->t('Stunde'),
						'week' => $gui2->t('Woche'),
						'month' => $gui2->t('Monat'),
					],
					'element' => 'select'
				]
			]
		]));

		$dialog->setElement($dialog->createMultiRow($gui2->t('Geplante Stunden'), [
			'db_alias' => 'ts_cjo',
			'items' => [
				[
					'db_column' => 'hours',
					'input' => 'input',
					'text_after' => $gui2->t('pro'),
					'style' => 'width: 80px;',
					'format' => new \Ext_Thebing_Gui2_Format_Amount(),
				],
				[
					'db_column' => 'hours_per',
					'input' => 'select',
					'select_options' => [
						'day' => $gui2->t('Tag'),
						'week' => $gui2->t('Woche'),
						'month' => $gui2->t('Monat')
					],
					'element' => 'select'
				]
			]
		]));

		$h3 = $dialog->create('h4');
		$h3->setElement($gui2->t('Sonstiges'));
		$dialog->setElement($h3);

		$dialog->setElement($dialog->createRow($gui2->t('Beschreibung'), 'textarea', [
			'db_column' => 'description',
			'db_alias' => 'ts_cjo'
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Kommentar'), 'textarea', [
			'db_column' => 'comment',
			'db_alias' => 'ts_cjo'
		]));

		return $dialog;
	}

	public static function getWhere(\Ext_Gui2 $gui2) {

		$where = ['active' => 1];

		return $where;
	}

	public static function getOrderby(){
		return [
			'ts_cjo.name' => 'ASC'
		];
	}

}
