<?php

namespace TsStudentApp\Messenger\Thread;

class School extends CoreCommunication {

	public function getName(): string {
		return $this->entity->getName();
	}

	public function getImage(): ?string {
		$logo = $this->entity->getLogo(true);
		return (!empty($logo)) ? $logo : null;
	}

	public function canCommunicate(): bool {
		return ($this->entity->getId() === $this->inquiry->getSchool()->getId());
	}

}
