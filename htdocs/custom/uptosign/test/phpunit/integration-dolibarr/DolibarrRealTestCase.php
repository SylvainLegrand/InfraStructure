<?php

namespace UptoSign\Tests\IntegrationDolibarr;

use PHPUnit\Framework\TestCase;

/**
 * Base class for integration tests with real Dolibarr
 */
abstract class DolibarrRealTestCase extends TestCase
{
    /** @var \DoliDB */
    protected $db;

    /** @var \User */
    protected $testUser;

    /** @var object */
    protected $conf;

    /** @var \Translate */
    protected $langs;

    protected function setUp(): void
    {
        global $db, $conf, $user, $langs;

        $this->db = $db;
        $this->conf = $conf;
        $this->testUser = $user;
        $this->langs = $langs;

        // Clean module tables between tests
        $this->cleanModuleTables();
    }

    /**
     * Clean module tables to ensure test isolation
     */
    protected function cleanModuleTables(): void
    {
        $tables = [
            'uptosign',
            'uptosign_uptosignconfig',
            'uptosign_uptosignlist',
            'uptosign_uptosignlistmembers'
        ];

        foreach ($tables as $table) {
            $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . $table);
        }
    }

    /**
     * Create a test Societe (third party)
     */
    protected function createTestSociete(array $data = []): \Societe
    {
        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

        $soc = new \Societe($this->db);
        $soc->name = $data['name'] ?? 'Test Company ' . uniqid();
        $soc->client = $data['client'] ?? 1;
        $soc->fournisseur = $data['fournisseur'] ?? 0;
        $soc->entity = $data['entity'] ?? 1;
        $soc->create($this->testUser);

        return $soc;
    }

    /**
     * Create a test Contact
     */
    protected function createTestContact(\Societe $soc, array $data = []): \Contact
    {
        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

        $contact = new \Contact($this->db);
        $contact->socid = $soc->id;
        $contact->firstname = $data['firstname'] ?? 'Test';
        $contact->lastname = $data['lastname'] ?? 'Contact ' . uniqid();
        $contact->email = $data['email'] ?? 'test' . uniqid() . '@example.com';
        $contact->phone_mobile = $data['phone_mobile'] ?? '+33600000000';
        $contact->entity = $data['entity'] ?? 1;
        $contact->create($this->testUser);

        return $contact;
    }

    /**
     * Create a test Propal (proposal)
     */
    protected function createTestPropal(\Societe $soc, array $data = []): \Propal
    {
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

        $propal = new \Propal($this->db);
        $propal->socid = $soc->id;
        $propal->date = $data['date'] ?? dol_now();
        $propal->entity = $data['entity'] ?? 1;
        $propal->create($this->testUser);

        return $propal;
    }

    /**
     * Assert that a record exists in the database
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $where = [];
        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $where[] = "$column IS NULL";
            } else {
                $where[] = "$column = '" . $this->db->escape($value) . "'";
            }
        }

        $sql = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . $table;
        $sql .= " WHERE " . implode(' AND ', $where);

        $result = $this->db->query($sql);
        $obj = $this->db->fetch_object($result);

        $this->assertGreaterThan(
            0,
            (int) $obj->cnt,
            "Table '$table' should contain record with: " . json_encode($conditions)
        );
    }

    /**
     * Assert that a record does not exist in the database
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $where = [];
        foreach ($conditions as $column => $value) {
            if ($value === null) {
                $where[] = "$column IS NULL";
            } else {
                $where[] = "$column = '" . $this->db->escape($value) . "'";
            }
        }

        $sql = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . $table;
        $sql .= " WHERE " . implode(' AND ', $where);

        $result = $this->db->query($sql);
        $obj = $this->db->fetch_object($result);

        $this->assertEquals(
            0,
            (int) $obj->cnt,
            "Table '$table' should NOT contain record with: " . json_encode($conditions)
        );
    }

    /**
     * Get count of records in a table
     */
    protected function getDatabaseCount(string $table, array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . $table;

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $column => $value) {
                if ($value === null) {
                    $where[] = "$column IS NULL";
                } else {
                    $where[] = "$column = '" . $this->db->escape($value) . "'";
                }
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = $this->db->query($sql);
        $obj = $this->db->fetch_object($result);

        return (int) $obj->cnt;
    }
}
