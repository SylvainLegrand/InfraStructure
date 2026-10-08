<?php
/**
 * NIVEAU 2 — infrasfiles_mail_invalid_addresses() : contrôle des adresses saisies dans le champ libre de l'envoi par tiers
 * (découpage comme CMailFile, vérification par isValidEmail()), et infrasfiles_is_post_request(). Aucun accès base ni envoi.
 */

global $db, $conf, $user, $langs;

$dolibarrHtdocs = rtrim((string) getenv('DOLIBARR_HTDOCS'), '/');
if (! $dolibarrHtdocs) {
	$dolibarrHtdocs = __DIR__.'/../../../..';
}
if (! is_file($dolibarrHtdocs.'/master.inc.php')) {
	throw new \RuntimeException('master.inc.php introuvable sous '.$dolibarrHtdocs.' — définir DOLIBARR_HTDOCS.');
}
require_once $dolibarrHtdocs.'/master.inc.php';
dol_include_once('/infrasfiles/core/lib/infrasfiles.lib.php');
dol_include_once('/infrasfiles/core/lib/infrasfilesmail.lib.php');

use PHPUnit\Framework\TestCase;

final class InfrasfilesMailAddressTest extends TestCase
{
	/** @var string|null Méthode HTTP d'origine */
	private $savedMethod = null;

	protected function setUp(): void
	{
		$this->savedMethod	= isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
	}

	protected function tearDown(): void
	{
		if ($this->savedMethod === null) {
			unset($_SERVER['REQUEST_METHOD']);
		} else {
			$_SERVER['REQUEST_METHOD']	= $this->savedMethod;
		}
	}

	public function testAdressesValides(): void
	{
		$this->assertSame([], infrasfiles_mail_invalid_addresses('compta@example.com'));
		$this->assertSame([], infrasfiles_mail_invalid_addresses('Jean Dupont <jean@example.com>, autre@example.com'));
		$this->assertSame([], infrasfiles_mail_invalid_addresses(' a@example.com ,  , b@example.com '), 'espaces et entrées vides ignorés');
		$this->assertSame([], infrasfiles_mail_invalid_addresses(''));
	}

	public function testAdressesInvalidesSignalees(): void
	{
		$this->assertSame(['pas-une-adresse'], infrasfiles_mail_invalid_addresses('ok@example.com, pas-une-adresse'));
		$this->assertSame(['faux@'], infrasfiles_mail_invalid_addresses('faux@'));
		$this->assertSame(['Nom <>'], infrasfiles_mail_invalid_addresses('Nom <>'), 'nom sans adresse');
		$this->assertSame(['a@b.fr; c@d.fr'], infrasfiles_mail_invalid_addresses('a@b.fr; c@d.fr'), 'le point-virgule n\'est pas un séparateur pour CMailFile');
	}

	public function testRequetePostSeulement(): void
	{
		$_SERVER['REQUEST_METHOD']	= 'POST';
		$this->assertTrue(infrasfiles_is_post_request());
		$_SERVER['REQUEST_METHOD']	= 'GET';
		$this->assertFalse(infrasfiles_is_post_request());
		unset($_SERVER['REQUEST_METHOD']);
		$this->assertFalse(infrasfiles_is_post_request(), 'CLI ou méthode absente');
	}
}
