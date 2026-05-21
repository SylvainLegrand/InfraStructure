<?php

namespace UptoSign\Tests\Http;

class AdminPagesHttpTest extends HttpTestCase
{
    protected static function getRouterPath(): string
    {
        return dirname(__DIR__, 3) . '/test/http/admin-router.php';
    }

    public function testAdminRouterPing(): void
    {
        $response = $this->get('/ping');
        $this->assertStatusCode(200, $response);
        $this->assertJsonEquals('status', 'ok', $response);
    }

    /**
     * Sanity check: at least one admin page must render real Dolibarr HTML.
     * If the router is misconfigured (CONTEXT_DOCUMENT_ROOT absent, shim broken,
     * wrong cwd, etc.) all pages silently die() on "Include of main fails" and
     * every other test becomes a false positive.
     */
    public function testSanityAdminPageRendersRealContent(): void
    {
        $response = $this->get('/admin/setup.php');
        $this->assertStatusCode(200, $response);
        $this->assertStringNotContainsString(
            'Include of main fails',
            $response['body'],
            'Router did not load main.inc.php -- every other test is a false positive.'
        );
        $this->assertGreaterThan(
            1000,
            strlen($response['body']),
            'admin/setup.php returned suspiciously little HTML -- router probably broken.'
        );
    }

    /**
     * @dataProvider adminPagesProvider
     */
    public function testAdminPageLoadsWithoutError(string $page): void
    {
        $response = $this->get('/admin/' . $page);
        $this->assertNoPhpError($response, 'admin/' . $page);
    }

    public static function adminPagesProvider(): array
    {
        $adminDir = dirname(__DIR__, 3) . '/admin';
        $files = glob($adminDir . '/*.php');
        $cases = [];
        foreach ($files as $file) {
            $basename = basename($file);
            $cases[$basename] = [$basename];
        }
        ksort($cases);
        return $cases;
    }

    /**
     * @dataProvider rootPagesProvider
     */
    public function testRootPageLoadsWithoutError(string $page): void
    {
        $response = $this->get('/' . $page);
        $this->assertNoPhpError($response, $page);
    }

    public static function rootPagesProvider(): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $excluded = ['t.php'];
        $files = glob($projectRoot . '/*.php');
        $cases = [];
        foreach ($files as $file) {
            $basename = basename($file);
            if (in_array($basename, $excluded, true)) {
                continue;
            }
            $cases[$basename] = [$basename];
        }
        ksort($cases);
        return $cases;
    }

    /**
     * Resolve a real object id created by the router. The router exposes
     * a /test-ids JSON endpoint with the rowids of objects created in its
     * deployment block. Falls back to 1 if the endpoint is unreachable
     * (works for the very first object of each kind on a fresh DB).
     */
    private function resolveTestId(string $key): int
    {
        // Hit any non-/ping page first so the deploy block runs and creates
        // the test data flag file. /test-ids returns valid IDs only after
        // module deployment has happened.
        $this->get('/uptosign_card.php');
        $response = $this->get('/test-ids');
        $json = $response['json'] ?? null;
        if (is_array($json) && isset($json[$key]) && (int) $json[$key] > 0) {
            return (int) $json[$key];
        }
        return 1;
    }

    public function testUptoSignCardPageWithId(): void
    {
        $id = $this->resolveTestId('uts_id');
        $response = $this->get('/uptosign_card.php?id=' . $id);
        $this->assertNoPhpError($response, 'uptosign_card.php?id=' . $id);
    }

    public function testUptoSignListCardPageWithId(): void
    {
        $id = $this->resolveTestId('utsl_id');
        $response = $this->get('/uptosignlist_card.php?id=' . $id);
        $this->assertNoPhpError($response, 'uptosignlist_card.php?id=' . $id);
    }

    public function testUptoSignConfigCardPageWithId(): void
    {
        $id = $this->resolveTestId('utsc_id');
        $response = $this->get('/uptosignconfig_card.php?id=' . $id);
        $this->assertNoPhpError($response, 'uptosignconfig_card.php?id=' . $id);
    }

    public function testEmployeeCardPageWithId(): void
    {
        // employee_card.php manipulates an UptoSign object, so we reuse the
        // UptoSign id. Same code path as uptosign_card but rendered for the
        // "employee" UI variant.
        $id = $this->resolveTestId('uts_id');
        $response = $this->get('/employee_card.php?id=' . $id);
        $this->assertNoPhpError($response, 'employee_card.php?id=' . $id);
    }

    /**
     * Sub-pages attached to an UptoSignList (cibles, contact, document, etc.)
     * all read GETPOSTINT('id') and skip their detailed render path when no id
     * is given. Test them with the real utsl_id so getNomUrl(), getLibStatut(),
     * tab rendering, and contact lookups are actually executed.
     *
     * @dataProvider uptosignListSubPagesProvider
     */
    public function testUptoSignListSubPageWithId(string $page): void
    {
        $id = $this->resolveTestId('utsl_id');
        $url = '/' . $page . '?id=' . $id;
        $response = $this->get($url);
        $this->assertNoPhpError($response, $url);
    }

    public static function uptosignListSubPagesProvider(): array
    {
        return [
            'uptosignlist_advcibles.php' => ['uptosignlist_advcibles.php'],
            'uptosignlist_agenda.php'    => ['uptosignlist_agenda.php'],
            'uptosignlist_cibles.php'    => ['uptosignlist_cibles.php'],
            'uptosignlist_contact.php'   => ['uptosignlist_contact.php'],
            'uptosignlist_docs.php'      => ['uptosignlist_docs.php'],
            'uptosignlist_document.php'  => ['uptosignlist_document.php'],
            'uptosignlist_note.php'      => ['uptosignlist_note.php'],
        ];
    }

    /**
     * @dataProvider allPostActionsProvider
     */
    public function testPostActionWithoutError(string $page, string $action, string $urlPrefix): void
    {
        $response = $this->post($urlPrefix . $page, [
            'action' => $action,
            'token' => 'test',
            'confirm' => 'yes',
        ]);

        $this->assertNoPhpError($response, "$urlPrefix$page (POST action=$action)");
    }

    /**
     * Scan admin/, root and ajax/ PHP files for action values.
     */
    public static function allPostActionsProvider(): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $cases = [];
        $cases += self::extractPostActions($projectRoot . '/admin', '/admin/');
        $cases += self::extractPostActions($projectRoot, '/');
        ksort($cases);
        return $cases;
    }

    /**
     * Extract action values from PHP files in a directory.
     * Scans for patterns like: $action == 'xxx'
     *
     * @param string $dir Directory to scan
     * @param string $urlPrefix URL prefix for the test (e.g. '/admin/', '/')
     * @return array<string, array{0: string, 1: string, 2: string}> [label => [page, action, urlPrefix]]
     */
    private static function extractPostActions(string $dir, string $urlPrefix): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir . '/*.php');
        $cases = [];
        // 'specimen' skipped: GETPOST('object') is empty in tests, causing Class "" not found.
        $skipActions = ['create', 'edit', 'delete', 'view', 'specimen'];
        $excluded = ['t.php'];
        foreach ($files as $file) {
            $basename = basename($file);
            if (in_array($basename, $excluded, true)) {
                continue;
            }
            $content = file_get_contents($file);
            // Match both == (loose) and === (strict) comparisons.
            if (preg_match_all('/\$action\s*===?\s*[\'"]([a-z_]+)[\'"]/i', $content, $matches)) {
                $actions = array_unique($matches[1]);
                foreach ($actions as $act) {
                    if (in_array($act, $skipActions, true)) {
                        continue;
                    }
                    $label = "$basename action=$act";
                    $cases[$label] = [$basename, $act, $urlPrefix];
                }
            }
        }
        return $cases;
    }
}
