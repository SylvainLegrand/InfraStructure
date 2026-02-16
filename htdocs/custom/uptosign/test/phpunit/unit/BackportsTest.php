<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for backport functions
 */
class BackportsTest extends TestCase
{
	// --- utsbackports_getDolGlobalString ---

	public function testGetDolGlobalStringExistingKey(): void
	{
		global $conf;
		$conf->global->TEST_KEY_EXISTING = 'test_value';

		$result = utsbackports_getDolGlobalString('TEST_KEY_EXISTING', 'default');
		$this->assertSame('test_value', $result);

		unset($conf->global->TEST_KEY_EXISTING);
	}

	public function testGetDolGlobalStringMissingKeyReturnsDefault(): void
	{
		$result = utsbackports_getDolGlobalString('NONEXISTENT_KEY_12345', 'my_default');
		$this->assertSame('my_default', $result);
	}

	public function testGetDolGlobalStringEmptyDefault(): void
	{
		$result = utsbackports_getDolGlobalString('NONEXISTENT_KEY_67890');
		$this->assertSame('', $result);
	}

	public function testGetDolGlobalStringReturnsString(): void
	{
		global $conf;
		$conf->global->TEST_KEY_NUMERIC = 42;

		$result = utsbackports_getDolGlobalString('TEST_KEY_NUMERIC', '');
		// Should return string type
		$this->assertIsString($result);

		unset($conf->global->TEST_KEY_NUMERIC);
	}

	public function testGetDolGlobalStringEmptyValueReturnsEmpty(): void
	{
		global $conf;
		$conf->global->TEST_KEY_EMPTY = '';

		$result = utsbackports_getDolGlobalString('TEST_KEY_EMPTY', 'fallback');
		// Dolibarr native getDolGlobalString returns '' for empty string, not default
		$this->assertSame('', $result);

		unset($conf->global->TEST_KEY_EMPTY);
	}

	public function testGetDolGlobalStringZeroValue(): void
	{
		global $conf;
		$conf->global->TEST_KEY_ZERO = 0;

		$result = utsbackports_getDolGlobalString('TEST_KEY_ZERO', 'fallback');
		// Dolibarr native returns '0' for numeric 0
		$this->assertSame('0', $result);

		unset($conf->global->TEST_KEY_ZERO);
	}

	// --- utsbackports_getFieldList ---

	public function testGetFieldListConcatenatesKeys(): void
	{
		$obj = new \stdClass();
		$obj->fields = [
			'rowid' => ['type' => 'integer'],
			'ref' => ['type' => 'varchar(128)'],
			'label' => ['type' => 'varchar(255)'],
		];

		$result = utsbackports_getFieldList($obj);
		$this->assertSame('rowid,ref,label', $result);
	}

	public function testGetFieldListEmptyFields(): void
	{
		$obj = new \stdClass();
		$obj->fields = [];

		$result = utsbackports_getFieldList($obj);
		$this->assertSame('', $result);
	}

	public function testGetFieldListSingleField(): void
	{
		$obj = new \stdClass();
		$obj->fields = ['rowid' => ['type' => 'integer']];

		$result = utsbackports_getFieldList($obj);
		$this->assertSame('rowid', $result);
	}
}
