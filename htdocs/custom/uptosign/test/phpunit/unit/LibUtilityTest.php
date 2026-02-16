<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for pure utility functions in lib/uptosign.lib.php
 */
class LibUtilityTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosign_relative_path')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	// --- uptosign_relative_path ---

	public function testRelativePathStripsDataRoot(): void
	{
		if (!defined('DOL_DATA_ROOT')) {
			define('DOL_DATA_ROOT', '/var/dolibarr/documents');
		}
		$result = uptosign_relative_path(DOL_DATA_ROOT . '/propal/PR001/file.pdf');
		$this->assertSame('propal/PR001/file.pdf', $result);
	}

	public function testRelativePathAlreadyRelative(): void
	{
		$result = uptosign_relative_path('propal/PR001/file.pdf');
		$this->assertSame('propal/PR001/file.pdf', $result);
	}

	public function testRelativePathNull(): void
	{
		$result = uptosign_relative_path(null);
		$this->assertSame('', $result);
	}

	public function testRelativePathEmpty(): void
	{
		$result = uptosign_relative_path('');
		$this->assertSame('', $result);
	}

	// --- uptosign_full_path ---

	public function testFullPathAddsDataRoot(): void
	{
		$result = uptosign_full_path('propal/PR001/file.pdf');
		$this->assertStringContainsString('propal/PR001/file.pdf', $result);
		$this->assertStringContainsString(DOL_DATA_ROOT, $result);
	}

	public function testFullPathAlreadyFull(): void
	{
		$fullPath = DOL_DATA_ROOT . '/propal/PR001/file.pdf';
		$result = uptosign_full_path($fullPath);
		$this->assertSame($fullPath, $result);
	}

	public function testFullPathEmpty(): void
	{
		$result = uptosign_full_path('');
		$this->assertSame('', $result);
	}

	public function testFullPathNull(): void
	{
		$result = uptosign_full_path(null);
		$this->assertNull($result);
	}

	public function testFullPathSlashHandling(): void
	{
		$result = uptosign_full_path('/propal/file.pdf');
		// Should not double the slash
		$this->assertStringNotContainsString('//', $result);
	}

	// --- uptosign_unify_object_name ---

	public function testUnifyObjectNameSimpleCode(): void
	{
		$this->assertSame('propal', uptosign_unify_object_name('propal'));
	}

	public function testUnifyObjectNameWithColon(): void
	{
		$this->assertSame('azur', uptosign_unify_object_name('propal:azur'));
	}

	public function testUnifyObjectNameWithMultipleColons(): void
	{
		// explode splits all, returns second element
		$this->assertSame('part2', uptosign_unify_object_name('part1:part2:part3'));
	}

	public function testUnifyObjectNameEmpty(): void
	{
		$this->assertSame('', uptosign_unify_object_name(''));
	}

	// --- uptosign_unify_api_name ---

	public function testUnifyApiNameWithoutPrefix(): void
	{
		$this->assertSame('uptosign', uptosign_unify_api_name('sign'));
	}

	public function testUnifyApiNameWithPrefix(): void
	{
		$this->assertSame('uptosign', uptosign_unify_api_name('uptosign'));
	}

	public function testUnifyApiNameSeal(): void
	{
		$this->assertSame('uptoseal', uptosign_unify_api_name('seal'));
	}

	public function testUnifyApiNameAlreadyPrefixedSeal(): void
	{
		$this->assertSame('uptoseal', uptosign_unify_api_name('uptoseal'));
	}

	public function testUnifyApiNameEmpty(): void
	{
		$this->assertSame('upto', uptosign_unify_api_name(''));
	}

	// --- uptosign_make_document_title ---

	public function testMakeDocumentTitleBasicRef(): void
	{
		global $langs;
		$savedLangs = $langs;

		$langs = new \stdClass();
		// Simulate trans() that returns the key if not found
		$langs = $this->createMock(\stdClass::class);

		// Restore and use a simple stub
		$langs = new class {
			public function trans($key, ...$args)
			{
				// Simulate "key not found" behavior
				return $key;
			}
		};

		$result = uptosign_make_document_title('PR-001', '', 'propal');
		// When trans returns the key, falls back to ref
		$this->assertSame('PR-001', $result);

		$langs = $savedLangs;
	}

	public function testMakeDocumentTitleWithCustomerRef(): void
	{
		global $langs;
		$savedLangs = $langs;

		$langs = new class {
			public function trans($key, ...$args)
			{
				return $key;
			}
		};

		$result = uptosign_make_document_title('PR-001', 'CUST-REF-42', 'propal');
		$this->assertSame('PR-001 (CUST-REF-42)', $result);

		$langs = $savedLangs;
	}

	public function testMakeDocumentTitleWithTranslation(): void
	{
		global $langs;
		$savedLangs = $langs;

		$langs = new class {
			public function trans($key, ...$args)
			{
				if ($key === 'UptoSignMailSubjectPropal') {
					return 'Proposition ' . ($args[0] ?? '');
				}
				return $key;
			}
		};

		$result = uptosign_make_document_title('PR-001', '', 'propal');
		$this->assertSame('Proposition PR-001', $result);

		$langs = $savedLangs;
	}

	public function testMakeDocumentTitleEmptyCustomerRef(): void
	{
		global $langs;
		$savedLangs = $langs;

		$langs = new class {
			public function trans($key, ...$args)
			{
				return $key;
			}
		};

		$result = uptosign_make_document_title('FA-001', '', 'facture');
		// No parentheses when customer_ref is empty
		$this->assertStringNotContainsString('(', $result);

		$langs = $savedLangs;
	}
}
