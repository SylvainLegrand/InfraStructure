<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSign: updateHookKey, checkFile, getOrigin, getOriginUrl, deleteLine
 */
class UptoSignMiscDbTest extends DolibarrRealTestCase
{
	/** @var string Relative temp dir under DOL_DATA_ROOT */
	private $tempRelDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->tempRelDir = 'uptosign_test_misc_' . uniqid();
		mkdir(DOL_DATA_ROOT . '/' . $this->tempRelDir, 0755, true);
	}

	protected function tearDown(): void
	{
		// Clean temp files
		$fullDir = DOL_DATA_ROOT . '/' . $this->tempRelDir;
		if (is_dir($fullDir)) {
			$files = glob($fullDir . '/*');
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
			rmdir($fullDir);
		}

		parent::tearDown();
	}

	/**
	 * Insert an UptoSign record with fields that create() may not persist.
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'MISC-' . uniqid();
		$uptosign->label = 'Misc test';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = $data['status'] ?? \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = $data['object_type'] ?? 'propal';
		$uptosign->fk_object = $data['fk_object'] ?? 1;
		$uptosign->create($this->testUser);

		$this->assertGreaterThan(0, $uptosign->id, 'UptoSign record should be created');

		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign SET"
			. " sign_id = '" . $this->db->escape($data['sign_id'] ?? '') . "'"
			. ", path_file = '" . $this->db->escape($data['path_file'] ?? '') . "'"
			. ", path_file_signed = '" . $this->db->escape($data['path_file_signed'] ?? '') . "'"
			. ", hash_file_signed = '" . $this->db->escape($data['hash_file_signed'] ?? '') . "'"
			. ", status = " . ((int) ($data['status'] ?? \UptoSign::STATUS_WAITING))
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		return $uptosign;
	}

	/**
	 * Create a fake file and return the full path.
	 */
	private function createFakeFile(string $relativePath, string $content = ''): string
	{
		$fullPath = DOL_DATA_ROOT . '/' . $relativePath;
		if (empty($content)) {
			$content = str_repeat('%PDF-fake-content-for-test-', 50);
		}
		file_put_contents($fullPath, $content);
		return $fullPath;
	}

	// ================================================================
	// updateHookKey
	// ================================================================

	public function testUpdateHookKeyStoresValue(): void
	{
		$uptosign = $this->insertUptoSign([]);

		$result = $uptosign->updateHookKey('test-hook-key-abc');

		$this->assertEquals($uptosign->id, $result);

		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $uptosign->id,
			'hook_key' => 'test-hook-key-abc',
		]);
	}

	public function testUpdateHookKeyOverwritesPrevious(): void
	{
		$uptosign = $this->insertUptoSign([]);

		$uptosign->updateHookKey('first-key');
		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $uptosign->id,
			'hook_key' => 'first-key',
		]);

		$result = $uptosign->updateHookKey('second-key');
		$this->assertEquals($uptosign->id, $result);

		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $uptosign->id,
			'hook_key' => 'second-key',
		]);
	}

	// ================================================================
	// checkFile
	// ================================================================

	public function testCheckFileReturnsTrueWhenValid(): void
	{
		$relativePath = $this->tempRelDir . '/signed-valid.pdf';
		$fullPath = $this->createFakeFile($relativePath);
		$hash = hash_file('sha256', $fullPath);

		$uptosign = $this->insertUptoSign([
			'path_file_signed' => $relativePath,
			'hash_file_signed' => $hash,
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		// Fetch to populate object properties from DB
		$uptosign->fetch($uptosign->id);

		$result = $uptosign->checkFile();

		$this->assertTrue($result);
	}

	public function testCheckFileReturnsFalseWhenHashMismatch(): void
	{
		$relativePath = $this->tempRelDir . '/signed-bad-hash.pdf';
		$this->createFakeFile($relativePath);

		$uptosign = $this->insertUptoSign([
			'path_file_signed' => $relativePath,
			'hash_file_signed' => 'wrong-hash-value',
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		$uptosign->fetch($uptosign->id);

		$result = $uptosign->checkFile();

		$this->assertFalse($result);
		$this->assertNotEmpty($uptosign->errors);
	}

	public function testCheckFileReturnsFalseWhenFileMissing(): void
	{
		$uptosign = $this->insertUptoSign([
			'path_file_signed' => $this->tempRelDir . '/nonexistent.pdf',
			'hash_file_signed' => 'some-hash',
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		$uptosign->fetch($uptosign->id);

		$result = $uptosign->checkFile();

		$this->assertFalse($result);
	}

	public function testCheckFileReturnsFalseWhenPathEmpty(): void
	{
		$uptosign = $this->insertUptoSign([
			'path_file_signed' => '',
			'hash_file_signed' => '',
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		$uptosign->fetch($uptosign->id);

		$result = $uptosign->checkFile();

		$this->assertFalse($result);
	}

	// ================================================================
	// getOrigin
	// ================================================================

	public function testGetOriginReturnsPropal(): void
	{
		$uptosign = new \UptoSign($this->db);
		$origin = $uptosign->getOrigin('propal');

		$this->assertInstanceOf(\Propal::class, $origin);
	}

	public function testGetOriginReturnsFacture(): void
	{
		$uptosign = new \UptoSign($this->db);
		$origin = $uptosign->getOrigin('facture');

		$this->assertInstanceOf(\Facture::class, $origin);
	}

	public function testGetOriginReturnsCommande(): void
	{
		$uptosign = new \UptoSign($this->db);
		$origin = $uptosign->getOrigin('commande');

		$this->assertInstanceOf(\Commande::class, $origin);
	}

	public function testGetOriginReturnsNullForInvalidType(): void
	{
		$uptosign = new \UptoSign($this->db);
		$origin = $uptosign->getOrigin('nonexistent_type');

		$this->assertNull($origin);
	}

	// ================================================================
	// getOriginUrl
	// ================================================================

	public function testGetOriginUrlForExistingPropal(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);

		$uptosign = new \UptoSign($this->db);
		$url = $uptosign->getOriginUrl($propal->id, 'propal');

		$this->assertNotEmpty($url);
		$this->assertStringContainsString('<a', $url);
	}

	public function testGetOriginUrlReturnsEmptyForNonExistent(): void
	{
		$uptosign = new \UptoSign($this->db);
		$url = $uptosign->getOriginUrl(999999, 'propal');

		$this->assertEquals('', $url);
	}

	public function testGetOriginUrlReturnsEmptyForInvalidType(): void
	{
		$uptosign = new \UptoSign($this->db);
		$url = $uptosign->getOriginUrl(1, 'nonexistent_type');

		$this->assertEquals('', $url);
	}

	// ================================================================
	// deleteLine (status guard)
	// ================================================================

	public function testDeleteLineBlockedByNegativeStatus(): void
	{
		$uptosign = $this->insertUptoSign([
			'status' => \UptoSign::STATUS_CANCELED,
		]);

		// Force status to -1 in DB
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign"
			. " SET status = -1"
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		$uptosign->fetch($uptosign->id);
		$this->assertEquals(-1, $uptosign->status);

		$result = $uptosign->deleteLine($this->testUser, 999);

		$this->assertEquals(-2, $result);
		$this->assertEquals('ErrorDeleteLineNotAllowedByObjectStatus', $uptosign->error);
	}
}
