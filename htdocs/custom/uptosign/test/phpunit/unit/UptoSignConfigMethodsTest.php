<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UptoSignConfig class methods that don't require DB access:
 * getDocumentModelDetails, LibStatut
 */
class UptoSignConfigMethodsTest extends TestCase
{
	/** @var \UptoSignConfig */
	private $config;

	/** @var object Saved $langs */
	private $savedLangs;

	protected function setUp(): void
	{
		global $langs;
		$this->savedLangs = $langs;

		$langs = new class {
			public function trans($key, ...$args) { return $key; }
			public function transnoentitiesnoconv($key, ...$args) { return $key; }
			public function load($module) {}
		};

		$ref = new \ReflectionClass(\UptoSignConfig::class);
		$this->config = $ref->newInstanceWithoutConstructor();
	}

	protected function tearDown(): void
	{
		global $langs;
		$langs = $this->savedLangs;
	}

	// --- getDocumentModelDetails ---

	public function testGetDocumentModelDetailsWithColon(): void
	{
		$result = $this->config->getDocumentModelDetails('propal:azur');
		$this->assertSame('propal', $result['type']);
		$this->assertSame('azur', $result['nom']);
	}

	public function testGetDocumentModelDetailsWithoutColon(): void
	{
		$result = $this->config->getDocumentModelDetails('azur');
		$this->assertSame('undef', $result['type']);
		$this->assertSame('azur', $result['nom']);
	}

	public function testGetDocumentModelDetailsWithComplexString(): void
	{
		$result = $this->config->getDocumentModelDetails('facture:crabe');
		$this->assertSame('facture', $result['type']);
		$this->assertSame('crabe', $result['nom']);
	}

	public function testGetDocumentModelDetailsEmptyString(): void
	{
		$result = $this->config->getDocumentModelDetails('');
		$this->assertSame('undef', $result['type']);
		$this->assertSame('', $result['nom']);
	}

	public function testGetDocumentModelDetailsContrat(): void
	{
		$result = $this->config->getDocumentModelDetails('contrat:strato');
		$this->assertSame('contrat', $result['type']);
		$this->assertSame('strato', $result['nom']);
	}

	// --- LibStatut ---

	public function testLibStatutDraft(): void
	{
		$result = $this->config->LibStatut(\UptoSignConfig::STATUS_DRAFT);
		$this->assertIsString($result);
	}

	public function testLibStatutValidated(): void
	{
		$result = $this->config->LibStatut(\UptoSignConfig::STATUS_VALIDATED);
		$this->assertIsString($result);
	}

	public function testLibStatutCanceled(): void
	{
		$result = $this->config->LibStatut(\UptoSignConfig::STATUS_CANCELED);
		$this->assertIsString($result);
	}

	public function testLibStatutDisabled(): void
	{
		$result = $this->config->LibStatut(\UptoSignConfig::STATUS_DISABLED);
		$this->assertIsString($result);
	}

	public function testGetLabelStatusDraft(): void
	{
		$this->config->status = \UptoSignConfig::STATUS_DRAFT;
		$result = $this->config->getLabelStatus();
		$this->assertIsString($result);
	}

	public function testGetLibStatutValidated(): void
	{
		$this->config->status = \UptoSignConfig::STATUS_VALIDATED;
		$result = $this->config->getLibStatut();
		$this->assertIsString($result);
	}

	// --- Status constants ---

	public function testStatusConstants(): void
	{
		$this->assertSame(-1, \UptoSignConfig::STATUS_DISABLED);
		$this->assertSame(0, \UptoSignConfig::STATUS_DRAFT);
		$this->assertSame(1, \UptoSignConfig::STATUS_VALIDATED);
		$this->assertSame(9, \UptoSignConfig::STATUS_CANCELED);
	}
}
