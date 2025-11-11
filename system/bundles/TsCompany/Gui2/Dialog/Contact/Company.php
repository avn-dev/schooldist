<?php

namespace TsCompany\Gui2\Dialog\Contact;

use TsCompany\Gui2\Dialog\AbstractDialog;
use TsCompany\Traits\Gui2\Dialog\ContactFields;

class Company extends AbstractDialog {
	use ContactFields;

	public function build(): void {

		$this->tab($this->t('Details'), function($tab) {
			$tab->aOptions = ['section' => 'companies_users_details'];

			$this
				->withContactBlock()
				->withContactDetailsBlock()

				->heading($this->t('Sonstiges'))
				->withCommentRow()
				->withGroupRow()
			;

		});

		$this->tab($this->t('Uploads'), function($tab) {
			$this->withUploadRow('/storage/company/user/');
		});

	}
}
