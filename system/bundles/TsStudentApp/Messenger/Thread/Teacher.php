<?php

namespace TsStudentApp\Messenger\Thread;

class Teacher extends CoreCommunication {

	public function getName(): string {
		return $this->entity->getName();
	}

	public function getImage(): ?string {
		$image = $this->entity->getProfilePicture();
		if ($image) {
			return $image->getPath(true).$image->file;
		}

		return null;
	}

	public function canCommunicate(): bool {

		// Kommunikation nur möglich wenn Lehrer der Buchung zugewiesen ist
		$teacher = collect($this->inquiry->getTuitionTeachers())
			->firstWhere('teacher_id', '=', $this->entity->getId());

		return !is_null($teacher);
	}

}
