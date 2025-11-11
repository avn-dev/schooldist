<?php

class Ext_Thebing_Gui2_Uploads {
	
	protected $_sHashName;
	protected $_sWDBasicClass;
	protected $_sUploadRight;
	protected $_sUploadPath;

	public function __construct($sHashName, $sWDBasicClass) {
		$this->_sHashName = $sHashName;
		$this->_sWDBasicClass = $sWDBasicClass;
	}

	public function setUploadRight($sRight) {
		$this->_sUploadRight = $sRight;
	}
	
	public function setUploadPath($sPath) {
		$this->_sUploadPath = $sPath;
	}
	
	public function get() {

		// PDF Upload Gui
		$oGuiPdf = new Ext_Thebing_Gui2(md5($this->_sHashName));
		$oGuiPdf->gui_description 	= 'Thebing » Uploads';

		$oDialogPDf = $oGuiPdf->createDialog('Datei "{filename}" bearbeiten', $oGuiPdf->t('Neue Datei hochladen'));
		$oDialogPDf->width = 950;

		$oUpload = new Ext_Gui2_Dialog_Upload($oGuiPdf, 'Upload', $oDialogPDf, 'filename', '', $this->_sUploadPath);
		$oUpload->bAddColumnData2Filename = 0;
		$oUpload->required = true;
		$oDialogPDf->setElement($oUpload);

		$oDialogPDf->setElement($oDialogPDf->createRow($oGuiPdf->t('Beschreibung'), 'textarea', array('db_alias' => '','db_column'=>'description')));

		$oGuiPdf->setWDBasic($this->_sWDBasicClass);
		$oGuiPdf->setTableData('where',  array('type' => 'pdf'));

		// Listen Optionen
		$oGuiPdf->gui_title 		= $oGuiPdf->t('Uploads');
		$oGuiPdf->column_sortable	= 1; // es geht nur eine sortierart!
		$oGuiPdf->row_sortable		= 0; // es geht nur eine sortierart! ( hat prioritär )
		$oGuiPdf->multiple_selection = 0;
		$oGuiPdf->query_id_column	= 'id';
		$oGuiPdf->query_id_alias	= '';
		$oGuiPdf->class_js			= 'UtilGui';

		if(Ext_Thebing_Access::hasRight($this->_sUploadRight)) {

			$oBar = $oGuiPdf->createBar();
			$oBar->width = '100%';

			/*$oLabel = $oBar->createLabelGroup($oGuiPdf->t('Aktionen'));
			$oBar->setElement($oLabel);*/

			$oIcon = $oBar->createNewIcon(
						$oGuiPdf->t('Neue Datei'),
						$oDialogPDf,
						$oGuiPdf->t('Neue Datei')
					);
			$oBar ->setElement($oIcon);

			$oIcon = $oBar->createDeleteIcon($oGuiPdf->t('Datei löschen'), $oGuiPdf->t('Datei löschen'));
			$oBar ->setElement($oIcon);

			$oGuiPdf->setBar($oBar);
		}

		$oColumn = $oGuiPdf->createColumn();
		$oColumn->db_column 	= 'agency_upload_pdf';
		$oColumn->select_column = 'filename';
		$oColumn->db_alias 		= '';
		$oColumn->title 		= $oGuiPdf->t('Datei');
		$oColumn->width 		= 50;
		$oColumn->width_resize 	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Upload('filename', $this->_sUploadPath);
		$oColumn->sortable 		= 0;
		$oGuiPdf->setColumn($oColumn);

		$oColumn = $oGuiPdf->createColumn();
		$oColumn->db_column		= 'description';
		$oColumn->db_alias 	   	= '';
		$oColumn->title 	   	= $oGuiPdf->t('Beschreibung');
		$oColumn->width 	   	= 150;
		$oColumn->width_resize 	= true;
		$oGuiPdf->setColumn($oColumn);

		$oGuiPdf->addDefaultColumns();

		return $oGuiPdf;
	}
	
}