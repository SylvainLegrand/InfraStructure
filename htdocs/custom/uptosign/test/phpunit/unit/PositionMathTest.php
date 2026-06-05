<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the magic-keyword position math (pdftotext path = TOOL B).
 *
 * Locks down:
 *   - points -> mm conversion (divide by 2.83, minus the 2 mm offset),
 *   - top-left origin (NO Y flip on this engine),
 *   - 1-based page counting from the <page> tags.
 *
 * The smalot path (TOOL A) needs a real parsed PDF and a flipped Y; it is not
 * covered here, but it shares the exact same constant and offset.
 */
class PositionMathTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosign_autoFindWordPositionInPagepdftotext')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	/**
	 * 283 pt / 2.83 = 100 mm, minus 2 mm offset = 98 mm.
	 * 566 pt / 2.83 = 200 mm, minus 2 mm offset = 198 mm.
	 * Word sits on the first page => human page 1.
	 */
	public function testConversionAndFirstPage(): void
	{
		$lines = [
			'<page width="595.276000" height="841.890000">',
			'<word xMin="283.000000" yMin="566.000000" xMax="320.000000" yMax="580.000000">UPTOSIGN_SIGN_TO_HERE</word>',
			'</page>',
		];

		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($lines, 'UPTOSIGN_SIGN_TO_HERE', $result);

		$this->assertTrue($found);
		$this->assertCount(1, $result);
		$this->assertSame('98', (string) $result[0][0]);   // X mm
		$this->assertSame('198', (string) $result[0][1]);  // Y mm (no flip)
		$this->assertSame(1, $result[0][2]);               // human page
	}

	/**
	 * A keyword located on the SECOND page must be reported as page 2,
	 * not page 1 (regression guard for the page off-by-one).
	 */
	public function testKeywordOnSecondPageReportsPageTwo(): void
	{
		$lines = [
			'<page width="595.276000" height="841.890000">',
			'<word xMin="100.000000" yMin="100.000000" xMax="150.000000" yMax="120.000000">SomethingElse</word>',
			'</page>',
			'<page width="595.276000" height="841.890000">',
			'<word xMin="283.000000" yMin="283.000000" xMax="320.000000" yMax="300.000000">UPTOSIGN_STAMP_SEAL_HERE</word>',
			'</page>',
		];

		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($lines, 'UPTOSIGN_STAMP_SEAL_HERE', $result);

		$this->assertTrue($found);
		$this->assertCount(1, $result);
		$this->assertSame(2, $result[0][2]); // reported on page 2
	}

	public function testNoMatchReturnsFalse(): void
	{
		$lines = [
			'<page width="595.276000" height="841.890000">',
			'<word xMin="10.000000" yMin="10.000000" xMax="20.000000" yMax="20.000000">Hello</word>',
			'</page>',
		];

		$result = [];
		$found = uptosign_autoFindWordPositionInPagepdftotext($lines, 'UPTOSIGN_SIGN_TO_HERE', $result);

		$this->assertFalse($found);
		$this->assertCount(0, $result);
	}
}
