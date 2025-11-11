<?php

namespace FileManager\Proxy;

use Tc\Proxy\Basic;

/**
 * @property \FileManager\Entity\File $oEntity
 */
class File extends Basic {

	protected $sEntityClass = '\FileManager\Entity\File';

	public function getMeta($sField, $sLanguageIso) {
		
		$aMeta = (array)$this->oEntity->meta;
		
		foreach($aMeta as $aRow) {
			if($aRow['language_iso'] === $sLanguageIso) {
				return $aRow[$sField];
			}
		}
		
	}

	public function getUrl() {
		return $this->oEntity->getUrl();
	}

	/**
	 * Die Datei wird in einen öffentlichen Ordner kopiert
	 * 
	 * @return string
	 */
	public function getPublicUrl() {
		return $this->oEntity->getPublicUrl();
	}
	
	public function isPdf() {
		
		$filecontent = file_get_contents($this->oEntity->getPathname());

		if (preg_match("/^%PDF-/", $filecontent)) {
			return true;
		}
		return false;
	}
	
	public function isImage() {
		if(exif_imagetype($this->oEntity->getPathname())) {
			return true;
		}
		return false;
	}
	
	public function getImgBuilderPath($setId, $useMedia=true) {
		
		$publicUrl = $this->getPublicUrl();
		$publicUrl = str_replace('/storage/public', '', $publicUrl);
		
		$imageBuilder = new \imgBuilder();
		
		$imageBuilderInfo = [0=>0];
		$imageBuilderInfo[1] = (int)$setId;
		$imageBuilderInfo[2] = $publicUrl;

		$image = $imageBuilder->buildImage($imageBuilderInfo);

		$pathInfo = pathinfo(\Util::getDocumentRoot(false).$image);

		$pathInfoOriginal = pathinfo($this->oEntity->getPathname());

		$targetFileName = $setId.'-'.$pathInfoOriginal['filename'].'.'.$pathInfo['extension'];
		
		copy(\Util::getDocumentRoot(false).$image, $imageBuilder->strTargetPath.$targetFileName);
		
		if($useMedia) {
			return str_replace('/storage/public/', '/media/', $imageBuilder->strTargetUrl).$targetFileName;
		} else {
			return $imageBuilder->strTargetUrl.$targetFileName;
		}
		
	}
	
}