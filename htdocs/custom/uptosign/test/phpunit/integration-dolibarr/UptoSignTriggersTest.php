<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Smoke tests for the UptoSign trigger class.
 *
 * The trigger class
 * (core/triggers/interface_99_modUptoSign_UptoSignTriggers.class.php)
 * listens to many Dolibarr events. A typo, a missing array key, or a method
 * that no longer exists on the passed object causes a silent crash in
 * production (because triggers swallow most errors).
 *
 * For each `case 'XXX':` declared in the trigger, this test class:
 *   1. Builds a minimal $object stub with the fields the trigger reads.
 *   2. Installs a custom error handler that captures any
 *      Warning/Notice/Deprecation raised from a /uptosign/ source file.
 *   3. Calls runTrigger() and asserts the captured list is empty.
 *
 * The trigger may legitimately return -1 or even throw -- we only care
 * about PHP warnings/notices coming from this module's code.
 */
class UptoSignTriggersTest extends DolibarrRealTestCase
{
    /**
     * Trigger codes the class listens to (extracted from the switch + dynamic
     * dispatch in runTrigger()).
     *
     * @return array<string, array{0: string}>
     */
    public static function triggerCodesProvider(): array
    {
        return array(
            'CONTACT_CREATE'         => array('CONTACT_CREATE'),
            'ORDER_MODIFY'           => array('ORDER_MODIFY'),
            'ORDER_DELETE'           => array('ORDER_DELETE'),
            'ORDER_CANCEL'           => array('ORDER_CANCEL'),
            'ORDER_SETDRAFT'         => array('ORDER_SETDRAFT'),
            'ORDER_UNVALIDATE'       => array('ORDER_UNVALIDATE'),
            'PROPAL_REOPEN'          => array('PROPAL_REOPEN'),
            'PROPAL_CLOSE_SIGNED'    => array('PROPAL_CLOSE_SIGNED'),
            'PROPAL_CLOSE_REFUSED'   => array('PROPAL_CLOSE_REFUSED'),
            'PROPAL_DELETE'          => array('PROPAL_DELETE'),
            'CONTRACT_CLOSED_SIGNED' => array('CONTRACT_CLOSED_SIGNED'),
            'BILL_VALIDATE'          => array('BILL_VALIDATE'),
            'INVOICE_SEALED'         => array('INVOICE_SEALED'),
            'FICHINTER_MODIFY'       => array('FICHINTER_MODIFY'),
            'FICHINTER_DELETE'       => array('FICHINTER_DELETE'),
            // Triggers commonly emitted by Dolibarr core that fall through to the
            // default branch -- we still want to ensure they raise no warning
            // (e.g. the default case dereferences $object->id).
            'PROPAL_VALIDATE'        => array('PROPAL_VALIDATE'),
            'COMMANDE_VALIDATE'      => array('COMMANDE_VALIDATE'),
            'CONTRACT_VALIDATE'      => array('CONTRACT_VALIDATE'),
            'FACTURE_VALIDATE'       => array('FACTURE_VALIDATE'),
        );
    }

    /**
     * Build a minimal $object stub appropriate for a given trigger code.
     *
     * The trigger reads at least:
     *   - $object->id           (used by every dol_syslog call)
     *   - $object->element      (used by cancelUptoSign())
     *   - $object->context[]    (defensive, used by some branches)
     *   - $object->linkedObjects (BILL_VALIDATE, INVOICE_SEALED -- pre-populated empty)
     *
     * For triggers gated on UPTOSIGN_* config flags, the flags stay disabled
     * so we hit only the cheap path. The stub does not need to be a real
     * Dolibarr object.
     */
    private function buildStubObject(string $triggerCode): \stdClass
    {
        $obj = new \stdClass();
        $obj->id = 0;
        $obj->context = array();
        $obj->linkedObjects = array();
        $obj->linkedObjectsIds = array();
        $obj->array_options = array();

        // Set $object->element to match the trigger family so cancelUptoSign()
        // does not blow up on an undefined property when it falls through.
        if (strpos($triggerCode, 'ORDER_') === 0 || $triggerCode === 'COMMANDE_VALIDATE') {
            $obj->element = 'commande';
        } elseif (strpos($triggerCode, 'PROPAL_') === 0) {
            $obj->element = 'propal';
        } elseif (strpos($triggerCode, 'BILL_') === 0
            || $triggerCode === 'INVOICE_SEALED'
            || $triggerCode === 'FACTURE_VALIDATE'
        ) {
            $obj->element = 'facture';
        } elseif (strpos($triggerCode, 'CONTRACT_') === 0) {
            $obj->element = 'contrat';
        } elseif (strpos($triggerCode, 'FICHINTER_') === 0) {
            $obj->element = 'fichinter';
        } elseif ($triggerCode === 'CONTACT_CREATE') {
            $obj->element = 'contact';
        } else {
            $obj->element = 'unknown';
        }

        return $obj;
    }

    /**
     * @dataProvider triggerCodesProvider
     */
    public function testTriggerDoesNotRaiseWarning(string $triggerCode): void
    {
        require_once DOL_DOCUMENT_ROOT
            . '/core/triggers/dolibarrtriggers.class.php';
        require_once dirname(__DIR__, 3)
            . '/core/triggers/interface_99_modUptoSign_UptoSignTriggers.class.php';

        $object = $this->buildStubObject($triggerCode);

        // Capture warnings/notices originating from /uptosign/ source files.
        $errors = array();
        set_error_handler(function ($severity, $message, $file, $line) use (&$errors) {
            if (strpos($file, '/uptosign/') !== false) {
                $errors[] = "[$severity] $message in $file:$line";
            }
            // Returning true tells PHP we handled it (no further reporting).
            return true;
        });

        $trigger = new \InterfaceUptoSignTriggers($this->db);

        try {
            // The trigger may legitimately return -1 or throw (network calls,
            // missing methods on stub, etc.) -- we only care about warnings.
            @$trigger->runTrigger(
                $triggerCode,
                $object,
                $this->testUser,
                $this->langs,
                $this->conf
            );
        } catch (\Throwable $e) {
            // Expected for some triggers when the stub does not implement
            // all Dolibarr-side methods (fetchObjectLinked, fetch_lines, etc.).
            // The crash itself is not a regression -- only warnings raised
            // BEFORE the crash from inside /uptosign/ matter.
        } finally {
            restore_error_handler();
        }

        $this->assertEmpty(
            $errors,
            "Trigger '$triggerCode' raised PHP warnings/notices from /uptosign/:\n"
            . implode("\n", $errors)
        );
    }
}
