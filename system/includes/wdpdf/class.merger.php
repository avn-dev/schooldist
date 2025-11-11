<?php
/**
 *  PDFMerger created by Jarrod Nettles December 2009
 *  jarrod@squarecrow.com
 *  
 *  v1.0
 * 
 * Class for easily merging PDFs (or specific pages of PDFs) together into one. Output to a file, browser, download, or return as a string.
 * Unfortunately, this class does not preserve many of the enhancements your original PDF might contain. It treats
 * your PDF page as an image and then concatenates them all together.
 * 
 * Note that your PDFs are merged in the order that you provide them using the addPDF function, same as the pages.
 * If you put pages 12-14 before 1-5 then 12-15 will be placed first in the output.
 * 
 * 
 * Uses FPDI 1.3.1 from Setasign
 * Uses FPDF 1.6 by Olivier Plathey with FPDF_TPL extension 1.1.3 by Setasign
 * 
 * Both of these packages are free and open source software, bundled with this class for ease of use. 
 * They are not modified in any way. PDFMerger has all the limitations of the FPDI package - essentially, it cannot import dynamic content
 * such as form fields, links or page annotations (anything not a part of the page content stream).
 * 
 * Klasse angepasst an plan-i:Framework
 * 
 */
class WDPdf_Merger
{

	private $_aFiles;	//['form.pdf']  ["1,2,4, 5-19"]
	private $_oFpdi;
	
	/**
	 * Add a PDF for inclusion in the merge with a valid file path. Pages should be formatted: 1,3,6, 12-16. 
	 * @param $sFilepath
	 * @param $mPages
	 * @return void
	 */
	public function addPDF($sFilepath, $mPages = 'all')
	{
		if(file_exists($sFilepath))
		{
			if(strtolower($mPages) != 'all')
			{
				$mPages = $this->_rewritepages($mPages);
			}
			
			$this->_aFiles[] = array($sFilepath, $mPages);
		}
		else
		{
			throw new exception("Could not locate PDF on '$sFilepath'");
		}
		
		return $this;
	}
	
	/**
	 * Merges your provided PDFs and outputs to specified location.
	 * @param $sOutputmode
	 * @param $outputname
	 * @return PDF
	 */
	public function merge($sOutputmode = 'browser', $sOutputpath = 'newfile.pdf') {

		if(
			!isset($this->_aFiles) || 
			!is_array($this->_aFiles)
		) {
			throw new exception("No PDFs to merge.");
		}

		$oFpdi = new \Pdf\Service\Fpdi();

		$oFpdi->setPrintHeader(false);
		$oFpdi->setPrintFooter(false);

		//merger operations
		foreach($this->_aFiles as $file)
		{
			$sFilename  = $file[0];
			$aFilepages = $file[1];
			
			$iCount = $oFpdi->setSourceFile($sFilename);
			
			//add the pages
			if($aFilepages == 'all')
			{
				for($i=1; $i<=$iCount; $i++)
				{
					$template 	= $oFpdi->importPage($i);
					$size 		= $oFpdi->getTemplateSize($template);
					
					$oFpdi->AddPage('P', array($size['width'], $size['height']));
					$oFpdi->useTemplate($template);
				}
			}
			else
			{
				foreach($aFilepages as $page)
				{
					if(!$template = $oFpdi->importPage($page)): throw new exception("Could not load page '$page' in PDF '$sFilename'. Check that the page exists."); endif;
					$size = $oFpdi->getTemplateSize($template);
					
					$oFpdi->AddPage('P', array($size['width'], $size['height']));
					$oFpdi->useTemplate($template);
				}
			}	
		}
		
		//output operations
		$sMode = $this->_switchmode($sOutputmode);
		
		if($sMode == 'S') {
			return $oFpdi->Output($sOutputpath, 'S');
		} else {
			
			// Bisherige Ausgabe abbrechen
			while(ob_get_level()) {
				ob_end_clean();
			}
			
			if($oFpdi->Output($sOutputpath, $sMode)) {
				return true;
			} else {
				throw new exception("Error outputting PDF to '$sOutputmode'.");
				return false;
			}
		}

	}

	/**
	 * FPDI uses single characters for specifying the output location. Change our more descriptive string into proper format.
	 * @param $sMode
	 * @return Character
	 */
	private function _switchmode($sMode)
	{
		switch(strtolower($sMode))
		{
			case 'download':
				return 'D';
				break;
			case 'browser':
				return 'I';
				break;
			case 'file':
				return 'F';
				break;
			case 'string':
				return 'S';
				break;
			default:
				return 'I';
				break;
		}
	}
	
	/**
	 * Takes our provided pages in the form of 1,3,4,16-50 and creates an array of all pages
	 * @param $sPages
	 * @return unknown_type
	 */
	private function _rewritepages($sPages)
	{
		$sPages = str_replace(' ', '', $sPages);
		$aPart = explode(',', $sPages);
		
		$aNewpages = array();
		
		//parse hyphens
		foreach($aPart as $i)
		{
			$ind = explode('-', $i);

			if(count($ind) == 2)
			{
				$x = $ind[0]; //start page
				$y = $ind[1]; //end page
				
				if($x > $y): throw new exception("Starting page, '$x' is greater than ending page '$y'."); return false; endif;	
				
				//add middle pages
				while($x <= $y): $aNewpages[] = (int) $x; $x++; endwhile;
			}
			else
			{
				$aNewpages[] = (int) $ind[0];
			}
		}
		
		return $aNewpages;
	}
	
}