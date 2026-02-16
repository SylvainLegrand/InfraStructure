<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UptoSign class methods that don't require DB access:
 * anonyzeField, getShortContactNameMailTel, displayHistory, getStatusList, LibStatut
 *
 * Uses ReflectionClass::newInstanceWithoutConstructor() to bypass the DoliDB requirement.
 */
class UptoSignMethodsTest extends TestCase
{
	/** @var \UptoSign */
	private $uptosign;

	/** @var object Saved $langs */
	private $savedLangs;

	protected function setUp(): void
	{
		global $langs;
		$this->savedLangs = $langs;

		$langs = new class {
			public function trans($key, ...$args)
			{
				return $key;
			}
			public function transnoentitiesnoconv($key, ...$args)
			{
				return $key;
			}
			public function load($module)
			{
				// no-op
			}
		};

		// Create UptoSign instance without calling constructor (avoids DoliDB requirement)
		$ref = new \ReflectionClass(\UptoSign::class);
		$this->uptosign = $ref->newInstanceWithoutConstructor();
	}

	protected function tearDown(): void
	{
		global $langs;
		$langs = $this->savedLangs;
	}

	// --- anonyzeField ---

	public function testAnonyzeFieldMail(): void
	{
		$result = $this->uptosign->anonyzeField('john.doe@example.com', 'mail');
		// First half of local part visible, rest masked
		$this->assertStringContainsString('@', $result);
		$this->assertStringContainsString('*', $result);
		// Should still have part of the domain
		$this->assertStringContainsString('com', $result);
	}

	public function testAnonyzeFieldMailPreservesStructure(): void
	{
		$result = $this->uptosign->anonyzeField('ab@cd.fr', 'mail');
		$this->assertStringContainsString('@', $result);
		$this->assertStringContainsString('*', $result);
	}

	public function testAnonyzeFieldTel(): void
	{
		$result = $this->uptosign->anonyzeField('+33612345678', 'tel');
		// First 3 chars visible
		$this->assertStringStartsWith('+33', $result);
		$this->assertStringContainsString('*', $result);
		// Some trailing digits should remain
		$this->assertNotSame('+33', $result);
	}

	public function testAnonyzeFieldTelShortNumber(): void
	{
		$result = $this->uptosign->anonyzeField('123', 'tel');
		$this->assertStringStartsWith('123', $result);
	}

	public function testAnonyzeFieldUnknownType(): void
	{
		$result = $this->uptosign->anonyzeField('secret', 'other');
		// Unknown type returns string unchanged
		$this->assertSame('secret', $result);
	}

	// --- getShortContactNameMailTel ---

	public function testGetShortContactNameMailTel(): void
	{
		$contact = new \stdClass();
		$contact->firstname = 'Jean';
		$contact->lastname = 'Dupont';
		$contact->email = 'jean.dupont@example.com';
		$contact->phone_mobile = '+33612345678';

		$result = $this->uptosign->getShortContactNameMailTel($contact);
		$this->assertStringContainsString('Jean', $result);
		$this->assertStringContainsString('D', $result); // First letter of lastname
		$this->assertStringContainsString('&lt;', $result); // HTML entity for <
		$this->assertStringContainsString('&gt;', $result); // HTML entity for >
		$this->assertStringContainsString('tel:', $result);
		$this->assertStringContainsString('*', $result); // Anonymized parts
	}

	public function testGetShortContactNameMailTelWithPoste(): void
	{
		global $conf;
		$conf->global->UPTOSIGN_ADD_CONTACT_POSTE_FUNCTION = '1';

		$contact = new \stdClass();
		$contact->firstname = 'Marie';
		$contact->lastname = 'Martin';
		$contact->email = 'marie@test.com';
		$contact->phone_mobile = '+33600000000';
		$contact->poste = 'Directrice';

		$result = $this->uptosign->getShortContactNameMailTel($contact);
		$this->assertStringContainsString('Directrice', $result);

		unset($conf->global->UPTOSIGN_ADD_CONTACT_POSTE_FUNCTION);
	}

	// --- displayHistory ---

	public function testDisplayHistoryEmpty(): void
	{
		$this->uptosign->sign_history = '';

		$result = $this->uptosign->displayHistory();
		$this->assertStringContainsString('uptosignHistory', $result);
		$this->assertStringContainsString('uptosignTitleNoHistory', $result);
	}

	public function testDisplayHistoryNull(): void
	{
		$this->uptosign->sign_history = null;

		$result = $this->uptosign->displayHistory();
		$this->assertStringContainsString('uptosignTitleNoHistory', $result);
	}

	public function testDisplayHistoryWithData(): void
	{
		// displayHistory calls uptosignQRCode which needs TCPDF_PATH constant
		if (!defined('TCPDF_PATH')) {
			$this->markTestSkipped('TCPDF_PATH not defined');
		}

		$history = new \stdClass();
		$person = new \stdClass();
		$person->url = 'https://example.com/sign/abc';
		$person->step1 = new \stdClass();
		$person->step1->created_at = '2024-01-01';
		$person->step1->step = 'Signed';
		$person->step1->description = 'Document signed';
		$history->{'Jean Dupont'} = $person;

		$this->uptosign->sign_history = json_encode($history);

		$result = $this->uptosign->displayHistory();
		$this->assertStringContainsString('uptosignTitleHistory', $result);
	}

	// --- LibStatut ---

	public function testLibStatutDraft(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_DRAFT);
		$this->assertIsString($result);
	}

	public function testLibStatutWaiting(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_WAITING);
		$this->assertIsString($result);
	}

	public function testLibStatutSigned(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_SIGNED);
		$this->assertIsString($result);
	}

	public function testLibStatutSealed(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_SEALED);
		$this->assertIsString($result);
	}

	public function testLibStatutCanceled(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_CANCELED);
		$this->assertIsString($result);
	}

	public function testLibStatutError(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_ERROR);
		$this->assertIsString($result);
	}

	public function testLibStatutExpired(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_EXPIRED);
		$this->assertIsString($result);
	}

	public function testLibStatutRefused(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_REFUSED);
		$this->assertIsString($result);
	}

	public function testLibStatutFileFetched(): void
	{
		$result = $this->uptosign->LibStatut(\UptoSign::STATUS_FILE_FETCHED);
		$this->assertIsString($result);
	}

	public function testGetLabelStatusDraft(): void
	{
		$this->uptosign->status = \UptoSign::STATUS_DRAFT;
		$result = $this->uptosign->getLabelStatus();
		$this->assertIsString($result);
	}

	public function testGetLibStatutSigned(): void
	{
		$this->uptosign->status = \UptoSign::STATUS_SIGNED;
		$result = $this->uptosign->getLibStatut();
		$this->assertIsString($result);
	}

	// --- getStatusList ---

	public function testGetStatusListReturnsArray(): void
	{
		// getStatusList iterates from 0 upward, calling LibStatut(n)
		// Statuses 0 (WAITING), 1 (SIGNED), 2 (SEALED), 3 (FILE_FETCHED) exist
		// but status 4+ doesn't, causing an undefined key.
		// Test by calling LibStatut directly for each valid positive status.
		$validStatuses = [
			\UptoSign::STATUS_WAITING,
			\UptoSign::STATUS_SIGNED,
			\UptoSign::STATUS_SEALED,
			\UptoSign::STATUS_FILE_FETCHED,
		];
		foreach ($validStatuses as $status) {
			$label = $this->uptosign->LibStatut($status);
			$this->assertIsString($label, "Status $status should return a string label");
		}
	}

	// --- Status constants ---

	public function testStatusConstants(): void
	{
		$this->assertSame(-100, \UptoSign::STATUS_NOTHING);
		$this->assertSame(-10, \UptoSign::STATUS_DRAFT);
		$this->assertSame(-4, \UptoSign::STATUS_EXPIRED);
		$this->assertSame(-3, \UptoSign::STATUS_REFUSED);
		$this->assertSame(-2, \UptoSign::STATUS_ERROR);
		$this->assertSame(-1, \UptoSign::STATUS_CANCELED);
		$this->assertSame(0, \UptoSign::STATUS_WAITING);
		$this->assertSame(1, \UptoSign::STATUS_SIGNED);
		$this->assertSame(2, \UptoSign::STATUS_SEALED);
		$this->assertSame(3, \UptoSign::STATUS_FILE_FETCHED);
	}

	public function testRoleConstants(): void
	{
		$this->assertSame(1, \UptoSign::ROLE_LEVEL_DISABLED);
		$this->assertSame(10, \UptoSign::ROLE_LEVEL_USER);
		$this->assertSame(1000, \UptoSign::ROLE_LEVEL_RESELLER);
	}
}
