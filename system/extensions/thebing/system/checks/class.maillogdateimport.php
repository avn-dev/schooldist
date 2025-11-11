<?
class Ext_Thebing_System_Checks_MailLogDateImport extends Ext_Thebing_System_ThebingCheck {

	public function isNeeded(){
		global $user_data;

		if(
			$user_data['name'] == 'admin'
		){
			return true;
		}

		return false;
	}

	public function executeCheck(){
		global $user_data, $_VARS;

		$sMailLogBackupBackupName = '__2010_08_17_kolumbus_email_log';



		$aSql = array();




		// All ALTEN Logs auslesen
		$sSql = "SELECT
						*
					FROM
						`kolumbus_maillog`
					WHERE
						`active` = 1
				";

		$aLogs = DB::getQueryData($sSql);

		// Alle NEUEN Logs auslesen
		$sSql = "SELECT
						*
					FROM
						`kolumbus_email_log`
				";
		$aNewLogs = DB::getQueryData($sSql);

		$iCountDelete = 0;
		// Alte Logs
		foreach((array)$aLogs as $aOldLog){

			// neue Logs
			foreach((array)$aNewLogs as $iNewLogKey=> $aNewLog){
				if(
					$aNewLog['user_id'] == $aOldLog['idCmsUser'] &&
					$aNewLog['subject'] == $aOldLog['subject'] &&
					$aNewLog['content'] == $aOldLog['content']
				){
					// Neuen Log updaten
					$sSql = "UPDATE
									`kolumbus_email_log`
								SET
									`created` = :created_old
								WHERE
									`subject` = :subject AND
									`content` = :content AND
									`user_id` = :user";

					$aSql = array();
					$aSql['created_old']	= $aOldLog['created'];
					$aSql['subject']	= $aNewLog['subject'];
					$aSql['content']	= $aNewLog['content'];
					$aSql['user']		= $aNewLog['user_id'];

					DB::executePreparedQuery($sSql, $aSql);

					// Wurde gefunden und aus Backup/Backup Logs löschen
					$sSql = "DELETE
								FROM
									" . $sMailLogBackupBackupName . "
								WHERE
									`subject` = :subject AND
									`content` = :content AND
									`user_id` = :user";

					unset($aSql['created_old']);
					DB::executePreparedQuery($sSql,$aSql);

					$iCountDelete++;
					unset($aNewLogs[$iNewLogKey]);
					break;
				}
			}
			
		}
__pout($iCountDelete);
		// Alle übergebliebenen Logs in neue Logs umschreiben
		$sSql = "SELECT
						*
					FROM
						`" . $sMailLogBackupBackupName . "`
					";
		$aBackupLogs = DB::getQueryData($sSql);
__pout($aBackupLogs);
		foreach((array)$aBackupLogs as $aBackupLog){
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

				$aSql = array();
				$aSql['client']			= $aBackupLog['client_id'];
				$aSql['application']	= $aBackupLog['application'];
				$aSql['object']			= $aBackupLog['object'];
				$aSql['object_id']		= $aBackupLog['object_id'];
				$aSql['user_id']		= $aBackupLog['user_id'];
				$aSql['sender_id']		= $aBackupLog['sender_id'];
				$aSql['recipients']		= $aBackupLog['recipients'];
				$aSql['documents']		= $aBackupLog['documents'];
				$aSql['attachments']	= $aBackupLog['attachments'];
				$aSql['flags']			= $aBackupLog['flags'];
				$aSql['subject']		= $aBackupLog['subject'];
				$aSql['content']		= $aBackupLog['content'];
				$aSql['created']		= $aBackupLog['created'];

				DB::executePreparedQuery($sSql,$aSql);
		}

		return true;
	}

	

}
