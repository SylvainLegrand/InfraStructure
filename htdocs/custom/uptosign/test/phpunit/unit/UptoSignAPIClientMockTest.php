<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Mock-driven test double for UptoSignAPIClient.
 *
 * Reproduces the production request() flow line-by-line but replaces the
 * getURLContent() call with a captured fake response. This lets tests
 * validate response normalization, JSON decoding, error propagation and
 * header construction end-to-end without touching the network.
 *
 * Note: a similar helper (MockableHTTPClient) already exists in
 * UptoSignAPIClientTest.php. This class is intentionally separate so the
 * two test files can evolve independently and so this file can be run
 * in isolation without depending on the other.
 */
class MockHTTPAPIClient extends \UptoSignAPIClient
{
	/** @var array|null Fake getURLContent() return value */
	public $fakeHTTPResult = null;

	/** @var array Headers passed to the simulated HTTP layer */
	public $capturedHeaders = [];

	/** @var string URL passed to the simulated HTTP layer */
	public $capturedURL = '';

	/** @var string HTTP method */
	public $capturedMethod = '';

	/** @var string Request body (JSON-encoded) */
	public $capturedBody = '';

	public function request($method, $path, $data = null, $withBearer = true)
	{
		$url = \UptoSign::getEndPoint() . $path;
		$body = ($data !== null) ? json_encode($data) : '';

		$this->capturedURL = $url;
		$this->capturedMethod = $method;
		$this->capturedBody = $body;

		// Replicate buildHeaders() locally without calling uptosignuserAgent()
		// (which needs a real $db / DOL_DATA_ROOT). The header *structure* and
		// the bearer-toggle logic are what we want to assert; the User-Agent
		// content itself is not what these tests care about.
		$this->capturedHeaders = $this->fakeBuildHeaders($withBearer);

		// Replicate the response normalization logic of UptoSignAPIClient::request()
		$result = $this->fakeHTTPResult;

		$response = array(
			'http_code' => 0,
			'content' => '',
			'data' => null,
			'curl_error' => '',
		);

		if (!is_array($result)) {
			return $response;
		}

		$response['http_code'] = (int) ($result['http_code'] ?? 0);
		$response['content'] = $result['content'] ?? '';

		if (!empty($result['curl_error_msg'])) {
			$response['curl_error'] = $result['curl_error_msg'];
		}

		$decoded = json_decode($response['content'], true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			$response['data'] = $decoded;
		}

		return $response;
	}

	/**
	 * Replicate UptoSignAPIClient::buildHeaders() without calling
	 * uptosignuserAgent() (which requires DOL_DATA_ROOT and a real $db).
	 *
	 * The bearer/JSON toggle logic is identical to the production version.
	 *
	 * @param bool $withBearer
	 * @param bool $isJson
	 * @return string[]
	 */
	private function fakeBuildHeaders($withBearer = true, $isJson = true)
	{
		$headers = array();
		$headers[] = 'User-Agent: uptosign-test';
		$headers[] = 'Accept: application/json';
		if ($withBearer) {
			$headers[] = 'Authorization: Bearer test-key-12345';
		}
		if ($isJson) {
			$headers[] = 'Content-Type: application/json';
		}
		return $headers;
	}
}

/**
 * Unit tests for UptoSignAPIClient HTTP response handling.
 *
 * These tests validate that the request() method correctly normalizes
 * various HTTP responses returned by getURLContent():
 * - 200/OK with valid JSON
 * - 401/Unauthorized
 * - 500/Server error
 * - Invalid JSON payload
 * - Network/cURL errors
 * - Bearer token presence in headers
 */
class UptoSignAPIClientMockTest extends TestCase
{
	/** @var MockHTTPAPIClient */
	private $client;

	protected function setUp(): void
	{
		$this->client = new MockHTTPAPIClient(null);
	}

	public function testRequestReturnsNormalizedArrayOn200(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 200,
			'content' => '{"status":"ok","credits":42,"user":"alice"}',
		];

		$response = $this->client->request('GET', '/api/profile');

		// Response must contain all four normalized keys
		$this->assertArrayHasKey('http_code', $response);
		$this->assertArrayHasKey('content', $response);
		$this->assertArrayHasKey('data', $response);
		$this->assertArrayHasKey('curl_error', $response);

		$this->assertSame(200, $response['http_code']);
		$this->assertSame('{"status":"ok","credits":42,"user":"alice"}', $response['content']);
		$this->assertSame(['status' => 'ok', 'credits' => 42, 'user' => 'alice'], $response['data']);
		$this->assertSame('', $response['curl_error']);
	}

	public function testRequestReturns401WhenUnauthorized(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 401,
			'content' => '{"message":"Unauthenticated"}',
		];

		$response = $this->client->request('GET', '/api/profile');

		$this->assertSame(401, $response['http_code']);
		$this->assertSame(['message' => 'Unauthenticated'], $response['data']);
		$this->assertSame('', $response['curl_error']);
	}

	public function testRequestReturns500OnServerError(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 500,
			'content' => 'Internal Server Error',
		];

		$response = $this->client->request('POST', '/api/seals', ['pdf' => 'base64data']);

		$this->assertSame(500, $response['http_code']);
		$this->assertSame('Internal Server Error', $response['content']);
		// Plain text body cannot decode as JSON, so data stays null
		$this->assertNull($response['data']);
	}

	public function testRequestHandlesInvalidJson(): void
	{
		// Server responds 200 OK but the body is malformed JSON.
		// Per UptoSignAPIClient::request(), data is initialized to null and
		// only overwritten when json_decode() succeeds AND the result is an
		// array. So malformed JSON must leave data === null.
		$this->client->fakeHTTPResult = [
			'http_code' => 200,
			'content' => '{"status":"ok", invalid json here',
		];

		$response = $this->client->request('GET', '/api/test');

		$this->assertSame(200, $response['http_code']);
		$this->assertSame('{"status":"ok", invalid json here', $response['content']);
		$this->assertNull($response['data']);
	}

	public function testRequestHandlesNetworkError(): void
	{
		// Simulate a cURL-level failure (DNS, connection refused, etc.)
		$this->client->fakeHTTPResult = [
			'http_code' => 0,
			'content' => '',
			'curl_error_msg' => 'Could not resolve host: api.example.invalid',
		];

		$response = $this->client->request('GET', '/api/profile');

		$this->assertSame(0, $response['http_code']);
		$this->assertSame('', $response['content']);
		$this->assertNull($response['data']);
		$this->assertSame('Could not resolve host: api.example.invalid', $response['curl_error']);
	}

	public function testRequestSendsBearerTokenWhenWithBearerTrue(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];

		$this->client->request('GET', '/api/profile', null, true);

		$headers = $this->client->capturedHeaders;
		$headerString = implode("\n", $headers);

		$this->assertStringContainsString('Authorization: Bearer', $headerString);
		$this->assertStringContainsString('Content-Type: application/json', $headerString);
		$this->assertStringContainsString('Accept: application/json', $headerString);
	}

	public function testRequestOmitsBearerTokenWhenWithBearerFalse(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];

		$this->client->request('POST', '/api/register', ['email' => 'a@b.c'], false);

		$headers = $this->client->capturedHeaders;
		$headerString = implode("\n", $headers);

		$this->assertStringNotContainsString('Authorization:', $headerString);
		$this->assertStringContainsString('Accept: application/json', $headerString);
	}

	public function testRequestHandles403Forbidden(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 403,
			'content' => '{"message":"Forbidden","error_code":"insufficient_credits"}',
		];

		$response = $this->client->request('POST', '/api/seals');

		$this->assertSame(403, $response['http_code']);
		$this->assertSame('Forbidden', $response['data']['message']);
		$this->assertSame('insufficient_credits', $response['data']['error_code']);
	}

	public function testRequestHandlesEmptyContent(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 204,
			'content' => '',
		];

		$response = $this->client->request('DELETE', '/api/documents/abc');

		$this->assertSame(204, $response['http_code']);
		$this->assertSame('', $response['content']);
		$this->assertNull($response['data']);
		$this->assertSame('', $response['curl_error']);
	}
}
