<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSignList class
 */
class UptoSignListIntegrationTest extends DolibarrRealTestCase
{
    public function testCreateUptoSignList(): void
    {
        $list = new \UptoSignList($this->db);
        $list->ref = 'LIST-' . uniqid();
        $list->label = 'Test signature list';
        $list->description = 'Test description';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;

        $result = $list->create($this->testUser);

        $this->assertGreaterThan(0, $result, 'Create should return positive ID');
        $this->assertGreaterThan(0, $list->id, 'Object should have ID after create');
        $this->assertDatabaseHas('uptosign_uptosignlist', ['rowid' => $list->id]);
    }

    public function testFetchUptoSignList(): void
    {
        // Create first
        $list = new \UptoSignList($this->db);
        $list->label = 'Test fetch list';
        $list->description = 'Description for fetch test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        $createdId = $list->id;

        // Re-fetch to get actual ref (Dolibarr uses (PROVid) format)
        $list->fetch($createdId);
        $createdRef = $list->ref;

        // Fetch by ID with new instance
        $fetched = new \UptoSignList($this->db);
        $result = $fetched->fetch($createdId);

        $this->assertGreaterThan(0, $result, 'Fetch should return positive value');
        $this->assertEquals($createdId, $fetched->id);
        $this->assertEquals($createdRef, $fetched->ref);
        $this->assertEquals('Test fetch list', $fetched->label);
        $this->assertEquals('Description for fetch test', $fetched->description);
    }

    public function testFetchByRef(): void
    {
        $list = new \UptoSignList($this->db);
        $uniqueRef = 'REFLIST-' . uniqid();
        $list->ref = $uniqueRef;
        $list->label = 'Test fetch by ref';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Fetch by ref
        $fetched = new \UptoSignList($this->db);
        $result = $fetched->fetch(0, $uniqueRef);

        $this->assertGreaterThan(0, $result, 'Fetch by ref should return positive value');
        $this->assertEquals($uniqueRef, $fetched->ref);
    }

    public function testUpdateUptoSignList(): void
    {
        $list = new \UptoSignList($this->db);
        $list->ref = 'UPDATE-' . uniqid();
        $list->label = 'Original label';
        $list->description = 'Original description';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Reload to get all fields
        $list->fetch($list->id);

        // Update
        $list->label = 'Updated label';
        $list->description = 'Updated description';
        $result = $list->update($this->testUser);

        $this->assertGreaterThan(0, $result, 'Update should return positive value');

        // Verify in database
        $verify = new \UptoSignList($this->db);
        $verify->fetch($list->id);

        $this->assertEquals('Updated label', $verify->label);
        $this->assertEquals('Updated description', $verify->description);
    }

    public function testDeleteUptoSignList(): void
    {
        $list = new \UptoSignList($this->db);
        $list->ref = 'DELETE-' . uniqid();
        $list->label = 'To be deleted';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        $createdId = $list->id;

        // Delete
        $result = $list->delete($this->testUser);

        $this->assertGreaterThan(0, $result, 'Delete should return positive value');
        $this->assertDatabaseMissing('uptosign_uptosignlist', ['rowid' => $createdId]);
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals(0, \UptoSignList::STATUS_DRAFT);
        $this->assertEquals(1, \UptoSignList::STATUS_VALIDATED);
        $this->assertEquals(2, \UptoSignList::STATUS_SENTPARTIALY);
        $this->assertEquals(3, \UptoSignList::STATUS_SENTCOMPLETELY);
        $this->assertEquals(9, \UptoSignList::STATUS_CANCELED);
    }

    /**
     * @dataProvider statusProvider
     */
    public function testCreateWithDifferentStatuses(int $status): void
    {
        $list = new \UptoSignList($this->db);
        $list->ref = 'STATUS-' . $status . '-' . uniqid();
        $list->label = 'Test status ' . $status;
        $list->status = $status;
        $list->entity = 1;

        $result = $list->create($this->testUser);

        $this->assertGreaterThan(0, $result, "Create with status $status should succeed");

        // Verify
        $verify = new \UptoSignList($this->db);
        $verify->fetch($list->id);
        $this->assertEquals($status, $verify->status);
    }

    public static function statusProvider(): array
    {
        return [
            'draft' => [\UptoSignList::STATUS_DRAFT],
            'validated' => [\UptoSignList::STATUS_VALIDATED],
            'sent_partially' => [\UptoSignList::STATUS_SENTPARTIALY],
            'sent_completely' => [\UptoSignList::STATUS_SENTCOMPLETELY],
            'canceled' => [\UptoSignList::STATUS_CANCELED],
        ];
    }

    public function testCreateWithNotes(): void
    {
        $list = new \UptoSignList($this->db);
        $list->ref = 'NOTES-' . uniqid();
        $list->label = 'Test with notes';
        $list->note_public = 'This is a public note';
        $list->note_private = 'This is a private note';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;

        $result = $list->create($this->testUser);

        $this->assertGreaterThan(0, $result);

        // Verify notes are stored
        $verify = new \UptoSignList($this->db);
        $verify->fetch($list->id);
        $this->assertEquals('This is a public note', $verify->note_public);
        $this->assertEquals('This is a private note', $verify->note_private);
    }

    public function testCreateMultipleAndCount(): void
    {
        // Create multiple records
        for ($i = 1; $i <= 5; $i++) {
            $list = new \UptoSignList($this->db);
            $list->ref = 'MULTI-' . $i . '-' . uniqid();
            $list->label = 'Test list ' . $i;
            $list->status = \UptoSignList::STATUS_DRAFT;
            $list->entity = 1;
            $list->create($this->testUser);
        }

        // Count should be 5
        $count = $this->getDatabaseCount('uptosign_uptosignlist');
        $this->assertEquals(5, $count);
    }

    public function testValidateWorkflow(): void
    {
        $list = new \UptoSignList($this->db);
        $list->ref = 'WORKFLOW-' . uniqid();
        $list->label = 'Workflow test';
        $list->status = \UptoSignList::STATUS_DRAFT;
        $list->entity = 1;
        $list->create($this->testUser);

        // Reload
        $list->fetch($list->id);

        // Update status to validated
        $list->status = \UptoSignList::STATUS_VALIDATED;
        $result = $list->update($this->testUser);

        $this->assertGreaterThan(0, $result);

        // Verify
        $verify = new \UptoSignList($this->db);
        $verify->fetch($list->id);
        $this->assertEquals(\UptoSignList::STATUS_VALIDATED, $verify->status);
    }
}
