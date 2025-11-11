<?php

namespace Core\Validator\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class Base64Image implements Rule
{
	public function passes($attribute, $value)
	{
		if (!preg_match('/^data:image\/(\w+);base64,/', $value)) {
			return false;
		}

		[$type, $data] = explode(';', $value);
		[, $data] = explode(',', $data);

		if (!in_array(Str::after($type, 'data:'), ['image/png', 'image/jpeg', 'image/gif'])) {
			return false;
		}

		if (strlen(base64_decode($data)) > 2 * 1024 * 1024) {
			return false;
		}

		return true;
	}

	public function message()
	{
		return 'The :attribute must be a valid base64 encoded image (PNG, JPEG, GIF) with a maximum size of 2MB.';
	}
}