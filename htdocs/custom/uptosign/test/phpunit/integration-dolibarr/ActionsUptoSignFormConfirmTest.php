<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Regression test for ActionsUptoSign::formConfirm() (Apr 2026 prod incident).
 *
 * Bug context: clicking the "Sceller le PDF" button on an invoice card triggers the
 * formConfirm() hook with action='uptoseal'. That hook calls uptosignListOfFilesLinkedTo()
 * which used to crash with a TypeError on EcmFiles::fetchAll() in some Dolibarr installs.
 *
 * Additionally, doActions() with action='uptoseal' read $parameters['last_main_doc']
 * without a default, raising "Undefined array key" warnings when the parameter was missing.
 *
 * This test calls the hook directly with minimal parameters (no last_main_doc, no title)
 * to ensure no Warning/Notice/TypeError is raised.
 */
class ActionsUptoSignFormConfirmTest extends DolibarrRealTestCase
{
    /**
     * The hook formConfirm() with action='uptoseal' must not raise TypeError or Warning
     * even when the invoice has no linked files.
     */
    public function testFormConfirmUptosealOnInvoiceWithoutLinkedFiles(): void
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        dol_include_once('/uptosign/class/actions_uptosign.class.php');

        $soc = $this->createTestSociete();
        $invoice = new \Facture($this->db);
        $invoice->socid = $soc->id;
        $invoice->date = dol_now();
        $invoice->cond_reglement_id = 1;
        $invoice->mode_reglement_id = 1;
        $invoice->fk_account = 1;
        $invoice->type = \Facture::TYPE_STANDARD;
        $invoice->create($this->testUser);

        $hook = new \ActionsUptoSign($this->db);
        $action = 'uptoseal';
        $parameters = array(); // intentionally empty -- mirrors the prod situation
        $hookmanager = null;

        // Capture any Warning/Notice raised during the call -- treat them as test failures.
        $errors = array();
        set_error_handler(function ($severity, $message, $file, $line) use (&$errors) {
            $errors[] = "$severity: $message in $file:$line";
            return true;
        });

        try {
            $result = $hook->formConfirm($parameters, $invoice, $action, $hookmanager);
            // The hook must complete -- it will return -1 if no files are linked,
            // which is the expected behavior. The point is "no crash, no warning".
            $this->assertIsInt($result);
        } finally {
            restore_error_handler();
        }

        $this->assertEmpty(
            $errors,
            "formConfirm(uptoseal) raised unexpected PHP warnings/notices:\n" . implode("\n", $errors)
        );
    }

    /**
     * doActions() with action='confirm_uptoseal' must handle missing 'last_main_doc'
     * parameter without raising "Undefined array key" warnings.
     */
    public function testDoActionsConfirmUptosealWithMissingParameters(): void
    {
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        dol_include_once('/uptosign/class/actions_uptosign.class.php');

        $soc = $this->createTestSociete();
        $invoice = new \Facture($this->db);
        $invoice->socid = $soc->id;
        $invoice->date = dol_now();
        $invoice->cond_reglement_id = 1;
        $invoice->mode_reglement_id = 1;
        $invoice->fk_account = 1;
        $invoice->type = \Facture::TYPE_STANDARD;
        $invoice->create($this->testUser);

        $hook = new \ActionsUptoSign($this->db);
        $action = 'confirm_uptoseal';
        // Minimal $parameters -- intentionally missing last_main_doc, contactToSignID, process
        $parameters = array(
            'currentcontext' => 'invoicecard',
        );
        $hookmanager = null;

        $errors = array();
        set_error_handler(function ($severity, $message, $file, $line) use (&$errors) {
            // Only fail on E_WARNING, E_NOTICE and E_USER_WARNING from OUR module
            if (strpos($file, '/uptosign/') !== false) {
                $errors[] = "$severity: $message in $file:$line";
            }
            return true;
        });

        try {
            // We don't care about the return value -- the hook will likely fail because
            // many things are not set up. We only want to ensure no PHP warnings are raised
            // from missing array keys in OUR code.
            @$hook->doActions($parameters, $invoice, $action, $hookmanager);
        } catch (\Throwable $e) {
            // The hook may throw legitimately (network call, auth, etc.) -- that's fine.
            // We only check warnings raised before the throw.
        } finally {
            restore_error_handler();
        }

        $undefinedKeyErrors = array_filter($errors, function ($e) {
            return strpos($e, 'Undefined array key') !== false
                || strpos($e, 'Undefined index') !== false;
        });

        $this->assertEmpty(
            $undefinedKeyErrors,
            "doActions(confirm_uptoseal) raised 'Undefined array key' warnings -- "
            . "all parameter reads must use `?? default`:\n" . implode("\n", $undefinedKeyErrors)
        );
    }
}
