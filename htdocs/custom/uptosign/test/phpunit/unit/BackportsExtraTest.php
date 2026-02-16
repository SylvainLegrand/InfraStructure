<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for additional backport functions:
 * utsbackports_dol_hash, utsbackports_dolGetLdapPasswordHash,
 * utsbackports_dol_verifyHash, utsbackports_buttonsSaveCancel
 */
class BackportsExtraTest extends TestCase
{
	/** @var object Saved global $conf */
	private $savedConf;

	/** @var object Saved global $langs */
	private $savedLangs;

	protected function setUp(): void
	{
		global $conf, $langs;
		$this->savedConf = clone $conf;
		$this->savedLangs = $langs;

		// Provide a mock $langs with trans() and dol_escape_htmltag
		$langs = new class {
			public function trans($key, ...$args)
			{
				return $key;
			}
		};
	}

	protected function tearDown(): void
	{
		global $conf, $langs;
		$conf = $this->savedConf;
		$langs = $this->savedLangs;
	}

	// --- utsbackports_dol_hash ---

	public function testDolHashMd5(): void
	{
		$result = utsbackports_dol_hash('hello', '3');
		$this->assertSame(md5('hello'), $result);
	}

	public function testDolHashMd5ByName(): void
	{
		$result = utsbackports_dol_hash('hello', 'md5');
		$this->assertSame(md5('hello'), $result);
	}

	public function testDolHashSha1(): void
	{
		$result = utsbackports_dol_hash('hello', '1');
		$this->assertSame(sha1('hello'), $result);
	}

	public function testDolHashSha1ByName(): void
	{
		$result = utsbackports_dol_hash('hello', 'sha1');
		$this->assertSame(sha1('hello'), $result);
	}

	public function testDolHashSha1md5(): void
	{
		$result = utsbackports_dol_hash('hello', '2');
		$this->assertSame(sha1(md5('hello')), $result);
	}

	public function testDolHashSha256(): void
	{
		$result = utsbackports_dol_hash('hello', '5');
		$this->assertSame(hash('sha256', 'hello'), $result);
	}

	public function testDolHashSha256ByName(): void
	{
		$result = utsbackports_dol_hash('hello', 'sha256');
		$this->assertSame(hash('sha256', 'hello'), $result);
	}

	public function testDolHashPasswordHash(): void
	{
		$result = utsbackports_dol_hash('hello', '6');
		$this->assertTrue(password_verify('hello', $result));
	}

	public function testDolHashPasswordHashByName(): void
	{
		$result = utsbackports_dol_hash('hello', 'password_hash');
		$this->assertTrue(password_verify('hello', $result));
	}

	public function testDolHashDefaultIsMd5(): void
	{
		global $conf;
		// No MAIN_SECURITY_HASH_ALGO defined, should use md5
		unset($conf->global->MAIN_SECURITY_HASH_ALGO);
		$result = utsbackports_dol_hash('test', '0');
		$this->assertSame(md5('test'), $result);
	}

	public function testDolHashWithSalt(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_SALT = 'mysalt';
		$result = utsbackports_dol_hash('hello', '3'); // md5
		$this->assertSame(md5('mysalt' . 'hello'), $result);
		unset($conf->global->MAIN_SECURITY_SALT);
	}

	public function testDolHashAutoPasswordHash(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'password_hash';
		$result = utsbackports_dol_hash('hello', '0');
		$this->assertTrue(password_verify('hello', $result));
	}

	public function testDolHashOpenLdap(): void
	{
		$result = utsbackports_dol_hash('hello', '4');
		// Default LDAP hash type is md5
		$this->assertStringStartsWith('{MD5}', $result);
	}

	public function testDolHashOpenLdapByName(): void
	{
		$result = utsbackports_dol_hash('hello', 'openldap');
		$this->assertStringStartsWith('{MD5}', $result);
	}

	public function testDolHashConfigSha1(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'sha1';
		$result = utsbackports_dol_hash('hello', '0');
		$this->assertSame(sha1('hello'), $result);
	}

	public function testDolHashConfigSha1md5(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'sha1md5';
		$result = utsbackports_dol_hash('hello', '0');
		$this->assertSame(sha1(md5('hello')), $result);
	}

	// --- utsbackports_dolGetLdapPasswordHash ---

	public function testLdapHashMd5(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'md5');
		$this->assertStringStartsWith('{MD5}', $result);
		$decoded = base64_decode(substr($result, 5));
		$this->assertSame(hash('md5', 'password', true), $decoded);
	}

	public function testLdapHashSha(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'sha');
		$this->assertStringStartsWith('{SHA}', $result);
	}

	public function testLdapHashSha256(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'sha256');
		$this->assertStringStartsWith('{SHA256}', $result);
	}

	public function testLdapHashSha512(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'sha512');
		$this->assertStringStartsWith('{SHA512}', $result);
	}

	public function testLdapHashMd5frommd5(): void
	{
		$md5hex = md5('password');
		$result = utsbackports_dolGetLdapPasswordHash($md5hex, 'md5frommd5');
		$this->assertStringStartsWith('{MD5}', $result);
	}

	public function testLdapHashSmd5(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'smd5');
		$this->assertStringStartsWith('{SMD5}', $result);
	}

	public function testLdapHashSsha(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'ssha');
		$this->assertStringStartsWith('{SSHA}', $result);
	}

	public function testLdapHashSsha256(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'ssha256');
		$this->assertStringStartsWith('{SSHA256}', $result);
	}

	public function testLdapHashSha384(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'sha384');
		$this->assertStringStartsWith('{SHA384}', $result);
	}

	public function testLdapHashSsha384(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'ssha384');
		$this->assertStringStartsWith('{SSHA384}', $result);
	}

	public function testLdapHashSsha512(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'ssha512');
		$this->assertStringStartsWith('{SSHA512}', $result);
	}

	public function testLdapHashClear(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'clear');
		$this->assertSame('{CLEAR}password', $result);
	}

	public function testLdapHashCrypt(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'crypt');
		$this->assertStringStartsWith('{CRYPT}', $result);
	}

	public function testLdapHashEmptyTypeDefaultsMd5(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', '');
		$this->assertStringStartsWith('{MD5}', $result);
	}

	public function testLdapHashUnknownTypeReturnsEmpty(): void
	{
		$result = utsbackports_dolGetLdapPasswordHash('password', 'unknown_type');
		$this->assertSame('', $result);
	}

	// --- utsbackports_dol_verifyHash ---

	public function testVerifyHashMd5(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = '';
		$hash = md5('test');
		$this->assertTrue(utsbackports_dol_verifyHash('test', $hash, '3'));
	}

	public function testVerifyHashSha1(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = '';
		$hash = sha1('test');
		$this->assertTrue(utsbackports_dol_verifyHash('test', $hash, '1'));
	}

	public function testVerifyHashSha256(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = '';
		$hash = hash('sha256', 'test');
		$this->assertTrue(utsbackports_dol_verifyHash('test', $hash, '5'));
	}

	public function testVerifyHashPasswordHash(): void
	{
		$hash = password_hash('test', PASSWORD_DEFAULT);
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'password_hash';
		$this->assertTrue(utsbackports_dol_verifyHash('test', $hash, '0'));
	}

	public function testVerifyHashFails(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = '';
		$this->assertFalse(utsbackports_dol_verifyHash('test', 'wronghash', '3'));
	}

	public function testVerifyHashPasswordHashUnknownLength(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'password_hash';
		// Hash is 50 chars (not 32, 40, or starting with $)
		$hash = str_repeat('a', 50);
		$this->assertFalse(utsbackports_dol_verifyHash('test', $hash, '0'));
	}

	public function testVerifyHashAutoDetectsMd5Length(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'password_hash';
		$hash = md5('test'); // 32 chars
		$this->assertTrue(utsbackports_dol_verifyHash('test', $hash, '0'));
	}

	public function testVerifyHashAutoDetectsSha1Length(): void
	{
		global $conf;
		$conf->global->MAIN_SECURITY_HASH_ALGO = 'password_hash';
		$hash = sha1(md5('test')); // 40 chars -> sha1md5
		$this->assertTrue(utsbackports_dol_verifyHash('test', $hash, '0'));
	}

	// --- utsbackports_buttonsSaveCancel ---

	public function testButtonsSaveCancelDefault(): void
	{
		$result = utsbackports_buttonsSaveCancel();
		$this->assertStringContainsString('<div class="center">', $result);
		$this->assertStringContainsString('name="save"', $result);
		$this->assertStringContainsString('name="cancel"', $result);
		$this->assertStringContainsString('</div>', $result);
	}

	public function testButtonsSaveCancelCreateLabel(): void
	{
		$result = utsbackports_buttonsSaveCancel('Create');
		$this->assertStringContainsString('name="add"', $result);
	}

	public function testButtonsSaveCancelAddLabel(): void
	{
		$result = utsbackports_buttonsSaveCancel('Add');
		$this->assertStringContainsString('name="add"', $result);
	}

	public function testButtonsSaveCancelModifyLabel(): void
	{
		$result = utsbackports_buttonsSaveCancel('Modify');
		$this->assertStringContainsString('name="edit"', $result);
	}

	public function testButtonsSaveCancelWithoutDiv(): void
	{
		$result = utsbackports_buttonsSaveCancel('Save', 'Cancel', [], 1);
		$this->assertStringNotContainsString('<div', $result);
		$this->assertStringNotContainsString('</div>', $result);
	}

	public function testButtonsSaveCancelWithMoreCss(): void
	{
		$result = utsbackports_buttonsSaveCancel('Save', 'Cancel', [], 0, 'my-custom-class');
		$this->assertStringContainsString('my-custom-class', $result);
	}

	public function testButtonsSaveCancelNoSave(): void
	{
		$result = utsbackports_buttonsSaveCancel('', 'Cancel');
		$this->assertStringNotContainsString('name="save"', $result);
		$this->assertStringContainsString('name="cancel"', $result);
	}

	public function testButtonsSaveCancelNoCancel(): void
	{
		$result = utsbackports_buttonsSaveCancel('Save', '');
		$this->assertStringContainsString('name="save"', $result);
		$this->assertStringNotContainsString('name="cancel"', $result);
	}
}
