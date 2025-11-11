<?
class Ext_Thebing_System_Checks_PC extends GlobalChecks {

	public function executeCheck(){
		global $user_data, $_VARS;

		if(!hasRight('modules_admin')){
			$this->_aFormErrors[] = 'Only an full CMS Administrator hast the Right to start this Script!';
			return false;
		}

		// Backup der Tabellen
		try{
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_courses');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_accommodations');
		} catch(Exception $e){
			__pout($e);
			//return false;
		}


		# PC importieren #

			if(
				!Ext_Thebing_Util::isDevSystem() &&
				!Ext_Thebing_Util::isTestSystem() &&
				!Ext_Thebing_Util::isLive2System()
			){

				$sSql = " SELECT
								*
							FROM
								`kolumbus_inquiries_program_change`";
				$aResult = DB::getQueryData($sSql);
				$i = 0;
				foreach($aResult as $aPC){
					$i++;
					if($aPC['inquiry_id'] <= 0){
						continue;
					}

					$oInquiry			= Ext_TS_Inquiry::getInstance($aPC['inquiry_id']);
					$oSchool			= new Ext_Thebing_School(null, $oInquiry->crs_partnerschool, true);

					if(!is_object($oSchool)){
						continue;
					}

					// Document anlegen
					$oDoc = new Ext_Thebing_Inquiry_Document(0);
					$oDoc->active			= 1;
					$oDoc->released			= 1;
					$oDoc->inquiry_id		= $oInquiry->id;
					$oDoc->document_number	= $aPC['invoiceNumber'];
					$oDoc->save();

					// Version anlegen
					$oVersion					= $oDoc->newVersion();
					$oVersion->comment			= $aPC['refound_comment'];
					$oVersion->date				= $aPC['date'];
					$oVersion->txt_address		= $aPC['address'];
					$oVersion->txt_subject		= $aPC['subject'];
					$oVersion->txt_intro		= $aPC['intro'];
					$oVersion->txt_outro		= $aPC['outro'];
					$oVersion->txt_enclosures	= $aPC['refound_comment'];
					$oVersion->txt_pdf			= $aPC['pdf_url'];
					$oVersion->txt_signature	= $aPC['signature'];
					$oVersion->signature		= $aPC['signature_img'];
					if($aPC['pdf_created'] > 0){

						$sPath				= $oSchool->getSchoolFileDir(false, false) . "/programchange/";
						$sDocumentNumber	= $oInquiry->document_number;
						$sDocumentNumber	= \Util::getCleanFileName($sDocumentNumber);
						$sFileName			= $sDocumentNumber."-".$aPC['id'];
						$oVersion->path		= $sPath.$sFileName.'.pdf';
					}
					$oVersion->save();


					// Items auslesen
					$sSql = " SELECT
								*
							FROM
								`kolumbus_inquiries_program_change_positions`
							WHERE
								`program_change_id` = :pc_id";
					$aPositions = DB::getPreparedQueryData($sSql, array('pc_id'=>(int)$aPC['id']));

					$fNettoAmount = 0;
					foreach($aPositions as $aPosition){
						// Item anlegen als VORORT
						$oItem						= $oVersion->newItem();
						$oItem->description			= $aPosition['text'];
						$oItem->old_description		= $aPosition['text'];
						$oItem->amount				= $aPosition['amount'];
						$oItem->amount_net			= $aPosition['amount_net'];
						$oItem->amount_provision	= $aPosition['amount_provision'];
						$oItem->amount_discount		= $aPosition['amount_discount'];
						$oItem->description_discount= $aPosition['description_discount'];
						$oItem->tax_category		= $aPosition['tax_category'];
						$oItem->type				= $aPosition['type'];
						$oItem->initalcost			=  1;
						$oItem->save();
						$fNettoAmount				+= $aPosition['amount_net'];

					}

					if($fNettoAmount > 0){
						$oDoc->type = 'netto_diff';
					} else {
						$oDoc->type = 'brutto_diff';
					}
					$oDoc->save();

					if($aPC['creditnote_document_id'] > 0){
						// creditnote doc id verbindung herstellen
						DB::updateJoinData(
							'kolumbus_inquiries_documents_creditnote',
							array('document_id' => (int)$oDoc->id),
							array($aPC['creditnote_document_id']),
							'creditnote_id'
						);
					}

					#ende PC importieren

					#kurs daten umschreiben
					// aktive kurse deaktivieren wenn sie kein PC Kurs sind UND nicht als "for_tuituion" makiert sind
					$sSql = "UPDATE
									`kolumbus_inquiries_courses`
								SET
									`active` = 0
								WHERE
									`inquiry_id` = :inquiry_id AND
									`program_change` = 0 AND
									`for_tuition` = 0";
					$aSql = array('inquiry_id' => (int)$oInquiry->id);
					DB::executePreparedQuery($sSql, $aSql);

					//PC flag zurücksetzten
					$sSql = "UPDATE
									`kolumbus_inquiries_courses`
								SET
									`program_change` = 0
								WHERE
									`inquiry_id` = :inquiry_id AND
									`program_change` = 1 AND
									`for_tuition` = 1";
					$aSql = array('inquiry_id' => (int)$oInquiry->id);
					DB::executePreparedQuery($sSql, $aSql);

					# ende kurs daten umschreiben #

					#Unterkunft daten umschreiben
					// aktive Unterkunft deaktivieren wenn sie kein PC Unterkunft sind UND nicht als "for_matching" makiert sind
					$sSql = "UPDATE
									`kolumbus_inquiries_accommodations`
								SET
									`active` = 0
								WHERE
									`inquiry_id` = :inquiry_id AND
									`program_change` = 0 AND
									`for_matching` = 0";
					$aSql = array('inquiry_id' => (int)$oInquiry->id);
					DB::executePreparedQuery($sSql, $aSql);

					//PC flag zurücksetzten
					$sSql = "UPDATE
									`kolumbus_inquiries_accommodations`
								SET
									`program_change` = 0
								WHERE
									`inquiry_id` = :inquiry_id AND
									`program_change` = 1 AND
									`for_matching` =  1";
					$aSql = array('inquiry_id' => (int)$oInquiry->id);
					DB::executePreparedQuery($sSql, $aSql);

					# ende Unterkunft daten umschreiben #
				}
			}

		## ENDE



		return true;
	}

}
