<?php
class Ext_TS_Inquiry_Saver_Visa extends Ext_TS_Inquiry_Saver_Abstract{
 
    public function prepareSaveValue($mValue, $sColumn) {
        
        $mValue = parent::prepareSaveValue($mValue, $sColumn);
        
        if(
            $sColumn == 'date_from' ||				#Visa
            $sColumn == 'date_until' ||				#Visa
            $sColumn == 'passport_date_of_issue' ||	#Visa
            $sColumn == 'passport_due_date'			#Visa
        ){
            $oSchoolForFormat   = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
            $mValue             = Ext_Thebing_Format::ConvertDate($mValue, $oSchoolForFormat->id, 1, true);
        }
        
        return $mValue;
        
    }
    
}