<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSign::doScheduledArchive()
 */
class UptoSignScheduledArchiveTest extends DolibarrRealTestCase
{
	/** @var string Relative temp dir under DOL_DATA_ROOT */
	private $tempRelDir;

	/** @var string|null Previous value of UPTOSIGN_ARCHIVE_AUTO_CRON */
	private $prevArchiveCron;

	protected function setUp(): void
	{
		parent::setUp();

		global $conf;
		$this->prevArchiveCron = $conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON ?? null;

		$this->tempRelDir = 'uptosign_test_archive_' . uniqid();
		mkdir(DOL_DATA_ROOT . '/' . $this->tempRelDir, 0755, true);
	}

	protected function tearDown(): void
	{
		global $conf;

		// Restore config
		if ($this->prevArchiveCron === null) {
			unset($conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON);
		} else {
			$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = $this->prevArchiveCron;
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
	 * Insert an UptoSign record with all fields needed for archive tests.
	 * Uses create() then SQL UPDATE for fields that create() may not persist.
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'ARCH-' . uniqid();
		$uptosign->label = 'Archive test';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = $data['status'] ?? \UptoSign::STATUS_FILE_FETCHED;
		$uptosign->entity = 1;
		$uptosign->object_type = $data['object_type'] ?? 'propal';
		$uptosign->fk_object = $data['fk_object'] ?? 1;
		$uptosign->create($this->testUser);

		$this->assertGreaterThan(0, $uptosign->id, 'UptoSign record should be created');

		// Update fields that create() might not save
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign SET"
			. " sign_id = '" . $this->db->escape($data['sign_id'] ?? '') . "'"
			. ", path_file = '" . $this->db->escape($data['path_file'] ?? '') . "'"
			. ", path_file_signed = '" . $this->db->escape($data['path_file_signed'] ?? '') . "'"
			. ", hash_file_signed = '" . $this->db->escape($data['hash_file_signed'] ?? '') . "'"
			. ", status = " . ((int) ($data['status'] ?? \UptoSign::STATUS_FILE_FETCHED))
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		return $uptosign;
	}

	/**
	 * Create a fake PDF file (content > 1024 bytes)
	 */
	private function createFakeFile(string $relativePath): string
	{
		$fullPath = DOL_DATA_ROOT . '/' . $relativePath;
		file_put_contents($fullPath, str_repeat('%PDF-fake-content-for-test-', 50));
		return $fullPath;
	}

	/**
	 * Build an UptoSign instance with a mock API client for testing.
	 */
	private function buildUptoSignWithMockApi(array $downloadResponses = []): \UptoSign
	{
		$uptosign = new \UptoSign($this->db);

		$mockClient = new class ($downloadResponses) {
			/** @var array<string, array> */
			private $responses;
			public function __construct(array $responses)
			{
				$this->responses = $responses;
			}
			public function downloadDocument($signId)
			{
				return $this->responses[$signId] ?? [
					'http_code' => 0,
					'content' => '',
					'data' => null,
					'curl_error' => 'no mock configured',
				];
			}
		};

		$uptosign->apiClient = $mockClient;
		return $uptosign;
	}

	// ================================================================
	// Test cases
	// ================================================================

	public function testReturnZeroWhenCronDisabled(): void
	{
		global $conf;
		unset($conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
	}

	public function testZeroArchivedWhenNoProcedures(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('0 files archived', $uptosign->output);
		$this->assertStringContainsString('0 errors', $uptosign->output);
	}

	public function testArchiveFromLocalSignedFile(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$pathFile = $this->tempRelDir . '/FA-001.pdf';
		$pathFileSigned = $this->tempRelDir . '/FA-001_signed.pdf';

		$this->createFakeFile($pathFile);
		$this->createFakeFile($pathFileSigned);

		$this->insertUptoSign([
			'status' => \UptoSign::STATUS_FILE_FETCHED,
			'sign_id' => 'sign-001',
			'path_file' => $pathFile,
			'path_file_signed' => $pathFileSigned,
			'hash_file_signed' => 'abc123',
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('1 files archived', $uptosign->output);

		// Check archive file exists with expected naming pattern
		$archiveFiles = glob(DOL_DATA_ROOT . '/' . $this->tempRelDir . '/FA-001_archive_uptosign_*.pdf');
		$this->assertCount(1, $archiveFiles, 'Exactly one archive file should be created');
		$this->assertGreaterThan(1024, filesize($archiveFiles[0]));
	}

	public function testSkipWhenArchiveAlreadyExists(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$pathFile = $this->tempRelDir . '/FA-002.pdf';
		$pathFileSigned = $this->tempRelDir . '/FA-002_signed.pdf';

		$this->createFakeFile($pathFile);
		$this->createFakeFile($pathFileSigned);

		$this->insertUptoSign([
			'status' => \UptoSign::STATUS_FILE_FETCHED,
			'sign_id' => 'sign-002',
			'path_file' => $pathFile,
			'path_file_signed' => $pathFileSigned,
			'hash_file_signed' => 'abc123',
		]);

		// Pre-create the archive file (any date pattern will match via the tms)
		// We need the exact date. Run once to create the archive, then run again.
		$uptosign = new \UptoSign($this->db);
		$uptosign->doScheduledArchive();
		$this->assertStringContainsString('1 files archived', $uptosign->output);

		// Second run: archive already exists, should skip
		$uptosign2 = new \UptoSign($this->db);
		$result = $uptosign2->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('0 files archived', $uptosign2->output);
	}

	public function testSkipProcedureWithoutPathFile(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$this->insertUptoSign([
			'status' => \UptoSign::STATUS_SIGNED,
			'sign_id' => 'sign-003',
			'path_file' => '',
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('0 files archived', $uptosign->output);
	}

	public function testRedownloadFromApiWhenSignedFileMissing(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$pathFile = $this->tempRelDir . '/FA-003.pdf';
		$this->createFakeFile($pathFile);
		// path_file_signed is empty = never downloaded

		$signId = 'sign-api-' . uniqid();
		$record = $this->insertUptoSign([
			'status' => \UptoSign::STATUS_SIGNED,
			'sign_id' => $signId,
			'path_file' => $pathFile,
			'path_file_signed' => '',
		]);

		$pdfContent = str_repeat('%PDF-signed-content-from-api-', 50);
		$uptosign = $this->buildUptoSignWithMockApi([
			$signId => [
				'http_code' => 200,
				'content' => $pdfContent,
				'data' => null,
				'curl_error' => '',
			],
		]);

		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('1 files archived', $uptosign->output);

		// Archive file should exist
		$archiveFiles = glob(DOL_DATA_ROOT . '/' . $this->tempRelDir . '/FA-003_archive_uptosign_*.pdf');
		$this->assertCount(1, $archiveFiles);
		$this->assertEquals($pdfContent, file_get_contents($archiveFiles[0]));

		// DB should be updated with path_file_signed pointing to archive
		$this->assertDatabaseHas('uptosign', [
			'rowid' => (string) $record->id,
			'status' => (string) \UptoSign::STATUS_FILE_FETCHED,
		]);

		// Verify path_file_signed is set (non-empty)
		$sql = "SELECT path_file_signed, hash_file_signed FROM " . MAIN_DB_PREFIX . "uptosign WHERE rowid = " . ((int) $record->id);
		$resql = $this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
		$this->assertNotEmpty($obj->path_file_signed);
		$this->assertNotEmpty($obj->hash_file_signed);
	}

	public function testApi404DoesNotCreateArchive(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$pathFile = $this->tempRelDir . '/FA-004.pdf';
		$this->createFakeFile($pathFile);

		$signId = 'sign-expired-' . uniqid();
		$this->insertUptoSign([
			'status' => \UptoSign::STATUS_SIGNED,
			'sign_id' => $signId,
			'path_file' => $pathFile,
			'path_file_signed' => '',
		]);

		$uptosign = $this->buildUptoSignWithMockApi([
			$signId => [
				'http_code' => 404,
				'content' => '',
				'data' => null,
				'curl_error' => '',
			],
		]);

		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('0 files archived', $uptosign->output);
		$this->assertStringContainsString('0 errors', $uptosign->output);

		// No archive file created
		$archiveFiles = glob(DOL_DATA_ROOT . '/' . $this->tempRelDir . '/FA-004_archive_uptosign_*.pdf');
		$this->assertCount(0, $archiveFiles);
	}

	public function testSkipsProceduresWithIneligibleStatus(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		$pathFile = $this->tempRelDir . '/FA-005.pdf';
		$this->createFakeFile($pathFile);

		// STATUS_WAITING = 0 should not be picked up by the query
		$this->insertUptoSign([
			'status' => \UptoSign::STATUS_WAITING,
			'sign_id' => 'sign-waiting',
			'path_file' => $pathFile,
			'path_file_signed' => '',
		]);

		// STATUS_CANCELED = -1 should not be picked up
		$this->insertUptoSign([
			'status' => \UptoSign::STATUS_CANCELED,
			'sign_id' => 'sign-canceled',
			'path_file' => $pathFile,
			'path_file_signed' => '',
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('0 procedures checked', $uptosign->output);
	}

	public function testArchiveMultipleProcedures(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ARCHIVE_AUTO_CRON = '1';

		for ($i = 1; $i <= 3; $i++) {
			$pathFile = $this->tempRelDir . '/MULTI-' . $i . '.pdf';
			$pathFileSigned = $this->tempRelDir . '/MULTI-' . $i . '_signed.pdf';
			$this->createFakeFile($pathFile);
			$this->createFakeFile($pathFileSigned);

			$this->insertUptoSign([
				'status' => \UptoSign::STATUS_SEALED,
				'sign_id' => 'sign-multi-' . $i,
				'path_file' => $pathFile,
				'path_file_signed' => $pathFileSigned,
				'hash_file_signed' => 'hash-' . $i,
				'fk_object' => $i,
			]);
		}

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->doScheduledArchive();

		$this->assertEquals(0, $result);
		$this->assertStringContainsString('3 files archived', $uptosign->output);

		$archiveFiles = glob(DOL_DATA_ROOT . '/' . $this->tempRelDir . '/MULTI-*_archive_uptosign_*.pdf');
		$this->assertCount(3, $archiveFiles);
	}
}
