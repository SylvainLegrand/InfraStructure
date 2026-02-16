<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSign fetch* method variants
 */
class UptoSignFetchVariantsTest extends DolibarrRealTestCase
{
	/**
	 * Insert an UptoSign record with fields that create() may not persist.
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'FETCH-' . uniqid();
		$uptosign->label = $data['label'] ?? 'Fetch test';
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

	// ================================================================
	// fetchWhereFileName
	// ================================================================

	public function testFetchWhereFileNameFindsRecord(): void
	{
		$this->insertUptoSign([
			'path_file' => 'propal/PR-001.pdf',
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereFileName('propal/PR-001.pdf');

		$this->assertGreaterThan(0, $result);
		$this->assertEquals('propal/PR-001.pdf', $uptosign->path_file);
	}

	public function testFetchWhereFileNameReturnsZeroWhenNotFound(): void
	{
		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereFileName('nonexistent/file.pdf');

		$this->assertEquals(0, $result);
	}

	// ================================================================
	// fetchWhereFileNameSigned
	// ================================================================

	public function testFetchWhereFileNameSignedFindsRecord(): void
	{
		$this->insertUptoSign([
			'path_file_signed' => 'propal/PR-001_signed.pdf',
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereFileNameSigned('propal/PR-001_signed.pdf');

		$this->assertGreaterThan(0, $result);
		$this->assertEquals('propal/PR-001_signed.pdf', $uptosign->path_file_signed);
	}

	public function testFetchWhereFileNameSignedReturnsZeroWhenNotFound(): void
	{
		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereFileNameSigned('nonexistent/signed.pdf');

		$this->assertEquals(0, $result);
	}

	// ================================================================
	// fetchWhereUuidSign
	// ================================================================

	public function testFetchWhereUuidSignFindsRecord(): void
	{
		$uuid = 'uuid-test-' . uniqid();
		$this->insertUptoSign([
			'sign_id' => $uuid,
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereUuidSign($uuid);

		$this->assertGreaterThan(0, $result);
		$this->assertEquals($uuid, $uptosign->sign_id);
	}

	public function testFetchWhereUuidSignReturnsZeroWhenNotFound(): void
	{
		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereUuidSign('nonexistent-uuid');

		$this->assertEquals(0, $result);
	}

	// ================================================================
	// fetchWhereLike
	// ================================================================

	public function testFetchWhereLikeReturnsMatchingRecords(): void
	{
		for ($i = 1; $i <= 3; $i++) {
			$this->insertUptoSign([
				'path_file' => 'propal/PR-' . $i . '.pdf',
				'fk_object' => $i,
			]);
		}
		$this->insertUptoSign([
			'path_file' => 'facture/FA-001.pdf',
			'fk_object' => 99,
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereLike('path_file', 'propal/');

		$this->assertIsArray($result);
		$this->assertCount(3, $result);
	}

	public function testFetchWhereLikeReturnsEmptyWhenNoMatch(): void
	{
		$this->insertUptoSign([
			'path_file' => 'propal/PR-001.pdf',
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchWhereLike('path_file', 'commande/');

		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	// ================================================================
	// fetchChilds
	// ================================================================

	public function testFetchChildsReturnsRecordsForObject(): void
	{
		for ($i = 1; $i <= 3; $i++) {
			$this->insertUptoSign([
				'fk_object' => 99,
				'object_type' => 'propal',
				'ref' => 'CHILD-' . $i . '-' . uniqid(),
			]);
		}

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchChilds(99, 'propal');

		$this->assertIsArray($result);
		$this->assertCount(3, $result);
		$this->assertInstanceOf(\UptoSign::class, $result[0]);
	}

	public function testFetchChildsWithApiNameFilter(): void
	{
		$this->insertUptoSign([
			'fk_object' => 50,
			'object_type' => 'propal',
			'api_name' => 'uptosign',
			'ref' => 'SIGN-' . uniqid(),
		]);
		$this->insertUptoSign([
			'fk_object' => 50,
			'object_type' => 'propal',
			'api_name' => 'uptoseal',
			'ref' => 'SEAL-' . uniqid(),
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchChilds(50, 'propal', 'uptosign');

		$this->assertIsArray($result);
		$this->assertCount(1, $result);
		$this->assertEquals('uptosign', $result[0]->api_name);
	}

	public function testFetchChildsReturnsEmptyWhenNone(): void
	{
		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchChilds(999999, 'propal');

		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}

	// ================================================================
	// fetchByObject (additional filter tests)
	// ================================================================

	public function testFetchByObjectWithHashFileNullFilter(): void
	{
		$this->insertUptoSign([
			'fk_object' => 70,
			'object_type' => 'propal',
			'hash_file' => null,
			'ref' => 'NULL-HASH-' . uniqid(),
		]);
		$this->insertUptoSign([
			'fk_object' => 70,
			'object_type' => 'propal',
			'hash_file' => 'abc123',
			'ref' => 'HAS-HASH-' . uniqid(),
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchByObject(70, 'propal', ['hash_file_null' => true]);

		$this->assertIsArray($result);
		$this->assertCount(1, $result);
	}

	public function testFetchByObjectWithSignStatusFilter(): void
	{
		$this->insertUptoSign([
			'fk_object' => 80,
			'object_type' => 'propal',
			'sign_status' => 'finished',
			'ref' => 'FINISHED-' . uniqid(),
		]);
		$this->insertUptoSign([
			'fk_object' => 80,
			'object_type' => 'propal',
			'sign_status' => 'pending',
			'ref' => 'PENDING-' . uniqid(),
		]);

		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchByObject(80, 'propal', ['sign_status' => 'finished']);

		$this->assertIsArray($result);
		$this->assertCount(1, $result);
	}

	public function testFetchByObjectReturnsEmptyWhenNoMatch(): void
	{
		$uptosign = new \UptoSign($this->db);
		$result = $uptosign->fetchByObject(999999, 'propal');

		$this->assertIsArray($result);
		$this->assertCount(0, $result);
	}
}
