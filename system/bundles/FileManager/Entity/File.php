<?php

namespace FileManager\Entity;

/**
 * @see \SplFileInfo
 * @method static FileRepository getRepository()
 */
class File extends \WDBasic {
	
	protected $_sTable = 'filemanager_files';
	protected $_sTableAlias = 'fm_f';
	protected $_sSortColumn = 'position';

	protected $_aJoinTables = [
		'tags' => [
			'table' => 'filemanager_files_tags',
			'foreign_key_field' => 'tag_id',
			'primary_key_field' => 'file_id',
			'class' => 'FileManager\Entity\Tag'			
		],
		'meta' => [
			'table' => 'filemanager_files_meta',
			'primary_key_field' => 'file_id',
		]
	];
	
	protected $sPath = 'storage/filemanager/';

	protected $sPublicPath = 'storage/public/filemanager/';				

	public function getEntity() {
		return \Factory::getInstance($this->entity, $this->entity_id);
	}

	/**
	 * Pfad OHNE Dateiname
	 *
	 * @param bool $bWithRoot
	 * @return string
	 */
	public function getPath($bWithRoot = true) {

		// In PHP 7.4 funktioniert method_exists nicht korrekt auf Traits
		if(in_array(\FileManager\Traits\FileManagerTrait::class, class_uses($this->entity))) {
			/** @var \FileManager\Traits\FileManagerTrait $oEntity */
			$oEntity = $this->getEntity();
			$sCleanClass = $oEntity->getFileManagerEntityPath();
		} else {
			$sCleanClass = \Util::getCleanFilename($this->entity);
		}

		$sPath = '';
		
		if($bWithRoot === true) {
			$sPath .= \Util::getDocumentRoot();
		}
		
		$sPath .= $this->sPath.$sCleanClass.'/';
				
		return $sPath;
	}

	/**
	 * Pfad MIT Dateiname
	 *
	 * @return string
	 */
	public function getPathname() {

		return $this->getPath().$this->file;

	}
	
	public function getPublicPath($bWithRoot=true) {
		
		$sPath = $this->getPath($bWithRoot);
		
		$sPath = str_replace($this->sPath, $this->sPublicPath, $sPath);
		
		return $sPath;
	}
	
	public function getUrl() {
		
		$sUrl = '/'.$this->getPath(false).$this->file;
		
		return $sUrl;
	}

	public function checkPublicFile() {

		$sPath = $this->getPath(true).$this->file;
		$sPublicPath = $this->getPublicPath(true).$this->file;

		if (
			is_file($sPath) &&
			!is_file($sPublicPath)
		) {
			\Util::checkDir($this->getPublicPath(true));
			return copy($sPath, $sPublicPath);
		}

		return true;

	}

	public function getPublicUrl() {

		$this->checkPublicFile();

		return '/'.$this->getPublicPath(false).$this->file;

	}

	public function getSize() {

		$sPath = $this->getPathname();
		if (is_file($sPath)) {
			return filesize($sPath);
		}

		return null;

	}

	/**
	 * TODO Die Methode sollte anders benannt werden.
	 *
	 * @deprecated
	 * @return string
	 */
	public function getFilesize() {

		return \Util::formatFilesize($this->getSize());
		
	}
	
	public function getExtension() {

		$aInfo = pathinfo($this->file);

		$sExt = $aInfo['extension'];

		return $sExt;
	}
	
	public function getIconClass() {

		$sExt = $this->getExtension();

		switch($sExt) {
			case 'jpg':
			case 'png':
			case 'gif':
				$sClass = 'fa-file-image-o';
				break;
			case 'pdf':
				$sClass = 'fa-file-pdf-o';
				break;
			default:
				$sClass = 'fa-paperclip';
				break;
		}
		
		return $sClass;
	}

	public function hasTag(string $sTag): bool {
		return in_array($sTag, $this->getTags());
	}

	public function getTags() {
		
		$aTags = $this->getJoinTableObjects('tags');
		
		$aReturn = [];
		foreach($aTags as $oTag) {
			$aReturn[$oTag->id] = $oTag->tag;
		}
		
		return $aReturn;
	}
	
	public function isImage() {
		
		$sExt = $this->getExtension();
		
		switch($sExt) {
			case 'jpg':
			case 'png':
			case 'gif':
				return true;
		}

		return false;
	}
	
	public function getThumbnail() {
		
		$aSet = [
			'date' => '1970-01-03',
			'x' => 243,
			'y' => 162,
			'bg_colour' => 'FFFFFF',
			'type' => 'jpg',
			'data' => [
				0 => [
					'type' => 'images',
					'file' => '',
					'x' => '0',
					'y' => '0',
					'from' => '1',
					'index' => '1',
					'text' => '',
					'user' => '1',
					'w' => '243',
					'h' => '162',
					'resize' => '1',
					'align' => 'C',
					'grayscale' => '0',
					'bg_colour' => 'ffffff',
					'rotate' => '0',
					'flip' => '',
					'position' => '1',
				]
			]
		];
		
		$sPath = $this->getPath(false).$this->file;
		$sPath = str_replace('storage/', '', $sPath);

		$aInfo = [
			1 => 'filemanager_thumbnail',
			2 => $sPath
		];

		$oImageBuilder = new \imgBuilder();
		$oImageBuilder->strImagePath = \Util::getDocumentRoot()."storage/";
		$oImageBuilder->strTargetPath = \Util::getDocumentRoot()."storage/tmp/";
		$oImageBuilder->strTargetUrl = "/storage/tmp/";
		$sImage = $oImageBuilder->buildImage($aInfo, false, true, $aSet);

		return $sImage;
	}

	public function delete() {
		
		$mErrors = parent::delete();
		
		if($mErrors === true) {
			
			$sPath = $this->getPathname();
			if(is_file($sPath)) {
				unlink($sPath);
			}
			
		}
		
		return $mErrors;
	}
	
}