<?php

namespace Admin\Traits\Component;

use Admin\Dto\Component\Parameters;
use Admin\Exceptions\InvalidComponentParameters;
use Core\Factory\ValidatorFactory;

trait WithParameters
{
	protected ?Parameters $parameters = null;

	public function rules(): array
	{
		return [];
	}

	public function setParameters(Parameters $parameters): static
	{
		$this->parameters = $parameters;
		return $this;
	}

	public function getParameterValues(): ?Parameters
	{
		return $this->parameters;
	}

	public function validate(array $values): array
	{
		return $this->validateRules($values);
	}

	protected function validateRules(array $values)
	{
		$rules = [
			...$this->rules()
			// TODO aus Parameter auslesen
		];

		if (empty($rules)) {
			return $values;
		}

		$validator = (new ValidatorFactory(\System::getInterfaceLanguage()))
			->make($values, $rules);

		if ($validator->fails()) {
			//dd($this::class, $validator->getMessageBag()->messages(), $values);
			throw new InvalidComponentParameters($this, $validator->getMessageBag()->messages());
		}

		return $validator->valid();
	}
}