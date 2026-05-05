<?php

namespace UptoSign\Tests\IntegrationDolibarr;

/**
 * Multi-object regression test for ActionsUptoSign hooks.
 *
 * The Apr 2026 prod incident ("Undefined array key last_main_doc" + TypeError on
 * EcmFiles::fetchAll) was triggered on invoices, but the same code path is reachable
 * for every object type the module handles:
 *   propal, commande, facture, contrat, fichinter, projet,
 *   supplier_proposal, order_supplier, societe, user.
 *
 * For each (object_type, action) combination we:
 *   - create a minimal real Dolibarr object (SQLite),
 *   - call formConfirm() and doActions() with intentionally empty $parameters
 *     (mirrors prod, where last_main_doc / process / contactToSignID may be missing),
 *   - capture every PHP warning/notice raised from files in /uptosign/,
 *   - assert that no "Undefined array key" / "Undefined index" warning is raised.
 *
 * Hook may legitimately throw (network, auth, etc.) further down the path; we only
 * assert on warnings raised BEFORE the throw.
 */
class ActionsUptoSignMultiObjectTest extends DolibarrRealTestCase
{
    /**
     * Provider for formConfirm() tests: object_type x ('uptosign', 'uptoseal').
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function objectTypeAndActionProvider(): array
    {
        $cases = [];
        $elements = [
            'propal',
            'commande',
            'facture',
            'contrat',
            'fichinter',
            'project',
            'supplier_proposal',
            'order_supplier',
            'societe',
            'user',
        ];
        foreach ($elements as $el) {
            foreach (['uptosign', 'uptoseal'] as $action) {
                $cases[$el . '_' . $action] = [$el, $action];
            }
        }
        return $cases;
    }

    /**
     * Provider for doActions() tests: object_type x ('confirm_uptosign', 'confirm_uptoseal').
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function confirmActionProvider(): array
    {
        $cases = [];
        $elements = [
            'propal',
            'commande',
            'facture',
            'contrat',
            'fichinter',
            'project',
            'supplier_proposal',
            'order_supplier',
            'societe',
            'user',
        ];
        foreach ($elements as $el) {
            foreach (['confirm_uptosign', 'confirm_uptoseal'] as $action) {
                $cases[$el . '_' . $action] = [$el, $action];
            }
        }
        return $cases;
    }

    /**
     * Build a real Dolibarr object of the requested type with the minimum required fields.
     * Returns the created object on success, or null if the type cannot be created here.
     *
     * @param string $element Object element name (propal, commande, facture, ...)
     * @return \CommonObject|null
     */
    protected function buildObjectOfType(string $element)
    {
        switch ($element) {
            case 'propal':
                require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                $soc = $this->createTestSociete();
                $obj = new \Propal($this->db);
                $obj->socid = $soc->id;
                $obj->date = dol_now();
                $obj->cond_reglement_id = 1;
                $obj->mode_reglement_id = 1;
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'commande':
                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                $soc = $this->createTestSociete();
                $obj = new \Commande($this->db);
                $obj->socid = $soc->id;
                $obj->date = dol_now();
                $obj->cond_reglement_id = 1;
                $obj->mode_reglement_id = 1;
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'facture':
                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                $soc = $this->createTestSociete();
                $obj = new \Facture($this->db);
                $obj->socid = $soc->id;
                $obj->date = dol_now();
                $obj->cond_reglement_id = 1;
                $obj->mode_reglement_id = 1;
                $obj->fk_account = 1;
                $obj->type = \Facture::TYPE_STANDARD;
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'contrat':
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $soc = $this->createTestSociete();
                $obj = new \Contrat($this->db);
                $obj->socid = $soc->id;
                $obj->date_contrat = dol_now();
                $obj->date_creation = dol_now();
                // commercial_signature_id and commercial_suivi_id are required (see Contrat::create)
                $obj->commercial_signature_id = (int) $this->testUser->id;
                $obj->commercial_suivi_id = (int) $this->testUser->id;
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'fichinter':
                require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
                $soc = $this->createTestSociete();
                $obj = new \Fichinter($this->db);
                $obj->socid = $soc->id;
                $obj->datec = dol_now();
                $obj->date_creation = dol_now();
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'project':
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                $obj = new \Project($this->db);
                $obj->ref = 'PR' . substr((string) uniqid(), -8);
                $obj->title = 'Test project ' . uniqid();
                $obj->date_c = dol_now();
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'supplier_proposal':
                require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
                $soc = $this->createTestSociete(['fournisseur' => 1]);
                $obj = new \SupplierProposal($this->db);
                $obj->socid = $soc->id;
                $obj->date = dol_now();
                $obj->cond_reglement_id = 1;
                $obj->mode_reglement_id = 1;
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'order_supplier':
                require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
                $soc = $this->createTestSociete(['fournisseur' => 1]);
                $obj = new \CommandeFournisseur($this->db);
                $obj->socid = $soc->id;
                $obj->date = dol_now();
                $obj->cond_reglement_id = 1;
                $obj->mode_reglement_id = 1;
                $res = $obj->create($this->testUser);
                if ($res <= 0) {
                    return null;
                }
                return $obj;

            case 'societe':
                return $this->createTestSociete();

            case 'user':
                // Use the existing test user -- creating a fresh user in SQLite
                // tends to require many extra columns. The hook only needs ->id and ->element.
                if (!empty($this->testUser->id)) {
                    return $this->testUser;
                }
                return null;

            default:
                return null;
        }
    }

    /**
     * Build the path prefix that identifies "really inside the uptosign module"
     * (not just any path containing 'uptosign' -- the vendored dolibarr lives under
     * .../uptosign/vendor/cap-rel/dolibarr-integration-sqlite/htdocs/...).
     *
     * We anchor on the module root + a known module subdir.
     */
    protected function isUptoSignModuleFile(string $file): bool
    {
        $moduleRoot = dirname(__DIR__, 3); // .../uptosign
        $vendorPrefix = $moduleRoot . '/vendor/';
        if (strpos($file, $vendorPrefix) === 0) {
            return false; // anything in vendor/ is third-party
        }
        return strpos($file, $moduleRoot . '/') === 0;
    }

    /**
     * @dataProvider objectTypeAndActionProvider
     */
    public function testFormConfirmOnAllObjectTypes(string $element, string $action): void
    {
        dol_include_once('/uptosign/class/actions_uptosign.class.php');

        $object = $this->buildObjectOfType($element);
        if ($object === null) {
            $this->markTestSkipped("Cannot create object of type '$element' in SQLite test env");
        }

        $hook = new \ActionsUptoSign($this->db);
        $parameters = array(); // intentionally empty -- mirrors prod
        $hookmanager = null;

        $errors = array();
        $self = $this;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errors, $self) {
            if ($self->isUptoSignModuleFile($file)) {
                $errors[] = "$severity: $message in $file:$line";
            }
            return true;
        });

        $result = null;
        try {
            $result = @$hook->formConfirm($parameters, $object, $action, $hookmanager);
        } catch (\Throwable $e) {
            // Hook may legitimately throw (auth, network, etc.) further in the path
        } finally {
            restore_error_handler();
        }

        // Filter for "Undefined array key" / "Undefined index" warnings -- the regression target
        $undefinedKeyErrors = array_filter($errors, function ($e) {
            return strpos($e, 'Undefined array key') !== false
                || strpos($e, 'Undefined index') !== false;
        });

        $this->assertEmpty(
            $undefinedKeyErrors,
            "formConfirm($action) on '$element' raised 'Undefined array key' warnings:\n"
            . implode("\n", $undefinedKeyErrors)
        );

        // No other PHP warnings/notices should be raised from /uptosign/ either
        $this->assertEmpty(
            $errors,
            "formConfirm($action) on '$element' raised PHP warnings/notices from /uptosign/:\n"
            . implode("\n", $errors)
        );

        // If the hook returned something, it must be int (-1, 0, or 1).
        // It returns null when it threw; in that case we skip the int check.
        if ($result !== null) {
            $this->assertIsInt(
                $result,
                "formConfirm($action) on '$element' must return an int, got: " . var_export($result, true)
            );
        }
    }

    /**
     * @dataProvider confirmActionProvider
     */
    public function testDoActionsConfirmOnAllObjectTypes(string $element, string $action): void
    {
        dol_include_once('/uptosign/class/actions_uptosign.class.php');

        $object = $this->buildObjectOfType($element);
        if ($object === null) {
            $this->markTestSkipped("Cannot create object of type '$element' in SQLite test env");
        }

        $hook = new \ActionsUptoSign($this->db);
        // Empty $parameters mirrors the prod situation: no last_main_doc, no process,
        // no contactToSignID, but a currentcontext is required for the hook to enter.
        $parameters = array(
            'currentcontext' => 'invoicecard',
        );
        $hookmanager = null;

        $errors = array();
        $self = $this;
        set_error_handler(function ($severity, $message, $file, $line) use (&$errors, $self) {
            if ($self->isUptoSignModuleFile($file)) {
                $errors[] = "$severity: $message in $file:$line";
            }
            return true;
        });

        try {
            @$hook->doActions($parameters, $object, $action, $hookmanager);
        } catch (\Throwable $e) {
            // Ignore -- we only check warnings raised before the throw
        } finally {
            restore_error_handler();
        }

        $undefinedKeyErrors = array_filter($errors, function ($e) {
            return strpos($e, 'Undefined array key') !== false
                || strpos($e, 'Undefined index') !== false;
        });

        $this->assertEmpty(
            $undefinedKeyErrors,
            "doActions($action) on '$element' raised 'Undefined array key' warnings -- "
            . "all parameter reads must use `?? default`:\n" . implode("\n", $undefinedKeyErrors)
        );
    }
}
