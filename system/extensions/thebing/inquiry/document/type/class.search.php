<?php


class Ext_Thebing_Inquiry_Document_Type_Search {

    protected $_aTypes = array();


    public function add($sType){
        $this->_aTypes[$sType] = $sType;
    }
    
    public function remove($sType){
        unset($this->_aTypes[$sType]);
    }

    public function addSection($sSection){
        $aSectionTypes = $this->getSectionTypes($sSection);
        $this->_aTypes += $aSectionTypes;
    }
    
    public function removeSection($sSection){
        $aSectionTypes = $this->getSectionTypes($sSection);
        $this->_aTypes -= $aSectionTypes;
    }
    
    public function getTypes(){
        return $this->_aTypes;
    }

	/**
	 * @see Ext_Thebing_Inquiry_Document_Search::getTypeData()
	 * @see Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString()
	 */
    public function getSectionTypes($sSection){
        switch($sSection) {
			case 'proforma_with_creditnote':
				$mReturn[] = 'proforma_creditnote';
            case 'proforma':
                $mReturn[] = 'proforma_brutto';
                $mReturn[] = 'proforma_brutto_diff';
                $mReturn[] = 'proforma_netto';
                $mReturn[] = 'proforma_netto_diff';
                $mReturn[] = 'group_proforma';
                $mReturn[] = 'group_proforma_netto';
                break;
            case 'invoice_creditnote_manual_creditnote_offer':
				$mReturn[] = 'offer_brutto';
				$mReturn[] = 'offer_netto';
			// Case für Dokumente die Nummernkreise haben können
            case 'invoice_with_creditnote_and_manual_creditnote':
                $mReturn['manual_creditnote'] = 'manual_creditnote';
            case 'invoice_with_creditnote':
                $mReturn['creditnote'] = 'creditnote';
				$mReturn[] = 'creditnote_subagency';
				$mReturn[] = 'proforma_creditnote';
            case 'invoice':
                $mReturn['storno'] = 'storno';
            case 'invoice_without_storno':
                $mReturn[] = 'brutto';
                $mReturn[] = 'netto';
                $mReturn[] = 'brutto_diff';
                $mReturn[] = 'brutto_diff_special';
                $mReturn[] = 'netto_diff';
                $mReturn[] = 'proforma_brutto';
                $mReturn[] = 'proforma_brutto_diff';
                $mReturn[] = 'proforma_netto';
                $mReturn[] = 'proforma_netto_diff';
                $mReturn[] = 'group_proforma';
                $mReturn[] = 'group_proforma_netto';
                $mReturn[] = 'credit_brutto';
                $mReturn[] = 'credit_netto';
                $mReturn[] = 'credit';//abwertskompatibilität
                break;
			case 'invoice_netto':
				$mReturn[] = 'proforma_netto';
            case 'invoice_netto_without_proforma':
                $mReturn[] = 'netto';
                $mReturn[] = 'netto_diff';
                $mReturn[] = 'credit_netto';
                break;
			case 'invoice_brutto':
				$mReturn[] = 'proforma_brutto';
            case 'invoice_brutto_without_proforma':
                $mReturn[] = 'brutto';
                $mReturn[] = 'brutto_diff';
                $mReturn[] = 'brutto_diff_special';
                $mReturn[] = 'credit_brutto';
                break;
            case 'invoice_proforma':
                $mReturn[] = 'proforma_brutto';
                $mReturn[] = 'proforma_netto';
                break;
            case 'invoice_with_creditnotes_and_without_proforma':
                $mReturn[] = 'manual_creditnote';
                $mReturn[] = 'creditnote';
                $mReturn[] = 'creditnote_subagency';
            case 'invoice_without_proforma':
                $mReturn[] = 'brutto';
                $mReturn[] = 'netto';
                $mReturn[] = 'brutto_diff';
                $mReturn[] = 'brutto_diff_special';
                $mReturn[] = 'netto_diff';
//                $mReturn[] = 'group_proforma';
//                $mReturn[] = 'group_proforma_netto';
                $mReturn[] = 'credit_brutto';
                $mReturn[] = 'credit_netto';
                $mReturn[] = 'credit';//abwärtskompatibilität
                $mReturn[] = 'storno';
                break;
            case 'invoice_without_proforma_and_cancellation':
                $mReturn[] = 'brutto';
                $mReturn[] = 'netto';
                $mReturn[] = 'brutto_diff';
                $mReturn[] = 'brutto_diff_special';
                $mReturn[] = 'netto_diff';
                $mReturn[] = 'credit_brutto';
                $mReturn[] = 'credit_netto';
                break;
            case 'receipt':
                $mReturn[] = 'receipt_customer';
                $mReturn[] = 'receipt_agency';
                $mReturn[] = 'document_payments_customer';
                $mReturn[] = 'document_payments_agency';
                $mReturn[] = 'document_payments_overview_customer';
                $mReturn[] = 'document_payments_overview_agency';
                break;
			case 'offer':
				$mReturn[] = 'offer_brutto';
				$mReturn[] = 'offer_netto';
				break;
            default:
                $mReturn[] = $sSection;
                break;
        }
        
        $mReturn = array_combine($mReturn, $mReturn);
        
        return $mReturn;
    }
		
}
