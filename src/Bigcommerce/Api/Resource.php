<?php
namespace Bigcommerce\Api;

use \stdClass;

class Resource
{
	/**
	 *
	 * @var stdClass|null
	 */
	protected stdClass|null $fields;

	/**
	 * @var int
	 */
	protected int $id;

	/**
	 * @var array
	 */
	protected array $ignoreOnCreate = [];

	/**
	 * @var array
	 */
	protected array $ignoreOnUpdate = [];

	/**
	 * @var array
	 */
	protected array $ignoreIfZero = [];

	public function __construct(mixed $object = false)
	{
		if (is_array($object)) {
			$object = (isset($object[0])) ? $object[0] : false;
		}

		$this->fields = ($object) ?: new stdClass;
		$this->id = ($object && isset($object->id)) ? $object->id : 0;
	}

	public function __get(string $field) : mixed
	{
		if (method_exists($this, $field) && isset($this->fields->$field)) {
			return $this->$field();
		}

		return (isset($this->fields->$field)) ? $this->fields->$field : null;
	}

	public function __set(string $field, mixed $value)
	{
		$this->fields->$field = $value;
	}

	public function __isset(string $field)
	{
		return (isset($this->fields->$field));
	}

	public function getAllFields() : stdClass|null
	{
		return $this->fields;
	}

	public function getCreateFields() : stdClass|null
	{
		$resource = $this->fields;

		foreach($this->ignoreOnCreate as $field) {
			if (isset($resource->$field)) unset($resource->$field);
		}

		return $resource;
	}

	public function getUpdateFields() : stdClass|null
	{
		$resource = $this->fields;

		foreach($this->ignoreOnUpdate as $field) {
			if (isset($resource->$field)) unset($resource->$field);
		}

		foreach($resource as $field => $value) {
			if ($this->isIgnoredField($field, $value)) unset($resource->$field);
		}

		return $resource;
	}

	private function isIgnoredField(string $field, mixed $value) : bool
	{
		if ($value === null) return true;

		if ((str_contains($field, "date")) && $value === "") return true;

		if (in_array($field, $this->ignoreIfZero) && $value === 0) return true;

		return false;
	}

}