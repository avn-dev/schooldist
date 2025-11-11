<?php

namespace Cms\Controller;

class ContentController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = 'edit';
	
	protected $_sViewClass = '\MVC_View_Smarty';
	
	protected $aBlocks = [];
	
	public function saveStructureAction() {
		
		$aItems = $this->_oRequest->getJSONDecodedPostData();
		
		foreach($aItems as $aItem) {
			
			if(strpos($aItem['id'], 'block') !== false) {
				$iContentId = str_replace('block_', '', $aItem['id']);
				$aUpdate = [
					'level' => ($aItem['order']+1),
					'parent_id' => null
				];
				if(!empty($aItem['parentId'])) {
					$aParts = explode('_', $aItem['parentId']);
					if(empty($aParts[1])) {
						$aUpdate['parent_id'] = null;
					} else {
						$aUpdate['parent_id'] = $aParts[1];
					}
					$aUpdate['number'] = $aParts[2];
				}

				\DB::updateData('cms_content', $aUpdate, '`id` = '.(int)$iContentId);
			}
			
		}

		$aResponse = [
			'success' => true
		];
		
		echo json_encode($aResponse);
		die();
	}
	
	public function deleteContentAction() {

		$iContentId = (int)$this->_oRequest->get('content_id');

		$oContent = \Cms\Entity\Content::getInstance($iContentId);
		$oContent->delete();

		$aResponse = [
			'success' => true
		];

		echo json_encode($aResponse);
		die();
	}

	public function structureAction() {

		$iPageId = $this->_oRequest->get('page_id');
		$sLanguage = $this->_oRequest->get('language');

		$aBlocks = \Cms\Entity\Block::getRepository()->findAll();
		foreach($aBlocks as $oBlock) {
			$this->aBlocks[$oBlock->block] = $oBlock->title;
		}
		
		asort($this->aBlocks);
		
		$oPage = \Cms\Entity\Page::getInstance($iPageId);
		
		$aStructure = $this->getAreas($oPage, $sLanguage);

		$this->set('aStructure', $aStructure);
		$this->set('oPage', $oPage);

	}
	
	protected function getAreasFromBlockTemplate($iContentId) {
		
		$oContent = \Cms\Entity\Content::getInstance($iContentId);

		$oBlock = \Cms\Entity\Block::getRepository()->findOneBy(['block'=>$oContent->file]);

		$blockCode = $oBlock->content;

		$bCheckCode = false;
		
		$aAreas = [];
		
		$pos=0;
		$asTagPre = array('#wd:', '#block:');
		foreach($asTagPre as $sNeedle) {

			while(false !== ($pos = strpos($blockCode, $sNeedle, $pos))) {

			    $end = strpos($blockCode,'#',$pos+1);
		    	$var = substr($blockCode, $pos+strlen($sNeedle), $end-$pos-strlen($sNeedle));
				$info = explode(":",$var);

				// Nur Platzhalter ersetzen, zu denen auch Inhalt gefunden wurde.
				if(count($info) > 1) {

					if($info[0] == "content") {
						
						$aAreas[] = (int)$info[1];
						
						$blockCode = str_replace($sNeedle.$var."#", $sBlockContent, $blockCode);
					}

				}

				$pos += strlen($sNeedle);

			}
		}
		
		return $aAreas;
	}
	
	/**
	 * Die Bereiche werden per DB und über die Templates geholt.
	 * Es werden nur die Blocktemplates geparst, daher braucht man noch die Bereiche der DB und bei den Blöcken wird die
	 * DB abgefragt, damit man Blöcke aus inaktiven Bereichen in aktive verschieben kann.
	 * 
	 * @param int $iPageId
	 * @param int $iParentId
	 * @return array
	 */
	protected function getAreas(\Cms\Entity\Page $oPage, $sLanguage, $iParentId=null) {

		$iPageId = $oPage->id;
		
		$sSql = "
			SELECT
				`number`
			FROM
				`cms_content`
			WHERE
				`active` = 1 AND
				`page_id` = :page_id AND
		";
		
		if(empty($iParentId)) {
			$sSql .= "`parent_id` IS NULL ";
		} else {
			$sSql .= "`parent_id` = :parent_id ";
		}
		
		$sSql .= "
			GROUP BY 
				`number`
			ORDER BY 
				`number`
		";
		$aSql = [
			'page_id' => (int)$iPageId,
			'parent_id' => (int)$iParentId
		];
		$aNumbers = (array)\DB::getQueryCol($sSql, $aSql);

		$aStructure = [];
		
		$aAreas = $this->getAreasFromBlockTemplate($iParentId);

		$aNumbers = $aNumbers + $aAreas;

		if(!empty($aNumbers)) {
			foreach($aNumbers as $iNumber) {
				$aContents = $this->getContents($oPage, $sLanguage, $iParentId, $iNumber);

				$aStructure[] = [
					'label' => 'Bereich '.$iNumber,
					'area' => $iParentId.'_'.$iNumber,
					'type' => 'area',
					'childs' => $aContents
				];
			}
		}

		return $aStructure;
	}
	
	protected function getContents(\Cms\Entity\Page $oPage, $sLanguage, $iParentId=null, $iNumber=1) {
		
		$iPageId = $oPage->id;
		
		$sSql = "
			SELECT
				*
			FROM
				`cms_content`
			WHERE
				`active` = 1 AND
				`page_id` = :page_id AND
		";

		if($iParentId === null) {
			$sSql .= "`parent_id` IS NULL AND";
		} else {
			$sSql .= "`parent_id` = :parent_id AND";
		}
				
		$sSql .= "
				`number` = :number
			ORDER BY 
				`level`
		";
		$aSql = [
			'page_id' => (int)$iPageId,
			'parent_id' => (int)$iParentId,
			'number' => (int)$iNumber
		];
		$aContents = \DB::getQueryRows($sSql, $aSql);

		$aReturn = [];
		
		if(!empty($aContents)) {
			foreach($aContents as $aContent) {

				$aChilds = $this->getAreas($oPage, $sLanguage, $aContent['id']);

				$sLabel = $this->aBlocks[$aContent['file']];
				
				if(!empty($aContent['title'])) {
					$sLabel .= ', '.$aContent['title'];
				}
				
				$bDisplayCondition = false;
				
				if(
					(
						!empty($aContent['validfrom']) &&
						$aContent['validfrom'] !== '0000-00-00 00:00:00'
					) ||
					(
						!empty($aContent['validto']) &&
						$aContent['validto'] !== '0000-00-00 00:00:00'
					) ||
					!empty($aContent['access'])						
				) {
					$bDisplayCondition = true;
				}
				
				$aReturn[] = [
					'label' => $sLabel,
					'type' => 'block',
					'id' => $aContent['id'],
					'childs' => $aChilds,
					'display_condition' => $bDisplayCondition
				];
			}
		}

		return $aReturn;
	}
	
}