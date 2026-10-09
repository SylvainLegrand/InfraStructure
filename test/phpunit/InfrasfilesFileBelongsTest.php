<?php
/**
 * NIVEAU 2 — infrasfiles_file_belongs_to() : règle d'appartenance d'un chemin de fichier posté à un objet, appliquée avant
 * toute suppression en masse. Objet factice (constructeur natif sans SQL, ref posée à la main) : aucun accès base ni disque.
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
dol_include_once('/infrasfiles/class/infrasfileswithdraw.class.php');

use PHPUnit\Framework\TestCase;

final class InfrasfilesFileBelongsTest extends TestCase
{
	private function order(string $ref = 'T260901'): InfrasFilesWithdraw
	{
		global $db;

		$order	= new InfrasFilesWithdraw($db);
		$order->id	= 21;
		$order->ref	= $ref;
		return $order;
	}

	public function testFichiersDeLObjetAcceptes(): void
	{
		$order	= $this->order();
		$this->assertTrue(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901/T260901-CU2609-0006.pdf'));
		$this->assertTrue(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901/Bon_de_prelevement_T260901-CU2609-00065.pdf'));
		$this->assertTrue(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw\\T260901\\depose a la main.pdf'), 'séparateurs Windows normalisés');
	}

	public function testFichiersDAutresObjetsOuElementsRefuses(): void
	{
		$order	= $this->order();
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260902/T260902-CU.pdf'), 'autre bon');
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'inventory/T260901/x.pdf'), 'autre élément, même référence');
		$this->assertFalse(infrasfiles_file_belongs_to('inventory', $order, 'widthdraw/T260901/x.pdf'), 'élément demandé différent de celui du chemin');
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901.pdf'), 'fichier à la racine de l\'élément (ex : SPECIMEN)');
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901/sub/x.pdf'), 'sous-répertoire');
	}

	public function testCheminsDangereuxRefuses(): void
	{
		$order	= $this->order();
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901/../T260902/x.pdf'));
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901/..'));
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T260901/.'));
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, '/widthdraw/T260901/x.pdf'), 'chemin absolu');
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, ''));
	}

	public function testReferenceAvecCaracteresSpeciaux(): void
	{
		$order	= $this->order('T26/09 01');	// le répertoire est la référence nettoyée par dol_sanitizeFileName()
		$subdir	= infrasfiles_get_subdir('widthdraw', $order);
		$this->assertTrue(infrasfiles_file_belongs_to('widthdraw', $order, $subdir.'/x.pdf'));
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw/T26/09 01/x.pdf'), 'référence brute non nettoyée');
	}

	public function testObjetSansReference(): void
	{
		$order	= $this->order('');
		$this->assertFalse(infrasfiles_file_belongs_to('widthdraw', $order, 'widthdraw//x.pdf'));
	}
}
