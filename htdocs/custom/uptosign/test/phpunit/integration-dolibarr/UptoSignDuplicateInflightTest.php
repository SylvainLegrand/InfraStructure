<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for uptosign_find_duplicate_inflight() - the idempotency guard that
 * prevents F5, back-arrow and double-click from spawning duplicate seal/sign procedures.
 */
class UptoSignDuplicateInflightTest extends DolibarrRealTestCase
{
	/** @var string Relative temp dir under DOL_DATA_ROOT */
	private $tempRelDir;

	protected function setUp(): void
	{
		parent::setUp();

		$this->tempRelDir = 'uptosign_test_dup_' . uniqid();
		mkdir(DOL_DATA_ROOT . '/' . $this->tempRelDir, 0755, true);
	}

	protected function tearDown(): void
	{
		$fullDir = DOL_DATA_ROOT . '/' . $this->tempRelDir;
		if (is_dir($fullDir)) {
			foreach (glob($fullDir . '/*') as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
			rmdir($fullDir);
		}
		parent::tearDown();
	}

	/**
	 * Insert an UptoSign row with all fields needed by the idempotency check.
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'DUP-' . uniqid();
		$uptosign->label = 'Duplicate inflight test';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = $data['status'] ?? \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = $data['object_type'] ?? 'propal';
		$uptosign->fk_object = $data['fk_object'] ?? 1;
		$uptosign->create($this->testUser);

		$this->assertGreaterThan(0, $uptosign->id, 'UptoSign record should be created');

		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign SET"
			. " path_file = '" . $this->db->escape($data['path_file'] ?? '') . "'"
			. ", hash_file = " . (isset($data['hash_file']) ? "'" . $this->db->escape($data['hash_file']) . "'" : "NULL")
			. ", api_name = '" . $this->db->escape($data['api_name'] ?? '') . "'"
			. ", status = " . ((int) ($data['status'] ?? \UptoSign::STATUS_WAITING))
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		return $uptosign;
	}

	/**
	 * Create a fake PDF under DOL_DATA_ROOT and return its absolute path.
	 */
	private function createPdfFile(string $name, string $content): string
	{
		$fullPath = DOL_DATA_ROOT . '/' . $this->tempRelDir . '/' . $name;
		file_put_contents($fullPath, $content);
		return $fullPath;
	}

	// ================================================================
	// Core idempotency cases
	// ================================================================

	public function testReturnsNullWhenNoExistingProcedure(): void
	{
		$pdf = $this->createPdfFile('PR-001.pdf', '%PDF-no-existing-proc');

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 1, 'propal', 'uptoseal', $pdf);

		$this->assertNull($result, 'No existing procedure should yield no duplicate');
	}

	public function testReturnsExistingProcedureWhenAllFieldsMatch(): void
	{
		$pdf = $this->createPdfFile('PR-002.pdf', '%PDF-content-A');
		$hash = hash_file('sha256', $pdf);
		$relPath = \uptosign_relative_path($pdf);

		$existing = $this->insertUptoSign([
			'fk_object'   => 42,
			'object_type' => 'propal',
			'api_name'    => 'uptoseal',
			'path_file'   => $relPath,
			'hash_file'   => $hash,
			'status'      => \UptoSign::STATUS_WAITING,
		]);

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 42, 'propal', 'uptoseal', $pdf);

		$this->assertInstanceOf(\UptoSign::class, $result);
		$this->assertEquals($existing->id, $result->id, 'Should return the in-flight procedure');
	}

	public function testReturnsNullWhenSourceFileWasRegenerated(): void
	{
		$pdf = $this->createPdfFile('PR-003.pdf', '%PDF-version-1');
		$oldHash = hash_file('sha256', $pdf);
		$relPath = \uptosign_relative_path($pdf);

		$this->insertUptoSign([
			'fk_object'   => 43,
			'object_type' => 'propal',
			'api_name'    => 'uptoseal',
			'path_file'   => $relPath,
			'hash_file'   => $oldHash,
			'status'      => \UptoSign::STATUS_WAITING,
		]);

		// User edits the order -> Dolibarr regenerates the PDF with new content but same path
		file_put_contents($pdf, '%PDF-version-2-after-edit');
		$this->assertNotEquals($oldHash, hash_file('sha256', $pdf), 'Sanity check: hash must change');

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 43, 'propal', 'uptoseal', $pdf);

		$this->assertNull($result, 'A regenerated source file means a legitimate new procedure');
	}

	public function testReturnsNullWhenProcedureIsAlreadySigned(): void
	{
		$pdf = $this->createPdfFile('PR-004.pdf', '%PDF-already-signed');
		$hash = hash_file('sha256', $pdf);
		$relPath = \uptosign_relative_path($pdf);

		$this->insertUptoSign([
			'fk_object'   => 44,
			'object_type' => 'propal',
			'api_name'    => 'uptoseal',
			'path_file'   => $relPath,
			'hash_file'   => $hash,
			'status'      => \UptoSign::STATUS_SIGNED,
		]);

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 44, 'propal', 'uptoseal', $pdf);

		$this->assertNull($result, 'Finished procedure should not block a new one');
	}

	public function testReturnsNullWhenApiNameDiffers(): void
	{
		$pdf = $this->createPdfFile('PR-005.pdf', '%PDF-api-name-test');
		$hash = hash_file('sha256', $pdf);
		$relPath = \uptosign_relative_path($pdf);

		// Existing sign procedure, user now requests a seal: independent procedures
		$this->insertUptoSign([
			'fk_object'   => 45,
			'object_type' => 'propal',
			'api_name'    => 'uptosign',
			'path_file'   => $relPath,
			'hash_file'   => $hash,
			'status'      => \UptoSign::STATUS_WAITING,
		]);

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 45, 'propal', 'uptoseal', $pdf);

		$this->assertNull($result, 'uptosign and uptoseal are independent procedures');
	}

	public function testReturnsNullWhenObjectTypeDiffers(): void
	{
		$pdf = $this->createPdfFile('PR-006.pdf', '%PDF-object-type-test');
		$hash = hash_file('sha256', $pdf);
		$relPath = \uptosign_relative_path($pdf);

		$this->insertUptoSign([
			'fk_object'   => 46,
			'object_type' => 'commande',
			'api_name'    => 'uptoseal',
			'path_file'   => $relPath,
			'hash_file'   => $hash,
			'status'      => \UptoSign::STATUS_WAITING,
		]);

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 46, 'propal', 'uptoseal', $pdf);

		$this->assertNull($result, 'Same id on different object types must not match');
	}

	public function testReturnsNullWhenSourceFileMissing(): void
	{
		$missingPath = DOL_DATA_ROOT . '/' . $this->tempRelDir . '/does-not-exist.pdf';

		$uts = new \UptoSign($this->db);
		$result = uptosign_find_duplicate_inflight($uts, 47, 'propal', 'uptoseal', $missingPath);

		$this->assertNull($result, 'Missing source file must not crash, returns null');
	}
}
