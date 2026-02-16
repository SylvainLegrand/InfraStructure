<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for uptosign_build_seal_params function
 */
class SealParamsTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosign_build_seal_params')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	public function testBuildSealParamsWithPositions(): void
	{
		$positionsSeal = [
			1 => [
				'STAMP' => [
					'defaultSealX' => 40,
					'defaultSealY' => 20,
					'defaultSealPage' => 1,
				],
			],
		];

		ob_start();
		$result = uptosign_build_seal_params($positionsSeal);
		$output = ob_get_clean();

		$this->assertIsArray($result);
		$this->assertCount(1, $result);
		$this->assertSame('seal-0', $result[0]['paramId']);
		$this->assertSame(40, $result[0]['defaultX']);
		$this->assertSame(20, $result[0]['defaultY']);
		$this->assertSame(1, $result[0]['defaultPage']);

		// Check hidden form fields are printed
		$this->assertStringContainsString('name="seal-0-signX"', $output);
		$this->assertStringContainsString('name="seal-0-signY"', $output);
		$this->assertStringContainsString('name="seal-0-page"', $output);
	}

	public function testBuildSealParamsWithMultiplePages(): void
	{
		$positionsSeal = [
			1 => [
				'STAMP' => [
					'defaultSealX' => 40,
					'defaultSealY' => 20,
					'defaultSealPage' => 1,
				],
			],
			3 => [
				'STAMP' => [
					'defaultSealX' => 100,
					'defaultSealY' => 50,
					'defaultSealPage' => 3,
				],
			],
		];

		ob_start();
		$result = uptosign_build_seal_params($positionsSeal);
		$output = ob_get_clean();

		$this->assertCount(2, $result);
		$this->assertSame('seal-0', $result[0]['paramId']);
		$this->assertSame('seal-1', $result[1]['paramId']);
		$this->assertSame(40, $result[0]['defaultX']);
		$this->assertSame(100, $result[1]['defaultX']);

		// Check hidden fields for both seals
		$this->assertStringContainsString('name="seal-0-signX"', $output);
		$this->assertStringContainsString('name="seal-1-signX"', $output);
	}

	public function testBuildSealParamsEmptyPositionsUsesDefaults(): void
	{
		$positionsSeal = [];

		ob_start();
		$result = uptosign_build_seal_params($positionsSeal);
		$output = ob_get_clean();

		$this->assertCount(1, $result);
		$this->assertSame('seal-0', $result[0]['paramId']);
		$this->assertSame(0, $result[0]['defaultX']);
		$this->assertSame(0, $result[0]['defaultY']);
		$this->assertSame(0, $result[0]['defaultPage']);

		$this->assertStringContainsString('name="seal-0-signX"', $output);
	}

	public function testBuildSealParamsContainsDescription(): void
	{
		$positionsSeal = [];

		ob_start();
		$result = uptosign_build_seal_params($positionsSeal);
		ob_end_clean();

		$this->assertStringContainsString('SCEAU UPTOSIGN', $result[0]['description']);
		$this->assertStringContainsString('uptosign.com', $result[0]['description']);
	}

	public function testBuildSealParamsHiddenFieldsAreInputs(): void
	{
		$positionsSeal = [
			1 => [
				'STAMP' => [
					'defaultSealX' => 40,
					'defaultSealY' => 20,
					'defaultSealPage' => 1,
				],
			],
		];

		ob_start();
		uptosign_build_seal_params($positionsSeal);
		$output = ob_get_clean();

		// All fields should be hidden inputs
		$this->assertSame(3, substr_count($output, 'type="hidden"'));
		$this->assertSame(3, substr_count($output, '<input'));
	}

	// --- uptosign_render_page_nav ---

	public function testRenderPageNavOutputsHtml(): void
	{
		ob_start();
		uptosign_render_page_nav();
		$output = ob_get_clean();

		$this->assertStringContainsString('id="paramPages"', $output);
		$this->assertStringContainsString('id="prev"', $output);
		$this->assertStringContainsString('id="next"', $output);
		$this->assertStringContainsString('id="page_num"', $output);
		$this->assertStringContainsString('id="page_count"', $output);
	}

	public function testRenderPageNavContainsSvgIcons(): void
	{
		ob_start();
		uptosign_render_page_nav();
		$output = ob_get_clean();

		// Should contain 2 SVG icons (prev and next)
		$this->assertSame(2, substr_count($output, '<svg'));
		$this->assertSame(2, substr_count($output, '</svg>'));
	}
}
