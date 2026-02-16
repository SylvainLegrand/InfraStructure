<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSignConfig: fetchListId, reopen, getTypeContactLabel
 */
class UptoSignConfigExtrasTest extends DolibarrRealTestCase
{
	/**
	 * Create a validated UptoSignConfig record.
	 */
	private function createValidatedConfig(array $data = []): \UptoSignConfig
	{
		$config = new \UptoSignConfig($this->db);
		$config->label = $data['label'] ?? 'Config test ' . uniqid();
		$config->model_pdf = $data['model_pdf'] ?? 'propal:azur';
		$config->sign_or_seal = $data['sign_or_seal'] ?? 'sign';
		$config->sign_coordinate = $data['sign_coordinate'] ?? '100,200';
		$config->page_sign = $data['page_sign'] ?? 1;
		$config->seal_coordinate = $data['seal_coordinate'] ?? '100,200';
		$config->page_seal = $data['page_seal'] ?? 1;
		$config->entity = 1;
		$config->status = \UptoSignConfig::STATUS_DRAFT;
		$config->create($this->testUser);

		$this->assertGreaterThan(0, $config->id, 'Config record should be created');

		// Validate
		$config->fetch($config->id);
		$config->validate($this->testUser);
		$config->fetch($config->id);

		$this->assertEquals(\UptoSignConfig::STATUS_VALIDATED, $config->status);

		return $config;
	}

	// ================================================================
	// fetchListId
	// ================================================================

	public function testFetchListIdReturnsIdsForValidConfig(): void
	{
		$this->createValidatedConfig(['model_pdf' => 'propal:azur', 'sign_or_seal' => 'sign']);
		$this->createValidatedConfig(['model_pdf' => 'propal:azur', 'sign_or_seal' => 'sign', 'label' => 'Second']);

		$config = new \UptoSignConfig($this->db);
		$result = $config->fetchListId('azur', 'propal', 'sign');

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
	}

	public function testFetchListIdFiltersBySignOrSeal(): void
	{
		$this->createValidatedConfig(['model_pdf' => 'propal:azur', 'sign_or_seal' => 'sign']);
		$this->createValidatedConfig(['model_pdf' => 'propal:azur', 'sign_or_seal' => 'seal']);

		$config = new \UptoSignConfig($this->db);
		$result = $config->fetchListId('azur', 'propal', 'seal');

		$this->assertIsArray($result);
		$this->assertCount(1, $result);
	}

	public function testFetchListIdReturnsMinusOneWhenNoConfig(): void
	{
		$config = new \UptoSignConfig($this->db);
		$result = $config->fetchListId('nonexistent', 'propal', 'sign');

		$this->assertEquals(-1, $result);
		$this->assertNotEmpty($config->errors);
	}

	public function testFetchListIdIgnoresDraftConfigs(): void
	{
		$config = new \UptoSignConfig($this->db);
		$config->label = 'Draft config';
		$config->model_pdf = 'propal:draft_model';
		$config->sign_or_seal = 'sign';
		$config->sign_coordinate = '100,200';
		$config->page_sign = 1;
		$config->seal_coordinate = '100,200';
		$config->page_seal = 1;
		$config->entity = 1;
		$config->status = \UptoSignConfig::STATUS_DRAFT;
		$config->create($this->testUser);

		$lookup = new \UptoSignConfig($this->db);
		$result = $lookup->fetchListId('draft_model', 'propal', 'sign');

		$this->assertEquals(-1, $result, 'Draft configs should not be returned');
	}

	public function testFetchListIdIgnoresDisabledConfigs(): void
	{
		$config = $this->createValidatedConfig([
			'model_pdf' => 'propal:disabled_model',
			'sign_or_seal' => 'sign',
		]);

		// Cancel = disabled in this context
		$config->fetch($config->id);
		$config->cancel($this->testUser);

		$lookup = new \UptoSignConfig($this->db);
		$result = $lookup->fetchListId('disabled_model', 'propal', 'sign');

		$this->assertEquals(-1, $result, 'Canceled/disabled configs should not be returned');
	}

	// ================================================================
	// reopen
	// ================================================================

	public function testReopenFromCanceled(): void
	{
		$config = $this->createValidatedConfig();

		$config->fetch($config->id);
		$config->cancel($this->testUser);
		$config->fetch($config->id);
		$this->assertEquals(\UptoSignConfig::STATUS_CANCELED, $config->status);

		$result = $config->reopen($this->testUser);
		$this->assertGreaterThanOrEqual(0, $result);

		$config->fetch($config->id);
		$this->assertEquals(\UptoSignConfig::STATUS_VALIDATED, $config->status);
	}

	public function testReopenFromValidatedReturnsZero(): void
	{
		$config = $this->createValidatedConfig();

		$config->fetch($config->id);
		$result = $config->reopen($this->testUser);

		$this->assertEquals(0, $result, 'Reopen on non-canceled should return 0');
	}

	// ================================================================
	// getTypeContactLabel
	// ================================================================

	public function testGetTypeContactLabelForPropal(): void
	{
		$config = new \UptoSignConfig($this->db);
		$result = $config->getTypeContactLabel('propal', 'external');

		$this->assertIsArray($result);
		$this->assertNotEmpty($result, 'Propal should have external contact types');
	}

	public function testGetTypeContactLabelUnknownElement(): void
	{
		$config = new \UptoSignConfig($this->db);
		$result = $config->getTypeContactLabel('nonexistent_element', 'external');

		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testGetTypeContactLabelShippingMapsToCommande(): void
	{
		$config = new \UptoSignConfig($this->db);
		$shippingResult = $config->getTypeContactLabel('shipping', 'external');
		$commandeResult = $config->getTypeContactLabel('commande', 'external');

		$this->assertEquals($shippingResult, $commandeResult, 'shipping should map to commande');
	}
}
