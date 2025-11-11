<?
class Ext_Thebing_Template_Payment_Receipt {
	
	protected $_sTable = 'kolumbus_template_payment_receipt';
	protected $_bAllowed = true;
	protected $_iInquiry = 0;
	protected $_oExt_Thebing_Basic;
	
	public function __construct($iTemplateId,$iInquiry = 0){

        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$oExt_Thebing_Basic = new Ext_Thebing_Basic($iTemplateId, $this->_sTable);
		if($iTemplateId > 0){
			if($oExt_Thebing_Basic->school_id != $iSessionSchoolId){
				$this->_bAllowed = false;
				return false;
			}
		}
		$this->_iInquiry = $iInquiry;
		$this->_oExt_Thebing_Basic = $oExt_Thebing_Basic;
	}

	public function __get($sField){
		if($this->_bAllowed){
			return  $this->_oExt_Thebing_Basic->$sField;
		}
		
	}
	
	public function getField($sField){
		$this->__get($sField);
	}
	
	public function __set($sField, $mValue){
		if($this->_bAllowed){
			$this->_oExt_Thebing_Basic->$sField = $mValue;
		}
	}
	
	public function setFieldValue($sField, $mValue){
		$this->__set($sField, $mValue);
	}
	
	
	
	public function save(){
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		if($this->_bAllowed && $iSessionSchoolId > 0){
			$this->_oExt_Thebing_Basic->active = 1;
			$this->_oExt_Thebing_Basic->school_id = $iSessionSchoolId;
			$this->_oExt_Thebing_Basic->save();
		}
		return $this;
	}
	
	public function getSubject(){
		$sSubject = $this->subject;
		if($this->_iInquiry > 0){
			$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($this->_iInquiry);
			$sSubject = $oPlaceholder->replace($sSubject);
		}
		return $sSubject;
	}
	
	public function getAddress(){
		$sAddress = $this->address;
		if($this->_iInquiry > 0){
			$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($this->_iInquiry);
			$sAddress = $oPlaceholder->replace($sAddress);
		}
		return $sAddress;
	}
	
	public function getIntro(){
		$sIntro = $this->intro;
		if($this->_iInquiry > 0){
			$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($this->_iInquiry);
			$sIntro = $oPlaceholder->replace($sIntro);
		}
		return $sIntro;
	}
	
	public function getOutro(){
		$sOutro = $this->outro;
		if($this->_iInquiry > 0){
			$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($this->_iInquiry);
			$sOutro = $oPlaceholder->replace($sOutro);
		}
		return $sOutro;
	}
	
	public function getSignature(){
		return $this->signature;
	}
	
	public function getSignatureImg(){
		return $this->signature_img;
	}
	
	public function delete(){
		$this->_oExt_Thebing_Basic->active = 0;
		$this->_oExt_Thebing_Basic->save();
	}
	
}
