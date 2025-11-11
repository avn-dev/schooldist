<?php

/**
 *  Ersetzt Platzhalter innerhalb der Schecksvorlagen
 */
class Ext_Thebing_Accounting_Cheque_Placeholder extends Ext_Thebing_Placeholder {

    protected $_oCheque;
    protected $_aChequeInfo = array();
    protected $_aSupplierInfo = array();

    public $_oPdfTemplate;

	/**
	 * @param <type> $iChequeId
	 */
	public function __construct($iChequeId = 0) {

            $this->_oCheque = Ext_Thebing_Accounting_Cheque::getInstance($iChequeId);
            $this->_aChequeInfo = $this->_oCheque->getAdditionalInfo(); //Allgemeine Informatioen
	}

	/**
	 * @return array
	 */
	public function getPlaceholders($sType = ''){
            $aPlaceholders = array(
                array(
                    'section'	   => L10N::t('Scheck', Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
                    'placeholders' => array(
                    'check_number'                => L10N::t('Schecknummer',				 Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
					'check_payment_date'          => L10N::t('Datum der Scheckbezahlung',	 Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
					'check_payment_amount_number' => L10N::t('Betrag als Zahl',				 Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
					'check_payment_amount_word'   => L10N::t('Betrag als Text',				 Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
					'check_payment_vendor'        => L10N::t('Empfänger der Scheckbezahlung',Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
					'check_payment_note'          => L10N::t('Kommentar',					 Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
					'check_payment_amount_number_nocurrency' => L10N::t('Währungsplatzhalter', Ext_Thebing_Accounting_Cheque_Gui2::getDescriptionPart()),
									)
			)
	   );
	return $aPlaceholders;
	}

	protected function _getReplaceValue($sField, array $aPlaceholder) {

		$mValue	 = false;
		$oCheque = $this->_oCheque;

		switch ($sField) {

			/**  check area **/
			case 'check_payment_amount_number_nocurrency':	//missing currency
				if(is_object($oCheque)){
					$mValue = Ext_Thebing_Format::Number($this->_aChequeInfo['amount']);
			    }
			    break;
            case 'check_payment_date':
                if(is_object($oCheque)) {

					$oSchool = Ext_Thebing_School::getInstance($this->_oCheque->school_id);
					$sLang = $oSchool->getLanguage();

					$mValue = [
						'value' => $oCheque->created,
						'format' => 'date',
						'language' => $sLang
					];
					
			    }
			    break;
            case 'check_payment_vendor':
                if(is_array($this->_aChequeInfo)){
                    $mValue = $this->_aChequeInfo['recipient'];
				}
				break;
            case 'check_payment_amount_number':
                if(
					is_object($oCheque) &&
					is_array($this->_aChequeInfo)
				){
                    $sAmount = Ext_Thebing_Format::Number($this->_aChequeInfo['amount'], $this->_aChequeInfo['currency_id'], $oCheque->school_id);
                    $mValue = $sAmount;
				}
				break;
            case 'check_payment_amount_word':
				if(is_array($this->_aChequeInfo)){
                    $oCurrency = Ext_Thebing_Currency::getInstance($this->_aChequeInfo['currency_id']);
                    $oSchool = Ext_Thebing_School::getInstance($this->_oCheque->school_id);
                    $sLang = $oSchool->getLanguage();

					$oNumbersWords = new \Ts\Helper\NumbersWords($sLang);

					$mValue = $oNumbersWords->toCurrency($this->_aChequeInfo['amount'], $oCurrency->iso4217);

				}
				break;
            case 'check_payment_note':		//Kommentar
                if(is_array($this->_aChequeInfo)){
                    $mValue = $this->_aChequeInfo['comment'];
				}
				break;
            case 'check_number':
				if(is_object($oCheque)) {
                    $mValue = $oCheque->cheque_number;
				}
				break;
            case 'user_id':
				if(is_object($oCheque)) {
                    $mValue = $oCheque->user_id;  //Format
				}
				break;
            case 'type':
				if(is_object($oCheque)){	// Zahlungstyp
					$mValue = $oCheque->type;
				}
				break;
        }

        return $mValue;
	}


	/*
	 *   Pdf Template laden (default Template), anschliessend können die Platzhalter
	 *  durch entsprechende Werte aus der Übersichtstabelle (DB) ersetzt werden.
	 */
	public function getPdfTemplate(){

		$sSql = "SELECT
					kpt.id,
					kpt.name
				FROM
					`kolumbus_pdf_templates` kpt  INNER JOIN
					`kolumbus_pdf_templates_types` kptt ON
						kpt.template_type_id = kptt.id INNER JOIN
					`kolumbus_pdf_templates_static_elements_values` kptsev ON
						kpt.id = kptsev.template_id
				WHERE
					kpt.type='cheque' AND
					kpt.active = 1";

		 $aData = DB::executeQuery($sSql);

		$this->_oPdfTemplate  = Ext_Thebing_Pdf_Template::getInstance((int)$aData['id']);
		
	}


}
