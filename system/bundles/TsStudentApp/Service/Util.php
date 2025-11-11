<?php

namespace TsStudentApp\Service;

class Util
{
	public static function imageUrl(string $type, string $id) {
		return sprintf('/interface/image/%s/%s', $type, $id);
	}

	/**
	 * URL zu einem Dokument
	 *
	 * @param string $type
	 * @param string $id
	 * @return string
	 */
	public static function documentUrl(string $type, string $id) {
		return sprintf('/interface/document/%s/%s', $type, $id);
	}
}