<?php

namespace Cms\Entity;

 class PageTemplate extends Page {

	/*
	 * Get alle Placeholder of the page
	 */
	public function getPlaceholder() {
		
		// Get Page Id
		$intPageId = $this->id;
		
		// Select all Placeholder
		$strSql = "
				SELECT 
					c.id AS content_id , 
					c.number AS element_id ,  
					c.* ,  
					e.title		modul_title,
					e.category	modul_category,
					b.title		block_title
				FROM 
					cms_content c LEFT OUTER JOIN  
						system_elements e ON e.file = c.file 
						LEFT OUTER JOIN  
						cms_blocks b ON b.block = c.file
				WHERE
					c.page_id = ".(int)$intPageId." AND 
					(
						`c`.`element` = 'modul' OR
						`c`.`element` = 'contentblock'
					)
				ORDER BY 
					`c`.`number` ASC";

		$aRows =  \DB::getQueryData($strSql);
		// Return Result
		return $aRows;
	}
	
	// Add Placeholder
	public function addContent($sFile, $sElement='modul') {
		
		$aPlaceholder = $this->getPlaceholder();
		
		$i=1;
		
		$iNumer = 1;
		
		foreach($aPlaceholder as $key =>$aValue){
			
			if($aValue['number']==$i){
				$i++;
			} else {
				break;
			}
		}
		
		$iNumer = $i ;	
		
		$sSQL = "
				INSERT INTO 
					cms_content 
				SET 
					page_id = :iPageid , 
					element = :sElement, 
					active = '1', 
					number = :iAnzPlatzhalter, 
					level = 1, 
					author = :iAuthor, 
					file = :sFile 
				";
		
		$aSQL = array(
					'iPageid'=> $this->id,
					'iAnzPlatzhalter'=>$iNumer,
					'iAuthor'=> \Access::getInstance()->id, 
					'sFile'=>$sFile,
					'sElement'=>$sElement);
		
		$rSQL = \DB::executePreparedQuery($sSQL, $aSQL);
	
	}

	/*
	 * Get Modul Result or with Parameter 1 = 1 array for GUI Form Select
	 */
	public function getModule($iForSelect=0){


		$strSql = "
					SELECT
						*
					FROM 
						system_elements
					WHERE 
						element='modul'
					ORDER BY 
						`category` ASC,
						`title` ASC
					";

		$arrSql = array();

		$arrSql = \DB::getQueryData($strSql);

		// Prepare Lizenses for managing in the Forms
		$arrModuleList = array();

		foreach ($arrSql as $arrValue) {

			$arrModuleList[] = array('display' => $arrValue['category']." &raquo; ".$arrValue['title'], 'value' => $arrValue['file']);

		}

		if($iForSelect == 0){

			$aBack = $arrSql;

		} else {

			$aBack = $arrModuleList;
		}

		return $aBack;
	}

	public function getBlocks($iForSelect=0){


		$strSql = "
					SELECT
						*
					FROM 
						cms_blocks
					WHERE 
						active = 1
					ORDER BY 
						`title` ASC
					";

		$arrSql = array();

		$arrSql = \DB::getQueryData($strSql);


		if($iForSelect == 0){

			$aBack = $arrSql;

		} else {

			// Prepare Lizenses for managing in the Forms
			$arrModuleList = array();

			foreach ($arrSql as $arrValue) {

				$arrModuleList[] = array('display' => $arrValue['title'], 'value' => $arrValue['block']);

			}
			$aBack = $arrModuleList;
		}

		return $aBack;
	}

	// Replace Placeholder in the Page Template with IMG
	public function replacePlaceholder() {

		$sTemplate = $this->template;
		
		while(preg_match("/<#content(.{3})#>/", $sTemplate, $regs)) {
			$new_number = $regs[1];
			
			if ($new_number >= 900) {
			
				$c_number = $new_number - 900;
				$temp_number = (int)$new_number;
				$sTemplate = str_replace("<#content$new_number#>","<IMG onClick=\"parent.parent.preload.switchLayer('1');\" src=\"".\System::d('domain')."/admin/includes/PHPcontent.php?element_id=".$new_number."&page_id=".$this->id."&element=template\" border=0>", $sTemplate);
			
			} else {
			
				$sTemplate = str_replace("<#content$new_number#>","<IMG onClick=\"parent.parent.preload.switchLayer('1');\" src=\"".\System::d('domain')."/admin/includes/PHPcontent.php?element_id=".$new_number."&page_id=".$this->id."&element=template\" border=0>", $sTemplate);
			
			}
		}
		
		return $sTemplate;
	}
 
}
