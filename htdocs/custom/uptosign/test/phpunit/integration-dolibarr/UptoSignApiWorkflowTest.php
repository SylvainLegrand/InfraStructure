<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSign API workflows: signFetch, deleteRemote, signInfo
 */
class UptoSignApiWorkflowTest extends DolibarrRealTestCase
{
	/** @var string Relative temp dir under DOL_DATA_ROOT */
	private $tempRelDir;

	/** @var array Previous global config values */
	private $prevConfig = [];

	protected function setUp(): void
	{
		parent::setUp();

		global $conf;

		$this->tempRelDir = 'uptosign_test_api_' . uniqid();
		mkdir(DOL_DATA_ROOT . '/' . $this->tempRelDir, 0755, true);

		// Save and set config values
		$configKeys = [
			'UPTOSIGN_FILENAME_SUFFIX_UPTOSEAL',
			'UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN',
			'UPTOSIGN_FILENAME_SUFFIX_PROOF',
		];
		foreach ($configKeys as $key) {
			$this->prevConfig[$key] = $conf->global->$key ?? null;
		}
		$conf->global->UPTOSIGN_FILENAME_SUFFIX_UPTOSEAL = 'sealed';
		$conf->global->UPTOSIGN_FILENAME_SUFFIX_UPTOSIGN = 'signed';
		$conf->global->UPTOSIGN_FILENAME_SUFFIX_PROOF = 'proof';
	}

	protected function tearDown(): void
	{
		global $conf;

		// Restore config
		foreach ($this->prevConfig as $key => $value) {
			if ($value === null) {
				unset($conf->global->$key);
			} else {
				$conf->global->$key = $value;
			}
		}

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
	 * Insert an UptoSign record with full control over fields.
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'API-' . uniqid();
		$uptosign->label = $data['label'] ?? 'API test';
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
			. ", hash_file = " . (isset($data['hash_file']) ? "'" . $this->db->escape($data['hash_file']) . "'" : "NULL")
			. ", hash_file_signed = '" . $this->db->escape($data['hash_file_signed'] ?? '') . "'"
			. ", api_name = '" . $this->db->escape($data['api_name'] ?? '') . "'"
			. ", sign_status = '" . $this->db->escape($data['sign_status'] ?? '') . "'"
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

	/**
	 * Build a stdClass proxy for use as the $object parameter in signFetch/signInfo.
	 * Avoids PHP 8.2 dynamic property deprecation on real Dolibarr objects.
	 */
	private function createObjectProxy(int $objectId, int $socId, string $element = 'propal', string $ref = ''): \stdClass
	{
		$obj = new \stdClass();
		$obj->element = $element;
		$obj->elementtype = $element;
		$obj->id = $objectId;
		$obj->ref = $ref ?: strtoupper($element) . '-' . uniqid();
		$obj->socid = $socId;
		$obj->status = 1;
		$obj->uptosignMessage = '';
		$obj->uptosignTitle = '';
		$obj->signOrSeal = '';
		return $obj;
	}

	/**
	 * Build mock API client supporting all methods.
	 *
	 * @param array $responses Keyed by sign_id, each containing 'status', 'download', 'proof', 'delete'
	 */
	private function buildMockApiClient(array $responses): object
	{
		return new class ($responses) {
			/** @var array */
			private $responses;

			public function __construct(array $responses)
			{
				$this->responses = $responses;
			}

			public function getDocumentStatus($signId)
			{
				return $this->get($signId, 'status');
			}

			public function downloadDocument($signId)
			{
				return $this->get($signId, 'download');
			}

			public function downloadProof($signId)
			{
				return $this->get($signId, 'proof');
			}

			public function deleteDocument($signId)
			{
				return $this->get($signId, 'delete');
			}

			private function get($signId, $type)
			{
				return $this->responses[$signId][$type]
					?? ['http_code' => 0, 'content' => '', 'data' => null, 'curl_error' => 'no mock'];
			}
		};
	}

	// ================================================================
	// signFetch
	// ================================================================

	public function testSignFetchReturnsNegativeOneWhenNoChildren(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		$result = $uptosign->signFetch($this->testUser, $object, 'uptoseal');

		$this->assertEquals(-1, $result);
	}

	public function testSignFetchSkipsDraftStatus(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptoseal',
			'sign_id' => 'test-draft',
			'status' => \UptoSign::STATUS_DRAFT,
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		$result = $uptosign->signFetch($this->testUser, $object, 'uptoseal');

		// STATUS_DRAFT is in the skip list, result = 1 (skipped)
		$this->assertEquals(1, $result);
	}

	public function testSignFetchSkipsAlreadyDownloaded(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$signedPath = $this->tempRelDir . '/already-signed.pdf';
		$this->createFakeFile($signedPath);

		$this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptoseal',
			'sign_id' => 'test-already',
			'status' => \UptoSign::STATUS_SIGNED,
			'path_file_signed' => $signedPath,
			'hash_file_signed' => 'existing-hash',
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		$result = $uptosign->signFetch($this->testUser, $object, 'uptoseal');

		// Already downloaded, skipped with result = 1
		$this->assertEquals(1, $result);
	}

	public function testSignFetchDownloadsAndSavesFile(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$srcPath = $this->tempRelDir . '/PR-download.pdf';
		$this->createFakeFile($srcPath);

		$signId = 'test-download-' . uniqid();
		$record = $this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptoseal',
			'sign_id' => $signId,
			'status' => \UptoSign::STATUS_WAITING,
			'path_file' => $srcPath,
			'hash_file_signed' => '',
			'path_file_signed' => '',
		]);

		$pdfContent = str_repeat('%PDF-sealed-content-from-api-test-', 100);
		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([
			$signId => [
				'download' => [
					'http_code' => 200,
					'content' => $pdfContent,
					'data' => null,
					'curl_error' => '',
				],
			],
		]);

		$result = $uptosign->signFetch($this->testUser, $object, 'uptoseal');

		// Verify result is not an error
		$this->assertNotEquals(-1, $result);

		// Verify DB was updated
		$sql = "SELECT status, path_file_signed, hash_file_signed"
			. " FROM " . MAIN_DB_PREFIX . "uptosign"
			. " WHERE rowid = " . ((int) $record->id);
		$resql = $this->db->query($sql);
		$obj = $this->db->fetch_object($resql);

		$this->assertEquals(\UptoSign::STATUS_FILE_FETCHED, (int) $obj->status);
		$this->assertNotEmpty($obj->path_file_signed);
		$this->assertNotEmpty($obj->hash_file_signed);

		// Verify file on disk
		$fullPath = DOL_DATA_ROOT . '/' . $obj->path_file_signed;
		$this->assertFileExists($fullPath);
		$this->assertEquals($pdfContent, file_get_contents($fullPath));
	}

	public function testSignFetchSetsExpiredForSmallContent(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$srcPath = $this->tempRelDir . '/PR-small.pdf';
		$this->createFakeFile($srcPath);

		$signId = 'test-small-' . uniqid();
		$record = $this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptoseal',
			'sign_id' => $signId,
			'status' => \UptoSign::STATUS_WAITING,
			'path_file' => $srcPath,
			'hash_file_signed' => '',
			'path_file_signed' => '',
		]);

		// Content < 1024 bytes triggers expired
		$tinyContent = 'small response';
		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([
			$signId => [
				'download' => [
					'http_code' => 200,
					'content' => $tinyContent,
					'data' => null,
					'curl_error' => '',
				],
			],
		]);

		$uptosign->signFetch($this->testUser, $object, 'uptoseal');

		// Child should be marked as EXPIRED
		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $record->id,
			'status' => (string) \UptoSign::STATUS_EXPIRED,
		]);
	}

	public function testSignFetchHandles404(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$srcPath = $this->tempRelDir . '/PR-404.pdf';
		$this->createFakeFile($srcPath);

		$signId = 'test-404-' . uniqid();
		$this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptoseal',
			'sign_id' => $signId,
			'status' => \UptoSign::STATUS_WAITING,
			'path_file' => $srcPath,
			'hash_file_signed' => '',
			'path_file_signed' => '',
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([
			$signId => [
				'download' => [
					'http_code' => 404,
					'content' => '',
					'data' => null,
					'curl_error' => '',
				],
			],
		]);

		$result = $uptosign->signFetch($this->testUser, $object, 'uptoseal');

		$this->assertEquals(-1, $result);
		$this->assertNotEmpty($uptosign->errors);
	}

	// ================================================================
	// deleteRemote
	// ================================================================

	public function testDeleteRemoteWithApiSuccess(): void
	{
		$record = $this->insertUptoSign([
			'sign_id' => 'delete-success-' . uniqid(),
			'status' => \UptoSign::STATUS_SIGNED,
			'hash_file' => 'somehash',
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->fetch($record->id);
		$uptosign->apiClient = $this->buildMockApiClient([
			$uptosign->sign_id => [
				'delete' => [
					'http_code' => 200,
					'content' => '',
					'data' => ['message' => 'deleted'],
					'curl_error' => '',
				],
			],
		]);

		$result = $uptosign->deleteRemote($this->testUser, true);

		$this->assertGreaterThanOrEqual(0, $result);
		$this->assertDatabaseMissing('uptosign', [
			'rowid' => (string) $record->id,
		]);
	}

	public function testDeleteRemoteWithApi404StillDeletes(): void
	{
		$record = $this->insertUptoSign([
			'sign_id' => 'delete-404-' . uniqid(),
			'status' => \UptoSign::STATUS_SIGNED,
			'hash_file' => 'somehash',
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->fetch($record->id);
		$uptosign->apiClient = $this->buildMockApiClient([
			$uptosign->sign_id => [
				'delete' => [
					'http_code' => 404,
					'content' => '',
					'data' => null,
					'curl_error' => '',
				],
			],
		]);

		$result = $uptosign->deleteRemote($this->testUser, true);

		$this->assertGreaterThanOrEqual(0, $result);
		$this->assertDatabaseMissing('uptosign', [
			'rowid' => (string) $record->id,
		]);
	}

	public function testDeleteRemoteWithApiError(): void
	{
		$record = $this->insertUptoSign([
			'sign_id' => 'delete-error-' . uniqid(),
			'status' => \UptoSign::STATUS_SIGNED,
			'hash_file' => 'somehash',
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->fetch($record->id);
		$uptosign->apiClient = $this->buildMockApiClient([
			$uptosign->sign_id => [
				'delete' => [
					'http_code' => 500,
					'content' => '',
					'data' => ['message' => 'Internal server error'],
					'curl_error' => '',
				],
			],
		]);

		$result = $uptosign->deleteRemote($this->testUser, true);

		$this->assertLessThan(0, $result);
		$this->assertNotEmpty($uptosign->errors);
		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $record->id,
		]);
	}

	public function testDeleteRemoteWithEmptySignIdNullHash(): void
	{
		$record = $this->insertUptoSign([
			'sign_id' => '',
			'hash_file' => null,
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->fetch($record->id);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		$result = $uptosign->deleteRemote($this->testUser, true);

		// Empty sign_id + null hash_file = proof file, deleted directly
		$this->assertGreaterThanOrEqual(0, $result);
		$this->assertDatabaseMissing('uptosign', [
			'rowid' => (string) $record->id,
		]);
	}

	public function testDeleteRemoteWithEmptySignIdNonNullHash(): void
	{
		$record = $this->insertUptoSign([
			'sign_id' => '',
			'hash_file' => 'non-null-hash',
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		$uptosign = new \UptoSign($this->db);
		$uptosign->fetch($record->id);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		$result = $uptosign->deleteRemote($this->testUser, true);

		// Empty sign_id + non-null hash_file: neither branch matches, no action
		$this->assertEquals(0, $result);
		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $record->id,
		]);
	}

	// ================================================================
	// signInfo (simple cases)
	// ================================================================

	public function testSignInfoReturnsNullWithNoChildren(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		// fetchChilds returns empty array, not -1, so the method processes
		// an empty foreach and returns $status which stays null
		$result = $uptosign->signInfo($this->testUser, $object);

		$this->assertNull($result);
	}

	public function testSignInfoLocalModeSkipsApiCall(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);
		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptosign',
			'sign_id' => 'test-local-' . uniqid(),
			'status' => \UptoSign::STATUS_SIGNED,
		]);

		// Set tms to recent time so 30-day check doesn't expire the child
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign"
			. " SET tms = '" . date('Y-m-d H:i:s') . "'"
			. " WHERE fk_object = " . ((int) $propal->id);
		$this->db->query($sql);

		$uptosign = new \UptoSign($this->db);
		$uptosign->apiClient = $this->buildMockApiClient([]);

		$result = $uptosign->signInfo($this->testUser, $object, 'local');

		// Local mode: returns status of child without API call
		$this->assertEquals(\UptoSign::STATUS_SIGNED, $result);
	}
}
