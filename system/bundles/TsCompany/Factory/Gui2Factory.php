<?php

namespace TsCompany\Factory;

use Illuminate\Support\Str;

class Gui2Factory {

	private $set;

	private $page;

	private $gui2;

	private $optionalData = [
		'js' => [
			'/admin/extensions/thebing/gui2/util.js'
		],
		'css' => []
	];

	public function __construct(int $type, string $set, string $dialogClass, string $configFile = 'TsCompany_companies') {

		$this->page = new \Ext_TS_Gui2_Page();

		$this->set = $set;

		$factory = new \Ext_Gui2_Factory($configFile);
		$this->gui2 = $factory->createGui($this->set, null, [
			'dialog' => $dialogClass,
			'type' => $type,
		]);

		$this->gui2->setTableData('where', ['ka.type' => ['&', $type]]);

		$this->page->setGui($this->gui2);
	}

	public function addGui(\Ext_Gui2 $gui2, $parentGuiData = [], $filter = []) {

		if(!empty($parentGuiData) && !isset($parentGuiData['hash'])) {
			$parentGuiData['hash'] = $this->gui2->hash;
		}

		$this->page->setGui($gui2, $parentGuiData, $filter);

		return $this;
	}

	public function withContacts(string $dialogClass, string $configFile = 'TsCompany_contacts') {

		$factory = new \Ext_Gui2_Factory($configFile);
		$gui2 = $factory->createGui($this->set, $this->gui2, [
			'dialog' => $dialogClass,
		]);

		$this->addGui($gui2, ['foreign_key' => 'company_id', 'parent_primary_key' => 'id', 'reload' => true]);

		return $this;
	}

	public function withComments(string $uploadPath, string $uploadRight) {

		// alten Hash beibehalten
		$hashPart = ($this->set === 'agency') ? 'agencies' : $this->set;

		$directory = \Ext_Thebing_Client::getInstance()->getFilePath(false);
		$uploadPath = ltrim($uploadPath, DIRECTORY_SEPARATOR);

		$uploadPath = Str::finish($directory.$uploadPath, DIRECTORY_SEPARATOR);

		$comments = new \Ext_Thebing_Gui2_Comments(sprintf('thebing_marketing_%s_comments', $hashPart), \TsCompany\Entity\Comment::class, $this->set);
		$comments->setContactSelectionClass(\TsCompany\Gui2\Selection\CompanyContacts::class);
		$comments->setUploadRight($uploadRight);
		$comments->setUploadPath($uploadPath);

		$this->addGui($comments->get(), ['foreign_key' => 'company_id', 'parent_primary_key' => 'id', 'reload' => true]);

		return $this;
	}

	public function withUploads(string $uploadPath, string $uploadRight) {

		// alten Hash beibehalten
		$hashPart = ($this->set === 'agency') ? 'agencies' : $this->set;

		$directory = \Ext_Thebing_Client::getInstance()->getFilePath(false);
		$uploadPath = ltrim($uploadPath, DIRECTORY_SEPARATOR);

		$uploadPath = Str::finish($directory.$uploadPath, DIRECTORY_SEPARATOR);

		$upload = new \Ext_Thebing_Gui2_Uploads(sprintf('thebing_%s_upload_pdf', $hashPart), \TsCompany\Entity\Upload::class);
		$upload->setUploadRight($uploadRight);
		$upload->setUploadPath($uploadPath);

		$this->addGui($upload->get(), ['foreign_key' => 'company_id', 'parent_primary_key' => 'id', 'reload' => true]);

		return $this;
	}

	public function withStudents(string $foreignKey, string $configFile = 'ts_students_simple') {

		$factory = new \Ext_Gui2_Factory($configFile);
		$studentlist = $factory->createGui($this->set, $this->gui2);
		$studentlist->gui_title = $studentlist->t('Einfache Schülerliste');

		$this->addGui($studentlist, ['foreign_key' => $foreignKey, 'parent_primary_key' => 'id', 'reload' => true]);

		return $this;
	}

	public function display($optionalData = []) {

		if(isset($optionalData['js'])) {
			$this->optionalData['js'] = array_merge($this->optionalData['js'], $optionalData['js']);
		}

		if(isset($optionalData['css'])) {
			$this->optionalData['css'] = array_merge($this->optionalData['css'], $optionalData['css']);
		}

		$this->page->display($this->optionalData);
	}

	public function getGui(): \Ext_TC_Gui2|\Ext_Gui2 {
		return $this->gui2;
	}
}
