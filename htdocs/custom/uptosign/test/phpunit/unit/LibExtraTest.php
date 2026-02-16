<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for additional utility functions in lib/uptosign.lib.php
 * (uptosignMergeMessage, uptosignStrRand, uptosignModel,
 *  uptosign_rename_file_dolibarr_guidelines, uptosign_find_next_filename,
 *  uptosign_autoFindWordPositionInPagepdftotext)
 */
class LibExtraTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosignMergeMessage')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	// --- uptosignMergeMessage ---

	public function testMergeMessageJsonArray(): void
	{
		$json = json_encode(['Hello', ' World']);
		$result = uptosignMergeMessage($json);
		$this->assertSame('Hello World', $result);
	}

	public function testMergeMessageJsonString(): void
	{
		$json = json_encode('Simple message');
		$result = uptosignMergeMessage($json);
		$this->assertSame('Simple message', $result);
	}

	public function testMergeMessageJsonObject(): void
	{
		$obj = new \stdClass();
		$obj->error = 'Something failed';
		$json = json_encode($obj);
		$result = uptosignMergeMessage($json);
		// Object is re-encoded to JSON string
		$this->assertStringContainsString('Something failed', $result);
	}

	public function testMergeMessageInvalidJson(): void
	{
		$result = uptosignMergeMessage('not json at all');
		// json_decode returns null for invalid JSON, none of the conditions match
		$this->assertSame('', $result);
	}

	public function testMergeMessageEmptyString(): void
	{
		$result = uptosignMergeMessage('');
		$this->assertSame('', $result);
	}

	public function testMergeMessageJsonNull(): void
	{
		$result = uptosignMergeMessage('null');
		$this->assertSame('', $result);
	}

	public function testMergeMessageStripsSlashes(): void
	{
		$json = json_encode('Hello \"World\"');
		$result = uptosignMergeMessage($json);
		// stripslashes is applied
		$this->assertStringNotContainsString('\\', $result);
	}

	// --- uptosignStrRand ---

	public function testStrRandDefaultLength(): void
	{
		$result = uptosignStrRand();
		$this->assertSame(32, strlen($result));
		$this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $result);
	}

	public function testStrRandCustomLength(): void
	{
		$result = uptosignStrRand(16);
		$this->assertSame(16, strlen($result));
	}

	public function testStrRandUniqueness(): void
	{
		$a = uptosignStrRand();
		$b = uptosignStrRand();
		$this->assertNotSame($a, $b);
	}

	// --- uptosignModel ---

	public function testModelFromModelPdf(): void
	{
		$doc = new \stdClass();
		$doc->model_pdf = 'azur';
		$doc->element = 'propal';
		$result = uptosignModel($doc);
		$this->assertSame('azur', $result);
	}

	public function testModelFallbackToModelpdfField(): void
	{
		$doc = new \stdClass();
		$doc->model_pdf = '';
		$doc->modelpdf = 'crabe';
		$doc->element = 'facture';
		$result = uptosignModel($doc);
		$this->assertSame('crabe', $result);
	}

	public function testModelFromGlobalConst(): void
	{
		global $conf;
		$conf->global->PROPAL_ADDON_PDF = 'azur';

		$doc = new \stdClass();
		$doc->model_pdf = '';
		$doc->modelpdf = '';
		$doc->element = 'propal';
		$result = uptosignModel($doc);
		$this->assertSame('azur', $result);

		unset($conf->global->PROPAL_ADDON_PDF);
	}

	public function testModelEmptyWhenNothingSet(): void
	{
		$doc = new \stdClass();
		$doc->model_pdf = '';
		$doc->modelpdf = '';
		$doc->element = 'nonexistent_element_xyz';
		$result = uptosignModel($doc);
		$this->assertSame('', $result);
	}

	// --- uptosign_rename_file_dolibarr_guidelines ---

	public function testRenameFileBasic(): void
	{
		$result = uptosign_rename_file_dolibarr_guidelines('FA-001.pdf');
		$this->assertSame('FA-001.pdf', $result);
	}

	public function testRenameFileWithSuffix(): void
	{
		$result = uptosign_rename_file_dolibarr_guidelines('FA-001.pdf', 'signed');
		$this->assertSame('FA-001-signed.pdf', $result);
	}

	public function testRenameFileAlreadySuffixed(): void
	{
		$result = uptosign_rename_file_dolibarr_guidelines('FA-001-signed.pdf', 'signed');
		$this->assertSame('FA-001-signed.pdf', $result);
	}

	public function testRenameFileWithTimestamp(): void
	{
		// File has old dolibarr17 timestamp suffix, should be cleaned
		$result = uptosign_rename_file_dolibarr_guidelines('FA-001-20230101120000.pdf');
		$this->assertSame('FA-001.pdf', $result);
	}

	public function testRenameFileWithSuffixAndTimestamp(): void
	{
		$result = uptosign_rename_file_dolibarr_guidelines('FA-001-signed-20230101120000.pdf', 'signed');
		$this->assertSame('FA-001-signed.pdf', $result);
	}

	public function testRenameFileNoExtension(): void
	{
		// No .pdf extension, regex won't match
		$result = uptosign_rename_file_dolibarr_guidelines('FA-001.doc', 'signed');
		$this->assertSame('FA-001.doc', $result);
	}

	// --- uptosign_find_next_filename ---

	public function testFindNextFilenameReturnsInput(): void
	{
		// Current implementation simply returns the input
		$result = uptosign_find_next_filename('FA-001.pdf');
		$this->assertSame('FA-001.pdf', $result);
	}

	public function testFindNextFilenameWithSignOrSeal(): void
	{
		$result = uptosign_find_next_filename('FA-001.pdf', 'sign');
		$this->assertSame('FA-001.pdf', $result);
	}

	// --- uptosign_autoFindWordPositionInPagepdftotext ---

	public function testPdftotextFindsKeyword(): void
	{
		$text = [
			'<page number="1" width="595" height="842">',
			'<word xMin="100.5" yMin="200.3" xMax="150" yMax="210">SIGNATURE</word>',
			'</page>',
		];
		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($text, 'SIGNATURE', $result);

		$this->assertTrue($found);
		$this->assertCount(1, $result);
		$this->assertSame(1, $result[0][2]); // page number
	}

	public function testPdftotextNotFound(): void
	{
		$text = [
			'<page number="1" width="595" height="842">',
			'<word xMin="100" yMin="200" xMax="150" yMax="210">HELLO</word>',
			'</page>',
		];
		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($text, 'SIGNATURE', $result);

		$this->assertFalse($found);
		$this->assertCount(0, $result);
	}

	public function testPdftotextMultiplePages(): void
	{
		$text = [
			'<page number="1" width="595" height="842">',
			'<word xMin="100" yMin="200" xMax="150" yMax="210">HELLO</word>',
			'</page>',
			'<page number="2" width="595" height="842">',
			'<word xMin="50.0" yMin="100.0" xMax="80" yMax="110">SIGNATURE</word>',
			'</page>',
		];
		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($text, 'SIGNATURE', $result);

		$this->assertTrue($found);
		$this->assertCount(1, $result);
		$this->assertSame(2, $result[0][2]); // page 2
	}

	public function testPdftotextMultipleOccurrences(): void
	{
		$text = [
			'<page number="1" width="595" height="842">',
			'<word xMin="100" yMin="200" xMax="150" yMax="210">SIGN</word>',
			'<word xMin="300" yMin="400" xMax="350" yMax="410">SIGN</word>',
			'</page>',
		];
		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($text, 'SIGN', $result);

		$this->assertTrue($found);
		$this->assertCount(2, $result);
	}

	public function testPdftotextEmptyText(): void
	{
		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext([], 'SIGN', $result);

		$this->assertFalse($found);
		$this->assertCount(0, $result);
	}
}
