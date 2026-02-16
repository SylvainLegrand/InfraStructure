<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for selector modules and UptosignListTargets base class.
 * Tests add_to_target(), addTargetsToDatabase(), clear_target(), update_nb()
 * and getContactsWithProcedures() with real Dolibarr + SQLite DB.
 */
class SelectorModulesIntegrationTest extends DolibarrRealTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// Load selector module classes
		$projectRoot = dirname(__DIR__, 3);
		require_once $projectRoot . '/core/modules/uptosignlist/modules_mailings.php';
		require_once $projectRoot . '/core/modules/uptosignlist/uts_inputmanual.modules.php';
		require_once $projectRoot . '/core/modules/uptosignlist/uts_contacts.modules.php';
	}

	/**
	 * Create a test UptoSignList and return its ID
	 */
	private function createTestList(): \UptoSignList
	{
		$list = new \UptoSignList($this->db);
		$list->ref = 'SELTEST-' . uniqid();
		$list->label = 'Selector test list';
		$list->status = \UptoSignList::STATUS_DRAFT;
		$list->entity = 1;
		$result = $list->create($this->testUser);
		$this->assertGreaterThan(0, $result, 'Failed to create test list');
		return $list;
	}

	// =========================================================
	// UptosignListTargets base class: addTargetsToDatabase
	// =========================================================

	public function testAddTargetsToDatabaseInsertsRecords(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles = [
			[
				'email' => 'alice@example.com',
				'lastname' => 'Dupont',
				'firstname' => 'Alice',
				'other' => 'Dept=Sales',
				'source_url' => '',
				'source_id' => '',
				'source_type' => 'manual',
				'mobile' => '',
			],
			[
				'email' => 'bob@example.com',
				'lastname' => 'Martin',
				'firstname' => 'Bob',
				'other' => '',
				'source_url' => '',
				'source_id' => '',
				'source_type' => 'manual',
				'mobile' => '+33612345678',
			],
		];

		$result = $base->addTargetsToDatabase($list->id, $cibles);

		$this->assertEquals(2, $result, 'Should insert 2 targets');
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => 'alice@example.com',
			'lastname' => 'Dupont',
			'firstname' => 'Alice',
		]);
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => 'bob@example.com',
			'lastname' => 'Martin',
		]);
	}

	public function testAddTargetsToDatabaseSkipsEmptyEmails(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles = [
			[
				'email' => '',
				'lastname' => 'Empty',
				'firstname' => '',
				'other' => '',
				'source_url' => '',
				'source_id' => '',
				'source_type' => 'manual',
				'mobile' => '',
			],
			[
				'email' => 'valid@example.com',
				'lastname' => 'Valid',
				'firstname' => '',
				'other' => '',
				'source_url' => '',
				'source_id' => '',
				'source_type' => 'manual',
				'mobile' => '',
			],
		];

		$result = $base->addTargetsToDatabase($list->id, $cibles);
		$this->assertEquals(1, $result, 'Should insert only 1 target (skip empty email)');
		$count = $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]);
		$this->assertEquals(1, $count);
	}

	public function testAddTargetsToDatabaseEmptyArray(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$result = $base->addTargetsToDatabase($list->id, []);
		$this->assertEquals(0, $result);
		$count = $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]);
		$this->assertEquals(0, $count);
	}

	// =========================================================
	// UptosignListTargets: clear_target
	// =========================================================

	public function testClearTargetRemovesAllMembers(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		// Add targets
		$cibles = [
			['email' => 'a@test.com', 'lastname' => 'A', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
			['email' => 'b@test.com', 'lastname' => 'B', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
			['email' => 'c@test.com', 'lastname' => 'C', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];
		$base->addTargetsToDatabase($list->id, $cibles);

		$this->assertEquals(3, $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]));

		// Clear
		$base->clear_target($list->id);

		$this->assertEquals(0, $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]));
	}

	public function testClearTargetDoesNotAffectOtherLists(): void
	{
		$list1 = $this->createTestList();
		$list2 = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles1 = [
			['email' => 'list1@test.com', 'lastname' => 'L1', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];
		$cibles2 = [
			['email' => 'list2@test.com', 'lastname' => 'L2', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];

		$base->addTargetsToDatabase($list1->id, $cibles1);
		$base->addTargetsToDatabase($list2->id, $cibles2);

		// Clear only list1
		$base->clear_target($list1->id);

		$this->assertEquals(0, $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list1->id]));
		$this->assertEquals(1, $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list2->id]));
	}

	// =========================================================
	// UptosignListTargets: update_nb
	// =========================================================

	public function testUpdateNbReturnsCorrectCount(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles = [
			['email' => 'x@test.com', 'lastname' => 'X', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
			['email' => 'y@test.com', 'lastname' => 'Y', 'firstname' => '', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];
		$base->addTargetsToDatabase($list->id, $cibles);

		$nb = $base->update_nb($list->id);
		$this->assertEquals(2, $nb);
	}

	public function testUpdateNbReturnsZeroForEmptyList(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$nb = $base->update_nb($list->id);
		$this->assertEquals(0, $nb);
	}

	// =========================================================
	// xinputuser: add_to_target with semicolon-separated lines
	// =========================================================

	public function testXInputUserSemicolonSingleLine(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = 'jean.dupont@example.com;Dupont;Jean;Direction';
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(1, $result, 'Should add 1 recipient');
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => 'jean.dupont@example.com',
			'lastname' => 'Dupont',
			'firstname' => 'Jean',
			'source_type' => 'manual',
		]);
	}

	public function testXInputUserSemicolonMultipleLines(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "alice@example.com;Dupont;Alice;RH\nbob@example.com;Martin;Bob;IT\ncharlie@example.com;Durand;Charlie;Sales";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(3, $result, 'Should add 3 recipients');
		$count = $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]);
		$this->assertEquals(3, $count);
	}

	// =========================================================
	// xinputuser: add_to_target with tab-separated lines (spreadsheet paste)
	// =========================================================

	public function testXInputUserTabSeparatedSingleLine(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "marie@example.com\tLeclerc\tMarie\tCompta";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(1, $result);
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => 'marie@example.com',
			'lastname' => 'Leclerc',
			'firstname' => 'Marie',
		]);
	}

	public function testXInputUserTabSeparatedMultipleLines(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		// Simulates copy/paste from Excel with 3 rows
		$_POST['xinputuser'] = "alice@example.com\tDupont\tAlice\tRH\nbob@example.com\tMartin\tBob\tIT\ncharlie@example.com\tDurand\tCharlie\tSales";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(3, $result, 'Should add 3 recipients from tab-separated input');
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', ['email' => 'alice@example.com', 'lastname' => 'Dupont']);
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', ['email' => 'bob@example.com', 'lastname' => 'Martin']);
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', ['email' => 'charlie@example.com', 'lastname' => 'Durand']);
	}

	// =========================================================
	// xinputuser: add_to_target with Windows line endings (CRLF)
	// =========================================================

	public function testXInputUserCrlfLineEndings(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "alice@example.com;Dupont;Alice\r\nbob@example.com;Martin;Bob\r\ncharlie@example.com;Durand;Charlie";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(3, $result, 'Should handle CRLF line endings');
	}

	// =========================================================
	// xinputuser: email-only lines (no name columns)
	// =========================================================

	public function testXInputUserEmailOnly(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "solo@example.com";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(1, $result);
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => 'solo@example.com',
			'lastname' => '',
			'firstname' => '',
		]);
	}

	public function testXInputUserMultipleEmailsOnly(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "a@example.com\nb@example.com\nc@example.com";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(3, $result, 'Should add 3 email-only recipients');
	}

	// =========================================================
	// xinputuser: empty lines are skipped
	// =========================================================

	public function testXInputUserSkipsEmptyLines(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "\n\nalice@example.com;Dupont;Alice\n\nbob@example.com;Martin;Bob\n\n";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(2, $result, 'Should skip empty lines');
	}

	// =========================================================
	// xinputuser: error cases
	// =========================================================

	public function testXInputUserEmptyInputReturnsError(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = '';
		$result = $mod->add_to_target($list->id);

		$this->assertLessThan(0, $result, 'Should return negative for empty input');
		$this->assertNotEmpty($mod->error);
	}

	public function testXInputUserInvalidEmailReturnsError(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = 'not-an-email;Dupont;Jean';
		$result = $mod->add_to_target($list->id);

		$this->assertLessThan(0, $result, 'Should return negative for invalid email');
		$this->assertNotEmpty($mod->error);
	}

	public function testXInputUserMixedValidAndInvalidEmails(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "valid@example.com;Good;Person\nnot-valid;Bad;Person\nalso-bad;Worse;Person";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(-2, $result, 'Should return -2 for 2 invalid emails');

		// No records should be inserted when there are errors
		$count = $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]);
		$this->assertEquals(0, $count, 'No records should be inserted when errors exist');
	}

	public function testXInputUserOnlyWhitespaceReturnsError(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "   \n   \n   ";
		$result = $mod->add_to_target($list->id);

		$this->assertLessThan(0, $result, 'Should return negative for whitespace-only input');
	}

	// =========================================================
	// xinputuser: mixed separators across lines
	// =========================================================

	public function testXInputUserMixedSeparators(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		// First line tab-separated (from Excel), second line semicolon (typed manually)
		$_POST['xinputuser'] = "alice@example.com\tDupont\tAlice\nbob@example.com;Martin;Bob";
		$result = $mod->add_to_target($list->id);

		$this->assertEquals(2, $result, 'Should handle mixed separators across lines');
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', ['email' => 'alice@example.com', 'lastname' => 'Dupont']);
		$this->assertDatabaseHas('uptosign_uptosignlistmembers', ['email' => 'bob@example.com', 'lastname' => 'Martin']);
	}

	// =========================================================
	// xinputuser: source_type is 'manual'
	// =========================================================

	public function testXInputUserSourceTypeIsManual(): void
	{
		$list = $this->createTestList();
		$mod = new \uptosignlist_uts_inputmanual($this->db);

		$_POST['xinputuser'] = "test@example.com;Test;User";
		$mod->add_to_target($list->id);

		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => 'test@example.com',
			'source_type' => 'manual',
		]);
	}

	// =========================================================
	// contacts1: add_to_target with real contacts
	// =========================================================

	public function testContacts1AddToTargetWithContacts(): void
	{
		$list = $this->createTestList();

		// Create thirdparty + contacts
		$soc = $this->createTestSociete(['client' => 1]);
		$contact1 = $this->createTestContact($soc, [
			'email' => 'contact1-' . uniqid() . '@example.com',
			'lastname' => 'Premier',
			'firstname' => 'Contact',
		]);
		$contact2 = $this->createTestContact($soc, [
			'email' => 'contact2-' . uniqid() . '@example.com',
			'lastname' => 'Deuxieme',
			'firstname' => 'Contact',
		]);

		// Ensure contacts are active (statut = 1)
		$this->db->query("UPDATE " . MAIN_DB_PREFIX . "socpeople SET statut = 1 WHERE rowid IN (" . ((int) $contact1->id) . ", " . ((int) $contact2->id) . ")");

		// Set filter to not filter anything
		$_POST['filter'] = '-1';
		$_POST['filter_jobposition'] = '-1';
		$_POST['filter_category'] = '-1';
		$_POST['filter_category_customer'] = '-1';
		$_POST['filter_category_supplier'] = '-1';

		$mod = new \uptosignlist_uts_contacts($this->db);
		$result = $mod->add_to_target($list->id);

		$this->assertGreaterThanOrEqual(2, $result, 'Should add at least 2 contacts');
		$count = $this->getDatabaseCount('uptosign_uptosignlistmembers', ['fk_uptosignlist' => $list->id]);
		$this->assertGreaterThanOrEqual(2, $count);
	}

	public function testContacts1SourceTypeIsContact(): void
	{
		$list = $this->createTestList();

		$soc = $this->createTestSociete(['client' => 1]);
		$email = 'srctype-' . uniqid() . '@example.com';
		$contact = $this->createTestContact($soc, ['email' => $email, 'lastname' => 'SourceTest']);
		$this->db->query("UPDATE " . MAIN_DB_PREFIX . "socpeople SET statut = 1 WHERE rowid = " . ((int) $contact->id));

		$_POST['filter'] = '-1';
		$_POST['filter_jobposition'] = '-1';
		$_POST['filter_category'] = '-1';
		$_POST['filter_category_customer'] = '-1';
		$_POST['filter_category_supplier'] = '-1';

		$mod = new \uptosignlist_uts_contacts($this->db);
		$mod->add_to_target($list->id);

		$this->assertDatabaseHas('uptosign_uptosignlistmembers', [
			'fk_uptosignlist' => $list->id,
			'email' => $email,
			'source_type' => 'contact',
		]);
	}

	public function testContacts1ExcludesAlreadyAdded(): void
	{
		$list = $this->createTestList();

		$soc = $this->createTestSociete(['client' => 1]);
		$email = 'dup-' . uniqid() . '@example.com';
		$contact = $this->createTestContact($soc, ['email' => $email, 'lastname' => 'DupTest']);
		$this->db->query("UPDATE " . MAIN_DB_PREFIX . "socpeople SET statut = 1 WHERE rowid = " . ((int) $contact->id));

		$_POST['filter'] = '-1';
		$_POST['filter_jobposition'] = '-1';
		$_POST['filter_category'] = '-1';
		$_POST['filter_category_customer'] = '-1';
		$_POST['filter_category_supplier'] = '-1';

		$mod = new \uptosignlist_uts_contacts($this->db);

		// First call
		$result1 = $mod->add_to_target($list->id);
		$this->assertGreaterThanOrEqual(1, $result1);

		// Second call should not add the same contact again (email-based exclusion)
		$result2 = $mod->add_to_target($list->id);
		$this->assertEquals(0, $result2, 'Should not add already-existing contacts');
	}

	// =========================================================
	// contacts1: getNbOfRecipients
	// =========================================================

	public function testContacts1GetNbOfRecipients(): void
	{
		// Create some contacts with emails
		$soc = $this->createTestSociete();
		$this->createTestContact($soc, ['email' => 'cnt-' . uniqid() . '@example.com']);
		$this->createTestContact($soc, ['email' => 'cnt-' . uniqid() . '@example.com']);

		$mod = new \uptosignlist_uts_contacts($this->db);
		$result = $mod->getNbOfRecipients();

		$this->assertIsInt($result);
		$this->assertGreaterThanOrEqual(2, $result);
	}

	// =========================================================
	// getContactsWithProcedures (new method on UptoSignList)
	// =========================================================

	public function testGetContactsWithProceduresReturnsEmptyForNoMembers(): void
	{
		$list = $this->createTestList();
		$list->fetch($list->id);

		$result = $list->getContactsWithProcedures();
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testGetContactsWithProceduresReturnsMembersWithoutUptosign(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles = [
			['email' => 'member1@test.com', 'lastname' => 'Alpha', 'firstname' => 'Alain', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
			['email' => 'member2@test.com', 'lastname' => 'Beta', 'firstname' => 'Bernard', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];
		$base->addTargetsToDatabase($list->id, $cibles);

		$list->fetch($list->id);
		$result = $list->getContactsWithProcedures();

		$this->assertIsArray($result);
		$this->assertCount(2, $result);

		// Sorted by lastname: Alpha first, Beta second
		$this->assertEquals('Alpha', $result[0]->lastname);
		$this->assertEquals('Alain', $result[0]->firstname);
		$this->assertEquals('member1@test.com', $result[0]->email);
		$this->assertEmpty($result[0]->uptosign_id);

		$this->assertEquals('Beta', $result[1]->lastname);
		$this->assertEquals('Bernard', $result[1]->firstname);
	}

	public function testGetContactsWithProceduresJoinsUptosign(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles = [
			['email' => 'linked@test.com', 'lastname' => 'Gamma', 'firstname' => 'Gerard', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];
		$base->addTargetsToDatabase($list->id, $cibles);

		// Create an UptoSign procedure and link it
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'UTS-LINK-' . uniqid();
		$uptosign->label = 'Linked procedure';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_SIGNED;
		$uptosign->entity = 1;
		$uptosign->object_type = 'uptosignlist';
		$uptosign->fk_object = $list->id;
		$uptosign->fk_uptosignlist = $list->id;
		$uptosign->create($this->testUser);

		// Link the member to the uptosign procedure
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign_uptosignlistmembers";
		$sql .= " SET fk_uptosign = " . ((int) $uptosign->id);
		$sql .= " WHERE fk_uptosignlist = " . ((int) $list->id);
		$sql .= " AND email = 'linked@test.com'";
		$this->db->query($sql);

		$list->fetch($list->id);
		$result = $list->getContactsWithProcedures();

		$this->assertCount(1, $result);
		$this->assertEquals('Gamma', $result[0]->lastname);
		$this->assertEquals($uptosign->id, $result[0]->uptosign_id);
		$this->assertEquals($uptosign->ref, $result[0]->uptosign_ref);
		$this->assertEquals(\UptoSign::STATUS_SIGNED, $result[0]->uptosign_status);
	}

	public function testGetContactsWithProceduresMixedLinkedAndUnlinked(): void
	{
		$list = $this->createTestList();
		$base = new \UptosignListTargets($this->db);

		$cibles = [
			['email' => 'linked2@test.com', 'lastname' => 'Alpha', 'firstname' => 'Anne', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
			['email' => 'unlinked@test.com', 'lastname' => 'Beta', 'firstname' => 'Benoit', 'other' => '', 'source_url' => '', 'source_id' => '', 'source_type' => 'manual', 'mobile' => ''],
		];
		$base->addTargetsToDatabase($list->id, $cibles);

		// Create UptoSign for first member only
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = 'UTS-MIX-' . uniqid();
		$uptosign->label = 'Mixed test';
		$uptosign->fk_soc = $soc->id;
		$uptosign->status = \UptoSign::STATUS_WAITING;
		$uptosign->entity = 1;
		$uptosign->object_type = 'uptosignlist';
		$uptosign->fk_object = $list->id;
		$uptosign->fk_uptosignlist = $list->id;
		$uptosign->create($this->testUser);

		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign_uptosignlistmembers";
		$sql .= " SET fk_uptosign = " . ((int) $uptosign->id);
		$sql .= " WHERE fk_uptosignlist = " . ((int) $list->id);
		$sql .= " AND email = 'linked2@test.com'";
		$this->db->query($sql);

		$list->fetch($list->id);
		$result = $list->getContactsWithProcedures();

		$this->assertCount(2, $result);

		// Alpha (linked) comes first alphabetically
		$this->assertEquals('Alpha', $result[0]->lastname);
		$this->assertEquals($uptosign->id, $result[0]->uptosign_id);

		// Beta (unlinked)
		$this->assertEquals('Beta', $result[1]->lastname);
		$this->assertEmpty($result[1]->uptosign_id);
	}

	// =========================================================
	// Cleanup $_POST between tests
	// =========================================================

	protected function tearDown(): void
	{
		unset(
			$_POST['xinputuser'],
			$_POST['filter'],
			$_POST['filter_jobposition'],
			$_POST['filter_category'],
			$_POST['filter_category_customer'],
			$_POST['filter_category_supplier']
		);
		parent::tearDown();
	}
}
