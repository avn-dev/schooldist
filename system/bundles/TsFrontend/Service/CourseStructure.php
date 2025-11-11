<?php

namespace TsFrontend\Service;

use \TsFrontend\DTO\CourseStructure\Node;

class CourseStructure {
	
	/**
	 * @var self
	 */
	static $instances = [];
	
	/**
	 * @var \Ext_Thebing_School 
	 */
	private $school;

	/**
	 * Virtuelle Kurskategorien identifizieren können
	 *
	 * @var int
	 */
	private int $counter = -1;

	/**
	 * Wegen Singleton müssen Nodes gecacht werden, damit $counter gleich bleibt
	 *
	 * @var array
	 */
	private array $nodes = [];
	
	/**
	 * 
	 * @param \Ext_Thebing_School $school
	 * @return \self
	 */
	static public function getInstance(\Ext_Thebing_School $school):self {
		
		if(!isset(self::$instances[$school->id])) {
			self::$instances[$school->id] = new self($school);
		}
		
		return self::$instances[$school->id];
	}
	
	private function __construct(\Ext_Thebing_School $school) {
		$this->school = $school;
	}
	
	public function isDefault() {
		
		$json = $this->school->frontend_course_structure;
		
		if(!empty($json)) {
			return false;
		}
		
		return true;
	}
	
	public function getStructure() {

		if (isset($this->nodes[$this->school->id])) {
			return $this->nodes[$this->school->id];
		}

		$json = $this->school->frontend_course_structure;
		
		// Angepasste Struktur
		if(!empty($json)) {
			
			$structure = json_decode($json, true);

			$this->nodes[$this->school->id] = new Node;
			$this->getCustomStructure($this->nodes[$this->school->id], $structure);
			
		} else {

			$this->nodes[$this->school->id] = $this->getDefaultStructure();

		}
		
		return $this->nodes[$this->school->id];
	}
	
	private function getCustomStructure(Node $masterNode, array $structure) {
		
		$languages = $this->school->getLanguages();

		foreach($structure as $item) {

			$node = new Node();
			
			$node->setId($item['type'], $item['id'] ?? $this->counter--);
			
			if($item['type'] === 'course') {
				
				$course = \Ext_Thebing_Tuition_Course::getInstance($item['id']);
				
				foreach($languages as $language) {
					$node->setName($course->getName($language), $language);
				}
				
			} else {
				// Kategorie

				foreach($item['names'] as $language=>$name) {
					$node->setName($name, $language);
				}
				
				if(!empty($item['childs'])) {
					$this->getCustomStructure($node, $item['childs']);
				}

				if (!empty($item['icon'])) {
					$node->setIcon($item['icon']);
				}
			}

			$masterNode->addChild($node);

		}
	
	}
	
	private function getDefaultStructure() {

		$languages = $this->school->getLanguages();

		$masterNode = new \TsFrontend\DTO\CourseStructure\Node;

		// Kategorien laden
		$categories = $this->school->getCourseCategoriesList('object');

		foreach($categories as $category) {

			$node = new \TsFrontend\DTO\CourseStructure\Node();
			$node->setId('category', $category->id);
			
			if (!empty($category->frontend_icon_class)) {
				$node->setIcon($category->frontend_icon_class);
			}

			foreach($languages as $language) {
				$node->setName($category->getName($language), $language);
			}

			// Pro Kategorie Kurse laden
			$courses = \Ext_Thebing_Tuition_Course::getRepository()->getBySchool($this->school, ['category_id' => $category->id]);

			foreach($courses as $course) {

				$child = new \TsFrontend\DTO\CourseStructure\Node();
				$child->setId('course', $course->id);
				foreach($languages as $language) {
					$child->setName($course->getName($language), $language);
				}

				$node->addChild($child);
			}

			$masterNode->addChild($node);

		}

		return $masterNode;
	}
	
	public function convertRequestData(array $data) {
		
		$structure = [];
		
		foreach($data as $item) {
			$new = [];
			
			if(isset($item['value']['course_id'])) {
				$new['type'] = 'course';
				$new['id'] = $item['value']['course_id'];
			} else {
				$new['type'] = 'category';
				$new['id'] = $item['value']['category_id'];
				$new['names'] = $item['value'];
				$new['icon'] = $item['value']['icon'];
				unset($new['names']['category_id'], $new['names']['icon']);
			}
			
			if(!empty($item['children'])) {
				$new['childs'] = $this->convertRequestData($item['children']);
			}
			
			$structure[] = $new;
		}
		
		return $structure;
	}
	
	public function saveRequestData(array $data) {
		
		$structure = $this->convertRequestData($data);
		
		$this->school->frontend_course_structure = json_encode($structure);
		$this->school->save();
		
	}
	
	public function reset() {
		
		$this->school->frontend_course_structure = null;
		$this->school->save();
		
	}
	
}
