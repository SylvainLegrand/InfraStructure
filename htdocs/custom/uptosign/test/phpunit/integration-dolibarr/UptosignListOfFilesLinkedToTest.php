<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Regression test for the EcmFiles::fetchAll() signature mismatch (Apr 2026 prod incident).
 *
 * Bug: in some Dolibarr installs, DOL_VERSION reports >= 20 but the actual
 * EcmFiles::fetchAll() method still has `array $filter` (Dolibarr 18/19 signature).
 * Our code used `version_compare(DOL_VERSION, "20.0.0") >= 0` to pick a USF string filter,
 * which triggered:
 *
 *   TypeError: EcmFiles::fetchAll(): Argument #5 ($filter) must be of type array, string given
 *
 * The fix uses ReflectionMethod to detect the real signature instead of relying on DOL_VERSION.
 *
 * This test guards the function so any future regression is caught at test time, not in prod.
 */
class UptosignListOfFilesLinkedToTest extends DolibarrRealTestCase
{
    public function testListOfFilesLinkedToHandlesBothFetchAllSignatures(): void
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        dol_include_once('/uptosign/lib/uptosign.lib.php');

        // Create a real invoice so the function has a CommonObject to query against
        $soc = $this->createTestSociete();
        $invoice = new \Facture($this->db);
        $invoice->socid = $soc->id;
        $invoice->date = dol_now();
        $invoice->cond_reglement_id = 1;
        $invoice->mode_reglement_id = 1;
        $invoice->fk_account = 1;
        $invoice->type = \Facture::TYPE_STANDARD;
        $createResult = $invoice->create($this->testUser);

        $this->assertGreaterThan(0, $createResult, 'Invoice creation failed');

        // The function must not throw -- regardless of the EcmFiles::fetchAll() signature
        // in the current Dolibarr install.
        $result = uptosignListOfFilesLinkedTo($invoice);

        $this->assertIsArray($result, 'uptosignListOfFilesLinkedTo() must return an array');
    }

    public function testListOfFilesLinkedToReturnsArrayWhenNoFilesLinked(): void
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        dol_include_once('/uptosign/lib/uptosign.lib.php');

        $soc = $this->createTestSociete();
        $invoice = new \Facture($this->db);
        $invoice->socid = $soc->id;
        $invoice->date = dol_now();
        $invoice->cond_reglement_id = 1;
        $invoice->mode_reglement_id = 1;
        $invoice->fk_account = 1;
        $invoice->type = \Facture::TYPE_STANDARD;
        $invoice->create($this->testUser);

        // No files linked yet -> empty array, no TypeError
        $result = uptosignListOfFilesLinkedTo($invoice);

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'No ECM files linked yet, expected empty array');
    }
}
