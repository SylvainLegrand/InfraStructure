<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSignList: reopen, getNbContacts, getContactsWithProcedures, deleteLine
 */
class UptoSignListMembersTest extends DolibarrRealTestCase
{
	/**
	 * Create and validate an UptoSignList.
	 */
	private function createTestList(array $data = []): \UptoSignList
	{
		$list = new \UptoSignList($this->db);
		$list->label = $data['label'] ?? 'List test ' . uniqid();
		$list->entity = 1;
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->create($this->testUser);

		$this->assertGreaterThan(0, $list->id, 'List should be created');

		// Validate (fetch before update in SQLite)
		$list->fetch($list->id);
		$list->validate($this->testUser);
		$list->fetch($list->id);

		$this->assertEquals(\UptoSignList::STATUS_VALIDATED, $list->status);

		return $list;
	}

	/**
	 * Insert a member row directly into the members table.
	 *
	 * @return int The inserted row id
	 */
	private function insertMember(int $listId, array $data = []): int
	{
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "uptosign_uptosignlistmembers"
			. " (fk_uptosignlist, firstname, lastname, email, mobile, source_type, source_id, status, fk_uptosign)"
			. " VALUES ("
			. ((int) $listId) . ","
			. "'" . $this->db->escape($data['firstname'] ?? 'John') . "',"
			. "'" . $this->db->escape($data['lastname'] ?? 'Doe') . "',"
			. "'" . $this->db->escape($data['email'] ?? 'john@example.com') . "',"
			. "'" . $this->db->escape($data['mobile'] ?? '+33600000000') . "',"
			. "'" . $this->db->escape($data['source_type'] ?? 'contact') . "',"
			. ((int) ($data['source_id'] ?? 0)) . ","
			. ((int) ($data['status'] ?? 0)) . ","
			. (isset($data['fk_uptosign']) ? ((int) $data['fk_uptosign']) : "NULL")
			. ")";

		$this->db->query($sql);
		return (int) $this->db->last_insert_id(MAIN_DB_PREFIX . "uptosign_uptosignlistmembers");
	}

	/**
	 * Insert an UptoSign record (same pattern as other test files).
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'LIST-' . uniqid();
		$uptosign->label = 'List member test';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = $data['status'] ?? \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = $data['object_type'] ?? 'propal';
		$uptosign->fk_object = $data['fk_object'] ?? 1;
		$uptosign->create($this->testUser);

		$this->assertGreaterThan(0, $uptosign->id, 'UptoSign record should be created');

		$setClauses = [];
		if (isset($data['path_file_signed'])) {
			$setClauses[] = "path_file_signed = '" . $this->db->escape($data['path_file_signed']) . "'";
		}
		$setClauses[] = "status = " . ((int) ($data['status'] ?? \UptoSign::STATUS_WAITING));

		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign SET "
			. implode(", ", $setClauses)
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		return $uptosign;
	}

	// ================================================================
	// reopen
	// ================================================================

	public function testReopenFromCanceled(): void
	{
		$list = $this->createTestList();

		$list->fetch($list->id);
		$list->cancel($this->testUser);
		$list->fetch($list->id);
		$this->assertEquals(\UptoSignList::STATUS_CANCELED, $list->status);

		$result = $list->reopen($this->testUser);
		$this->assertGreaterThanOrEqual(0, $result);

		$list->fetch($list->id);
		$this->assertEquals(\UptoSignList::STATUS_VALIDATED, $list->status);
	}

	public function testReopenFromDraftGoesToValidated(): void
	{
		$list = new \UptoSignList($this->db);
		$list->label = 'Draft list ' . uniqid();
		$list->entity = 1;
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->create($this->testUser);

		$list->fetch($list->id);
		$this->assertEquals(\UptoSignList::STATUS_DRAFT, $list->status);

		// reopen on draft: status != STATUS_VALIDATED → calls setStatusCommon to VALIDATED
		$result = $list->reopen($this->testUser);
		$this->assertGreaterThanOrEqual(0, $result);

		$list->fetch($list->id);
		$this->assertEquals(\UptoSignList::STATUS_VALIDATED, $list->status);
	}

	public function testReopenFromValidatedReturnsZero(): void
	{
		$list = $this->createTestList();

		$list->fetch($list->id);
		$result = $list->reopen($this->testUser);

		$this->assertEquals(0, $result, 'Reopen on already validated should return 0');
	}

	// ================================================================
	// getNbContacts
	// ================================================================

	public function testGetNbContactsReturnsZeroForEmptyList(): void
	{
		$list = $this->createTestList();

		$count = $list->getNbContacts();

		$this->assertEquals(0, $count);
	}

	public function testGetNbContactsReturnsMemberCount(): void
	{
		$list = $this->createTestList();

		$this->insertMember($list->id, ['firstname' => 'Alice', 'lastname' => 'Martin']);
		$this->insertMember($list->id, ['firstname' => 'Bob', 'lastname' => 'Dupont']);
		$this->insertMember($list->id, ['firstname' => 'Claire', 'lastname' => 'Durand']);

		$count = $list->getNbContacts();

		$this->assertEquals(3, $count);
	}

	public function testGetNbContactsCachesResult(): void
	{
		$list = $this->createTestList();

		$this->insertMember($list->id, ['firstname' => 'Alice', 'lastname' => 'Martin']);
		$this->insertMember($list->id, ['firstname' => 'Bob', 'lastname' => 'Dupont']);

		$count1 = $list->getNbContacts();
		$this->assertEquals(2, $count1);

		// Insert another member after cache was populated
		$this->insertMember($list->id, ['firstname' => 'Claire', 'lastname' => 'Durand']);

		// Cache should return the previous value (2, not 3)
		$count2 = $list->getNbContacts();
		$this->assertEquals(2, $count2, 'Cached value should be returned');
	}

	// ================================================================
	// getContacts
	// ================================================================

	public function testGetContactsReturnsEmptyForEmptyList(): void
	{
		$list = $this->createTestList();

		$contacts = $list->getContacts();

		$this->assertIsArray($contacts);
		$this->assertCount(0, $contacts);
	}

	public function testGetContactsReturnsMemberData(): void
	{
		$list = $this->createTestList();

		$this->insertMember($list->id, ['firstname' => 'Alice', 'lastname' => 'Martin']);
		$this->insertMember($list->id, ['firstname' => 'Bob', 'lastname' => 'Dupont']);

		$contacts = $list->getContacts();

		$this->assertIsArray($contacts);
		$this->assertCount(2, $contacts);
		$this->assertTrue(property_exists($contacts[0], 'firstname'));
		$this->assertTrue(property_exists($contacts[0], 'lastname'));
	}

	public function testGetContactsCachesResult(): void
	{
		$list = $this->createTestList();

		$this->insertMember($list->id, ['firstname' => 'Alice', 'lastname' => 'Martin']);

		$contacts1 = $list->getContacts();
		$this->assertCount(1, $contacts1);

		// Insert another member after cache was populated
		$this->insertMember($list->id, ['firstname' => 'Bob', 'lastname' => 'Dupont']);

		// Cache should return the previous value (1, not 2)
		$contacts2 = $list->getContacts();
		$this->assertCount(1, $contacts2, 'Cached value should be returned');
	}

	// ================================================================
	// getContactsWithProcedures
	// ================================================================

	public function testGetContactsWithProceduresEmptyList(): void
	{
		$list = $this->createTestList();

		$results = $list->getContactsWithProcedures();

		$this->assertIsArray($results);
		$this->assertCount(0, $results);
	}

	public function testGetContactsWithProceduresNoLinkedUptosign(): void
	{
		$list = $this->createTestList();

		$this->insertMember($list->id, [
			'firstname' => 'Alice',
			'lastname' => 'Martin',
		]);
		$this->insertMember($list->id, [
			'firstname' => 'Bob',
			'lastname' => 'Dupont',
		]);

		$results = $list->getContactsWithProcedures();

		$this->assertIsArray($results);
		$this->assertCount(2, $results);
		// LEFT JOIN with no matching uptosign -> uptosign fields are null
		$this->assertNull($results[0]->uptosign_id);
		$this->assertNull($results[0]->uptosign_status);
	}

	public function testGetContactsWithProceduresWithLinkedUptosign(): void
	{
		$list = $this->createTestList();

		$uptosign = $this->insertUptoSign([
			'status' => \UptoSign::STATUS_SIGNED,
		]);

		$this->insertMember($list->id, [
			'firstname' => 'Alice',
			'lastname' => 'Martin',
			'fk_uptosign' => $uptosign->id,
		]);
		$this->insertMember($list->id, [
			'firstname' => 'Bob',
			'lastname' => 'Dupont',
		]);

		$results = $list->getContactsWithProcedures();

		$this->assertIsArray($results);
		$this->assertCount(2, $results);

		// Results ordered by lastname, firstname: Dupont Bob first, then Martin Alice
		$bob = $results[0];
		$alice = $results[1];

		$this->assertEquals('Dupont', $bob->lastname);
		$this->assertNull($bob->uptosign_id);

		$this->assertEquals('Martin', $alice->lastname);
		$this->assertEquals($uptosign->id, $alice->uptosign_id);
		$this->assertEquals(\UptoSign::STATUS_SIGNED, $alice->uptosign_status);
	}

	public function testGetContactsWithProceduresOrdersByLastnameFirstname(): void
	{
		$list = $this->createTestList();

		$this->insertMember($list->id, ['firstname' => 'Zoe', 'lastname' => 'Alpha']);
		$this->insertMember($list->id, ['firstname' => 'Anna', 'lastname' => 'Zeta']);
		$this->insertMember($list->id, ['firstname' => 'Bob', 'lastname' => 'Alpha']);

		$results = $list->getContactsWithProcedures();

		$this->assertCount(3, $results);
		// Order: Alpha Bob, Alpha Zoe, Zeta Anna
		$this->assertEquals('Alpha', $results[0]->lastname);
		$this->assertEquals('Bob', $results[0]->firstname);
		$this->assertEquals('Alpha', $results[1]->lastname);
		$this->assertEquals('Zoe', $results[1]->firstname);
		$this->assertEquals('Zeta', $results[2]->lastname);
	}

	public function testGetContactsWithProceduresReturnsExpectedFields(): void
	{
		$list = $this->createTestList();

		$uptosign = $this->insertUptoSign([
			'status' => \UptoSign::STATUS_FILE_FETCHED,
		]);

		$this->insertMember($list->id, [
			'firstname' => 'Alice',
			'lastname' => 'Martin',
			'email' => 'alice@example.com',
			'mobile' => '+33611111111',
			'source_type' => 'contact',
			'source_id' => 42,
			'fk_uptosign' => $uptosign->id,
		]);

		$results = $list->getContactsWithProcedures();

		$this->assertCount(1, $results);
		$row = $results[0];

		// Member fields
		$expectedFields = [
			'rowid', 'firstname', 'lastname', 'email', 'mobile',
			'source_type', 'source_id', 'member_status',
			'uptosign_id', 'uptosign_ref', 'uptosign_status',
			'path_file_signed', 'uptosign_date', 'date_sign',
		];
		foreach ($expectedFields as $field) {
			$this->assertTrue(property_exists($row, $field), "Field '$field' should exist on result row");
		}

		$this->assertEquals('Alice', $row->firstname);
		$this->assertEquals('alice@example.com', $row->email);
		$this->assertEquals($uptosign->id, $row->uptosign_id);
	}

	// ================================================================
	// deleteLine (status guard)
	// ================================================================

	public function testDeleteLineBlockedByNegativeStatus(): void
	{
		$list = $this->createTestList();

		// Force status to canceled (STATUS_CANCELED = 9, not negative)
		// The guard checks status < 0, so set it to -1 directly
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign_uptosignlist"
			. " SET status = -1"
			. " WHERE rowid = " . ((int) $list->id);
		$this->db->query($sql);

		$list->fetch($list->id);
		$this->assertEquals(-1, $list->status);

		$memberId = $this->insertMember($list->id, ['firstname' => 'Test', 'lastname' => 'User']);

		$result = $list->deleteLine($this->testUser, $memberId);

		$this->assertEquals(-2, $result);
		$this->assertEquals('ErrorDeleteLineNotAllowedByObjectStatus', $list->error);
	}
}
