<?php

class Ext_PdfMultiple extends Ext_Gui2_Pdf_Abstract
{
	public function getPdfPath($iSelectedId)
	{
		if($iSelectedId==55){
			return \Util::getDocumentRoot().'media/office.pdf';
		}elseif($iSelectedId==56){
			return \Util::getDocumentRoot().'media/downloads/1_first.pdf';
		}
	}
}
