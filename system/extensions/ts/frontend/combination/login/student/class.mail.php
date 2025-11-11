<?php

/**
 * Wird wohl nicht mehr verwendet. War für alten Schüler-Login?
 */
class Ext_TS_Frontend_Combination_Login_Student_Mail extends Ext_TS_Frontend_Combination_Login_Student_Abstract
{
	protected function _setData()
	{	
		$oInquiry		= $this->_getInquiry();
		$oCustomer		= $oInquiry->getCustomer();
		$oSchool		= $oInquiry->getSchool();
		$iSchoolId		= (int)$oSchool->id;

		$oDate			= new WDDate();
		$oFormatDate	= new Ext_Thebing_Gui2_Format_Date(false, $iSchoolId);

		$oGuiTemp	= new Ext_Thebing_Gui2_Communication(md5('xxx'), 'Ext_Thebing_Communication_Gui2_Data');
		$oGuiTemp->setWDBasic('Ext_Thebing_Email_Log');
		$oGuiTemp->setTableData('orderby', array('kel.created'=>'DESC'));

		// Bar
		$oBar						= $oGuiTemp->createBar();
		$oFilter					= $oBar->createFilter();
		$oFilter->db_column			= array('subject', 'content');
		$oFilter->db_alias			= array('', '');
		$oFilter->db_operator		= 'LIKE';
		$oFilter->id				= 'search';
		$oBar->setElement($oFilter);
		$oGuiTemp->setBar($oBar);

		$oData		= $oGuiTemp->getDataObject();

		$sSecureUrl = $this->_getUrl('get_file').'&file=';

		$oGuiTemp->setMainObject(array($oCustomer->id), 'Ext_TS_Inquiry_Contact_Traveller');
		$oGuiTemp->setRelationObject(array($oInquiry->id), 'Ext_TS_Inquiry');

		$aFilter = array();

		if(
			$sSearch = $this->_getParam('search')
		)
		{
			$aFilter['search'] = $sSearch;
		}

		$aResults	= $oData->getTableQueryData($aFilter);

		if(
			isset($aResults['data'])
		)
		{
			$aMails		= (array)$aResults['data'];
		}
		else
		{
			$aMails		= array();
		}

		$sTableCommunication = '';
		$sTableCommunication .= '<table>';
		$sTableCommunication .= '<tr>';

		$sTableCommunication .= '<th class="date">';
		$sTableCommunication .= $this->t('Date');
		$sTableCommunication .= '</th>';

		$sTableCommunication .= '<th class="time">';
		$sTableCommunication .= $this->t('Time');
		$sTableCommunication .= '</th>';

		$sTableCommunication .= '<th class="from">';
		$sTableCommunication .= $this->t('From');
		$sTableCommunication .= '</th>';

		$sTableCommunication .= '<th>';
		$sTableCommunication .= $this->t('Subject');
		$sTableCommunication .= '</th>';

		$sTableCommunication .= '<th class="content">';
		$sTableCommunication .= $this->t('Content');
		$sTableCommunication .= '</th>';

		$sTableCommunication .= '<th class="attachment">';
		$sTableCommunication .= $this->t('Attachments');
		$sTableCommunication .= '</th>';

		$sTableCommunication .= '</tr>';

		foreach($aMails as $aMail)
		{
			$oDate->set($aMail['created'], WDDate::TIMESTAMP);

			$sDate		= $oFormatDate->format($aMail['created']);
			$sTime		= $oDate->get(WDDate::HOUR).':'.$oDate->get(WDDate::MINUTE);
			$sSubject	= $aMail['subject'];
			$sContent	= htmlspecialchars(substr($aMail['content'],0,70));
			if(strlen($aMail['content']) > 70){
				$sContent .= '...';
			}

			$iSenderId		= (int)$aMail['sender_id'];
			$oSystemUser	= Ext_Thebing_User::getInstance($iSenderId);
			$sSenderMail	= $oSystemUser->email;

			$sContentAttachments  = '';
			$aAttachments	= json_decode($aMail['attachments'], true);
			$aDocuments		= json_decode($aMail['documents'], true);

			foreach($aAttachments as $sFilePath => $sFileName)
			{
				if(
					!empty($sContentAttachments)
				)
				{
					$sContentAttachments .= '<br />';
				}

				$sFileName	= str_replace($oInquiry->id.'_','',$sFileName);
				$sRoothPath	= Util::getDocumentRoot().'storage';
				$sFilePath	= str_replace($sRoothPath, '', $sFilePath);

				$sLink = '<a href="'.$sSecureUrl.$sFilePath.'" target="_blank">';
				$sLink .= $sFileName;
				$sLink .= '</a>';

				$sContentAttachments .= $sLink;
			}
			
			if(
				isset($aDocuments) &&
				isset($aDocuments['inquiry'])
			)
			{
				$aDocuments = (array)$aDocuments['inquiry'];

				foreach($aDocuments as $iVersionId)
				{
					$oInquiryDocumentVersion	= Ext_Thebing_Inquiry_Document_Version::getInstance($iVersionId);

					if(
						$oInquiryDocumentVersion->id > 0
					)
					{
						if(
							!empty($sContentAttachments)
						)
						{
							$sContentAttachments .= '<br />';
						}

						$sFilePath = $oInquiryDocumentVersion->path;

						$sFileName = basename($sFilePath);

						$sLink = '<a href="'.$sSecureUrl.$sFilePath.'" target="_blank">';
						$sLink .= $sFileName;
						$sLink .= '</a>';

						$sContentAttachments .= $sLink;
					}
				}
			}

			$sTableCommunication .= '<tr>';

			$sTableCommunication .= '<td class="date">';
			$sTableCommunication .= $sDate;
			$sTableCommunication .= '</td>';

			$sTableCommunication .= '<td class="time">';
			$sTableCommunication .= $sTime;
			$sTableCommunication .= '</td>';

			$sTableCommunication .= '<td class="from">';
			$sTableCommunication .= $sSenderMail;
			$sTableCommunication .= '</td>';

			$sTableCommunication .= '<td>';
			$sTableCommunication .= $sSubject;
			$sTableCommunication .= '</td>';

			$sTableCommunication .= '<td class="content">';
			$sTableCommunication .= $sContent;
			$sTableCommunication .= '</td>';

			$sTableCommunication .= '<td class="attachment">';
			$sTableCommunication .= $sContentAttachments;
			$sTableCommunication .= '</td>';

			$sTableCommunication .= '</tr>';
		}

		$sTableCommunication .= '</table>';

		$this->_assign('sMailsData', $sTableCommunication);

		$sSearch = '';
		if(
			$this->_getParam('search')
		)
		{
			$sSearch = $this->_getParam('search');
		}

		$this->_assign('sSearch', $sSearch);

		$this->_setTask('showMails');
	}

}
