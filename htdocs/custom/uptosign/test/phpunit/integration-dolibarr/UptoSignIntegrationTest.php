<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Integration tests for UptoSign class
 */
class UptoSignIntegrationTest extends DolibarrRealTestCase
{
    public function testDolibarrInitialized(): void
    {
        $this->assertNotNull($this->db, 'Database should be initialized');
        $this->assertNotNull($this->testUser, 'User should be initialized');
        $this->assertGreaterThan(0, $this->testUser->id, 'User should have valid ID');
    }

    public function testCreateUptoSign(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'TEST-' . uniqid();
        $uptosign->label = 'Test signature';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;

        $result = $uptosign->create($this->testUser);

        $this->assertGreaterThan(0, $result, 'Create should return positive ID');
        $this->assertGreaterThan(0, $uptosign->id, 'Object should have ID after create');
        $this->assertDatabaseHas('uptosign', ['rowid' => $uptosign->id]);
    }

    public function testFetchUptoSign(): void
    {
        // Create first
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'FETCH-' . uniqid();
        $uptosign->label = 'Test fetch';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->create($this->testUser);

        $createdId = $uptosign->id;
        $createdRef = $uptosign->ref;

        // Fetch by ID
        $fetched = new \UptoSign($this->db);
        $result = $fetched->fetch($createdId);

        $this->assertGreaterThan(0, $result, 'Fetch should return positive value');
        $this->assertEquals($createdId, $fetched->id);
        $this->assertEquals($createdRef, $fetched->ref);
        $this->assertEquals('Test fetch', $fetched->label);
    }

    public function testFetchByRef(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uniqueRef = 'REF-' . uniqid();
        $uptosign->ref = $uniqueRef;
        $uptosign->label = 'Test fetch by ref';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->create($this->testUser);

        // Fetch by ref
        $fetched = new \UptoSign($this->db);
        $result = $fetched->fetch(0, $uniqueRef);

        $this->assertGreaterThan(0, $result, 'Fetch by ref should return positive value');
        $this->assertEquals($uniqueRef, $fetched->ref);
    }

    public function testUpdateUptoSign(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'UPDATE-' . uniqid();
        $uptosign->label = 'Original label';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->create($this->testUser);

        // Reload to get all fields
        $uptosign->fetch($uptosign->id);

        // Update
        $uptosign->label = 'Updated label';
        $uptosign->status = \UptoSign::STATUS_SIGNED;
        $result = $uptosign->update($this->testUser);

        $this->assertGreaterThan(0, $result, 'Update should return positive value');

        // Verify in database
        $verify = new \UptoSign($this->db);
        $verify->fetch($uptosign->id);

        $this->assertEquals('Updated label', $verify->label);
        $this->assertEquals(\UptoSign::STATUS_SIGNED, $verify->status);
    }

    public function testDeleteUptoSign(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'DELETE-' . uniqid();
        $uptosign->label = 'To be deleted';
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;
        $uptosign->create($this->testUser);

        $createdId = $uptosign->id;

        // Delete
        $result = $uptosign->delete($this->testUser);

        $this->assertGreaterThan(0, $result, 'Delete should return positive value');
        $this->assertDatabaseMissing('uptosign', ['rowid' => $createdId]);
    }

    public function testStatusConstants(): void
    {
        $this->assertEquals(-100, \UptoSign::STATUS_NOTHING);
        $this->assertEquals(-10, \UptoSign::STATUS_DRAFT);
        $this->assertEquals(-4, \UptoSign::STATUS_EXPIRED);
        $this->assertEquals(-3, \UptoSign::STATUS_REFUSED);
        $this->assertEquals(-2, \UptoSign::STATUS_ERROR);
        $this->assertEquals(-1, \UptoSign::STATUS_CANCELED);
        $this->assertEquals(0, \UptoSign::STATUS_WAITING);
        $this->assertEquals(1, \UptoSign::STATUS_SIGNED);
        $this->assertEquals(2, \UptoSign::STATUS_SEALED);
        $this->assertEquals(3, \UptoSign::STATUS_FILE_FETCHED);
    }

    /**
     * @dataProvider statusProvider
     */
    public function testCreateWithDifferentStatuses(int $status): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'STATUS-' . $status . '-' . uniqid();
        $uptosign->label = 'Test status ' . $status;
        $uptosign->fk_soc = $soc->id;
        $uptosign->status = $status;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;

        $result = $uptosign->create($this->testUser);

        $this->assertGreaterThan(0, $result, "Create with status $status should succeed");

        // Verify
        $verify = new \UptoSign($this->db);
        $verify->fetch($uptosign->id);
        $this->assertEquals($status, $verify->status);
    }

    public static function statusProvider(): array
    {
        return [
            'draft' => [\UptoSign::STATUS_DRAFT],
            'waiting' => [\UptoSign::STATUS_WAITING],
            'signed' => [\UptoSign::STATUS_SIGNED],
            'sealed' => [\UptoSign::STATUS_SEALED],
            'canceled' => [\UptoSign::STATUS_CANCELED],
            'error' => [\UptoSign::STATUS_ERROR],
            'refused' => [\UptoSign::STATUS_REFUSED],
            'expired' => [\UptoSign::STATUS_EXPIRED],
        ];
    }

    public function testCreateWithContact(): void
    {
        $soc = $this->createTestSociete();
        $contact = $this->createTestContact($soc);

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'CONTACT-' . uniqid();
        $uptosign->label = 'Test with contact';
        $uptosign->fk_soc = $soc->id;
        $uptosign->fk_contact_sign = $contact->id;
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;

        $result = $uptosign->create($this->testUser);

        $this->assertGreaterThan(0, $result);

        // Verify contact is stored
        $verify = new \UptoSign($this->db);
        $verify->fetch($uptosign->id);
        $this->assertEquals($contact->id, $verify->fk_contact_sign);
    }

    public function testCreateWithSignId(): void
    {
        $soc = $this->createTestSociete();

        $uptosign = new \UptoSign($this->db);
        $uptosign->ref = 'SIGNID-' . uniqid();
        $uptosign->label = 'Test with sign ID';
        $uptosign->fk_soc = $soc->id;
        $uptosign->sign_id = 'ext-sign-' . uniqid();
        $uptosign->sign_status = 'pending';
        $uptosign->api_name = 'uptosign';
        $uptosign->status = \UptoSign::STATUS_WAITING;
        $uptosign->entity = 1;
        $uptosign->object_type = 'propal';
        $uptosign->fk_object = 1;

        $result = $uptosign->create($this->testUser);

        $this->assertGreaterThan(0, $result);

        // Verify
        $verify = new \UptoSign($this->db);
        $verify->fetch($uptosign->id);
        $this->assertEquals($uptosign->sign_id, $verify->sign_id);
        $this->assertEquals('pending', $verify->sign_status);
        $this->assertEquals('uptosign', $verify->api_name);
    }

    public function testCreateMultipleAndList(): void
    {
        $soc = $this->createTestSociete();

        // Create multiple records
        for ($i = 1; $i <= 3; $i++) {
            $uptosign = new \UptoSign($this->db);
            $uptosign->ref = 'LIST-' . $i . '-' . uniqid();
            $uptosign->label = 'Test list ' . $i;
            $uptosign->fk_soc = $soc->id;
            $uptosign->status = \UptoSign::STATUS_WAITING;
            $uptosign->entity = 1;
            $uptosign->object_type = 'propal';
            $uptosign->fk_object = $i;
            $uptosign->create($this->testUser);
        }

        // Count should be 3
        $count = $this->getDatabaseCount('uptosign');
        $this->assertEquals(3, $count);
    }

    public function testObjectTypeVariations(): void
    {
        $soc = $this->createTestSociete();
        $objectTypes = ['propal', 'commande', 'facture', 'contrat', 'fichinter'];

        foreach ($objectTypes as $type) {
            $uptosign = new \UptoSign($this->db);
            $uptosign->ref = 'TYPE-' . $type . '-' . uniqid();
            $uptosign->label = 'Test type ' . $type;
            $uptosign->fk_soc = $soc->id;
            $uptosign->status = \UptoSign::STATUS_WAITING;
            $uptosign->entity = 1;
            $uptosign->object_type = $type;
            $uptosign->fk_object = 1;

            $result = $uptosign->create($this->testUser);
            $this->assertGreaterThan(0, $result, "Create with object_type '$type' should succeed");

            // Verify
            $verify = new \UptoSign($this->db);
            $verify->fetch($uptosign->id);
            $this->assertEquals($type, $verify->object_type);
        }
    }
}
