<?php

class Checks_ContentStructure extends GlobalChecks {

	protected $_iLatestBlockContent = 800;


	public function getTitle() {
		return 'Update structure of content table';
	}
	
	public function getDescription() {
		return 'Optimises nested content elements.';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {

		$sKey = 'wdbasic_table_description_cms_content';
		WDCache::delete($sKey);
	
		$sKey = 'db_table_description_cms_content';	
		WDCache::delete($sKey);
	
		$aFields = DB::describeTable('cms_content');
		
		if(isset($aFields['parent_id'])) {
			return true;
		}

		$bBackup = Util::backupTable('cms_content');
		if(!$bBackup) {
			return false;
		}

		DB::addField('cms_content', 'parent_id', 'INT NULL', 'page_id', 'INDEX');
		
		DB::begin('Checks_ContentStructure');
		
		$sSql = "
			SELECT 
				`block`
			FROM 
				`cms_blocks`
			WHERE
				`content` LIKE :content
		";
		$aSql = [
			'content' => '%#block:content:%'
		];
		$aRelevantBlocks = DB::getQueryCol($sSql, $aSql);

		$sSql = "
			SELECT 
				*
			FROM 
				`cms_content`
			WHERE
				`element` = 'block' AND
				`file` IN (:blocks)
			ORDER BY
				`page_id`, 
				`number`
		";
		$aSql = [
			'blocks' => $aRelevantBlocks
		];
		$aContents = (array)DB::getQueryRows($sSql, $aSql);

		foreach($aContents as $aContent) {
			$this->checkBlock($aContent);			
		}

		DB::commit('Checks_ContentStructure');
		
		return true;
	}

	protected function checkBlock($aContent) {

		$iContentId = $aContent['id'];

		$oBlock = \Cms\Entity\Block::getRepository()->findOneBy(['block'=>$aContent['file']]);

		$blockCode = $oBlock->content;

		$oContent = Cms\Entity\Content::getInstance($iContentId);

		$bCheckCode = false;
		
		$pos=0;
		$asTagPre = array('#wd:', '#block:');
		foreach($asTagPre as $sNeedle) {

			while(false !== ($pos = strpos($blockCode,$sNeedle,$pos))) {

			    $end = strpos($blockCode,'#',$pos+1);
		    	$var = substr($blockCode, $pos+strlen($sNeedle), $end-$pos-strlen($sNeedle));
				$info = explode(":",$var);

				// Nur Platzhalter ersetzen, zu denen auch Inhalt gefunden wurde.
				if(count($info) > 1) {

					if($info[0] == "content") {

						$iContentPlaceholderNumber = $this->_iLatestBlockContent+(int)$info[1];

						$sSql = "
							SELECT 
								* 
							FROM 
								cms_content 
							WHERE 
								page_id = :page_id AND 
								number = :number AND 
								element = 'block' AND 
								active = 1
						";
						$aSql = [
							'page_id' => (int)$aContent['page_id'],
							'number' => $iContentPlaceholderNumber
						];
						$aChilds = DB::getQueryRows($sSql, $aSql);

						if(!empty($aChilds)) {
							foreach($aChilds as $aChild) {
								$aUpdate = [
									'parent_id' => $aContent['id'],
									'number' => (int)$info[1]
								];

								DB::updateData('cms_content', $aUpdate, '`id` = '.(int)$aChild['id']);
							}
						}

						$sBlockContent = '<#content'.$iContentPlaceholderNumber.'#>';

						$bCheckCode = true;

						$blockCode = str_replace($sNeedle.$var."#", $sBlockContent, $blockCode);

					} else {
						$pos += strlen($sNeedle);
					}
				} else {
					$pos += strlen($sNeedle);
				}
			}
		}

		return $blockCode;
	}
	
}
