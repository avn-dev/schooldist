<?php

namespace TsCompany\Gui2\Dialog;

use TsCompany\Traits\Gui2\Dialog\CompanyFields;

class Company extends AbstractDialog {
	use CompanyFields;

	public function getTitle(): string{
		return $this->t('Neue Firma anlegen');
	}

	public function getEditTitle(): string{
		return $this->t('Firma "{ext_1}" editieren');
	}

	public function build(): void {

		$this->tab($this->t('Daten'), function($tab) {
			$tab->aOptions = ['section' => 'companies_details'];

			$this
				->withActiveRow()
				->withNumberRow()
				->withNameBlock()
			;

			$tab->setElement($this->dialog->createRow($this->t('Branchen'), 'select', array(
				'db_alias' => 'ka',
				'db_column'	=> 'industries',
				'required' => true,
				'select_options' => \TsCompany\Entity\Industry::getSelectOptions(true),
				'multiple' => 5,
				'jquery_multiple' => true,
				'style' => 'height: 105px;',
				'searchable' => true
			)));

			$this
				->withWebRow()
				->withAddressBlock()
			;

			$this
				->heading($this->t('Sonstiges'))
				->withCommentRow()
				->withCorrespondenceLanguageRow()
			;

		});

		$this->tab($this->t('Info'), function($tab) {
			$tab->aOptions = ['section' => 'companies_info'];
			$this->withInfoBlock();
		});

		$this->tab($this->t('Bank'), function($tab) {
			$tab->aOptions = ['section' => 'companies_bank'];
			$this->withBankBlock(false);
		});

	}
}
