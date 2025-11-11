<?php


class Ext_TS_Frontend_Combination_Login_Student_Document extends Ext_TS_Frontend_Combination_Login_Student_Abstract
{
	protected function _setData()
	{
		$oInquiry			= $this->_getInquiry();
		$aDocuments			= (array)$oInquiry->getDocuments('invoice_without_storno', true, true);
		$aAdditional		= (array)$oInquiry->getDocuments('additional_document', true, true);
		$iCurrencyId		= (int)$oInquiry->getCurrency();
		$oSchool			= $oInquiry->getSchool();
		$iSchoolId			= (int)$oSchool->id;
		$oFormatDate		= new Ext_Thebing_Gui2_Format_Date(false, $iSchoolId);
		$oDummy				= null;
		$aTemplateTypes		= Ext_Thebing_Pdf_Template::getApplications();
		$sSecureUrl			= $this->_getUrl('get_file').'&file=';

		$aInquiryIds		= array($oInquiry->id);
		$aPayments			= (array)Ext_Thebing_Inquiry_Payment::searchPaymentsByInquiryArray($aInquiryIds);

		$sTableDocumentsHtml = '';
		$sTableDocumentsHtml .= '<table>';
		$sTableDocumentsHtml .= '<tr>';

		$sTableDocumentsHtml .= '<th>';
		$sTableDocumentsHtml .= $this->t('Inv No');
		$sTableDocumentsHtml .= '</th>';

		$sTableDocumentsHtml .= '<th>';
		$sTableDocumentsHtml .= $this->t('Due Date');
		$sTableDocumentsHtml .= '</th>';

		$sTableDocumentsHtml .= '<th>';
		$sTableDocumentsHtml .= $this->t('Amount');
		$sTableDocumentsHtml .= '</th>';

		$sTableDocumentsHtml .= '<th>';
		$sTableDocumentsHtml .= $this->t('PDF');
		$sTableDocumentsHtml .= '</th>';

		$sTableDocumentsHtml .= '</tr>';
		
		foreach($aDocuments as $oDocument)
		{
			$oVersion = $oDocument->getLastVersion();

			if(
			  is_object($oVersion) &&
			  $oVersion instanceof Ext_Thebing_Inquiry_Document_Version
			)
			{
				$sTableDocumentsHtml .= '<tr>';

				$fAmount	= $oVersion->getAmount();

				$sAmount	= Ext_Thebing_Format::Number($fAmount, $iCurrencyId, $iSchoolId, true, 2);
				$sDate		= $oFormatDate->format($oVersion->amount_finalpay_due);

				//@todo: controller dafür bauen, inquiry_ids abchecken, so könnte jeder in jedes dokument reinschauen
				$sFilePath	= $oVersion->getPath();
				$sUrl		= $sSecureUrl.$sFilePath;

				$sImg		= '<a href="'.$sUrl.'" target="_blank">';
				$sImg		.= '<img src="../icef_login/page_white_acrobat.png" style="margin-top: 2px;" alt="PDF" />';
				$sImg		.= '</a>';

				$sTableDocumentsHtml .= '<td>';
				$sTableDocumentsHtml .= $oDocument->document_number;
				$sTableDocumentsHtml .= '</td>';
				
				$sTableDocumentsHtml .= '<td>';
				$sTableDocumentsHtml .= $sDate;
				$sTableDocumentsHtml .= '</td>';

				$sTableDocumentsHtml .= '<td>';
				$sTableDocumentsHtml .= $sAmount;
				$sTableDocumentsHtml .= '</td>';

				$sTableDocumentsHtml .= '<td>';
				$sTableDocumentsHtml .= $sImg;
				$sTableDocumentsHtml .= '</td>';

				$sTableDocumentsHtml .= '</tr>';
			}
		}

		$sTableDocumentsHtml .= '</table>';

		$sTableAdditionalHtml = '';
		$sTableAdditionalHtml .= '<table>';
		$sTableAdditionalHtml .= '<tr>';

		$sTableAdditionalHtml .= '<th>';
		$sTableAdditionalHtml .= $this->t('Document');
		$sTableAdditionalHtml .= '</th>';

		$sTableAdditionalHtml .= '<th>';
		$sTableAdditionalHtml .= $this->t('Date');
		$sTableAdditionalHtml .= '</th>';

		$sTableAdditionalHtml .= '<th>';
		$sTableAdditionalHtml .= $this->t('PDF');
		$sTableAdditionalHtml .= '</th>';

		$sTableAdditionalHtml .= '</tr>';

		foreach($aAdditional as $oDocument)
		{
			$oVersion	= $oDocument->getLastVersion();

			if(
			  is_object($oVersion) &&
			  $oVersion instanceof Ext_Thebing_Inquiry_Document_Version
			)
			{
				$oTemplate	= $oVersion->getTemplate();
				$sType		= $oTemplate->type;
				$sDate		= $oFormatDate->format($oVersion->date);

				if(
					isset($aTemplateTypes[$sType])
				)
				{
					$sDocumentType = $aTemplateTypes[$sType];
				}
				else
				{
					$sDocumentType = '';
				}

				//@todo: controller dafür bauen, inquiry_ids abchecken, so könnte jeder in jedes dokument reinschauen
				$sFilePath	= $oVersion->getPath();
				$sUrl		= $sSecureUrl.$sFilePath;

				$sImg		= '<a href="'.$sUrl.'" target="_blank">';
				$sImg		.= '<img src="../icef_login/page_white_acrobat.png" style="margin-top: 2px;" alt="PDF" />';
				$sImg		.= '</a>';

				$sTableAdditionalHtml .= '<tr>';

				$sTableAdditionalHtml .= '<td>';
				$sTableAdditionalHtml .= $sDocumentType;
				$sTableAdditionalHtml .= '</td>';

				$sTableAdditionalHtml .= '<td>';
				$sTableAdditionalHtml .= $sDate;
				$sTableAdditionalHtml .= '</td>';

				$sTableAdditionalHtml .= '<td>';
				$sTableAdditionalHtml .= $sImg;
				$sTableAdditionalHtml .= '</td>';

				$sTableAdditionalHtml .= '</tr>';
			}
		}

		$sTableAdditionalHtml .= '</table>';


		$sTablePaymentHtml = '';
		$sTablePaymentHtml .= '<table>';
		$sTablePaymentHtml .= '<tr>';

		$sTablePaymentHtml .= '<th>';
		$sTablePaymentHtml .= $this->t('Inv No');
		$sTablePaymentHtml .= '</th>';

		$sTablePaymentHtml .= '<th>';
		$sTablePaymentHtml .= $this->t('Payment Date');
		$sTablePaymentHtml .= '</th>';

		$sTablePaymentHtml .= '<th>';
		$sTablePaymentHtml .= $this->t('Amount');
		$sTablePaymentHtml .= '</th>';

		$sTablePaymentHtml .= '<th>';
		$sTablePaymentHtml .= $this->t('PDF');
		$sTablePaymentHtml .= '</th>';

		$sTablePaymentHtml .= '</tr>';

		foreach($aPayments as $aPayment)
		{
			$oPayment = new Ext_Thebing_Inquiry_Payment($aPayment['id']);

			$sDocumentNumber	= $aPayment['document_number'];
			$sDate				= $oFormatDate->format($oPayment->date);
			$fPaymentAmount		= $oPayment->getAmount();
			$sAmount			= Ext_Thebing_Format::Number($fPaymentAmount, $iCurrencyId, $iSchoolId, true, 2);

			// Kunden Quittungen
			$aCustomerPaymentDocuments = $oPayment->searchDocument('receipt_customer', $aPayment['first_inquiry_id'], false);
			if(!empty($aCustomerPaymentDocuments))
			{
				foreach((array)$aCustomerPaymentDocuments as $iKey=>$oPaymentDocument)
				{
					$oPaymentVersion = $oPaymentDocument->getLastVersion();
					$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oPaymentVersion->template_id);

					$aCustomerPaymentDocuments[$iKey] = array();
					$aCustomerPaymentDocuments[$iKey]['path']	  = $oPaymentVersion->path;
					$aCustomerPaymentDocuments[$iKey]['template'] = $oTemplate->name;
				}
			}

			$sTablePaymentHtml .= '<tr>';

			$sTablePaymentHtml .= '<td>';
			$sTablePaymentHtml .= $sDocumentNumber;
			$sTablePaymentHtml .= '</td>';

			$sTablePaymentHtml .= '<td>';
			$sTablePaymentHtml .= $sDate;
			$sTablePaymentHtml .= '</td>';

			$sTablePaymentHtml .= '<td>';
			$sTablePaymentHtml .= $sAmount;
			$sTablePaymentHtml .= '</td>';

			$sTablePaymentHtml .= '<td>';
			if(!empty($aCustomerPaymentDocuments))
			{
				foreach((array)$aCustomerPaymentDocuments as $aCustomerPaymentDocument)
				{
					//@todo: controller dafür bauen, inquiry_ids abchecken, so könnte jeder in jedes dokument reinschauen
					$sFilePath	= $aCustomerPaymentDocument['path'];
					$sUrl		= $sSecureUrl.$sFilePath;

					$sImg		= '<a href="'.$sUrl.'" target="_blank">';
					$sImg		.= '<img src="../icef_login/page_white_acrobat.png" style="margin-top: 2px;" alt="PDF" />';
					$sImg		.= '</a>';

					$sTablePaymentHtml .= $sImg;
				}
			}
			else
			{
				$sTablePaymentHtml .= '&nbsp;';
			}
			$sTablePaymentHtml .= '</td>';

			$sTableAdditionalHtml .= '</tr>';
		}

		$sTablePaymentHtml .= '</table>';

		$this->_assign('sInvoiceTable', $sTableDocumentsHtml);
		$this->_assign('sAdditionalTable', $sTableAdditionalHtml);
		$this->_assign('sPaymentTable', $sTablePaymentHtml);

		$this->_setTask('showDocuments');
	}

}