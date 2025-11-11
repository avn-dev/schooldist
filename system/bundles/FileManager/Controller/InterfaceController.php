<?php

namespace FileManager\Controller;

class InterfaceController extends \MVC_Abstract_Controller {
	
	/**
	 * @todo http://koopmann-online.de/media/bootstrap/js/pasteimage.js
	 */
	public function ViewAction() {
		
		$oSmarty = new \SmartyWrapper();
		$oSmarty->setTemplateDir(\Util::getDocumentRoot(true));

		$sClass = $this->_oRequest->get('entity');
		$iId = (int)$this->_oRequest->get('id');

		$oSmarty->assign('sClass', $sClass);
		$oSmarty->assign('iId', $iId);

		$aTags = $this->getTags($sClass, false);

		$oSmarty->assign('aTags', $aTags);

		$oEntity = $this->getEntity($sClass, $iId);
		$oSmarty->assign('oEntity', $oEntity);
		
		$aLanguages = \Factory::executeStatic('Util', 'getLanguages', 'frontend');
		
		$oSmarty->assign('aLanguages', $aLanguages);

		$sTemplatePath = \Util::getDocumentRoot().'system/bundles/FileManager/Resources/views/filemanager.tpl';
		$sContent = $oSmarty->fetch($sTemplatePath);

		echo $sContent;

		die();
	}
	
	private function getTags($sClass, $bIncludeGlobal=true) {
		
		if($bIncludeGlobal === true) {
			$sWhere = " AND 
				(
					`entity` = :entity OR
					`entity` IS NULL
				)";
		} else {
			$sWhere = " AND 
				`entity` = :entity
				";
		}
		
		$sSql = "
			SELECT 
				`id`,
				`tag`
			FROM 
				`filemanager_tags` 
			WHERE 
				`active` = 1".$sWhere."
			ORDER BY
				`tag`
		";
		$aSql = [
			'entity' => $sClass
		];
		$aTags = (array)\DB::getQueryPairs($sSql, $aSql);
		
		return $aTags;
	}
	
	private function getEntity($sClass, $iId) {
		
		if(class_exists($sClass)) {
			
			$oReflection = new \ReflectionClass($sClass);
			$bEntityClass = $oReflection->isSubclassOf('WDBasic');
		
			if($bEntityClass === true) {
				$oEntity = $sClass::getInstance($iId);
				return $oEntity;
			}
			
		}
		
	}
	
	public function postSaveAction() {

		$bMove = false;

		// TODO Gegen UploadedFile ersetzen
		foreach($_FILES['file']['tmp_name'] as $iFileKey=>$sTmpName) {

			if(is_file($_FILES['file']['tmp_name'][$iFileKey])) {

				$sClass = $this->_oRequest->get('entity');
				$iId = (int)$this->_oRequest->get('id');

				$oEntity = $this->getEntity($sClass, $iId);

				if($oEntity instanceof \WDBasic) {

					$sFileName = $oEntity->id.'_'.\Util::getCleanFilename($_FILES['file']['name'][$iFileKey]);

					$oFile = new \FileManager\Entity\File();
					$oFile->entity = $sClass;
					$oFile->entity_id = (int)$iId;
					$oFile->file = $sFileName;

					\Util::checkDir($oFile->getPath());

					if(move_uploaded_file($_FILES['file']['tmp_name'][$iFileKey], $oFile->getPathname())) {

						\Util::changeFileMode($oFile->getPathname());

						$oFile->save();

					}

				}

			}

		}
		
		$this->set('success', $bMove);
		
	}
	
	public function postLoadAction() {
		
		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');
			
		$aCriteria = [
			'entity' => $sClass,
			'entity_id' => $iId
		];
		
		$oRepo = \FileManager\Entity\File::getRepository();
		$aFiles = $oRepo->findBy($aCriteria);
		
		$aTags = $this->getTags($sClass);
		
		$oSmarty = new \SmartyWrapper();
		
		$oSmarty->assign('aTags', $aTags);
		
		$sContent = '';
		foreach($aFiles as $oFile) {

			$oSmarty->assign('oFile', $oFile);

			$sTemplatePath = \Util::getDocumentRoot().'system/bundles/FileManager/Resources/views/file.tpl';
			$sContent .= $oSmarty->fetch($sTemplatePath);
			
		}
		
		$this->set('html', $sContent);
		
	}
	
	public function postGetMetaAction() {
		
		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');
		$iFileId = (int)$this->_oRequest->get('file_id');
		
		$oFile = \FileManager\Entity\File::getInstance($iFileId);
		
		$this->set('file_id', $iFileId);
			
		if(
			$oFile->entity == $sClass &&
			$oFile->entity_id == $iId
		) {
		
			$aMeta = [];
			
			foreach($oFile->meta as $aItem) {
				$aMeta[$aItem['language_iso']] = $aItem;
			}
			
			$this->set('meta', $aMeta);
			
		}		
		
	}
		
	public function postSaveMetaAction() {
		
		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');
		$iFileId = (int)$this->_oRequest->get('file_id');
		
		$oFile = \FileManager\Entity\File::getInstance($iFileId);
		
		if(
			$oFile->entity == $sClass &&
			$oFile->entity_id == $iId
		) {
			
			$aTitle = $this->_oRequest->input('title');
			$aDescription = $this->_oRequest->input('description');
			$aSource = $this->_oRequest->input('source');
		
			$aMeta = [];
			foreach($aTitle as $sLanguageIso=>$sTitle) {
				$aMeta[] = [
					'language_iso' => $sLanguageIso,
					'title' => $aTitle[$sLanguageIso],
					'description' => $aDescription[$sLanguageIso],
					'source' => $aSource[$sLanguageIso]
				];
			}
			
			$oFile->meta = $aMeta;
			
			$oFile->save();
			
		}
		
		$this->set('file_id', $iFileId);
		$this->set('success', true);

	}
		
	public function postSaveTagsAction() {
		
		$aFileTags = (array)$this->_oRequest->input('tags');

		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');
			
		$aCriteria = [
			'entity' => $sClass,
			'entity_id' => $iId
		];
		
		$oPersister = \WDBasic_Persister::getInstance();
		
		$oRepo = \FileManager\Entity\File::getRepository();
		$aFiles = $oRepo->findBy($aCriteria);
		
		foreach($aFiles as $oFile) {
			$oFile->tags = [];
			$oPersister->attach($oFile);
		}
		
		foreach($aFileTags as $iFileId=>$aTagIds) {
			$oFile = \FileManager\Entity\File::getInstance($iFileId);
			$oFile->tags = $aTagIds;
			$oPersister->attach($oFile);
		}

		$oPersister->save();

		$this->set('success', true);
		
	}
	
	public function postDeleteAction() {

		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');
		$iFileId = (int)$this->_oRequest->get('file_id');
			
		$oFile = \FileManager\Entity\File::getInstance($iFileId);
		
		if(
			$oFile->entity == $sClass &&
			$oFile->entity_id == $iId
		) {
			$oFile->delete();
		}
		
		$this->set('id', $oFile->id);
		
	}
	
	public function sortableAction() {
		
		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');
		$aFiles = $this->_oRequest->get('filemanager-file');

		$oPersister = \WDBasic_Persister::getInstance();

		foreach($aFiles as $iPosition=>$iFileId) {
			$oFile = \FileManager\Entity\File::getInstance($iFileId);
			// Sicherheit
			if(
				$oFile->entity == $sClass &&
				$oFile->entity_id == $iId
			) {
				$oFile->position = ($iPosition+1);
				$oPersister->attach($oFile);
			}
		}

		$oPersister->save();

		$this->set('success', true);

	}
	
}