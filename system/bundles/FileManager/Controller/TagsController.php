<?php

namespace FileManager\Controller;

class TagsController extends \MVC_Abstract_Controller {

	public function postSaveAction() {

		$aTags = (array)$this->_oRequest->input('tags');
		$sClass = $this->_oRequest->get('class');
		$iId = (int)$this->_oRequest->get('id');

		// Alle Tags der Entity holen
		$sSql = "
			SELECT 
				`tag`, 
				`id` 
			FROM 
				`filemanager_tags` 
			WHERE 
				`active` = 1 AND 
				`entity` = :entity
		";
		$aSql = [
			'entity' => $sClass
		];
		$aCurrentTags = (array)\DB::getQueryPairs($sSql, $aSql);
		
		// Neue Tags anlegen
		foreach($aTags as $sTag) {
			if(!isset($aCurrentTags[$sTag])) {
				$oTag = new \FileManager\Entity\Tag();
				$oTag->entity = $sClass;
				$oTag->tag = $sTag;
				$oTag->save();
			} else {
				unset($aCurrentTags[$sTag]);
			}
		}

		// Nicht verwendete Tags löschen (auch die Verwendung)
		foreach($aCurrentTags as $sTag=>$iId) {
			
			$sSql = "
				DELETE 
					`filemanager_tags`, 
					`filemanager_files_tags` 
				FROM 
					`filemanager_tags` LEFT JOIN 
					`filemanager_files_tags` ON 
						`filemanager_tags`.`id` = `filemanager_files_tags`.`tag_id` 
				WHERE 
					`filemanager_tags`.`id` = :id
			";
			$aSql = ['id'=>$iId];
			\DB::executePreparedQuery($sSql, $aSql);

		}

		sort($aTags);

		$this->set('success', true);
		$this->set('tags', $aTags);

	}

}