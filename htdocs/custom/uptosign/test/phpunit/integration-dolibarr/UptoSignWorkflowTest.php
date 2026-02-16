<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for workflow methods (validate, setDraft, cancel)
 * and search methods (fetchWhere*, fetchAll, createFromClone)
 */
class UptoSignWorkflowTest extends DolibarrRealTestCase
{
    // ============================================
    // UptoSignList workflow tests
    // ============================================

    public function testUptoSignListValidateFromDraft(): void
    {
        $list = new \UptoSignList($this->db);
        $list->label = 'Validate test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        $this->assertEquals(\UptoSignList::STATUS_DRAFT, $list->status);

        // Validate
        $result = $list->validate($this->testUser);

        $this->assertGreaterThanOrEqual(0, $result, 'Validate should succeed');

        // Reload and check
        $list->fetch($list->id);
        $this->assertEquals(\UptoSignList::STATUS_VALIDATED, $list->status);
    }

    public function testUptoSignListValidateAlreadyValidated(): void
    {
        $list = new \UptoSignList($this->db);
        $list->label = 'Already validated test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Validate first time
        $list->validate($this->testUser);
        $list->fetch($list->id);

        // Try to validate again
        $result = $list->validate($this->testUser);

        $this->assertEquals(0, $result, 'Validate on already validated should return 0');
    }

    public function testUptoSignListSetDraftFromValidated(): void
    {
        $list = new \UptoSignList($this->db);
        $list->label = 'SetDraft test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Validate first
        $list->validate($this->testUser);
        $list->fetch($list->id);
        $this->assertEquals(\UptoSignList::STATUS_VALIDATED, $list->status);

        // Set back to draft
        $result = $list->setDraft($this->testUser);

        $this->assertGreaterThanOrEqual(0, $result);

        // Reload and check
        $list->fetch($list->id);
        $this->assertEquals(\UptoSignList::STATUS_DRAFT, $list->status);
    }

    public function testUptoSignListSetDraftAlreadyDraft(): void
    {
        $list = new \UptoSignList($this->db);
        $list->label = 'Already draft test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Try to set draft when already draft
        $result = $list->setDraft($this->testUser);

        $this->assertEquals(0, $result, 'setDraft on draft should return 0');
    }

    public function testUptoSignListCancelFromValidated(): void
    {
        $list = new \UptoSignList($this->db);
        $list->label = 'Cancel test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Validate first
        $list->validate($this->testUser);
        $list->fetch($list->id);

        // Cancel
        $result = $list->cancel($this->testUser);

        $this->assertGreaterThanOrEqual(0, $result);

        // Reload and check
        $list->fetch($list->id);
        $this->assertEquals(\UptoSignList::STATUS_CANCELED, $list->status);
    }

    public function testUptoSignListCancelFromDraft(): void
    {
        $list = new \UptoSignList($this->db);
        $list->label = 'Cancel from draft test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Try to cancel from draft (not allowed)
        $result = $list->cancel($this->testUser);

        $this->assertEquals(0, $result, 'Cancel from draft should return 0');
    }

    // ============================================
    // UptoSignConfig workflow tests
    // ============================================

    public function testUptoSignConfigValidate(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        // Note: validate() requires comma-separated coordinates (regex: /^[0-9]*,[0-9]*/)
        $config->seal_coordinate = '100,100';
        $config->page_seal = 1;
        $config->status = \UptoSignConfig::STATUS_DRAFT;
        $config->entity = 1;
        $config->create($this->testUser);

        $config->fetch($config->id);

        // Validate
        $result = $config->validate($this->testUser);

        $this->assertNotFalse($result, 'Validate should succeed with valid coordinates');

        // Reload and check
        $config->fetch($config->id);
        $this->assertEquals(\UptoSignConfig::STATUS_VALIDATED, $config->status);
    }

    public function testUptoSignConfigSetDraft(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        $config->seal_coordinate = '100;100';
        $config->page_seal = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;
        $config->create($this->testUser);

        $config->fetch($config->id);

        // Set to draft
        $result = $config->setDraft($this->testUser);

        $this->assertGreaterThanOrEqual(0, $result);

        // Reload and check
        $config->fetch($config->id);
        $this->assertEquals(\UptoSignConfig::STATUS_DRAFT, $config->status);
    }

    public function testUptoSignConfigCancel(): void
    {
        $config = new \UptoSignConfig($this->db);
        $config->label = 'CustomerSign';
        $config->sign_or_seal = 'sign';
        $config->model_pdf = 'propal:azur';
        $config->seal_coordinate = '100;100';
        $config->page_seal = 1;
        $config->status = \UptoSignConfig::STATUS_VALIDATED;
        $config->entity = 1;
        $config->create($this->testUser);

        $config->fetch($config->id);

        // Cancel
        $result = $config->cancel($this->testUser);

        $this->assertGreaterThanOrEqual(0, $result);

        // Reload and check
        $config->fetch($config->id);
        $this->assertEquals(\UptoSignConfig::STATUS_CANCELED, $config->status);
    }

    // ============================================
    // FetchAll tests
    // ============================================

    public function testUptoSignFetchAll(): void
    {
        $soc = $this->createTestSociete();

        // Create multiple records
        for ($i = 1; $i <= 3; $i++) {
            $uptosign = new \UptoSign($this->db);
            $uptosign->ref = 'FETCHALL-' . $i . '-' . uniqid();
            $uptosign->label = 'FetchAll test ' . $i;
            $uptosign->fk_soc = $soc->id;
            $uptosign->status = \UptoSign::STATUS_WAITING;
            $uptosign->entity = 1;
            $uptosign->object_type = 'propal';
            $uptosign->fk_object = $i;
            $uptosign->create($this->testUser);
        }

        // FetchAll
        $uptosign = new \UptoSign($this->db);
        $result = $uptosign->fetchAll();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testUptoSignListFetchAll(): void
    {
        // Create multiple lists
        for ($i = 1; $i <= 3; $i++) {
            $list = new \UptoSignList($this->db);
            $list->label = 'FetchAll list ' . $i;
            $list->status = \UptoSignList::STATUS_DRAFT;
            $list->entity = 1;
            $list->create($this->testUser);
        }

        // FetchAll
        $list = new \UptoSignList($this->db);
        $result = $list->fetchAll();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testUptoSignConfigFetchAll(): void
    {
        // Create multiple configs
        for ($i = 1; $i <= 3; $i++) {
            $config = new \UptoSignConfig($this->db);
            $config->label = 'CustomerSign';
            $config->sign_or_seal = 'sign';
            $config->model_pdf = 'propal:model' . $i;
            $config->seal_coordinate = '100;100';
            $config->page_seal = 1;
            $config->status = \UptoSignConfig::STATUS_VALIDATED;
            $config->entity = 1;
            $config->create($this->testUser);
        }

        // FetchAll
        $config = new \UptoSignConfig($this->db);
        $result = $config->fetchAll();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    // ============================================
    // FetchWhere tests (UptoSign specific)
    // ============================================

    public function testUptoSignFetchWhere(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'FETCHWHERE-' . uniqid();
        $uptosign->label = 'FetchWhere test';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 999;
        $uptosign->create($this->testUser);

        $createdId = $uptosign->id;

        // FetchWhere by fk_object
        $fetched = new \UptoSign($this->db);
        $result = $fetched->fetchWhere('fk_object', 999);

        $this->assertGreaterThan(0, $result);
        $this->assertEquals($createdId, $fetched->id);
    }

    public function testUptoSignFetchWhereNotFound(): void
    {
        $uptosign = new \UptoSign($this->db);
        $result = $uptosign->fetchWhere('fk_object', 999999);

        $this->assertEquals(0, $result, 'FetchWhere with no match should return 0');
    }

    // ============================================
    // CreateFromClone tests
    // ============================================

    public function testUptoSignListCreateFromClone(): void
    {
        // Create original
        $original = new \UptoSignList($this->db);
        $original->label = 'Original list';
        $original->description = 'Original description';
        $original->note_public = 'Public note';
        $original->note_private = 'Private note';
        $original->status = \UptoSignList::STATUS_DRAFT;
        $original->entity = 1;
        $original->create($this->testUser);

        $originalId = $original->id;

        // Clone (returns object on success, -1 on failure)
        $cloner = new \UptoSignList($this->db);
        $result = $cloner->createFromClone($this->testUser, $originalId);

        $this->assertIsObject($result, 'Clone should return an object');
        $this->assertInstanceOf(\UptoSignList::class, $result);
        $this->assertNotEquals($originalId, $result->id, 'Clone ID should be different');
        $this->assertEquals(\UptoSignList::STATUS_DRAFT, $result->status);
    }

    public function testUptoSignConfigCreateFromClone(): void
    {
        // Create original
        $original = new \UptoSignConfig($this->db);
        $original->label = 'CustomerSign';
        $original->sign_or_seal = 'sign';
        $original->model_pdf = 'propal:azur';
        $original->seal_coordinate = '100;100';
        $original->page_seal = 1;
        $original->sign_coordinate = '200;200';
        $original->page_sign = 2;
        $original->status = \UptoSignConfig::STATUS_VALIDATED;
        $original->entity = 1;
        $original->create($this->testUser);

        $originalId = $original->id;

        // Clone (returns object on success, -1 on failure)
        $cloner = new \UptoSignConfig($this->db);
        $result = $cloner->createFromClone($this->testUser, $originalId);

        $this->assertIsObject($result, 'Clone should return an object');
        $this->assertInstanceOf(\UptoSignConfig::class, $result);
        $this->assertNotEquals($originalId, $result->id, 'Clone ID should be different');
        // Label is prefixed with "Copy of" or translated equivalent
        $this->assertStringContainsString('CustomerSign', $result->label);
        $this->assertEquals('sign', $result->sign_or_seal);
    }

    // ============================================
    // Basic signInit validation tests
    // ============================================

    public function testUptoSignSignInitWithInvalidObject(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'SIGNINIT-' . uniqid();
        $uptosign->label = 'SignInit test';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->create($this->testUser);

        // Call signInit with non-object (should return -1)
        $result = $uptosign->signInit($this->testUser, "not an object", "/tmp");

        $this->assertEquals(-1, $result, 'signInit with non-object should return -1');
        $this->assertNotEmpty($uptosign->errors);
    }

    public function testUptoSignSignInitWithNoFile(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'SIGNINIT-' . uniqid();
        $uptosign->label = 'SignInit test';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->create($this->testUser);

        // Create a mock object
        $mockObject = new \stdClass();
        $mockObject->ref = 'TEST-REF';

        // Call signInit with empty directory (should return -3)
        $result = $uptosign->signInit($this->testUser, $mockObject, "/tmp/nonexistent-dir-" . uniqid());

        $this->assertEquals(-3, $result, 'signInit with no PDF file should return -3');
        $this->assertContains('UptoSignNoPdfFilesAssociated', $uptosign->errors);
    }
}
