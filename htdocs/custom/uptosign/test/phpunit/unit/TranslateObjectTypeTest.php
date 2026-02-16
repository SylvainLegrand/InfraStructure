<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Helper class to track loadLangs calls
 */
class LangsLoadTracker
{
	private $tracker;

	public function __construct(\stdClass $tracker)
	{
		$this->tracker = $tracker;
	}

	public function trans($key, ...$args)
	{
		return $key;
	}

	public function loadLangs($array)
	{
		$this->tracker->loaded = array_merge($this->tracker->loaded, $array);
	}
}

/**
 * Unit tests for uptosign_translate_object_type function
 */
class TranslateObjectTypeTest extends TestCase
{
	/** @var object Original $langs to restore after tests */
	private $savedLangs;

	/** @var object Mock $langs */
	private $mockLangs;

	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosign_translate_object_type')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	protected function setUp(): void
	{
		global $langs;
		$this->savedLangs = $langs;
		$this->mockLangs = new class {
			public function trans($key, ...$args)
			{
				return $key;
			}
			public function loadLangs($array)
			{
				// no-op for tests
			}
		};
		$langs = $this->mockLangs;
	}

	protected function tearDown(): void
	{
		global $langs;
		$langs = $this->savedLangs;
	}

	/**
	 * @dataProvider objectTypeTranslationProvider
	 */
	public function testTranslateObjectType(string $code, string $expectedTransKey): void
	{
		$result = uptosign_translate_object_type($code);
		$this->assertSame($expectedTransKey, $result);
	}

	public function objectTypeTranslationProvider(): array
	{
		return [
			'agenda' => ['agenda', 'Agenda'],
			'bankaccount' => ['bankaccount', 'BankAccount'],
			'contrat' => ['contrat', 'Contract'],
			'contract' => ['contract', 'Contract'],
			'delivery' => ['delivery', 'Delivery'],
			'expensereport' => ['expensereport', 'ExpenseReport'],
			'propal' => ['propal', 'Proposal'],
			'order' => ['order', 'Order'],
			'ficheinter' => ['ficheinter', 'InterventionCard'],
			'shipping' => ['shipping', 'Expedition'],
			'expedition' => ['expedition', 'Expedition'],
			'invoice' => ['invoice', 'Bill'],
			'facture' => ['facture', 'Bill'],
			'societe' => ['societe', 'ThirdParty'],
			'invoice_supplier' => ['invoice_supplier', 'SupplierBill'],
			'mouvement' => ['mouvement', 'Mouvement'],
			'order_supplier' => ['order_supplier', 'SupplierOrder'],
			'product' => ['product', 'Product'],
			'project' => ['project', 'Project'],
			'project_task' => ['project_task', 'Task'],
			'reception' => ['reception', 'Reception'],
			'resource' => ['resource', 'Resource'],
			'stock' => ['stock', 'Stock'],
			'bank_account' => ['bank_account', 'RIB'],
		];
	}

	public function testTranslateObjectTypeUnknownReturnsCode(): void
	{
		$result = uptosign_translate_object_type('my_custom_element');
		$this->assertSame('my_custom_element', $result);
	}

	public function testTranslateObjectTypeEmpty(): void
	{
		$result = uptosign_translate_object_type('');
		$this->assertSame('', $result);
	}

	public function testTranslateInvoiceLoadsLangs(): void
	{
		global $langs;
		$tracker = new \stdClass();
		$tracker->loaded = [];
		$langs = new LangsLoadTracker($tracker);

		uptosign_translate_object_type('facture');
		$this->assertContains('bills', $tracker->loaded);
	}

	public function testTranslateDeliveryLoadsLangs(): void
	{
		global $langs;
		$tracker = new \stdClass();
		$tracker->loaded = [];
		$langs = new LangsLoadTracker($tracker);

		uptosign_translate_object_type('delivery');
		$this->assertContains('deliveries', $tracker->loaded);
	}
}
