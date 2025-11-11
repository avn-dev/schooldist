<?php

namespace TsCompany\Gui2\Dialog\Contact;

use TsCompany\Gui2\Dialog\AbstractDialog;
use TsCompany\Traits\Gui2\Dialog\ContactFields;

class Agency extends AbstractDialog {
	use ContactFields;

	public function build(): void {

		$this->tab($this->t('Details'), function($tab) {
			$tab->aOptions = ['section' => 'agencies_users_details'];

			$this
				->withContactBlock()
				->withContactDetailsBlock()

				->heading($this->t('Sonstiges'))
				->withCommentRow()
				->withGroupRow()
			;

		});

		$this->tab($this->t('Uploads'), function($tab) {
			$this->withUploadRow('/storage/agency/user/');
		});

		$this->tab($this->t('Verantwortlichkeit'), function($tab) {
			$tab->setElement($this->dialog->createRow($this->t('Pickup'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'transfer',
				'required'			=> 0,
			)));

			$tab->setElement($this->dialog->createRow($this->t('Unterkunft'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'accommodation',
				'required'			=> 0,
			)));

			$tab->setElement($this->dialog->createRow($this->t('Mahnung'), 'checkbox', array(
				'db_alias'			=> '',
				'db_column'			=> 'reminder',
				'required'			=> 0,
			)));
		});

	}
}
