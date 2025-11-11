<?php


class Ext_TS_Frontend_Combination_Login_Student_File extends Ext_TS_Frontend_Combination_Login_Student_Abstract
{
	protected function _setData()
	{
		if(
			$this->_getParam('document_id')
		)
		{
			$iDocumentId			= (int)$this->_getParam('document_id');
			$oDocument				= Ext_Thebing_Inquiry_Document::getInstance($iDocumentId);
			$oInquiryForDocument	= $oDocument->getInquiry();
			$oInquiry				= $this->_getInquiry();
			$iInquiryId				= (int)$oInquiry->id;
			$iInquiryIdForDocument	= (int)$oInquiryForDocument->id;
			$oVersion				= $oDocument->getLastVersion();

			if(
				$iInquiryId > 0 &&
				$iInquiryIdForDocument == $iInquiryId &&
				is_object($oVersion) &&
				$oVersion instanceof Ext_Thebing_Inquiry_Document_Version
			)
			{
				$sFileName = $oVersion->getPath(true);

				$this->_setHeader('application/pdf', $sFileName);

				$this->_setTask('abc');
			}
			else
			{
				$this->_default();
			}
		}
		else
		{
			$this->_default();
		}
	}
}