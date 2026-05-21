<?php

namespace UptoSign\Tests\Http;

/**
 * HTTP functional tests for the seal/sign idempotency guard in uptosign_tab.php.
 *
 * Covers the F5 / back-arrow / double-click scenario: an in-flight UptoSign procedure
 * already exists for a (fk_object, object_type, api_name, path_file) tuple with the
 * exact same sha256 of the source PDF, and a fresh POST must NOT create a duplicate.
 *
 * What is testable end-to-end:
 *   - the idempotency guard branch (PRG redirect to the existing card)
 *   - the count of llx_uptosign rows stays at 1 after repeated POSTs
 *   - the guard correctly skips when the source PDF was regenerated (hash differs)
 *
 * What is NOT testable here (would require a remote DocWizon API mock):
 *   - the nominal flow where the POST actually reaches sealOrSignInitLight()
 *     and creates a new remote procedure
 *
 * @requires PHP >= 8.2
 */
class UptoSignSealIdempotencyHttpTest extends HttpTestCase
{
    /**
     * Set up a fresh fixture (new propal + PDF + in-flight UptoSign) for each test.
     *
     * @return array{propal_id:int, propal_ref:string, pdf_full_path:string, pdf_full_path_b64:string, pdf_rel_path:string, uts_id:int, hash:string}
     */
    private function createSealFixture(): array
    {
        $response = $this->get('/seal-test-fixtures-create');
        $this->assertStatusCode(200, $response);
        $this->assertNotNull($response['json'], 'Fixture endpoint must return JSON');
        $this->assertArrayHasKey('uts_id', $response['json']);
        $this->assertGreaterThan(0, $response['json']['uts_id']);
        return $response['json'];
    }

    /**
     * Read the current count of UptoSign rows for a given object.
     */
    private function countUptoSign(int $fkObject, string $objectType, string $apiName): int
    {
        $response = $this->get(sprintf(
            '/seal-test-count?fk_object=%d&object_type=%s&api_name=%s',
            $fkObject,
            urlencode($objectType),
            urlencode($apiName)
        ));
        $this->assertStatusCode(200, $response);
        return (int) $response['json']['count'];
    }

    /**
     * Build the minimal POST body the idempotency guard inspects.
     * The guard runs BEFORE llxHeader and reads only: action, id, objectType,
     * pdfFileName, token. The stamp/contact fields are not needed because the
     * guard short-circuits the request before they are parsed.
     */
    private function buildSealPostBody(array $fixture, string $action = 'uptoseal'): array
    {
        return [
            'action' => $action,
            'id' => (string) $fixture['propal_id'],
            'objectType' => 'propal',
            'pdfFileName' => $fixture['pdf_full_path_b64'],
            'token' => 'test',
        ];
    }

    public function testGuardRedirectsToExistingProcedureOnDuplicatePost(): void
    {
        $fixture = $this->createSealFixture();

        $response = $this->post('/uptosign_tab.php', $this->buildSealPostBody($fixture));

        $this->assertStatusCode(302, $response);
        $this->assertArrayHasKey('location', $response['headers'], 'PRG must emit a Location header');
        $location = $response['headers']['location'][0];
        $this->assertStringContainsString('uptosign_card.php', $location, 'Should redirect to the existing card');
        $this->assertStringContainsString('id=' . $fixture['uts_id'], $location, 'Should target the in-flight UptoSign id');

        // No new UptoSign row was created.
        $count = $this->countUptoSign($fixture['propal_id'], 'propal', 'uptoseal');
        $this->assertSame(1, $count, 'Duplicate POST must not create a new UptoSign row');
    }

    public function testGuardKeepsSingleProcedureOnRepeatedPost(): void
    {
        $fixture = $this->createSealFixture();
        $body = $this->buildSealPostBody($fixture);

        // First POST -> guard triggers, 302 to existing card.
        $r1 = $this->post('/uptosign_tab.php', $body);
        $this->assertStatusCode(302, $r1);
        // Simulate F5 / browser repost -> guard triggers again, still 302, no new row.
        $r2 = $this->post('/uptosign_tab.php', $body);
        $this->assertStatusCode(302, $r2);

        $this->assertSame(
            $r1['headers']['location'][0],
            $r2['headers']['location'][0],
            'Both POSTs must redirect to the same procedure card'
        );

        $count = $this->countUptoSign($fixture['propal_id'], 'propal', 'uptoseal');
        $this->assertSame(1, $count, 'Repeated POST must keep exactly one UptoSign row');
    }

    public function testGuardSkippedWhenSourceFileWasRegenerated(): void
    {
        $fixture = $this->createSealFixture();

        // Simulate the user editing the propal: Dolibarr regenerates the PDF at the
        // same path with new content -> new sha256 -> guard must NOT short-circuit.
        $bump = $this->get('/seal-test-bump-pdf?path=' . urlencode($fixture['pdf_rel_path']));
        $this->assertStatusCode(200, $bump);
        $this->assertNotSame($fixture['hash'], $bump['json']['new_hash'], 'Sanity: PDF hash must change after bump');

        $response = $this->post('/uptosign_tab.php', $this->buildSealPostBody($fixture));

        // Regardless of what happens downstream (the nominal path tries to reach the
        // remote DocWizon API and may fail in this test environment), the guard must
        // NOT have redirected the user back to the stale in-flight procedure: a fresh
        // procedure for the regenerated document would be legitimate.
        $location = $response['headers']['location'][0] ?? '';
        $this->assertStringNotContainsString(
            'uptosign_card.php?id=' . $fixture['uts_id'],
            $location,
            'Guard must skip when source PDF was regenerated (different sha256)'
        );
    }
}
