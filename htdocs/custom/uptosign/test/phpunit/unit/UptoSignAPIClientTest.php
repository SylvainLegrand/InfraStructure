<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that intercepts the HTTP call
 * and captures request arguments for route/method verification
 */
class TestableAPIClient extends \UptoSignAPIClient
{
	/** @var array Captured request arguments */
	public $lastRequest = [];

	/** @var array Fake response to return */
	public $fakeResponse = [];

	public function request($method, $path, $data = null, $withBearer = true)
	{
		$this->lastRequest = [
			'method' => $method,
			'path' => $path,
			'data' => $data,
			'withBearer' => $withBearer,
		];

		if (!empty($this->fakeResponse)) {
			return $this->fakeResponse;
		}

		return [
			'http_code' => 200,
			'content' => '{}',
			'data' => [],
			'curl_error' => '',
		];
	}
}

/**
 * Testable subclass that uses the REAL request() logic
 * but intercepts getURLContent() via a protected wrapper
 */
class MockableHTTPClient extends \UptoSignAPIClient
{
	/** @var array|null Fake getURLContent result */
	public $fakeHTTPResult = null;

	/** @var string Last URL passed to getURLContent */
	public $lastURL = '';

	/** @var string Last HTTP method */
	public $lastMethod = '';

	/** @var string Last request body */
	public $lastBody = '';

	/** @var array Last headers */
	public $lastHeaders = [];

	public function request($method, $path, $data = null, $withBearer = true)
	{
		global $conf;

		$url = \UptoSign::getEndPoint() . $path;
		$body = ($data !== null) ? json_encode($data) : '';

		$this->lastURL = $url;
		$this->lastMethod = $method;
		$this->lastBody = $body;

		// Build headers using reflection to access private buildHeaders
		$reflection = new \ReflectionMethod($this, 'buildHeaders');
		$reflection->setAccessible(true);
		$this->lastHeaders = $reflection->invoke($this, $withBearer);

		// Use fake HTTP result instead of real getURLContent
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
}

/**
 * Unit tests for UptoSignAPIClient - API method routing
 */
class UptoSignAPIClientTest extends TestCase
{
	/** @var TestableAPIClient */
	private $client;

	protected function setUp(): void
	{
		$this->client = new TestableAPIClient(null);
	}

	// --- getProfile ---

	public function testGetProfileCallsPostProfile(): void
	{
		$this->client->getProfile();

		$this->assertSame('POST', $this->client->lastRequest['method']);
		$this->assertSame('/api/profile', $this->client->lastRequest['path']);
		$this->assertTrue($this->client->lastRequest['withBearer']);
		$this->assertArrayHasKey('json', $this->client->lastRequest['data']);
	}

	// --- createAccount ---

	public function testCreateAccountCallsPostRegister(): void
	{
		$this->client->createAccount('John', 'Doe', 'john@example.com', 'secret123');

		$this->assertSame('POST', $this->client->lastRequest['method']);
		$this->assertSame('/api/register', $this->client->lastRequest['path']);
		$this->assertFalse($this->client->lastRequest['withBearer']);
		$this->assertSame('John', $this->client->lastRequest['data']['firstname']);
		$this->assertSame('Doe', $this->client->lastRequest['data']['lastname']);
		$this->assertSame('john@example.com', $this->client->lastRequest['data']['email']);
		$this->assertSame('secret123', $this->client->lastRequest['data']['password']);
		$this->assertSame('secret123', $this->client->lastRequest['data']['password_confirmation']);
	}

	// --- login ---

	public function testLoginCallsPostLogin(): void
	{
		$this->client->login('user@example.com', 'pass123');

		$this->assertSame('POST', $this->client->lastRequest['method']);
		$this->assertSame('/api/login', $this->client->lastRequest['path']);
		$this->assertFalse($this->client->lastRequest['withBearer']);
		$this->assertSame('user@example.com', $this->client->lastRequest['data']['email']);
		$this->assertSame('pass123', $this->client->lastRequest['data']['password']);
	}

	// --- healthCheck ---

	public function testHealthCheckCallsGetRuok(): void
	{
		$this->client->healthCheck('admin@test.com', '2.0');

		$this->assertSame('GET', $this->client->lastRequest['method']);
		$this->assertSame('/api/ruok', $this->client->lastRequest['path']);
		$this->assertTrue($this->client->lastRequest['withBearer']);
		$this->assertSame('admin@test.com', $this->client->lastRequest['data']['json']['email']);
		$this->assertSame('2.0', $this->client->lastRequest['data']['json']['protocol']);
	}

	// --- createProcedure ---

	public function testCreateProcedureSealCallsPostSeals(): void
	{
		$this->client->createProcedure(['pdf' => 'base64data'], 'seal');

		$this->assertSame('POST', $this->client->lastRequest['method']);
		$this->assertSame('/api/seals', $this->client->lastRequest['path']);
		$this->assertTrue($this->client->lastRequest['withBearer']);
	}

	public function testCreateProcedureSignCallsPostDocuments(): void
	{
		$this->client->createProcedure(['pdf' => 'base64data'], 'sign');

		$this->assertSame('POST', $this->client->lastRequest['method']);
		$this->assertSame('/api/documents', $this->client->lastRequest['path']);
	}

	// --- getDocumentStatus ---

	public function testGetDocumentStatusCallsGetDocuments(): void
	{
		$this->client->getDocumentStatus('abc-123');

		$this->assertSame('GET', $this->client->lastRequest['method']);
		$this->assertSame('/api/documents/abc-123', $this->client->lastRequest['path']);
		$this->assertTrue($this->client->lastRequest['withBearer']);
	}

	// --- downloadDocument ---

	public function testDownloadDocumentCallsCorrectPath(): void
	{
		$this->client->downloadDocument('doc-456');

		$this->assertSame('GET', $this->client->lastRequest['method']);
		$this->assertSame('/api/documents/doc-456/download', $this->client->lastRequest['path']);
		$this->assertTrue($this->client->lastRequest['withBearer']);
	}

	// --- downloadProof ---

	public function testDownloadProofCallsCorrectPath(): void
	{
		$this->client->downloadProof('doc-789');

		$this->assertSame('GET', $this->client->lastRequest['method']);
		$this->assertSame('/api/documents/doc-789/downloadProof', $this->client->lastRequest['path']);
	}

	// --- deleteDocument ---

	public function testDeleteDocumentCallsDeleteMethod(): void
	{
		$this->client->deleteDocument('doc-del');

		$this->assertSame('DELETE', $this->client->lastRequest['method']);
		$this->assertSame('/api/documents/doc-del', $this->client->lastRequest['path']);
		$this->assertTrue($this->client->lastRequest['withBearer']);
	}

	// --- Response structure ---

	public function testResponseStructure(): void
	{
		$response = $this->client->getProfile();

		$this->assertArrayHasKey('http_code', $response);
		$this->assertArrayHasKey('content', $response);
		$this->assertArrayHasKey('data', $response);
		$this->assertArrayHasKey('curl_error', $response);
	}
}

/**
 * Unit tests for UptoSignAPIClient - request() response normalization and headers
 */
class UptoSignAPIClientRequestTest extends TestCase
{
	/** @var MockableHTTPClient */
	private $client;

	protected function setUp(): void
	{
		$this->client = new MockableHTTPClient(null);
	}

	// --- Response normalization ---

	public function testRequestNormalizesValidJsonResponse(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 200,
			'content' => '{"status":"ok","credits":42}',
		];

		$response = $this->client->request('GET', '/api/test');

		$this->assertSame(200, $response['http_code']);
		$this->assertSame('{"status":"ok","credits":42}', $response['content']);
		$this->assertSame(['status' => 'ok', 'credits' => 42], $response['data']);
		$this->assertSame('', $response['curl_error']);
	}

	public function testRequestNormalizesNonJsonContent(): void
	{
		// Binary content (e.g. PDF download)
		$this->client->fakeHTTPResult = [
			'http_code' => 200,
			'content' => '%PDF-1.4 binary content here',
		];

		$response = $this->client->request('GET', '/api/documents/123/download');

		$this->assertSame(200, $response['http_code']);
		$this->assertNull($response['data']);
	}

	public function testRequestHandlesCurlError(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 0,
			'content' => '',
			'curl_error_msg' => 'Connection refused',
		];

		$response = $this->client->request('POST', '/api/test');

		$this->assertSame(0, $response['http_code']);
		$this->assertSame('Connection refused', $response['curl_error']);
	}

	public function testRequestHandlesNonArrayResult(): void
	{
		$this->client->fakeHTTPResult = null;

		$response = $this->client->request('GET', '/api/test');

		$this->assertSame(0, $response['http_code']);
		$this->assertSame('', $response['content']);
		$this->assertNull($response['data']);
		$this->assertSame('', $response['curl_error']);
	}

	public function testRequestHandles404Response(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 404,
			'content' => '{"error":"Not found"}',
		];

		$response = $this->client->request('GET', '/api/documents/nonexistent');

		$this->assertSame(404, $response['http_code']);
		$this->assertSame(['error' => 'Not found'], $response['data']);
	}

	public function testRequestHandles500Response(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 500,
			'content' => 'Internal Server Error',
		];

		$response = $this->client->request('POST', '/api/seals');

		$this->assertSame(500, $response['http_code']);
		$this->assertNull($response['data']);
	}

	public function testRequestHandlesEmptyJsonResponse(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 200,
			'content' => '{}',
		];

		$response = $this->client->request('GET', '/api/test');

		$this->assertSame([], $response['data']);
	}

	public function testRequestHandlesMissingHttpCode(): void
	{
		$this->client->fakeHTTPResult = [
			'content' => '{"ok":true}',
		];

		$response = $this->client->request('GET', '/api/test');

		$this->assertSame(0, $response['http_code']);
		$this->assertSame(['ok' => true], $response['data']);
	}

	public function testRequestHandlesMissingContent(): void
	{
		$this->client->fakeHTTPResult = [
			'http_code' => 204,
		];

		$response = $this->client->request('DELETE', '/api/test');

		$this->assertSame(204, $response['http_code']);
		$this->assertSame('', $response['content']);
	}

	// --- buildHeaders (tested via reflection in MockableHTTPClient) ---

	public function testBuildHeadersWithBearer(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];
		$this->client->request('GET', '/api/test', null, true);

		$headers = $this->client->lastHeaders;
		$headerString = implode("\n", $headers);

		$this->assertStringContainsString('User-Agent:', $headerString);
		$this->assertStringContainsString('Accept: application/json', $headerString);
		$this->assertStringContainsString('Authorization: Bearer', $headerString);
		$this->assertStringContainsString('Content-Type: application/json', $headerString);
	}

	public function testBuildHeadersWithoutBearer(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];
		$this->client->request('POST', '/api/register', ['email' => 'test@test.com'], false);

		$headers = $this->client->lastHeaders;
		$headerString = implode("\n", $headers);

		$this->assertStringContainsString('User-Agent:', $headerString);
		$this->assertStringContainsString('Accept: application/json', $headerString);
		$this->assertStringNotContainsString('Authorization:', $headerString);
	}

	// --- URL construction ---

	public function testRequestBuildsCorrectURL(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];
		$this->client->request('GET', '/api/documents/abc-123');

		$this->assertStringContainsString('/api/documents/abc-123', $this->client->lastURL);
	}

	// --- Body encoding ---

	public function testRequestEncodesDataAsJson(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];
		$data = ['key' => 'value', 'number' => 42];
		$this->client->request('POST', '/api/test', $data);

		$this->assertSame(json_encode($data), $this->client->lastBody);
	}

	public function testRequestNullDataSendsEmptyBody(): void
	{
		$this->client->fakeHTTPResult = ['http_code' => 200, 'content' => '{}'];
		$this->client->request('GET', '/api/test', null);

		$this->assertSame('', $this->client->lastBody);
	}
}
