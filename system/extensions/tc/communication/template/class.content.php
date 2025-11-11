<?php

/**
 * Kommunikation: Template Inhate (Sprachtabs)
 * E-Mail und SMS
 */
class Ext_TC_Communication_Template_Content extends Ext_TC_Basic {

	protected $_sTable = 'tc_communication_templates_contents';
	
	protected $_sTableAlias = 'tc_ctc';
	
	protected $_aJoinedObjects = [
		'template' => [
			'class' => Ext_TC_Communication_Template::class,
			'key' => 'template_id',
			'type' => 'parent'
		],
		'layout' => [
			'class' => Ext_TC_Communication_Template_Email_Layout::class,
			'key' => 'layout_id',
			'type' => 'parent'
		]
	];

	protected $_aJoinTables = [
		'content_uploads' => [
			'table' => 'tc_communication_templates_contents_uploads',
			'foreign_key_field' => 'filename',
			'primary_key_field' => 'content_id'
		],
		'to_uploads' => [
			'table' => 'tc_communication_templates_contents_to_uploads',
			'foreign_key_field' => 'upload_id',
			'primary_key_field' => 'content_id'
		]
	];

	/**
	 * Fügt das Layout hinzu, falls es eines gibt
	 *
	 * @param string $content
	 * @return string
	 */
	public function insertLayout(string $content): string
	{
		$layout = $this->getLayout();

		if($layout->exist()) {
			$content = $layout->generateContent($content);
		}

		return $content;
	}

	public function getLayout(): \Ext_TC_Communication_Template_Email_Layout
	{
		return $this->getJoinedObject('layout');
	}

	public function getUploadFilePaths(): array
	{
		$type = $this->getJoinedObject('template')->type;

		$files = array_map(fn ($fileName) => storage_path('tc/communication/templates/'.$type.'/'.$fileName), $this->content_uploads);

		return array_filter($files, fn ($path) => file_exists($path));
	}

}
