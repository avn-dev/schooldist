<?
class Ext_Thebing_System_Checks_MailLogImport extends Ext_Thebing_System_ThebingCheck {


	public function executeCheck(){
		global $user_data, $_VARS;

		try{
		//	Ext_Thebing_Util::backupTable('kolumbus_email_log');
		} catch(Exception $e){
			__pout($e);
			//return false;
		}

		$aSql = array();


		$sSql = "DELETE
					FROM
						`kolumbus_email_log`
					WHERE
						`created` < '2010-08-13 18:44:55'";


		DB::executeQuery($sSql);


		// All Logs
		$sSql = "SELECT
						*
					FROM
						`kolumbus_maillog`
					WHERE
						`active` = 1
				";

		$aLogs = DB::getQueryData($sSql);

		// All Inquiry
		$sSql = "SELECT
							id, office, idUser
						FROM
							`kolumbus_inquiries`
						WHERE
							`idUser` > 0
						";
			$aSql['user'] = (int)$aLog['idUser'];

			$aInquiries = DB::getPreparedQueryData($sSql,$aSql);


		foreach((array)$aLogs as $aLog){




			foreach($aInquiries as $aInquiry){

				if($aInquiry['idUser'] != $aLog['idUser']){
					continue;
				}
				$aRecipients = array();
				$aRecipients['to'] = $aLog['email'];
				$sAttachments = $this->getAttachments($aLog['attachments']);

				$sSql = "INSERT INTO
								`kolumbus_email_log`
							SET
								`client_id` = :client,
								`application` = :application,
								`object` = :object,
								`object_id` = :object_id,
								`user_id` = :user_id,
								`sender_id` = :sender_id,
								`recipients`= :recipients,
								`documents` = :documents,
								`attachments` = :attachments,
								`flags` = :flags,
								`subject` = :subject,
								`content` = :content
							";
				$aSql['client']			= (int)$aInquiry['office'];
				$aSql['application']	= 'inbox';
				$aSql['object']			= 'Ext_Thebing_Inquiry';
				$aSql['object_id']		= (int)$aInquiry['id'];
				$aSql['user_id']		= (int)$aLog['idCmsUser'];
				$aSql['sender_id']		= (int)$aLog['idCmsUser'];
				$aSql['recipients']		= json_encode($aRecipients);
				$aSql['documents']		= '';
				$aSql['attachments']	= $sAttachments;
				$aSql['flags']			= '';
				$aSql['subject']		= $aLog['subject'];
				$aSql['content']		= $aLog['content'];

				DB::executePreparedQuery($sSql,$aSql);

			}
		}

		return true;
	}

	public function getAttachments($sOldAttachments){
		$sAttachments = '';
		$aOldAttachments =  unserialize  ( $sOldAttachments );
		if(empty($aOldAttachments)) {
			return '';
		}
		$aTemp = array();

		foreach((array)$aOldAttachments as $sKey => $sValue){
			$aPath = pathinfo($sValue);

			$aTemp[$sValue] = $aPath['basename'];
		}

		$sAttachments = json_encode($aTemp);
		return $sAttachments;
	}

}
