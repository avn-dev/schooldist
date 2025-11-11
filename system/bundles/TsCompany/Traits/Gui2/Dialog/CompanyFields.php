<?php

namespace TsCompany\Traits\Gui2\Dialog;

use TsCompany\Entity\AbstractCompany;

trait CompanyFields {

	protected function withActiveRow() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Aktiv'), 'checkbox', array(
			'db_alias' => 'ka',
			'db_column' => 'status',
			'required' => 0,
		)));

		return $this;
	}

	protected function withNameBlock() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Name'), 'input', array(
			'db_alias' => 'ka',
			'db_column' => 'ext_1',
			'required' => 1,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Abkürzung'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_2',
			'required'			=> 1,
		)));

		return $this;
	}

	protected function withNumberRow() {

		// Wenn bereits eine Nummer mit Nummernkreis generiert wurde, darf diese nicht mehr bearbeitet werden
		$disabled = false;
		if(
			$this->entity instanceof AbstractCompany &&
			$this->entity->exist() &&
			!empty($this->entity->numbers) &&
			$this->entity->numbers[0]['numberrange_id'] != 0
		) {
			$disabled = true;
		}

		$this->currentElement->setElement($this->dialog->createRow($this->t('Nummer'), 'input', array(
			'db_column' => 'number',
			'disabled' => $disabled
		)));

		return $this;
	}

	protected function withWebRow() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Web'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_10',
			'required'			=> 0,
		)));

		return $this;
	}

	protected function withAddressBlock(bool $withHeading = true) {

		if($withHeading) {
			$this->heading($this->t('Adresse'));
		}

		$this->currentElement->setElement($this->dialog->createRow($this->t('Adresse'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_3',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Adresszusatz'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_35',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('PLZ'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_4',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Stadt'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_5',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Bundesland'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'state',
			'required'			=> 0,
		)));

		$countries = \Ext_Thebing_Data::getCountryList();
		$countries = \Ext_Thebing_Util::addEmptyItem($countries);

		$this->currentElement->setElement($this->dialog->createRow($this->t('Land'), 'select', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_6',
			'required'			=> 0,
			'select_options'	=> $countries,
		)));

		return $this;
	}

	protected function withInfoBlock() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Gründungsjahr'), 'input', [
			'db_alias' => 'ka',
			'db_column' => 'founding_year',
			'required' => 0,
			'max_length' => 4,
		]));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Beginn der Zusammenarbeit'), 'input', [
			'db_alias' => 'ka',
			'db_column' => 'start_cooperation',
			'required' => 0,
		]));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Anzahl der Mitarbeiter'), 'input', [
			'db_alias' => 'ka',
			'db_column' => 'staffs',
			'required' => 0,
			'max_length' => 5,
		]));

		return $this;
	}

	protected function withBankBlock(bool $withHeading = true) {

		if($withHeading) {
			$this->heading($this->t('Bank'));
		}

		$this->currentElement->setElement($this->dialog->createRow($this->t('Kontoinhaber'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_12',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Name der Bank'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_13',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Bankleitzahl'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_14',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('Kontonummer'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_16',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('SWIFT/BIC'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_15',
			'required'			=> 0,
		)));

		$this->currentElement->setElement($this->dialog->createRow($this->t('IBAN'), 'input', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_17',
			'required'			=> 0,
		)));

		return $this;
	}

	protected function withCommentRow() {

		$this->currentElement->setElement($this->dialog->createRow($this->t('Kommentar'), 'textarea', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'comment',
			'required'			=> 0,
		)));

		return $this;
	}

	protected function withCorrespondenceLanguageRow() {

		$languages			= \Ext_Thebing_Client::getLangList(true);
		$languages			= \Ext_Thebing_Util::addEmptyItem($languages);

		$this->currentElement->setElement($this->dialog->createRow($this->t('Korrespondenzsprache'), 'select', array(
			'db_alias'			=> 'ka',
			'db_column'			=> 'ext_33',
			'select_options'	=> $languages,
			'required'			=> 1,
		)));

		return $this;
	}

	protected function withValidityGui($parentType, $itemType, $itemTitle) {

		$validity = new \Ext_Thebing_Validity($this->gui2, $parentType, $itemType);
		$validity->setItemTitle($itemTitle);

		$this->currentElement->setElement($validity->getValidityGui());

		return $this;
	}

}
