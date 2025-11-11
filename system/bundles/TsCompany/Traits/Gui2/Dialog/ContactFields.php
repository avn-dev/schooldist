<?php

namespace TsCompany\Traits\Gui2\Dialog;

trait ContactFields {

	public function getTitle(): string{
		return $this->t('Neuen Mitarbeiter anlegen');
	}

	public function getEditTitle(): string{
		return $this->t('Mitarbeiter "{name}" editieren');
	}

	protected function withContactBlock() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Hauptkontaktperson'), 'checkbox', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'master_contact',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Anrede'), 'select', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'gender',
			'required'			=> 1,
			'select_options'	=> \Ext_TC_Util::getPersonTitles(),
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Vorname'), 'input', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'firstname',
			'required'			=> 1,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Nachname'), 'input', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'lastname',
			'required'			=> 1,
		)));

		return $this;
	}

	protected function withContactDetailsBlock(bool $withHeading = true) {

		if($withHeading) {
			$this->heading($this->t('Kontaktdaten'));
		}

		$this->currentElement->setElement($this->dialog->createRow($this->t('E-Mail'), 'input', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'email',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Telefon'), 'input', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'phone',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Fax'), 'input', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'fax',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Skype'), 'input', array(
			'db_alias'			=> 'ts_ac',
			'db_column'			=> 'skype',
			'required'			=> 0,
		)));

		return $this;
	}

	protected function withCommentRow() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Kommentar'), 'textarea', array(
			'db_column' => 'comment'
		)));

		return $this;
	}

	protected function withGroupRow() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Abteilung'), 'input', array(
			'db_alias'			=> '',
			'db_column' => 'group'
		)));

		return $this;
	}

	protected function withUploadRow(string $uploadPath) {

		$upload = new \Ext_Gui2_Dialog_Upload($this->gui2, $this->t('Foto'), $this->dialog, 'image', '', $uploadPath);
		$this->currentElement->setElement($upload->generateHTML());

		return $this;
	}

}
