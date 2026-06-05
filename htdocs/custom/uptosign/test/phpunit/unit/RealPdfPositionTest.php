<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end position tests against a REAL generated PDF.
 *
 * A two-page A4 PDF is built on the fly with two magic keywords placed at
 * exact PDF points, then fed to BOTH extraction engines:
 *   - TOOL A = Smalot\PdfParser (uptosign_autoFindWordPositionInPage)
 *   - TOOL B = pdftotext -bbox   (uptosign_autoFindWordPositionInPagepdftotext)
 *
 * The fixture also validates the two recent fixes:
 *   - non-A4-only conversion (here MediaBox is 595.276 x 841.890, not the
 *     rounded 595 x 842 the old code special-cased),
 *   - the smalot page off-by-one (a keyword on physical page 2 must be
 *     reported as page 2, and page 1 must be scanned).
 *
 * Placement math (origin top-left, target of the remote API), with
 * PT_PER_MM = 2.83 and the historical 2 mm offset:
 *   xPt = (Xmm + 2) * 2.83
 *   yPt(baseline, bottom-left) = pageHeightPt - (Ymm + 2) * 2.83
 *
 * Box 1 (page 1, seal):  target X=50mm  Y=50mm   -> baseline (147.16, 694.73)
 * Box 2 (page 2, sign):  target X=100mm Y=200mm  -> baseline (288.66, 270.23)
 */
class RealPdfPositionTest extends TestCase
{
	/** @var string */
	private static $pdfPath = '';

	const PAGE_HEIGHT_PT = 841.890;

	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosign_autoFindWordPositionInPage')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
		self::$pdfPath = sys_get_temp_dir() . '/uts_position_fixture_' . getmypid() . '.pdf';
		file_put_contents(self::$pdfPath, self::buildPdf());
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$pdfPath !== '' && file_exists(self::$pdfPath)) {
			unlink(self::$pdfPath);
		}
	}

	/**
	 * Build a minimal valid two-page A4 PDF with one keyword per page.
	 *
	 * @return string raw PDF bytes
	 */
	private static function buildPdf(): string
	{
		$mb = '[0 0 595.276 841.890]';
		$c1 = "BT /F1 12 Tf 147.16 694.73 Td (UPTOSIGN_STAMP_SEAL_HERE) Tj ET";
		$c2 = "BT /F1 12 Tf 288.66 270.23 Td (UPTOSIGN_SIGN_TO_HERE) Tj ET";

		$o = [];
		$o[1] = "<</Type/Catalog/Pages 2 0 R>>";
		$o[2] = "<</Type/Pages/Kids[3 0 R 5 0 R]/Count 2>>";
		$o[3] = "<</Type/Page/Parent 2 0 R/MediaBox$mb/Resources<</Font<</F1 7 0 R>>>>/Contents 4 0 R>>";
		$o[4] = "<</Length " . strlen($c1) . ">>\nstream\n$c1\nendstream";
		$o[5] = "<</Type/Page/Parent 2 0 R/MediaBox$mb/Resources<</Font<</F1 7 0 R>>>>/Contents 6 0 R>>";
		$o[6] = "<</Length " . strlen($c2) . ">>\nstream\n$c2\nendstream";
		$o[7] = "<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>";

		$pdf = "%PDF-1.4\n";
		$off = [];
		for ($i = 1; $i <= 7; $i++) {
			$off[$i] = strlen($pdf);
			$pdf .= "$i 0 obj\n" . $o[$i] . "\nendobj\n";
		}
		$xrefPos = strlen($pdf);
		$pdf .= "xref\n0 8\n0000000000 65535 f \n";
		for ($i = 1; $i <= 7; $i++) {
			$pdf .= sprintf("%010d 00000 n \n", $off[$i]);
		}
		$pdf .= "trailer\n<</Size 8/Root 1 0 R>>\nstartxref\n$xrefPos\n%%EOF";
		return $pdf;
	}

	/**
	 * Build a single-page PDF whose MediaBox is declared ONLY on the parent
	 * /Pages node (PDF inheritance), not on the /Page itself. This reproduces
	 * the production case (TEREA documents) that raised "Undefined array key 3"
	 * and broke positioning.
	 *
	 * @return string raw PDF bytes
	 */
	private static function buildPdfInheritedMediaBox(): string
	{
		$c1 = "BT /F1 12 Tf 147.16 694.73 Td (UPTOSIGN_STAMP_SEAL_HERE) Tj ET";
		$o = [];
		$o[1] = "<</Type/Catalog/Pages 2 0 R>>";
		// MediaBox is HERE (parent), inherited by the page below.
		$o[2] = "<</Type/Pages/Kids[3 0 R]/Count 1/MediaBox[0 0 595.276 841.890]>>";
		// No MediaBox on the page itself.
		$o[3] = "<</Type/Page/Parent 2 0 R/Resources<</Font<</F1 4 0 R>>>>/Contents 5 0 R>>";
		$o[4] = "<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>";
		$o[5] = "<</Length " . strlen($c1) . ">>\nstream\n$c1\nendstream";

		$pdf = "%PDF-1.4\n";
		$off = [];
		for ($i = 1; $i <= 5; $i++) {
			$off[$i] = strlen($pdf);
			$pdf .= "$i 0 obj\n" . $o[$i] . "\nendobj\n";
		}
		$xrefPos = strlen($pdf);
		$pdf .= "xref\n0 6\n0000000000 65535 f \n";
		for ($i = 1; $i <= 5; $i++) {
			$pdf .= sprintf("%010d 00000 n \n", $off[$i]);
		}
		$pdf .= "trailer\n<</Size 6/Root 1 0 R>>\nstartxref\n$xrefPos\n%%EOF";
		return $pdf;
	}

	/**
	 * Regression guard for the production bug: when MediaBox is only on the
	 * parent /Pages node, the position must still be computed correctly
	 * (previously: "Undefined array key 3" + wrong/empty position).
	 */
	public function testInheritedMediaBoxStillResolves(): void
	{
		$path = sys_get_temp_dir() . '/uts_inherited_mb_' . getmypid() . '.pdf';
		file_put_contents($path, self::buildPdfInheritedMediaBox());

		try {
			$parser = new \Smalot\PdfParser\Parser();
			$pdf = $parser->parseFile($path);

			$result = [];
			$found = uptosign_autoFindWordPositionInPage($pdf, 'UPTOSIGN_STAMP_SEAL_HERE', $result);

			$this->assertTrue($found, 'keyword must still be found with inherited MediaBox');
			$this->assertCount(1, $result);
			$this->assertEqualsWithDelta(50, (float) $result[0][0], 0.5, 'X mm');
			$this->assertEqualsWithDelta(50, (float) $result[0][1], 0.5, 'Y mm resolved from inherited MediaBox');
			$this->assertSame(1, $result[0][2]);
		} finally {
			if (file_exists($path)) {
				unlink($path);
			}
		}
	}

	// --- TOOL A : Smalot ---------------------------------------------------

	public function testSmalotSealOnPageOne(): void
	{
		$parser = new \Smalot\PdfParser\Parser();
		$pdf = $parser->parseFile(self::$pdfPath);

		$result = [];
		$found = uptosign_autoFindWordPositionInPage($pdf, 'UPTOSIGN_STAMP_SEAL_HERE', $result);

		$this->assertTrue($found);
		$this->assertCount(1, $result);
		// Baseline coordinates: smalot is exact (no glyph-box involved).
		$this->assertEqualsWithDelta(50, (float) $result[0][0], 0.5, 'X mm');
		$this->assertEqualsWithDelta(50, (float) $result[0][1], 0.5, 'Y mm');
		$this->assertSame(1, $result[0][2], 'page 1 must be scanned');
	}

	public function testSmalotSignOnPageTwo(): void
	{
		$parser = new \Smalot\PdfParser\Parser();
		$pdf = $parser->parseFile(self::$pdfPath);

		$result = [];
		$found = uptosign_autoFindWordPositionInPage($pdf, 'UPTOSIGN_SIGN_TO_HERE', $result);

		$this->assertTrue($found);
		$this->assertCount(1, $result);
		$this->assertEqualsWithDelta(100, (float) $result[0][0], 0.5, 'X mm');
		$this->assertEqualsWithDelta(200, (float) $result[0][1], 0.5, 'Y mm');
		// Regression guard: previously reported as page 1 (off-by-one).
		$this->assertSame(2, $result[0][2], 'keyword on physical page 2 must report page 2');
	}

	// --- TOOL B : pdftotext ------------------------------------------------

	public function testPdftotextMatchesWithinAscentTolerance(): void
	{
		$cmd = "pdftotext -bbox " . escapeshellarg(self::$pdfPath) . " -";
		$output = [];
		if (exec($cmd, $output) === false || empty($output)) {
			$this->markTestSkipped('pdftotext not available on this host');
		}

		$resSeal = [];
		uptosign_autoFindWordPositionInPagepdftotext($output, 'UPTOSIGN_STAMP_SEAL_HERE', $resSeal);
		$this->assertCount(1, $resSeal);
		$this->assertEqualsWithDelta(50, (float) $resSeal[0][0], 0.5, 'X mm exact');
		// Y comes from the glyph bounding-box top, ~3 mm above the baseline.
		$this->assertEqualsWithDelta(50, (float) $resSeal[0][1], 5, 'Y mm within ascent tolerance');
		$this->assertSame(1, $resSeal[0][2]);

		$resSign = [];
		uptosign_autoFindWordPositionInPagepdftotext($output, 'UPTOSIGN_SIGN_TO_HERE', $resSign);
		$this->assertCount(1, $resSign);
		$this->assertEqualsWithDelta(100, (float) $resSign[0][0], 0.5, 'X mm exact');
		$this->assertEqualsWithDelta(200, (float) $resSign[0][1], 5, 'Y mm within ascent tolerance');
		$this->assertSame(2, $resSign[0][2]);
	}

	/**
	 * Both engines must agree on X (exact) and page, and stay within a few mm
	 * on Y (baseline vs glyph-box top). This is the core "consistency between
	 * TOOL A and TOOL B" guarantee.
	 */
	public function testBothEnginesAgree(): void
	{
		$parser = new \Smalot\PdfParser\Parser();
		$pdf = $parser->parseFile(self::$pdfPath);
		$smalot = [];
		uptosign_autoFindWordPositionInPage($pdf, 'UPTOSIGN_SIGN_TO_HERE', $smalot);

		$cmd = "pdftotext -bbox " . escapeshellarg(self::$pdfPath) . " -";
		$output = [];
		if (exec($cmd, $output) === false || empty($output)) {
			$this->markTestSkipped('pdftotext not available on this host');
		}
		$poppler = [];
		uptosign_autoFindWordPositionInPagepdftotext($output, 'UPTOSIGN_SIGN_TO_HERE', $poppler);

		$this->assertEqualsWithDelta((float) $smalot[0][0], (float) $poppler[0][0], 0.5, 'X agree');
		$this->assertEqualsWithDelta((float) $smalot[0][1], (float) $poppler[0][1], 5, 'Y agree within ascent');
		$this->assertSame($smalot[0][2], $poppler[0][2], 'page agree');
	}
}
