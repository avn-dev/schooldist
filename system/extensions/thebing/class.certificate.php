<?php

/**
 * The student certificates class
 */

class Ext_Thebing_Certificate
{
	/**
	 * The student card settings
	 */
	private $_aData = array(
		'id'			=> null,
		'created'		=> null,
		'changed'		=> null,
		'active'		=> 1,
		'school_id'		=> null,
		'din'			=> '210/297',
		'font'			=> 'Arial',
		'font_color'	=> '000000',
		'font_size'		=> 10,
		'text_pos'		=> '0/0',
		'text_align'	=> 'L',
		'text'			=> ''
	);


	/**
	 * The available DIN formats
	 */
	private $_aDINs = array(
		'297/420'	=> 'A3',
		'210/297'	=> 'A4',
		'148/210'	=> 'A5',
		'216/279'	=> 'Letter',
		'216/356'	=> 'Legal',
		'*'			=> 'Manualy'
	);


	/**
	 * The main PDF document
	 */
	private $_oMainPDF;
	private $_oPDF;


	/**
	 * The constructor
	 */
	public function __construct()
	{
		$this->_loadData();
	}


	/**
	 * Returns the value of key from data array
	 * 
	 * @param string : The key from data array
	 * 
	 * @example : To get the X/Y positions of age (default '0/0') do
	 * 		$x = $oObject->text_X;
	 * 		$y = $oObject->text_Y;
	 */
	public function __get($sName)
	{
		if(array_key_exists($sName, $this->_aData))
		{
			return $this->_aData[$sName];
		}

		if($sName == 'aDINs')
		{
			return $this->_aDINs;
		}

		if(
			($sPos = substr($sName, strpos($sName, '_X'))) == '_X'
				||
			($sPos = substr($sName, strpos($sName, '_Y'))) == '_Y'
		)
		{
			if(!array_key_exists(str_replace($sPos, '', $sName), $this->_aData))
			{
				throw new Exception('The "'.$sName.'" does not exists!');
			}

			$aTmp = explode('/', $this->_aData[str_replace($sPos, '', $sName)]);

			if(count($aTmp) == 1)
			{
				return '';
			}

			switch($sPos)
			{
				case '_X':	return $aTmp[0];
				case '_Y':	return $aTmp[1];
			}
		}

		throw new Exception('The "'.$sName.'" does not exists!');
	}


	/**
	 * Sets the value into the key in data array
	 * 
	 * @param string : The key in data array
	 * @param mixed : The value
	 */
	public function __set($sName, $mValue)
	{
		if(array_key_exists($sName, $this->_aData))
		{
			$this->_aData[$sName] = $mValue;
			return;
		}

		throw new Exception('The "'.$sName.'" does not exists!');
	}


	public function createCertificates($aData = array())
	{

		/*
		foreach((array)$aData as $iKey=>$iCourseId) {
			$oCourse = new Ext_Thebing_Inquiry_Course($iCourseId);
			$oInquiry = new Ext_Thebing_Inquiry($oCourse->inquiry_id);
			$oCustomer = $oInquiry->getCustomer();
			break;
		}*/
		$iFirstId	= reset($aData);
		$oInquiry	= Ext_TS_Inquiry::getInstance($iFirstId);
		$oSchool	= $oInquiry->getSchool();
		$sSchoolDir = $oSchool->getSchoolFileDir();
		$oCustomer	= $oInquiry->getCustomer();
		$sFile		= NULL;
		
		if(!empty($aData) && $oInquiry->id > 0){
			
			$oInbox = $oInquiry->getInbox();
			
			$aTemplates = Ext_Thebing_Pdf_Template_Search::s('document_certificates', $oCustomer->getLanguage(), $oSchool->id, $oInbox->id);
			
			if(!empty($aTemplates)){
				$oTemplate = $aTemplates[0];
				$oPdf  = new Ext_Thebing_Pdf_Basic($oTemplate->id, $oSchool->id);
				
				foreach((array)$aData as $iKey => $iInquiryId) {
					//$oCourse = new Ext_Thebing_Inquiry_Course($iCourseId);
					
					$oDoc = new Ext_Thebing_Pdf_Document();
					$oDoc->inquiry_id = $iInquiryId;
					
					$oPdf->addDocument($oDoc);
				}

				$sFile = $oPdf->createPDF($sSchoolDir.'/', 'certificates');
				
			} else {
				throw new Exception('NO_TEMPLATE');
			}
			
		}

		return $sFile;
	}

	/**
	 * Prepares the students data for PDF output
	 * 
	 * @return array : The array with prepared studen data
	 */
	public function getStudentsByIDs($aIDs = array())
	{
		return $aIDs;
	}


	/**
	 * Saves the settings into the DB
	 */
	public function save()
	{
		$iID = $this->_aData['id'];
		unset($this->_aData['id'], $this->_aData['created'], $this->_aData['changed']);

		$sSET = "";
		$i = 1;
		foreach($this->_aData as $sKey => $mValue)
		{
			$sSET .= " `".$sKey."` = :".$sKey." ";
			if(count($this->_aData) > $i)
			{
				$sSET .= ", ";
			}
			$i++;
		}

		if((int)$iID <= 0)
		{
			unset($this->_aData['id']);
			$this->_aData['created'] = date('YmdHis');

			$sSET .= ", `created` = :created";

			$sSQL = "
				INSERT INTO `kolumbus_tpl_cerificate`
				SET {SET}
			";
			DB::executePreparedQuery(str_replace('{SET}', $sSET, $sSQL), $this->_aData);
			$this->_aData['id'] = DB::fetchInsertId();
		}
		else
		{
			$sSQL = "
				UPDATE `kolumbus_tpl_cerificate`
				SET {SET}
				WHERE `school_id` = :tmp_id
			";
			DB::executePreparedQuery(
				str_replace('{SET}', $sSET, $sSQL),
				array_merge($this->_aData, array('tmp_id' => \Core\Handler\SessionHandler::getInstance()->get('sid')))
			);
		}

		return $this;
	}


	/**
	 * Loads specified data from DB
	 */
	private function _loadData()
	{
		$this->_aData['school_id'] = \Core\Handler\SessionHandler::getInstance()->get('sid');;

//		$sSQL = "
//			SELECT *, UNIX_TIMESTAMP(`created`) AS `created`, UNIX_TIMESTAMP(`changed`) AS `changed`
//			FROM `kolumbus_tpl_cerificate`
//			WHERE `school_id` = :iSchoolID
//			LIMIT 1
//		";
//		$aSettings = DB::getPreparedQueryData($sSQL, array('iSchoolID' => $this->_aData['school_id']));
//
//		if(!empty($aSettings))
//		{
//			foreach($aSettings[0] as $mKey => $mValue)
//			{
//				if(array_key_exists($mKey, $this->_aData))
//				{
//					$this->_aData[$mKey] = $mValue;
//				}
//			}
//		}
				
	}
}

?>