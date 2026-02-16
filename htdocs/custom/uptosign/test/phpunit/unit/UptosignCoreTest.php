<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for uptosignCore class (attribute management, ArrayAccess)
 */
class UptosignCoreTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!class_exists('uptosignCore')) {
			dol_include_once('/uptosign/class/uptosignCore.class.php');
		}
	}

	// --- Constructor and fill ---

	public function testConstructorWithEmptyArray(): void
	{
		$core = new \uptosignCore([]);
		$this->assertNull($core->getAttribute('db'));
		$this->assertNull($core->getAttribute('src_file_name'));
		$this->assertNull($core->getAttribute('object'));
	}

	public function testConstructorFillsAttributes(): void
	{
		$core = new \uptosignCore([
			'plugin_name' => 'TestPlugin',
			'title' => 'Test Document',
			'seal_x' => 100,
			'seal_y' => 50,
			'seal_page' => 2,
		]);

		$this->assertSame('TestPlugin', $core->getAttribute('plugin_name'));
		$this->assertSame('Test Document', $core->getAttribute('title'));
		$this->assertSame(100, $core->getAttribute('seal_x'));
		$this->assertSame(50, $core->getAttribute('seal_y'));
		$this->assertSame(2, $core->getAttribute('seal_page'));
	}

	public function testConstructorIgnoresNonFillableKeys(): void
	{
		$core = new \uptosignCore([
			'plugin_name' => 'TestPlugin',
			'nonexistent_key' => 'should be ignored',
		]);

		$this->assertSame('TestPlugin', $core->getAttribute('plugin_name'));
		$this->assertNull($core->getAttribute('nonexistent_key'));
	}

	// --- setAttribute / getAttribute ---

	public function testSetAndGetAttribute(): void
	{
		$core = new \uptosignCore([]);
		$core->setAttribute('plugin_name', 'MyPlugin');
		$this->assertSame('MyPlugin', $core->getAttribute('plugin_name'));
	}

	public function testSetAttributeWithNumericValue(): void
	{
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('seal_x', 42);
		$this->assertTrue($result);
		$this->assertSame(42, $core->getAttribute('seal_x'));
	}

	public function testSetAttributeWithArrayValue(): void
	{
		$signers = [
			['id' => 1, 'firstname' => 'Eric', 'lastname' => 'Test'],
		];
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('list_of_signers', $signers);
		$this->assertTrue($result);
		$this->assertSame($signers, $core->getAttribute('list_of_signers'));
	}

	public function testSetAttributeWithObjectValue(): void
	{
		$obj = new \stdClass();
		$obj->element = 'propal';
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('object', $obj);
		$this->assertTrue($result);
		$this->assertSame($obj, $core->getAttribute('object'));
	}

	public function testSetAttributeEmptyStringReturnsFalse(): void
	{
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('plugin_name', '');
		$this->assertFalse($result);
	}

	public function testSetAttributeNullReturnsFalse(): void
	{
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('plugin_name', null);
		$this->assertFalse($result);
	}

	public function testSetAttributeSrcFileNameExistingFile(): void
	{
		$tmpFile = tempnam(sys_get_temp_dir(), 'uptosign_test_');
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('src_file_name', $tmpFile);
		$this->assertTrue($result);
		$this->assertSame($tmpFile, $core->getAttribute('src_file_name'));
		unlink($tmpFile);
	}

	public function testSetAttributeSrcFileNameNonExistentReturnsFalse(): void
	{
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('src_file_name', '/nonexistent/file.pdf');
		$this->assertFalse($result);
	}

	public function testGetAttributeEmptyKeyReturnsNull(): void
	{
		$core = new \uptosignCore([]);
		$this->assertNull($core->getAttribute(''));
	}

	public function testGetAttributeUnknownKeyReturnsNull(): void
	{
		$core = new \uptosignCore([]);
		$this->assertNull($core->getAttribute('unknown_key'));
	}

	// --- Magic methods __get / __set ---

	public function testMagicSet(): void
	{
		$core = new \uptosignCore([]);
		$core->plugin_name = 'MagicPlugin';
		$this->assertSame('MagicPlugin', $core->getAttribute('plugin_name'));
	}

	public function testMagicIsset(): void
	{
		$core = new \uptosignCore(['plugin_name' => 'TestPlugin']);
		$this->assertTrue(isset($core->plugin_name));
		$this->assertFalse(isset($core->nonexistent));
	}

	public function testMagicUnsetFillableKey(): void
	{
		$core = new \uptosignCore(['plugin_name' => 'TestPlugin']);
		unset($core->plugin_name);
		$this->assertFalse(isset($core->plugin_name));
	}

	// --- ArrayAccess ---

	public function testArrayAccessOffsetSet(): void
	{
		$core = new \uptosignCore([]);
		$core['plugin_name'] = 'ArrayPlugin';
		$this->assertSame('ArrayPlugin', $core['plugin_name']);
	}

	public function testArrayAccessOffsetExists(): void
	{
		$core = new \uptosignCore(['plugin_name' => 'TestPlugin']);
		$this->assertTrue(isset($core['plugin_name']));
		$this->assertFalse(isset($core['nonexistent']));
	}

	public function testArrayAccessOffsetUnset(): void
	{
		$core = new \uptosignCore(['plugin_name' => 'TestPlugin']);
		unset($core['plugin_name']);
		$this->assertFalse(isset($core['plugin_name']));
	}

	public function testArrayAccessOffsetGet(): void
	{
		$core = new \uptosignCore(['title' => 'My Document']);
		$this->assertSame('My Document', $core['title']);
	}

	// --- fill ---

	public function testFillWithMultipleAttributes(): void
	{
		$core = new \uptosignCore([]);
		$core->fill([
			'plugin_name' => 'Plugin1',
			'title' => 'Title1',
			'seal_x' => 10,
			'nonexistent' => 'ignored',
		]);

		$this->assertSame('Plugin1', $core->getAttribute('plugin_name'));
		$this->assertSame('Title1', $core->getAttribute('title'));
		$this->assertSame(10, $core->getAttribute('seal_x'));
		$this->assertNull($core->getAttribute('nonexistent'));
	}

	// --- getAttributes ---

	public function testGetAttributesReturnsArray(): void
	{
		$core = new \uptosignCore(['plugin_name' => 'TestPlugin']);
		$attrs = $core->getAttributes();
		$this->assertIsArray($attrs);
		$this->assertSame('TestPlugin', $attrs['plugin_name']);
	}

	// --- getResult ---

	public function testGetResultReturnsEmptyArrayInitially(): void
	{
		$core = new \uptosignCore([]);
		$this->assertSame([], $core->getResult());
	}

	// --- Default values ---

	public function testDefaultSealPosition(): void
	{
		$core = new \uptosignCore([]);
		$attrs = $core->getAttributes();
		$this->assertSame(40, $attrs['seal_x']);
		$this->assertSame(20, $attrs['seal_y']);
		$this->assertSame(1, $attrs['seal_page']);
	}

	// --- run() early returns ---

	public function testRunWithEmptyUserReturnsMinusOne(): void
	{
		$core = new \uptosignCore([]);
		$user = new \User(null);
		$result = $core->run([], $user);
		$this->assertSame(-1, $result);
	}

	public function testRunWithUserWithoutIdReturnsMinusOne(): void
	{
		$core = new \uptosignCore([]);
		$user = new \User(null);
		$user->id = 0;
		$result = $core->run([], $user);
		$this->assertSame(-1, $result);
	}

	// --- offsetExists edge cases ---

	public function testOffsetExistsReturnsFalseForNullValue(): void
	{
		$core = new \uptosignCore([]);
		// db is null by default
		$this->assertFalse(isset($core['db']));
	}

	public function testOffsetExistsReturnsTrueForNonEmptyValue(): void
	{
		$core = new \uptosignCore(['title' => 'Hello']);
		$this->assertTrue(isset($core['title']));
	}

	// --- setAttribute edge cases ---

	public function testSetAttributeBooleanFalseReturnsFalse(): void
	{
		$core = new \uptosignCore([]);
		$result = $core->setAttribute('redirect_sign', false);
		$this->assertFalse($result);
	}

	public function testSetAttributeBooleanTrueReturnsFalse(): void
	{
		$core = new \uptosignCore([]);
		// true is not string/numeric/object/array, so should return false
		// unless it's treated as numeric (1)
		$result = $core->setAttribute('redirect_sign', true);
		// is_numeric(true) returns false, is_string(true) returns false
		// but !empty(true) is true and is_numeric isn't checked first
		// Actually: !empty(true) && is_string(true) → false
		// !empty(true) && is_numeric(true) → false
		// !empty(true) && is_object(true) → false
		// !empty(true) && is_array(true) → false
		// So it returns false
		$this->assertFalse($result);
	}

	// --- fill edge case ---

	public function testFillEmptyArray(): void
	{
		$core = new \uptosignCore(['plugin_name' => 'Initial']);
		$core->fill([]);
		// Should not change existing attributes
		$this->assertSame('Initial', $core->getAttribute('plugin_name'));
	}

	// --- Fillable list ---

	public function testAllFillableKeysAccepted(): void
	{
		$core = new \uptosignCore([]);
		$fillable = [
			'plugin_name' => 'Plugin',
			'seal_x' => 10,
			'seal_y' => 20,
			'seal_page' => 1,
			'title' => 'Title',
			'procedure' => 'sign',
			'hook_uri' => 'https://example.com/hook',
			'hook_key' => 'secretkey',
			'mail_alerts' => 'admin@test.com',
		];
		$core->fill($fillable);

		foreach ($fillable as $key => $value) {
			$this->assertSame($value, $core->getAttribute($key), "Key '$key' should be set");
		}
	}
}
