<?php

namespace UptoSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for object type unification functions
 */
class ObjectTypeTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!function_exists('uptosign_unify_object_type')) {
			dol_include_once('/uptosign/lib/uptosign.lib.php');
		}
	}

	// --- uptosign_unify_object_type_from_code ---

	/**
	 * @dataProvider objectTypeFromCodeProvider
	 */
	public function testUnifyObjectTypeFromCode(string $input, string $expected): void
	{
		$this->assertSame($expected, uptosign_unify_object_type_from_code($input));
	}

	public function objectTypeFromCodeProvider(): array
	{
		return [
			'shipping to expedition' => ['shipping', 'expedition'],
			'expedition stays expedition' => ['expedition', 'expedition'],
			'invoice to invoice' => ['invoice', 'invoice'],
			'facture to invoice' => ['facture', 'invoice'],
			'order to order' => ['order', 'order'],
			'commande to order' => ['commande', 'order'],
			'contract to contrat' => ['contract', 'contrat'],
			'contrat stays contrat' => ['contrat', 'contrat'],
			'propal stays propal' => ['propal', 'propal'],
			'fichinter stays fichinter' => ['fichinter', 'fichinter'],
			'unknown stays unchanged' => ['custom_element', 'custom_element'],
		];
	}

	// --- uptosign_unify_object_type ---

	/**
	 * @dataProvider objectTypeProvider
	 */
	public function testUnifyObjectType(string $input, string $expected): void
	{
		$this->assertSame($expected, uptosign_unify_object_type($input));
	}

	public function objectTypeProvider(): array
	{
		return [
			'simple code propal' => ['propal', 'propal'],
			'simple code facture' => ['facture', 'invoice'],
			'simple code shipping' => ['shipping', 'expedition'],
			'colon format propal:azur' => ['propal:azur', 'propal'],
			'colon format facture:crabe' => ['facture:crabe', 'invoice'],
			'colon format commande:einstein' => ['commande:einstein', 'order'],
			'colon format contrat:something' => ['contrat:something', 'contrat'],
		];
	}
}
