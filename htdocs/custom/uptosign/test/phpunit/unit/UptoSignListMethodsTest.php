<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UptoSignList class methods that don't require DB access:
 * LibStatut, getLabelStatus, getLibStatut, status constants
 */
class UptoSignListMethodsTest extends TestCase
{
	/** @var \UptoSignList */
	private $list;

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

		$ref = new \ReflectionClass(\UptoSignList::class);
		$this->list = $ref->newInstanceWithoutConstructor();
	}

	protected function tearDown(): void
	{
		global $langs;
		$langs = $this->savedLangs;
	}

	// --- LibStatut ---

	public function testLibStatutDraft(): void
	{
		$result = $this->list->LibStatut(\UptoSignList::STATUS_DRAFT);
		$this->assertIsString($result);
	}

	public function testLibStatutValidated(): void
	{
		$result = $this->list->LibStatut(\UptoSignList::STATUS_VALIDATED);
		$this->assertIsString($result);
	}

	public function testLibStatutCanceled(): void
	{
		$result = $this->list->LibStatut(\UptoSignList::STATUS_CANCELED);
		$this->assertIsString($result);
	}

	public function testGetLabelStatusDraft(): void
	{
		$this->list->status = \UptoSignList::STATUS_DRAFT;
		$result = $this->list->getLabelStatus();
		$this->assertIsString($result);
	}

	public function testGetLibStatutValidated(): void
	{
		$this->list->status = \UptoSignList::STATUS_VALIDATED;
		$result = $this->list->getLibStatut();
		$this->assertIsString($result);
	}

	// --- Status constants ---

	public function testStatusConstants(): void
	{
		$this->assertSame(0, \UptoSignList::STATUS_DRAFT);
		$this->assertSame(1, \UptoSignList::STATUS_VALIDATED);
		$this->assertSame(9, \UptoSignList::STATUS_CANCELED);
	}
}
