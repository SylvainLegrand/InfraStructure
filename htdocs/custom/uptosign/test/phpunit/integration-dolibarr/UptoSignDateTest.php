<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for date/time handling in UptoSign:
 * - createEvent() uses date_sign when available
 * - signInfo() correctly sets date_sign from API response
 * - DateTime parsing with UTC timezone
 * - fetchByObject returns date_sign for trigger usage
 * - uptosignAddActionComm() accepts optional date parameter
 */
class UptoSignDateTest extends DolibarrRealTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// Clean actioncomm events from previous tests
		$this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "actioncomm WHERE code IN ('AC_UPTOSIGN', 'AC_UPTOSEAL', 'AC_TEST')");

		// Ensure AC_OTH_AUTO action type exists (required by ActionComm::create)
		$sql = "SELECT id FROM " . MAIN_DB_PREFIX . "c_actioncomm WHERE code = 'AC_OTH_AUTO'";
		$resql = $this->db->query($sql);
		if ($resql && $this->db->num_rows($resql) == 0) {
			$this->db->query(
				"INSERT INTO " . MAIN_DB_PREFIX . "c_actioncomm (id, code, type, libelle, module, active, position)"
				. " VALUES (50, 'AC_OTH_AUTO', 'system', 'Other (automatically)', NULL, 1, 50)"
			);
		}
	}

	/**
	 * Insert an UptoSign record with full control over fields.
	 */
	private function insertUptoSign(array $data): \UptoSign
	{
		$soc = $this->createTestSociete();
		$uptosign = new \UptoSign($this->db);
		$uptosign->ref = $data['ref'] ?? 'DATE-' . uniqid();
		$uptosign->label = $data['label'] ?? 'Date test';
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
	 * Build a stdClass proxy for use as the $object parameter.
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
	 * Build mock API client.
	 */
	private function buildMockApiClient(array $responses): object
	{
		return new class ($responses) {
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

	/**
	 * Fetch the most recent ActionComm matching criteria.
	 * Returns an object with datep and datep2, or null.
	 */
	private function fetchActionComm(string $where): ?object
	{
		// ActionComm table uses datep (start) and datep2 (end) columns
		$sql = "SELECT datep, datep2 FROM " . MAIN_DB_PREFIX . "actioncomm"
			. " WHERE " . $where
			. " ORDER BY id DESC LIMIT 1";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return null;
		}
		$obj = $this->db->fetch_object($resql);
		return $obj ?: null;
	}

	// ================================================================
	// createEvent() date handling
	// ================================================================

	public function testCreateEventUsesDateSignWhenAvailable(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);

		$uptosign = $this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptosign',
			'sign_id' => 'test-date-' . uniqid(),
			'status' => \UptoSign::STATUS_SIGNED,
		]);

		$uptosign->fetch($uptosign->id);

		// Set date_sign to a known past date: 2024-06-15 10:30:00 UTC
		$knownTimestamp = gmmktime(10, 30, 0, 6, 15, 2024);
		$uptosign->date_sign = $knownTimestamp;

		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);
		$object->signOrSeal = 'uptosign';
		$object->uptosignMessage = 'DocumentSigned';

		$uptosign->createEvent($object);

		$row = $this->fetchActionComm(
			"ref_ext = '" . $this->db->escape($propal->ref . ':uptosign') . "'"
		);
		$this->assertNotNull($row, 'ActionComm should have been created');

		$datep = $this->db->jdate($row->datep);
		$datep2 = $this->db->jdate($row->datep2);

		$this->assertEquals($knownTimestamp, $datep, 'ActionComm datep should match date_sign');
		$this->assertEquals($knownTimestamp, $datep2, 'ActionComm datep2 should match date_sign');
	}

	public function testCreateEventFallsBackToDolNowWhenNoDateSign(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);

		$uptosign = $this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptosign',
			'sign_id' => 'test-now-' . uniqid(),
			'status' => \UptoSign::STATUS_WAITING,
		]);

		$uptosign->fetch($uptosign->id);

		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);
		$object->signOrSeal = 'uptosign';
		$object->uptosignMessage = 'UptoSignProcessStarted';

		$before = dol_now();
		$uptosign->createEvent($object);
		$after = dol_now();

		$row = $this->fetchActionComm(
			"ref_ext = '" . $this->db->escape($propal->ref . ':uptosign') . "'"
		);
		$this->assertNotNull($row, 'ActionComm should have been created');

		$datep = $this->db->jdate($row->datep);

		$this->assertGreaterThanOrEqual($before, $datep, 'ActionComm datep should be >= test start time');
		$this->assertLessThanOrEqual($after + 2, $datep, 'ActionComm datep should be <= test end time (with margin)');
	}

	// ================================================================
	// signInfo() date_sign from API
	// ================================================================

	public function testSignInfoSetsDateSignFromApiResponse(): void
	{
		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);

		$signId = 'test-signinfo-date-' . uniqid();
		$uptosign = $this->insertUptoSign([
			'fk_object' => $propal->id,
			'object_type' => 'propal',
			'api_name' => 'uptosign',
			'sign_id' => $signId,
			'status' => \UptoSign::STATUS_WAITING,
		]);

		// Set tms to recent time (avoids 30-day expiration check where tms is TEXT in SQLite)
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign"
			. " SET tms = '" . date('Y-m-d H:i:s') . "'"
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		$apiCreatedAt = '2024-06-15T08:00:00Z';
		$apiUpdatedAt = '2024-06-15T10:30:00Z';

		$expectedDateCreation = (new \DateTime($apiCreatedAt, new \DateTimeZone('UTC')))->getTimestamp();
		$expectedDateSign = (new \DateTime($apiUpdatedAt, new \DateTimeZone('UTC')))->getTimestamp();

		$mockClient = $this->buildMockApiClient([
			$signId => [
				'status' => [
					'http_code' => 200,
					'content' => '',
					'data' => [
						'status' => 'finished',
						'history' => [],
						'createdAt' => $apiCreatedAt,
						'updatedAt' => $apiUpdatedAt,
					],
					'curl_error' => '',
				],
			],
		]);

		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);

		$uptosign->apiClient = $mockClient;
		$uptosign->signInfo($this->testUser, $object);

		// Re-fetch from database
		$fresh = new \UptoSign($this->db);
		$fresh->fetch($uptosign->id);

		$this->assertEquals($expectedDateCreation, $fresh->date_creation, 'date_creation should match API createdAt');
		$this->assertEquals($expectedDateSign, $fresh->date_sign, 'date_sign should match API updatedAt');
	}

	// ================================================================
	// DateTime UTC timezone parsing
	// ================================================================

	public function testDateTimeParsingWithUTCTimezone(): void
	{
		// Without timezone suffix: should be treated as UTC
		$dt1 = new \DateTime('2024-06-15T10:30:00', new \DateTimeZone('UTC'));
		$ts1 = $dt1->getTimestamp();

		// With Z suffix: Z takes precedence, same result
		$dt2 = new \DateTime('2024-06-15T10:30:00Z', new \DateTimeZone('UTC'));
		$ts2 = $dt2->getTimestamp();

		$this->assertEquals($ts1, $ts2, 'Both should produce the same UTC timestamp');

		// With explicit offset: offset takes precedence over constructor timezone
		$dt3 = new \DateTime('2024-06-15T10:30:00+02:00', new \DateTimeZone('UTC'));
		$ts3 = $dt3->getTimestamp();

		// +02:00 means the actual UTC time is 08:30, so 2 hours less
		$this->assertEquals($ts1 - 7200, $ts3, 'Offset +02:00 should result in timestamp 2h earlier than UTC');
	}

	// ================================================================
	// fetchByObject for trigger date resolution
	// ================================================================

	public function testFetchByObjectReturnsDateSign(): void
	{
		$uptosign = $this->insertUptoSign([
			'fk_object' => 999,
			'object_type' => 'contrat',
			'api_name' => 'uptosign',
			'sign_id' => 'test-trigger-' . uniqid(),
			'status' => \UptoSign::STATUS_SIGNED,
		]);

		$knownTimestamp = gmmktime(14, 0, 0, 3, 20, 2024);

		// Set date_sign via SQL
		$sql = "UPDATE " . MAIN_DB_PREFIX . "uptosign"
			. " SET date_sign = '" . $this->db->idate($knownTimestamp) . "'"
			. " WHERE rowid = " . ((int) $uptosign->id);
		$this->db->query($sql);

		$lookup = new \UptoSign($this->db);
		$records = $lookup->fetchByObject(999, 'contrat');

		$this->assertIsArray($records, 'fetchByObject should return an array');
		$this->assertNotEmpty($records, 'Should find at least one record');

		$found = false;
		foreach ($records as $record) {
			if (!empty($record->date_sign)) {
				$this->assertEquals($knownTimestamp, $record->date_sign, 'date_sign should match the known timestamp');
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, 'At least one record should have date_sign set');
	}

	// ================================================================
	// uptosignAddActionComm with explicit date
	// ================================================================

	public function testUptosignAddActionCommWithExplicitDate(): void
	{
		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);

		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);
		$object->fk_project = 0;

		$knownTimestamp = gmmktime(9, 15, 0, 1, 10, 2024);

		uptosignAddActionComm($object, 'TEST', 'Test label', 'Test desc', ['msg'], '', $knownTimestamp);

		$row = $this->fetchActionComm(
			"code = 'AC_TEST' AND fk_element = " . ((int) $propal->id)
		);
		$this->assertNotNull($row, 'ActionComm should have been created');

		$datep = $this->db->jdate($row->datep);
		$datep2 = $this->db->jdate($row->datep2);

		$this->assertEquals($knownTimestamp, $datep, 'datep should match explicit date');
		$this->assertEquals($knownTimestamp, $datep2, 'datep2 should match explicit date');
	}

	public function testUptosignAddActionCommWithoutDateUsesDolNow(): void
	{
		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

		$soc = $this->createTestSociete();
		$propal = $this->createTestPropal($soc);

		$object = $this->createObjectProxy($propal->id, $soc->id, 'propal', $propal->ref);
		$object->fk_project = 0;

		$before = dol_now();
		uptosignAddActionComm($object, 'TEST', 'Test label', 'Test desc', ['msg'], '');
		$after = dol_now();

		$row = $this->fetchActionComm(
			"code = 'AC_TEST' AND fk_element = " . ((int) $propal->id)
		);
		$this->assertNotNull($row, 'ActionComm should have been created');

		$datep = $this->db->jdate($row->datep);

		$this->assertGreaterThanOrEqual($before, $datep, 'datep should be >= test start');
		$this->assertLessThanOrEqual($after + 2, $datep, 'datep should be <= test end (with margin)');
	}
}
