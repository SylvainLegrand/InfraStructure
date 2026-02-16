<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSignSignatoryResolver:
 * getTypeContactCode, getSourceContactCode
 */
class UptoSignSignatoryResolverTest extends DolibarrRealTestCase
{
	/** @var \UptoSignSignatoryResolver */
	private $resolver;

	protected function setUp(): void
	{
		parent::setUp();
		$this->resolver = new \UptoSignSignatoryResolver($this->db);
	}

	// ============================================
	// getTypeContactCode
	// ============================================

	public function testGetTypeContactCodeForPropal(): void
	{
		$result = $this->resolver->getTypeContactCode('propal', 'external');

		$this->assertIsArray($result);
		// Dolibarr should have standard contact types for propal
		$this->assertNotEmpty($result, 'propal should have external contact types');

		// Each entry should have id, code, source keys
		foreach ($result as $id => $entry) {
			$this->assertArrayHasKey('id', $entry);
			$this->assertArrayHasKey('code', $entry);
			$this->assertArrayHasKey('source', $entry);
			$this->assertEquals('external', $entry['source']);
		}
	}

	public function testGetTypeContactCodeForPropalInternal(): void
	{
		$result = $this->resolver->getTypeContactCode('propal', 'internal');

		$this->assertIsArray($result);
		foreach ($result as $entry) {
			$this->assertEquals('internal', $entry['source']);
		}
	}

	public function testGetTypeContactCodeForPropalAll(): void
	{
		$result = $this->resolver->getTypeContactCode('propal', 'all');

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);
	}

	public function testGetTypeContactCodeForFacture(): void
	{
		$result = $this->resolver->getTypeContactCode('facture', 'external');

		$this->assertIsArray($result);
	}

	public function testGetTypeContactCodeForCommande(): void
	{
		$result = $this->resolver->getTypeContactCode('commande', 'external');

		$this->assertIsArray($result);
	}

	public function testGetTypeContactCodeShippingMapsToCommande(): void
	{
		$shipping = $this->resolver->getTypeContactCode('shipping', 'external');
		$expedition = $this->resolver->getTypeContactCode('expedition', 'external');
		$commande = $this->resolver->getTypeContactCode('commande', 'external');

		// shipping and expedition should both map to commande
		$this->assertEquals($commande, $shipping);
		$this->assertEquals($commande, $expedition);
	}

	public function testGetTypeContactCodeWithFilter(): void
	{
		$result = $this->resolver->getTypeContactCode('propal', 'external', 'position', ['code' => 'CUSTOMER']);

		$this->assertIsArray($result);
		foreach ($result as $entry) {
			$this->assertEquals('CUSTOMER', $entry['code']);
		}
	}

	public function testGetTypeContactCodeUnknownElement(): void
	{
		$result = $this->resolver->getTypeContactCode('nonexistent_element', 'external');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	// ============================================
	// getSourceContactCode
	// ============================================

	public function testGetSourceContactCodeForPropal(): void
	{
		$result = $this->resolver->getSourceContactCode('propal');

		$this->assertIsArray($result);
		$this->assertNotEmpty($result);

		// Should contain 'internal' and/or 'external'
		$sources = array_values($result);
		foreach ($sources as $source) {
			$this->assertContains($source, ['internal', 'external']);
		}
	}

	public function testGetSourceContactCodeForFacture(): void
	{
		$result = $this->resolver->getSourceContactCode('facture');

		$this->assertIsArray($result);
	}

	public function testGetSourceContactCodeEmptyElement(): void
	{
		$result = $this->resolver->getSourceContactCode('');

		$this->assertNull($result);
	}

	public function testGetSourceContactCodeShippingMapsToCommande(): void
	{
		$shipping = $this->resolver->getSourceContactCode('shipping');
		$commande = $this->resolver->getSourceContactCode('commande');

		$this->assertEquals($commande, $shipping);
	}

	public function testGetSourceContactCodeUnknownElement(): void
	{
		$result = $this->resolver->getSourceContactCode('nonexistent_element');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}
}
