<?php

abstract class Ext_TS_Accounting_Provider_Grouping_Abstract extends Ext_Thebing_Basic implements \Communication\Interfaces\Model\HasCommunication {

	const sL10N = 'Thebing » Accounting » Provider Payments';

	abstract public function getItem();

	/**
	 * @return string
	 */
	abstract public function getType();

	/**
	 * @return string|null
	 */
	public function getPdfPath() {

		if(empty($this->file)) {
			return null;
		}

		return Util::getDocumentRoot().'storage/'.$this->file;

	}

	/**
	 * @inheritdoc
	 */
	public function delete() {

		$bSuccess = parent::delete();

		if($bSuccess) {
			$sFile = $this->getPdfPath();
			if(
				$sFile !== null &&
				is_file($sFile)
			) {
				unlink($sFile);
			}
		}

		return $bSuccess;

	}

	/**
	 * PDF einer Bezahlung generieren
	 *
	 * @param Ext_Thebing_Pdf_Template $oTemplate
	 * @param array $aAdditionalData Zusätzliche Daten, die in die Platzhalterklasse eingefügt werden
	 * @return string
	 */
	public function createPdf(Ext_Thebing_Pdf_Template $oTemplate, $aAdditionalData) {

		$sType = $this->getType();
		$sFilePath = '';

		$oPdf = new Ext_Thebing_Pdf_Basic($oTemplate->id);
		$oPdf->sDocumentType = 'payment_provider_'.$sType;

		$sFileName = $oTemplate->name.'_'.$this->id;
		$sFileName = \Util::getCleanFileName($sFileName);

		$sPath = Ext_Thebing_Util::getSecureDirectory(true).'provider_payment/'.$sType.'/';

		$bCreate = $this->_prepareDocument($oPdf, $oTemplate, $aAdditionalData);
		if($bCreate) {
			$sFilePath = $oPdf->createPdf($sPath, $sFileName);
			$this->log(Ext_Thebing_Log::PDF_CREATED);
		}

		return $sFilePath;

	}

	/**
	 * Daten vorbereiten für createPdf()
	 *
	 * @param Ext_Thebing_Pdf_Basic $oPdf
	 * @param Ext_Thebing_Pdf_Template $oTemplate
	 * @param array $aAdditionalData Wert wird in die Templateklasse geschleust
	 * @return bool
	 */
	protected function _prepareDocument(Ext_Thebing_Pdf_Basic $oPdf, Ext_Thebing_Pdf_Template $oTemplate, $aAdditionalData) {
		if($oTemplate->id > 0) {

			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$sLanguage = $oSchool->getLanguage();

			$oPlaceholder = $this->getOldPlaceholderObject();
			$oPlaceholder->sTemplateLanguage = $sLanguage;
			$oPlaceholder->setAdditionalData('grouping_data', $aAdditionalData);

			$sTextIntro = $oTemplate->getStaticElementValue($sLanguage, 'text1');
			$sTextOutro = $oTemplate->getStaticElementValue($sLanguage, 'text2');
			$sSubject = $oTemplate->getStaticElementValue($sLanguage, 'subject');
			$sDate = $oTemplate->getStaticElementValue($sLanguage, 'date');
			$sAddress = $oTemplate->getStaticElementValue($sLanguage, 'address');

			$sTextIntro = $oPlaceholder->replace($sTextIntro);
			$sTextOutro = $oPlaceholder->replace($sTextOutro);
			$sSubject = $oPlaceholder->replace($sSubject);
			$sDate = $oPlaceholder->replace($sDate);
			$sAddress = $oPlaceholder->replace($sAddress);

			$aData = array(
				'txt_intro' => $sTextIntro,
				'txt_outro' => $sTextOutro,
				'txt_address' => $sAddress,
				'txt_subject' => $sSubject,
				'date' => $sDate,
				'txt_pdf' => '',
				'txt_signature' => '',
				'signature' => ''
			);

			try {
				$oPdf->createDummyDocument($aData, [], [], ['placeholder' => $oPlaceholder]);
			} catch(Exception $e) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * @return Ext_Thebing_Admin_Payment
	 */
	public function getPaymentMethod() {
		$oPayment = Ext_Thebing_Admin_Payment::getInstance($this->payment_method_id);
		return $oPayment;
	}

	/**
	 * @return Ext_Thebing_Payment_Provider_Abstract[]
	 */
	public function getPayments() {
		return $this->getJoinedObjectChilds('payments');
	}

	/**
	 * @return array
	 */
	public static function getOrderby() {
		return array('date' => 'DESC');
	}

}