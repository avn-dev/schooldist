<?php

namespace TsFrontend\DTO\CourseStructure;

class Node {
	
	private $id;
	private $type = 'master';
	private $names = [];
	private $childs = [];
	private $icon = '';
	
	public function __construct() {
	}
	
	public function addChild(self $child) {
		$this->childs[] = $child;
	}
	
	public function setId(string $type, int $id=null) {
		$this->type = $type;
		$this->id = $id;
	}
	
	public function setName(string $name, string $iso) {
		$this->names[$iso] = $name;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getType() {
		return $this->type;
	}

	public function getName(string $language = null) {
		if ($language === null) {
			$language = \Ext_Thebing_School::fetchInterfaceLanguage();
		}
		return $this->names[$language];
	}
	
	public function getChilds() {
		return $this->childs;
	}
	
	/**
	 * Gibt ein Array mit Daten für die Baumstruktur zurück
	 * @return array
	 */
	public function getData() {
		
		$data = [];
		
		if($this->type === 'course') {
			$data['course_id'] = $this->id;
		} elseif($this->type === 'category') {
			if($this->id > 0) {
				$data['category_id'] = $this->id;
			}

			$data['icon'] = $this->icon;
			$data = array_merge($data, $this->names);
		}
		
		return $data;
	}

	/**
	 * @return string
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @param string $icon
	 */
	public function setIcon(string $icon) {
		$this->icon = $icon;
	}

}
