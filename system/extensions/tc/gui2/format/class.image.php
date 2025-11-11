<?php
/**
 * Formatklasse für Bilder in Listen
 */
class Ext_TC_Gui2_Format_Image extends Ext_Gui2_View_Format_Abstract
{
	/**
	 * @param string $mediaDirectory
	 * @param string $title Titel für das img
	 * @param string $set
	 * @param int $width
	 * @param int $height
	 * @param int $resize
	 */
	public function __construct(
		protected string $mediaDirectory = '',
		protected string $title = '',
		protected string $set = 'gui2_format_image',
		protected int $width = 120,
		protected int $height = 120,
		protected int $resize = 1
	)
	{
		if (
			str_starts_with($mediaDirectory, '/')
		) {
			$mediaDirectory = substr($mediaDirectory, 1);
		}

		$this->mediaDirectory = $mediaDirectory;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null): ?string
	{
		/** @todo: db_column title,alt **/
		/** @todo: image resize mit php **/

		if (!empty($mValue)) {

			$file = \Util::getDocumentRoot().'storage/'.$this->mediaDirectory.'/'.$mValue;

			if (file_exists($file) ) {

				$filePath	= $this->mediaDirectory.'/'.$mValue;

				$image = $this->buildImage($mValue);

				$onClick = 'onclick="window.open(\'/storage/download/'.$filePath.'\'); return false"';

				return '<img style="cursor:pointer;" ' . $onClick . ' src="'.$image.'" alt="' . $this->title . '" title="' . $this->title . '"/>';
			}
		}

		return null;
	}

	public function buildImage(string $image): string
	{
		$set = [
			'date' => $this->width.'-'.$this->height.'-'.$this->resize,
			'x' => $this->width,
			'y' => $this->height,
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
					'w' => $this->width,
					'h' => $this->height,
					'resize' => $this->resize,
					'align' => 'C',
					'grayscale' => '0',
					'bg_colour' => 'ffffff',
					'rotate' => '0',
					'flip' => '',
					'position' => '1',
				]
			]
		];

		$filePath	= $this->mediaDirectory.'/'.$image;

		$info = [
			1 => $this->set,
			2 => $filePath
		];

		$imageBuilder = new \imgBuilder();
		$imageBuilder->strImagePath = \Util::getDocumentRoot()."storage/";
		$imageBuilder->strTargetPath = \Util::getDocumentRoot()."storage/tmp/";
		$imageBuilder->strTargetUrl = "/storage/tmp/";

		return $imageBuilder->buildImage($info, false, true, $set);
	}

	public function align(&$oColumn = null)
	{
		return 'center';
	}

}