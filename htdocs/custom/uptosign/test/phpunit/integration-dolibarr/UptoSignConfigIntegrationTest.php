<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSignConfig class
 */
class UptoSignConfigIntegrationTest extends DolibarrRealTestCase
{
    public function testCreateUptoSignConfig(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        $config->seal_coordinate = '100;100';
        $config->page_seal = 1;
        $config->sign_coordinate = '150;200';
        $config->page_sign = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;

        $result = $config->create($this->testUser);

        $this->assertGreaterThan(0, $result, 'Create should return positive ID');
        $this->assertGreaterThan(0, $config->id, 'Object should have ID after create');
        $this->assertDatabaseHas('uptosign_uptosignconfig', ['rowid' => $config->id]);
    }

    public function testFetchUptoSignConfig(): void
    {
        // Create first
        $config = new \UptoSignConfig($this->db);
        $config->label = 'VendorSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'commande:einstein';
        $config->seal_coordinate = '50;50';
        $config->page_seal = 1;
        $config->sign_coordinate = '100;150';
        $config->page_sign = 2;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;
        $config->create($this->testUser);

        $createdId = $config->id;

        // Fetch by ID
        $fetched = new \UptoSignConfig($this->db);
        $result = $fetched->fetch($createdId);

        $this->assertGreaterThan(0, $result, 'Fetch should return positive value');
        $this->assertEquals($createdId, $fetched->id);
        $this->assertEquals('VendorSign', $fetched->label);
        $this->assertEquals('sign', $fetched->sign_or_seal);
        $this->assertEquals('commande:einstein', $fetched->model_pdf);
        $this->assertEquals('100;150', $fetched->sign_coordinate);
        $this->assertEquals(2, $fetched->page_sign);
    }

    public function testUpdateUptoSignConfig(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        $config->seal_coordinate = '100;100';
        $config->page_seal = 1;
        $config->sign_coordinate = '150;200';
        $config->page_sign = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;
        $config->create($this->testUser);

        // Reload to get all fields
        $config->fetch($config->id);

        // Update coordinates
        $config->sign_coordinate = '200;300';
        $config->page_sign = 3;
        $result = $config->update($this->testUser);

        $this->assertGreaterThan(0, $result, 'Update should return positive value');

        // Verify in database
        $verify = new \UptoSignConfig($this->db);
        $verify->fetch($config->id);

        $this->assertEquals('200;300', $verify->sign_coordinate);
        $this->assertEquals(3, $verify->page_sign);
    }

    public function testDeleteUptoSignConfig(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'DocumentSeal';
        $config->sign_or_seal = 'seal';
        $config->model_pdf = 'facture:crabe';
        $config->seal_coordinate = '75;75';
        $config->page_seal = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;
        $config->create($this->testUser);

        $createdId = $config->id;

        // Delete
        $result = $config->delete($this->testUser);

        $this->assertGreaterThan(0, $result, 'Delete should return positive value');
        $this->assertDatabaseMissing('uptosign_uptosignconfig', ['rowid' => $createdId]);
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals(-1, \UptoSignConfig::STATUS_DISABLED);
        $this->assertEquals(0, \UptoSignConfig::STATUS_DRAFT);
        $this->assertEquals(1, \UptoSignConfig::STATUS_VALIDATED);
        $this->assertEquals(9, \UptoSignConfig::STATUS_CANCELED);
    }

    public function testCreateSignConfig(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        $config->seal_coordinate = '0;0';
        $config->page_seal = 1;
        $config->sign_coordinate = '150;200';
        $config->page_sign = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;

        $result = $config->create($this->testUser);

        $this->assertGreaterThan(0, $result);

        // Verify sign_or_seal is correctly stored
        $verify = new \UptoSignConfig($this->db);
        $verify->fetch($config->id);
        $this->assertEquals('sign', $verify->sign_or_seal);
    }

    public function testCreateSealConfig(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'DocumentSeal';
        $config->sign_or_seal = 'seal';
        $config->model_pdf = 'facture:sponge';
        $config->seal_coordinate = '400;700';
        $config->page_seal = 2;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;

        $result = $config->create($this->testUser);

        $this->assertGreaterThan(0, $result);

        // Verify
        $verify = new \UptoSignConfig($this->db);
        $verify->fetch($config->id);
        $this->assertEquals('seal', $verify->sign_or_seal);
        $this->assertEquals('400;700', $verify->seal_coordinate);
        $this->assertEquals(2, $verify->page_seal);
    }

    /**
     * @dataProvider labelProvider
     */
    public function testCreateWithDifferentLabels(string $label): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = $label;
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        $config->seal_coordinate = '100;100';
        $config->page_seal = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;

        $result = $config->create($this->testUser);

        $this->assertGreaterThan(0, $result, "Create with label '$label' should succeed");

        // Verify
        $verify = new \UptoSignConfig($this->db);
        $verify->fetch($config->id);
        $this->assertEquals($label, $verify->label);
    }

    public static function labelProvider(): array
    {
        return [
            'VendorSign' => ['VendorSign'],
            'CustomerSign' => ['CustomerSign'],
            'DocumentSeal' => ['DocumentSeal'],
        ];
    }

    /**
     * @dataProvider modelPdfProvider
     */
    public function testCreateWithDifferentModelPdf(string $modelPdf): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = $modelPdf;
        $config->seal_coordinate = '100;100';
        $config->page_seal = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;

        $result = $config->create($this->testUser);

        $this->assertGreaterThan(0, $result, "Create with model_pdf '$modelPdf' should succeed");

        // Verify
        $verify = new \UptoSignConfig($this->db);
        $verify->fetch($config->id);
        $this->assertEquals($modelPdf, $verify->model_pdf);
    }

    public static function modelPdfProvider(): array
    {
        return [
            'propal_azur' => ['propal:azur'],
            'commande_einstein' => ['commande:einstein'],
            'facture_crabe' => ['facture:crabe'],
            'contrat_strato' => ['contrat:strato'],
            'fichinter_soleil' => ['fichinter:soleil'],
        ];
    }

    public function testCreateMultipleConfigs(): void
    {
        $modelTypes = ['propal', 'commande', 'facture'];

        foreach ($modelTypes as $type) {
            $config = new \UptoSignConfig($this->db);
            $config->label = 'CustomerSign';
            $config->sign_or_seal = 'sign';
            $config->model_pdf = $type . ':standard';
            $config->seal_coordinate = '100;100';
            $config->page_seal = 1;
            $config->status = \UptoSignConfig::STATUS_VALIDATED;
            $config->entity = 1;

            $result = $config->create($this->testUser);
            $this->assertGreaterThan(0, $result);
        }

        // Count should be 3
        $count = $this->getDatabaseCount('uptosign_uptosignconfig');
        $this->assertEquals(3, $count);
    }

    public function testCoordinateFormats(): void
    {
        $coordinates = [
            '0;0',
            '100;200',
            '500;700',
            '50.5;100.25',  // decimal coordinates
        ];

        foreach ($coordinates as $coord) {
            $config = new \UptoSignConfig($this->db);
            $config->label = 'CustomerSign';
            $config->sign_or_seal = 'sign';
            $config->model_pdf = 'propal:test';
            $config->seal_coordinate = $coord;
            $config->page_seal = 1;
            $config->sign_coordinate = $coord;
            $config->page_sign = 1;
            $config->status = \UptoSignConfig::STATUS_VALIDATED;
            $config->entity = 1;

            $result = $config->create($this->testUser);
            $this->assertGreaterThan(0, $result, "Create with coordinate '$coord' should succeed");

            // Verify
            $verify = new \UptoSignConfig($this->db);
            $verify->fetch($config->id);
            $this->assertEquals($coord, $verify->seal_coordinate);
            $this->assertEquals($coord, $verify->sign_coordinate);

            // Clean up for next iteration
            $config->delete($this->testUser);
        }
    }
}
