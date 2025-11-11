<?php

class Ext_Poll_Poll extends WDBasic {
 
	protected $_sTable = 'poll_init';

	protected static $_sBackupDir = '/storage/poll/backup/';

	protected $bCompleted = false;

	public function copy($sName=false) {

		DB::setResultType(MYSQL_ASSOC);

		$oDb = DB::getDefaultConnection();
		
		$sSql = "SELECT * FROM poll_init WHERE `id` = :id";
		$aSql = array('id'=>$this->_aData['id']);
		$aPoll = $oDb->preparedQueryData($sSql, $aSql);
		$aPoll = $aPoll[0];

		unset($aPoll['id']);
		if($sName) {
			$aPoll['name'] = $sName;
		} else {
			$aPoll['name'] = "Kopie von ".$aPoll['name'];
		}

		$aSql = array();
		$sSql = "INSERT INTO poll_init SET ";
		foreach((array)$aPoll as $sField=>$mValue) {
			$sSql .= "`".$sField."` = :".$sField.", ";
			$aSql[$sField] = $mValue;
		}
		$sSql = substr($sSql, 0, -2);
		$oDb->preparedQuery($sSql, $aSql);
		$iPoll = $oDb->getInsertID();

		$aCompare = array();
		$aCompareQuestion = array();

		$sSql = "SELECT * FROM poll_paragraphs WHERE `idPoll` = :id";
		$aSql = array('id'=>$this->_aData['id']);
		$aData = $oDb->preparedQueryData($sSql, $aSql);
		foreach((array)$aData as $aItem) {
			$aSql = array();
			$iOldParagraph = $aItem['id'];
			unset($aItem['id']);
			$aItem['idPoll'] = $iPoll;
			$sSql = "INSERT INTO poll_paragraphs SET ";
			foreach((array)$aItem as $sField=>$mValue) {
				$sSql .= "`".$sField."` = :".$sField.", ";
				$aSql[$sField] = $mValue;
			}
			$sSql = substr($sSql, 0, -2);
			$oDb->preparedQuery($sSql, $aSql);
			$aCompare[$iOldParagraph] = $oDb->getInsertID();
		}

		$sSql = "SELECT * FROM poll_questions WHERE `idPoll` = :id";
		$aSql = array('id'=>$this->_aData['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aData as $aItem) {
			$aSql = array();
			$iOldQuestion = $aItem['id'];
			unset($aItem['id']);
			$aItem['idPoll'] = $iPoll;
			$aItem['idParagraph'] = $aCompare[$aItem['idParagraph']];
			$sSql = "INSERT INTO poll_questions SET ";
			foreach((array)$aItem as $sField=>$mValue) {
				$sSql .= "`".$sField."` = :".$sField.", ";
				$aSql[$sField] = $mValue;
			}
			$sSql = substr($sSql, 0, -2);
			$oDb->preparedQuery($sSql, $aSql);
			
			$aCompareQuestion[$iOldQuestion] = $oDb->getInsertID();

		}

		foreach($aCompareQuestion as $iOldQuestionId=>$iNewQuestionId) {
			
			$sSql = "SELECT * FROM poll_questions_conditions WHERE `question_id` = :id";
			$aSql = array('id'=>$iOldQuestionId);
			$aData = DB::getPreparedQueryData($sSql, $aSql);
			foreach((array)$aData as $aItem) {
				$aSql = array();
				unset($aItem['id']);
				$aItem['question_id'] = $iNewQuestionId;

				foreach($aCompareQuestion as $iOldId=>$iNewId) {
					$aItem['settings'] = str_replace('question_'.$iOldId, 'question_'.$iNewId, $aItem['settings']);
				}

				DB::insertData('poll_questions_conditions', $aItem);

			}
			
		}
		
		$sSql = "SELECT * FROM poll_routing WHERE `poll_id` = :id";
		$aSql = array('id'=>$this->_aData['id']);
		$aData = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aData as $aItem) {
			$aSql = array();
			$iOldQuestion = $aItem['id'];
			unset($aItem['id']);
			$aItem['poll_id'] = $iPoll;
			
			foreach($aCompareQuestion as $iOldId=>$iNewId) {
				$aItem['settings'] = str_replace('question_'.$iOldId, 'question_'.$iNewId, $aItem['settings']);
			}

			$sSql = "INSERT INTO poll_routing SET ";
			foreach((array)$aItem as $sField=>$mValue) {
				$sSql .= "`".$sField."` = :".$sField.", ";
				$aSql[$sField] = $mValue;
			}
			$sSql = substr($sSql, 0, -2);
			$oDb->preparedQuery($sSql, $aSql);

		}

		return $iPoll;

	}

	public function copyParagraph($iParagraphId) {
		
		$sSql = "
			SELECT 
				* 
			FROM 
				poll_paragraphs 
			WHERE 
				`idPoll` = :poll_id AND
				`id` = :id
		";
		$aSql = array(
			'poll_id'=> (int)$this->id,
			'id' => (int)$iParagraphId
		);
		$aParagraph = (array)DB::getQueryRow($sSql, $aSql);

		$aSql = array();
		$iOldParagraph = $aParagraph['id'];
		unset($aParagraph['id']);
		foreach($aParagraph as $sKey=>&$mValue) {
			if(strpos($sKey, '_title') !== false) {
				$mValue = 'Kopie von '.$mValue;
			}
		}
		$iNewParagraph = DB::insertData('poll_paragraphs', $aParagraph);

        // TODO Redundant mit copyQuestion()

		$sSql = "
			SELECT 
				* 
			FROM 
				poll_questions 
			WHERE 
				`idPoll` = :poll_id AND
				`idParagraph` = :paragraph_id
		";
		$aSql = array(
			'poll_id'=> (int)$this->id,
			'paragraph_id' => $iOldParagraph
		);
		$aQuestions = DB::getPreparedQueryData($sSql, $aSql);
		foreach((array)$aQuestions as $aQuestion) {
            $iOldQuestion = $aQuestion['id'];
			unset($aQuestion['id']);
			$aQuestion['idParagraph'] = $iNewParagraph;
			$iNewQuestion = DB::insertData('poll_questions', $aQuestion);

            $sSql = "
                SELECT 
                    * 
                FROM 
                    poll_questions_conditions 
                WHERE 
                    `question_id` = :question_id AND
                    `active` = 1
            ";

            $aQuestionConditions = DB::getPreparedQueryData($sSql, ['question_id' => $iOldQuestion]);
            foreach ($aQuestionConditions as $aQuestionCondition) {
                unset($aQuestionCondition['id']);
                $aQuestionCondition['question_id'] = $iNewQuestion;
                DB::insertData('poll_questions_conditions', $aQuestionCondition);
            }

		}

		$this->updateReportTable();
	}

	public function copyQuestion($iQuestionId) {
		
		$sSql = "
			SELECT 
				* 
			FROM 
				poll_questions 
			WHERE 
				`idPoll` = :poll_id AND
				`id` = :id
		";
		$aSql = array(
			'poll_id'=> (int)$this->id,
			'id' => (int)$iQuestionId
		);
		$aQuestion = (array)DB::getQueryRow($sSql, $aSql);

		$aSql = array();
		$iOldQuestion = $aQuestion['id'];
		unset($aQuestion['id']);
		foreach($aQuestion as $sKey=>&$mValue) {
			if(strpos($sKey, '_title') !== false) {
				$mValue = 'Kopie von '.$mValue;
			}
		}
		$iNewQuestion = DB::insertData('poll_questions', $aQuestion);

        $sSql = "
                SELECT 
                    * 
                FROM 
                    poll_questions_conditions 
                WHERE 
                    `question_id` = :question_id AND
                    `active` = 1
            ";

        $aQuestionConditions = DB::getPreparedQueryData($sSql, ['question_id' => $iOldQuestion]);
        foreach ($aQuestionConditions as $aQuestionCondition) {
            unset($aQuestionCondition['id']);
            $aQuestionCondition['question_id'] = $iNewQuestion;
            DB::insertData('poll_questions_conditions', $aQuestionCondition);
        }

		$this->updateReportTable();
	}

	public function getQuestions($sLanguage = false, $bWithData = false) {

		$sSelect = '';

		if($sLanguage) {
			$sSelect .= ', q.'.$sLanguage.'_title title, p.'.$sLanguage.'_title as ptitle';
		}

		if ($bWithData) {
			$sSelect .= ', q.data';
		}

		$sSql = "
				SELECT
					q.id,
					q.template,
					p.id pid,
					p.idPage
					".$sSelect."
				FROM
					poll_paragraphs p,
					poll_questions q
				WHERE
					q.idPoll = ".(int)$this->id." AND
					p.id = q.idParagraph AND
					p.active = 1 AND
					q.active = 1
				ORDER BY
					p.idPage,
					p.position,
					q.position ASC";

		$aQuestions = DB::getQueryData($sSql);
		
		return (array)$aQuestions;

	}
	
	public function delete() {

		DB::executeQuery("DROP TABLE `poll_report_".(int)$this->id."`");

		DB::executeQuery("DELETE FROM poll_init WHERE id = ".(int)$this->id." LIMIT 1");
		DB::executeQuery("DELETE FROM poll_questions WHERE idPoll = ".(int)$this->id."");
		DB::executeQuery("DELETE FROM poll_paragraphs WHERE idPoll = ".(int)$this->id."");
		DB::executeQuery("DELETE FROM poll_results WHERE idPoll = ".(int)$this->id."");

	}
	
	public static function cleanExportData($sData) {
		
		$sData = str_replace('"', '""', $sData);
		$sData = preg_replace("/(\r|\n|\r\n)/", ' ', $sData); 
		
		return $sData;
		
	}

	public function createReportTable() {

		$sSql = "
			CREATE TABLE IF NOT EXISTS `poll_report_".(int)$this->id."` (
			  `id` int(11) NOT NULL auto_increment,
			  `idPoll` int(11) NOT NULL default '0',
			  `idUser` int(11) NOT NULL default '0',
			  `idTable` int(11) NOT NULL default '0',
			  `date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `sid` varchar(32) NOT NULL default '',
			  `ip` varchar(255) NOT NULL default '',
			  `spy` varchar(255) NOT NULL default '',
			  `complete` tinyint(4) NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `idPoll` (`idPoll`),
			  KEY `idUser` (`idUser`),
			  KEY `idTable` (`idTable`),
			  KEY `sid` (`sid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		DB::executeQuery($sSql);

	}

	public function updateReportTable() {

		$aQuestions = $this->getQuestions();

		foreach((array)$aQuestions as $aQuestion) {
			DB::addField("poll_report_".(int)$this->id, "f_".$aQuestion['id'], "TEXT NOT NULL");
		}

	}

	public function reworkReportTable() {

		$sTable = "poll_report_".(int)$this->id;

		$aColumns = array_keys(\DB::describeTable($sTable));

		if (!empty($aColumns)) {

			$bBackup = \Util::backupTable($sTable);

			if (!$bBackup) {
				throw new \RuntimeException('Backup failed ['.$sTable.']');
			}

			$aQuestionIds = array_column($this->getQuestions(), 'id');

			foreach ($aColumns as $sColumn) {

				if (substr($sColumn, 0, 2) !== 'f_') {
					continue;
				}

				$iColumnQuestion = str_replace('f_', '', $sColumn);

				if (!in_array($iColumnQuestion, $aQuestionIds)) {
					\DB::executePreparedQuery("ALTER TABLE #table DROP #column", ['table' => $sTable, 'column' => $sColumn]);
				}

			}

		}

	}

	public function countPages($intParagraphId=null) {
		
		$sWhere = "";
		
		if($intParagraphId !== null) {
			$arrParagraph = DB::getQueryRow("SELECT * FROM poll_paragraphs WHERE id = ".(int)$intParagraphId." ");
			$sWhere = "AND 
				position < ".(int)$arrParagraph['position']." ";
		}
		$strSql = "
			SELECT 
				* 
			FROM 
				poll_paragraphs 
			WHERE 
				idPoll = ".(int)$this->id." AND
				isPage = 1 AND
				active = 1
				".$sWhere."
			ORDER BY 
				position DESC 
			";
		$aPages = (array)DB::getQueryCol($strSql);
		$iPages = count($aPages);

		return $iPages;
	}

	public function createBackup() {
		global $system_data;

		$aInit = array();
		$aResults = array();

		$aInit['init'] = DB::getRowData('poll_init', $this->id);

		$sSql = "
			SELECT
				*
			FROM
				`poll_paragraphs`
			WHERE
				`idPoll` = :poll_id AND
				`active` = 1
				";
		$aSql = array('poll_id'=>(int)$this->id);
		$aInit['paragraphs'] = DB::getQueryRows($sSql, $aSql);

		$sSql = "
			SELECT
				*
			FROM
				`poll_questions`
			WHERE
				`idPoll` = :poll_id AND
				`active` = 1
				";
		$aSql = array('poll_id'=>(int)$this->id);
		$aInit['questions'] = DB::getQueryRows($sSql, $aSql);

		$aInit['question_conditions'] = array();
		foreach($aInit['questions'] as $aQuestion) {
			
			$sSql = "
				SELECT
					*
				FROM
					`poll_questions_conditions`
				WHERE
					`question_id` = :question_id AND
					`active` = 1
					";
			$aSql = array('question_id'=>(int)$aQuestion['id']);
			$aQuestionConditions = DB::getQueryRows($sSql, $aSql);
			if(!empty($aQuestionConditions)) {
				foreach($aQuestionConditions as $aQuestionCondition) {
					$aInit['question_conditions'][] = $aQuestionCondition;
				}
			}

		}
		
		$sSql = "
			SELECT
				*
			FROM
				#table
			WHERE
				1
				";
		$aSql = array('table'=>'poll_report_'.$this->id);
		$aResults['report'] = DB::getQueryRows($sSql, $aSql);

		$sSql = "
			SELECT
				*
			FROM
				`poll_results`
			WHERE
				`idPoll` = :poll_id
				";
		$aSql = array('poll_id'=>(int)$this->id);
		$aResults['results'] = DB::getQueryRows($sSql, $aSql);

		$sSql = "
			SELECT
				*
			FROM
				`poll_routing`
			WHERE
				`poll_id` = :poll_id
				";
		$aSql = array('poll_id'=>(int)$this->id);
		$aResults['routings'] = DB::getQueryRows($sSql, $aSql);

		$sName = date('Ymd_His').'_'.$this->id.'_'.$this->name.'.zip';
		$sName = \Util::getCleanFileName($sName);

		$sPath = \Util::getDocumentRoot().self::$_sBackupDir;
		Util::checkDir($sPath);

		$sPath = $sPath.$sName;

		$oZip = new ZipArchive;
		$bZip = $oZip->open($sPath, ZipArchive::CREATE);
		if ($bZip === true) {
			$oZip->addFromString('init.txt', json_encode($aInit));
			$oZip->addFromString('results.txt', json_encode($aResults));
			$oZip->close();

			chmod($sPath, $system_data['chmod_mode_file']);

			return true;
		} else {
			return false;
		}

	}

	public static function restoreBackup($sFile) {
		global $objWebDynamicsDAO;

		$sTransactionPoint = 'Ext_Poll_Poll::restoreBackup';
		
		DB::begin($sTransactionPoint);

		try {
			$aLanguages = $objWebDynamicsDAO->getWebSiteLanguages(1);

			$sPath = \Util::getDocumentRoot().self::$_sBackupDir;
			$sPath = $sPath.$sFile;

			$content = '';
			$oZip = new ZipArchive();
			$bZip = $oZip->open($sPath);
			if ($bZip === true) {

				$hZip = $oZip->getStream('init.txt');
				if(!$hZip) {
					return false;
				}

				$sInit = '';
				while(!feof($hZip)) {
					$sInit .= fread($hZip, 1024);
				}
				fclose($hZip);

				$hZip = $oZip->getStream('results.txt');
				if(!$hZip) {
					return false;
				}

				$sResults = '';
				while(!feof($hZip)) {
					$sResults .= fread($hZip, 1024);
				}
				fclose($hZip);

				$aInit = json_decode($sInit, true);
				$aResults = json_decode($sResults, true);

			} else {
				return false;
			}

			/**
			 * Import settings
			 */
			$aData = array();
			$aData['changed'] = $aInit['init']['changed'];
			$aData['created'] = $aInit['init']['created'];
			$aData['name'] = $aInit['init']['name'];
			$aData['active'] = $aInit['init']['active'];
			$aData['languages'] = $aInit['init']['languages'];
			$aData['connect_customer'] = $aInit['init']['connect_customer'];
			$aData['url'] = $aInit['init']['url'];
			foreach((array)$aLanguages as $sLanguage) {
				$aData[$sLanguage.'_data'] = $aInit['init'][$sLanguage.'_data'];
			}
			$iPollId = DB::insertData('poll_init', $aData);

			$aParagrapheIds = array();
			$aQuestionIds = array();

			foreach((array)$aInit['paragraphs'] as $aParagraphes) {
				
				if(empty($aParagraphes)) {
					continue;
				}

				$aData = array();

				$aData['idPoll'] = $iPollId;
				$aData['idPage'] = $aParagraphes['idPage'];
				$aData['isPage'] = $aParagraphes['isPage'];
				$aData['isNonlinear'] = $aParagraphes['isNonlinear'];
				$aData['position'] = $aParagraphes['position'];
				$aData['active'] = $aParagraphes['active'];
				$aData['css'] = $aParagraphes['css'];
				$aData['label_width'] = $aParagraphes['label_width'];
				foreach((array)$aLanguages as $sLanguage) {
					$aData[$sLanguage.'_description'] = $aParagraphes[$sLanguage.'_description'];
					$aData[$sLanguage.'_title'] = $aParagraphes[$sLanguage.'_title'];
				}
				$iParagraphId = DB::insertData('poll_paragraphs', $aData);

				$aParagrapheIds[$aParagraphes['id']] = $iParagraphId;

			}

			foreach((array)$aInit['questions'] as $aQuestions) {
				$aData = array();

				if(empty($aParagrapheIds[$aQuestions['idParagraph']])) {
					continue;
				}
				
				$aData['idPoll'] = $iPollId;
				$aData['idParagraph'] = $aParagrapheIds[$aQuestions['idParagraph']];
				$aData['data'] = $aQuestions['data'];
				$aData['parameter'] = $aQuestions['parameter'];
				$aData['input_addon'] = $aQuestions['input_addon'];
				$aData['input_css'] = $aQuestions['input_css'];
				$aData['route'] = $aQuestions['route'];
				$aData['position'] = $aQuestions['position'];
				$aData['template'] = $aQuestions['template'];
				$aData['important'] = $aQuestions['important'];
				$aData['hidden'] = $aQuestions['hidden'];
				$aData['active'] = $aQuestions['active'];
				$aData['css'] = $aQuestions['css'];
				$aData['css_input'] = $aQuestions['css_input'];
				$aData['css_value'] = $aQuestions['css_value'];
				//$aData['routing'] = $aQuestions['routing'];
				$aData['weighting'] = $aQuestions['weighting'];
				$aData['input_css'] = $aQuestions['input_css'];

				foreach((array)$aLanguages as $sLanguage) {
					$aData[$sLanguage.'_title'] = $aQuestions[$sLanguage.'_title'];
					$aData[$sLanguage.'_description'] = $aQuestions[$sLanguage.'_description'];
					$aData[$sLanguage.'_block_head'] = $aQuestions[$sLanguage.'_block_head'];				
				}
				$iQuestionId = DB::insertData('poll_questions', $aData);

				$aQuestionIds[$aQuestions['id']] = $iQuestionId;

			}

			foreach((array)$aInit['question_conditions'] as $aCondition) {

				if(empty($aCondition)) {
					continue;
				}

				$aData = $aCondition;
				$aData['question_id'] = $aQuestionIds[$aData['question_id']];

				foreach($aQuestionIds as $iOldId=>$iNewId) {
					$aData['settings'] = str_replace('question_'.$iOldId, 'question_'.$iNewId, $aData['settings']);
				}

				DB::insertData('poll_questions_conditions', $aData);

			}

			$oPoll = new Ext_Poll_Poll($iPollId);
			$oPoll->createReportTable();
			$oPoll->updateReportTable();

			/**
			 * Import results
			 */

			$aReportIds = array();

			foreach((array)$aResults['report'] as $aReport) {

				if(empty($aReport)) {
					continue;
				}
				
				$aData = array();
				$aData['idPoll'] = $iPollId;
				$aData['idUser'] = $aReport['idUser'];
				$aData['idTable'] = $aReport['idTable'];
				$aData['date'] = $aReport['date'];
				$aData['sid'] = $aReport['sid'];
				$aData['ip'] = $aReport['ip'];
				$aData['spy'] = $aReport['spy'];
				$aData['complete'] = $aReport['complete'];
				foreach((array)$aQuestionIds as $iOldId=>$iNewId) {
					$aData['f_'.$iNewId] = $aReport['f_'.$iOldId];
				}
				$iReportId = DB::insertData('poll_report_'.$oPoll->id, $aData);

			}

			foreach((array)$aResults['results'] as $aResult) {

				if(empty($aResult)) {
					continue;
				}

				$aData = array();
				$aData['idPoll'] = $iPollId;
				$aData['idQuestion'] = $aQuestionIds[$aResult['idQuestion']];
				$aData['idUser'] = $aResult['idUser'];
				$aData['idTable'] = $aResult['idTable'];
				$aData['idReport'] = $aResult['idReport'];
				$aData['ip'] = $aResult['ip'];
				$aData['loop'] = $aResult['loop'];
				$aData['sid'] = $aResult['sid'];
				$aData['data'] = $aResult['data'];
				$aData['created'] = $aResult['created'];

				$iResultId = DB::insertData('poll_results', $aData);

			}

			foreach((array)$aResults['routings'] as $aResult) {

				$aData = $aResult;
				$aData['poll_id'] = $iPollId;
				$iResultId = DB::insertData('poll_routing', $aData);

			}

			DB::commit($sTransactionPoint);
			
			return true;
			
		} catch (Exception $e) {
			
			__out($e->getMessage());
			
			DB::rollback($sTransactionPoint);
			
			return false;
			
		}
		
	}

	public static function listBackups($sSearch) {

		$sPath = \Util::getDocumentRoot().self::$_sBackupDir.'*';

		$aFiles = glob($sPath);

		$aReturn = array();

		if(is_array($aFiles)) {
		
			foreach((array)$aFiles as $sFile) {
				$aInfo = pathinfo($sFile);
				$aReturn[] = array(
					'path'=>$sFile,
					'file'=>$aInfo['basename'],
					'size'=>filesize($sFile)
				);
			}
		}

		return $aReturn;

	}

	public static function saveBackupFile($aFile) {
		
		$bMatch = preg_match("/([0-9]{4})([0-9]{2})([0-9]{2})_([0-9]{2})([0-9]{2})([0-9]{2})_([0-9]+)_/", $aFile['name'], $aMatch);

		if(!$bMatch) {
			return false;
		}

		$sPath = \Util::getDocumentRoot().self::$_sBackupDir;
		$sName = \Util::getCleanFileName($aFile['name']);

		$sTarget = $sPath.$sName;

		if(is_file($sTarget)) {
			return false;
		}

		$bSuccess = move_uploaded_file($aFile['tmp_name'], $sTarget);
		chmod($sTarget, 0777);

		return $bSuccess;

	}

	public function clearResults() {

		$sSql = "TRUNCATE TABLE #table";
		$aSql = array('table'=>'poll_report_'.$this->id);
		DB::executePreparedQuery($sSql, $aSql);

		$sSql = "DELETE FROM poll_results WHERE idPoll = :poll_id";
		$aSql = array('poll_id'=>$this->id);
		DB::executePreparedQuery($sSql, $aSql);

	}

	public function getLanguages() {
		global $objWebDynamicsDAO;

		$aLanguages = json_decode($this->languages, true);

		$aLanguages = (array)$aLanguages;

		if(empty($aLanguages)) {
			$aItems = $objWebDynamicsDAO->getWebSiteLanguages(1);
			$aLanguages = $aItems;
		}

		return $aLanguages;

	}

	public function getResultLoops($iResultId) {

		$sSql = "
			SELECT
				`loop`
			FROM
				`poll_results`
			WHERE
				`idPoll` = :poll_id AND
				`idReport` = :report_id
			ORDER BY
				`loop` DESC
			LIMIT 1
			";
		$aSql = array(
			'report_id' => (int)$iResultId,
			'poll_id' => (int)$this->id
		);
		$iLoops = (int)DB::getQueryOne($sSql, $aSql);
		$iLoops++;

		return $iLoops;

	}

	public function getPlaceholderQuestionInput($iQuestionId, $sLanguage, $mValue, $bError) {

		$oQuestion = \Poll\Entity\Question::getInstance($iQuestionId);
		
		$sInput = '';

		$sClass = '';		
		if($bError) {
			$sClass = ' error';
		}
		
		$sValue = (string)$mValue;
		
		switch ($oQuestion->template) {
			case 'text':
				$sInput = '<input type="text" class="result_'.$oQuestion->id.''.$sClass.'" name="result['.$oQuestion->id.']" value="'.\Util::getEscapedString($sValue).'" '.$oQuestion->input_addon.' />';
				break;
			case 'check':
				
				$aOptions = $oQuestion->getOptions($sLanguage);

				$sInput = '';
				foreach($aOptions as $mKey=>$mOption) {				
					$sInput .= '<input type="hidden" name="result['.$oQuestion->id.']['.$mKey.']" value="" />';
					$sInput .= '<input type="checkbox" class="result_'.$oQuestion->id.''.$sClass.'" name="result['.$oQuestion->id.']['.$mKey.']" value="'.\Util::getEscapedString($mKey).'" '.$oQuestion->input_addon.' '.((in_array($mKey, $mValue))?'checked':'').' /> '.$mOption.' ';
				}

				break;
			case 'select':

				$aOptions = $oQuestion->getOptions($sLanguage);

				$sInput = '<select class="result_'.$oQuestion->id.''.$sClass.'" name="result['.$oQuestion->id.']">';
				foreach($aOptions as $mKey=>$mOption) {
					if($mKey == $sValue) {
						$sInput .= '<option value="'.$mKey.'" selected="selected">'.$mOption.'</option>';
					} else {
						$sInput .= '<option value="'.$mKey.'">'.$mOption.'</option>';	
					}
				}
				$sInput .= '</select>';
				break;
			default:
				$sInput = "Invalid placeholder question!";
				break;
		}
		
		return $sInput;
	}
	
	public function replacePlaceholderQuestions(&$sCode, $aPlaceholderQuestions, $sLanguage) {

		$count = 0;
		while(($iPos = strpos($sCode, '{placeholder:')) !== false) {

			// Wenn hier vergessen wurde die Klammer des Platzhalters zu schließen ist $sPlaceholder=false
			$sPlaceholder = substr($sCode, $iPos, strpos($sCode, '}', $iPos)-$iPos+1);

			if ($sPlaceholder) {
				$iQuestionId = str_replace('}', '', str_replace('{placeholder:', '', $sPlaceholder));

				if(array_key_exists($iQuestionId, $aPlaceholderQuestions)) {
					$sQuestionReplace = $this->getPlaceholderQuestionInput($iQuestionId, $sLanguage, $aPlaceholderQuestions[$iQuestionId]['value'], $aPlaceholderQuestions[$iQuestionId]['error']);
				} else {
					$sQuestionReplace = 'Question is on this page not available! ('.$iQuestionId.')';
				}
			} else {
				// Versuchen den falschen Platzhalter zu finden damit dieser trotzdem durch irgendwas ersetzt werden kann
				// ansonsten springt er immer wieder in die While-Schleife

				$sPlaceholder = substr($sCode, $iPos, 25);

				$aMatches = [];
				preg_match('/\{placeholder:([0-9]+)/ims', $sPlaceholder, $aMatches);
				if (!empty($aMatches[0])) {
					$sPlaceholder = $aMatches[0];
				}

				$sQuestionReplace = 'INVALID PLACEHOLDER';
			}

			$sCode = str_replace($sPlaceholder, $sQuestionReplace, $sCode);
			$count++;

			if ($count > 200) {
				// Endlosschleife verhindern
				break;
			}
		}

	}

	// Sortieren von Arrays nach [position]
	public static function sortDataByPosition($a, $b) {
		if ($a['position'] == $b['position']) {
		   return 0;
	   }
	   return ($a['position'] < $b['position']) ? -1 : 1; 
	}

	public static function sortDataByValue($a, $b) {
		if ($a['value'] == $b['value']) {
		   return 0;
	   }
	   return ($a['value'] < $b['value']) ? -1 : 1; 
	}

	public function getItemName() {
		return $this->name;
	}
	
	/**
	 * Speichert einen Durchlauf als vollständig
	 * @param int $iReportSessionId
	 */
	public function setCompleted($iReportSessionId, $aRequestResult, $aReport) {
		global $_VARS;

		$sCompleteCondition = $this->complete_condition;

		$bCondition = true;
		if(!empty($sCompleteCondition)) {
			$aCompleteCondition = json_decode($sCompleteCondition, true);

			$oConditionHelper = new \Poll\Helper\ConditionHelper;

			$bCondition = $oConditionHelper->checkConditions($aCompleteCondition, $aRequestResult, $aReport);
		}

		if($bCondition === true) {
			
			DB::executeQuery("UPDATE poll_report_".(int)$this->id." SET `complete` = 1 WHERE id = ".(int)$iReportSessionId."");
			
			unset($_VARS);
			unset($_SESSION['poll'][$this->id]['report_id']);
			
			$this->bCompleted = true;
			
		}

	}
	
	/**
	 * @return boolean
	 */
	public function getCompleted() {
		return $this->bCompleted;
	}
	
	/**
	 * Holt Eingaben von einem Durchlauf
	 * @param int $iReportSessionId
	 * @param int $iLoop
	 * @return array
	 */
	public function getReport($iReportSessionId, $iLoop=0) {
		
		$iLoops = $this->getResultLoops($iReportSessionId);

		$aReport = (array)DB::getQueryRow("SELECT * FROM poll_report_".(int)$this->id." WHERE id = ".(int)$iReportSessionId);

		if($iLoops > 0) {
			// Ergebnisse aufbereiten
			foreach($aReport as $sKey=>&$sValue) {

				if(strpos($sKey, 'f_') === 0) {

					$aValues = explode('|', $sValue, $iLoops);
					$sValue = $aValues[$iLoop];

				}

			}
		}
		
		return $aReport;
	}
	
	public function exportResults($sLanguage, $sCsv, $bParagraphs, $sEmpty, $bSplit, $bUtf8 = false, $bHideIps = false) {
		global $system_data;

		set_time_limit(1800);
		ini_set("memory_limit", '4G');

		$aCaptions = array();
		
		$aQuestions = $this->getQuestions($sLanguage);
		foreach((array)$aQuestions as $aCaption) {
			$aCaptions[$aCaption['pid']]['title'] = $aCaption['ptitle'];
			$aCaptions[$aCaption['pid']]['questions'][$aCaption['id']]['title'] = $aCaption['title'];
			$aCaptions[$aCaption['pid']]['questions'][$aCaption['id']]['template'] = $aCaption['template'];
			$aCaptions[$aCaption['pid']]['idPage'] = $aCaption['idPage'];
		}

		// Ermitteln der Anzahl der Seiten
		$aAmountPages = get_data(DB::executeQuery("SELECT COUNT(*) as count FROM poll_paragraphs WHERE idPoll = ".(int)$this->id." AND isPage = 1"));
		$sAmountPages = $aAmountPages['count'];
		$csv = "\"Eingabedatum\"".$sCsv;
		$csv .= "\"Dauer der Session\"".$sCsv;
		$csv .= "\"IP\"".$sCsv;
		$csv .= "\"Benutzer\"".$sCsv;
		$csv .= "\"Parameter\"".$sCsv;

		$sQuestionIds = '"date"'.$sCsv.'"duration"'.$sCsv.'"ip"'.$sCsv.'"user"'.$sCsv.'"spy"'.$sCsv;

		$aSelect = array(); 

		// Spaltennamen
		foreach ((array)$aCaptions as $pkey => $pval) {

			if ($pval['idPage'] < $sAmountPages) {
				// Titel des Paragraphen, wenn Paragraph gesetzt ist
				if ($bParagraphs == 1) {
					$csv .= "\"".strip_tags($aCaptions[$pkey]['title'])."\"".$sCsv;
					$sQuestionIds .= $sCsv;
				}
				// Elemente
				foreach ((array)$pval['questions'] as $ckey => $cval) {

					$aSelect[] = 'f_'.$ckey;

					if($cval['template'] == 'matrix') {

						$oQuestion = \Poll\Entity\Question::getInstance($ckey);

						$aQuestionData = $oQuestion->getDataArray();

						foreach($aQuestionData['questions'] as $aQuestion) {

							foreach($aQuestionData['answer_groups'] as $aAnswerGroups) {

								$csv .= "\"".$cval['title']." - ".$aQuestion['name_'.$sLanguage]." - ".$aAnswerGroups['name_'.$sLanguage]."\"".$sCsv;
								$sQuestionIds .= $ckey.$sCsv;

							}

						}

					} else {

						// Aufsplitten der Multiple-Choice Elemente
						$csv .= "\"".$cval['title']."\"".$sCsv;
						$sQuestionIds .= $ckey.$sCsv;

						if($bSplit == 1) {

							if(in_array($cval['template'], array("check", "list"))) {
								$aCheckValues = DB::getQueryRow("SELECT data FROM poll_questions WHERE idPoll = ".(int)$this->id." AND id = '".$ckey."' LIMIT 1");
								$aValues[$ckey] = Util::decodeSerializeOrJson($aCheckValues['data']);
							}
							foreach ((array)$aValues[$ckey] as $vkey => $vval) {
								$csv .= "\"".$cval['title'].".".$vval['value']."\"".$sCsv;
								$sQuestionIds .= $ckey.$sCsv;
							}

						}

					}

				}
			}
		}
		$csv .= "\r\n";
		$csv .= $sQuestionIds."\r\n";

//		$sQuery = "SELECT id FROM poll_questions WHERE idPoll = '".$_VARS['poll_id']."'";
//		$rRes = DB::executeQuery($sQuery);
//		while ($aRes = get_data($rRes)) {
//			$aAvailableFields[] = $aRes['id'];
//		}

		/// NOTFALL PLAN
		/*
		$i = 0;
		$sQuery = "SELECT * FROM poll_results WHERE idPoll = 15 ORDER BY `created` ASC, idQuestion ASC";
		$rResult2 = DB::executeQuery($sQuery);
		while ($aResult = get_data($rResult2)) {
			if ($lastId > $aResult['idQuestion'] || $lastId = 0) $i++;
			$lastId = $aResult['idQuestion'];
			$aResultList[$i]['field_'.$aResult['idQuestion']] = $aResult['data'];
		}
		*/
		///

		$sSelect = implode(", ", $aSelect);
		if(!empty($sSelect)) {
			$sSelect .= ',';
		}

		// Auslesen der Datensätze
		$sSql = "
			SELECT 
				p.id, 
				p.spy, 
				p.ip, 
				p.idTable, 
				p.idUser, 
				".$sSelect."
				UNIX_TIMESTAMP(p.`date`) AS `set_date`,
				MAX(UNIX_TIMESTAMP(r.created)) - MIN(UNIX_TIMESTAMP(r.created)) `duration`
			FROM 
				`poll_report_".(int)$this->id."` p JOIN
				`poll_results` r ON
					p.idPoll = r.idPoll AND
					p.id = r.idReport
			WHERE 
				p.idPoll = ".(int)$this->id." 
			GROUP BY
				p.id
			ORDER BY 
				p.date DESC
			";
		$aResultList = DB::getQueryData($sSql);

		foreach((array)$aResultList as $aResult) {

			// IPs entfernen wenn diese nicht exportiert werden sollen (Ticket #890)
			if($bHideIps) {
				$aResult['ip'] = '';
			}

			// Benutzer, falls vorhanden
			if ($aResult['idTable'] > 0) {
				$rNickname = DB::getQueryRow("SELECT nickname FROM customer_db_".(int)$aResult['idTable']." WHERE id = ".(int)$aResult['idUser']);
				$aResult['nickname'] = $rNickname['nickname'];
			} else {
				$aResult['nickname'] = 'n/a';
			}

			$csv .= "\"" . date('d.m.Y H:i:s', $aResult['set_date']) . "\"".$sCsv;
			$csv .= "\"" . intervall($aResult['duration']) . "\"".$sCsv;
			$csv .= "\"" . $aResult['ip'] . "\"".$sCsv;
			$csv .= "\"" . $aResult['nickname'] . "\"".$sCsv;
			$csv .= "\"".$aResult['spy']."\"".$sCsv;

			foreach((array)$aCaptions as $pkey => $pval) {

				if($pval['idPage'] < $sAmountPages) {

					// Leere Spalte, wenn Paragraph
					if ($bParagraphs == 1) {
						$csv .= "\"\"".$sCsv;
					}

					// Daten des aktuellen Datensatzes
					foreach ((array)$pval['questions'] as $ckey => $cval) {

						// Wenn das Resultat leer ist
						if ($aResult['f_'.$ckey] == "") {
							$aResult['f_'.$ckey] = $sEmpty;
						}

						if($cval['template'] == 'matrix') {

							$aMatrixData = Util::decodeSerializeOrJson($aResult['f_'.$ckey]);

							$oQuestion = \Poll\Entity\Question::getInstance($ckey);

							$aQuestionData = $oQuestion->getDataArray();

							foreach($aQuestionData['questions'] as $aQuestion) {

								foreach($aQuestionData['answer_groups'] as $aAnswerGroups) {

									if(isset($aMatrixData[$aQuestion['key']][$aAnswerGroups['key']])) {
										$mValue = $aMatrixData[$aQuestion['key']][$aAnswerGroups['key']];
									} else {
										$mValue = $sEmpty;
									}

									if(is_array($mValue)) {
										$mValue = implode('|', $mValue);
									}

									$csv .= "\"".Ext_Poll_Poll::cleanExportData($mValue)."\"".$sCsv;
									$aExploded = @explode("|", $mValue);

								}

							}

						} else {

							$csv .= "\"".Ext_Poll_Poll::cleanExportData($aResult['f_'.$ckey])."\"".$sCsv;
							$aExploded = @explode("|", $aResult['f_'.$ckey]);

						}

						if ($bSplit == 1) {

							foreach ((array)$aValues[$ckey] as $vkey => $vval) {
								if ((in_array($vval['value'], $aExploded))) {
									$csv .= "\"1\"".$sCsv;
								} else {
									$csv .= "\"0\"".$sCsv;	
								}
							}

						}

					}
				}

			}
			$csv .= "\r\n";
		}

		$sDocumentRoot = $_SERVER['DOCUMENT_ROOT'];

		$strDir = "/storage/poll/";
		if(!is_dir($sDocumentRoot.$strDir)) {
			mkdir($sDocumentRoot.$strDir, $system_data['chmod_mode_dir']);
		}

		if($bUtf8) {
			$strFile = $strDir."poll.txt";
		} else {
			$strFile = $strDir."poll.csv";
			$csv = iconv('utf-8', 'cp1252//TRANSLIT', $csv);
		}

		@unlink($sDocumentRoot.$strFile);
		$fh = fopen($sDocumentRoot.$strFile, "w+");
		fwrite($fh, $csv);
		fclose($fh);

		chmod($sDocumentRoot.$strFile, $system_data['chmod_mode_dir']);

		return $strFile;
	}

}
