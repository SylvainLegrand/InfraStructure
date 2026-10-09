<?php
/**
 * NIVEAU 2.5 — InfrasFilesWithdraw::infrasfilesGetAddressees() et InfrasFilesDocumentTrait::infrasfilesGetFileAddressees() :
 * rapprochement des PDF d'un bon avec le tiers (ou la maison mère) à qui ils sont adressés, à partir du nom du fichier.
 * L'objet est instancié (constructeur natif sans SQL) mais ses lignes sont injectées à la main dans ->infrasfiles_lines
 * et les fichiers sont passés en paramètre : aucune lecture ni écriture en base, aucun accès disque.
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

final class InfrasfilesFileAddresseesTest extends TestCase
{
	/**
	 * Ligne factice d'un bon.
	 *
	 * @param	int		$rowid		Id de ligne
	 * @param	int		$socid		Id du tiers
	 * @param	string	$name		Nom du tiers
	 * @param	string	$code		Code client ('' = aucun)
	 * @param	string	$email		Email du tiers
	 * @param	array	$parent		Maison mère (id, name, code, email) ou [] si aucune
	 * @return	array
	 */
	private function line(int $rowid, int $socid, string $name, string $code, string $email = '', array $parent = []): array
	{
		return [
			'rowid'		=> $rowid,
			'fk_soc'	=> $socid,
			'name'		=> $name,
			'code'		=> $code,
			'email'		=> $email,
			'address'	=> '',
			'zip'		=> '',
			'town'		=> '',
			'amount'	=> 10.0 * $rowid,
			'fk_parent'	=> $parent ? (int) $parent['id'] : 0,
			'to_parent'	=> $parent ? 1 : 0,
			'parent'	=> $parent,
			'documents'	=> [['ref' => 'FA-'.$rowid]],
		];
	}

	/**
	 * Bon factice T260901 : tiers A (CU2609-0006), tiers B (CU2609-00065, dont le code commence par celui de A, rattaché à la
	 * maison mère P), tiers C sans code client (suffixe ID<id>), et P (CU-PARENT) qui n'a pas de ligne en propre.
	 *
	 * @return	InfrasFilesWithdraw
	 */
	private function order(): InfrasFilesWithdraw
	{
		global $db;

		$parent	= ['id' => 300, 'name' => 'Parent P', 'code' => 'CU-PARENT', 'email' => 'parent@example.invalid'];
		$order	= new InfrasFilesWithdraw($db);
		$order->id		= 21;
		$order->ref		= 'T260901';
		$order->type	= 'debit-order';
		$order->infrasfiles_lines	= [
			$this->line(1, 100, 'Tiers A', 'CU2609-0006', 'a@example.invalid'),
			$this->line(2, 100, 'Tiers A', 'CU2609-0006', 'a@example.invalid'),
			$this->line(3, 200, 'Tiers B', 'CU2609-00065', '', $parent),
			$this->line(4, 400, 'Tiers C', ''),
		];
		return $order;
	}

	/**
	 * Fichiers tels que dol_dir_list() les renvoie (seule la clé 'name' est utilisée).
	 *
	 * @param	string[]	$names	Noms de fichiers
	 * @return	array
	 */
	private function files(array $names): array
	{
		return array_map(function ($name) {
			return ['name' => $name, 'fullname' => '/tmp/'.$name];
		}, $names);
	}

	public function testDestinatairesPossiblesTiersEtMaisonsMeres(): void
	{
		$addressees	= $this->order()->infrasfilesGetAddressees();
		$byId	= [];
		foreach ($addressees as $addressee) {
			$byId[$addressee['id']]	= $addressee;
		}
		$this->assertCount(4, $addressees, 'un destinataire par tiers distinct, plus la maison mère');
		$this->assertSame('CU2609-0006', $byId[100]['suffix']);
		$this->assertSame('CU2609-00065', $byId[200]['suffix']);
		$this->assertSame('ID400', $byId[400]['suffix'], 'repli ID<id> sans code client');
		$this->assertSame('CU-PARENT', $byId[300]['suffix'], 'la maison mère est un destinataire possible même sans ligne propre');
		$this->assertSame('parent@example.invalid', $byId[300]['email']);
		$this->assertSame('a@example.invalid', $byId[100]['email']);
	}

	public function testRapprochementNomDeFichierModuleEtInfraSPackPlus(): void
	{
		$map	= $this->order()->infrasfilesGetFileAddressees($this->files([
			'T260901-CU2609-0006.pdf',							// modèle bordereau, tiers A
			'Bon_de_prelevement_T260901-CU2609-00065.pdf',		// préfixe InfraSPackPlus, tiers B
			'T260901-CU2609-00065_Bon.pdf',						// suffixe InfraSPackPlus en mode multi-fichiers, tiers B
			'T260901-CU-PARENT.pdf',							// PDF adressé à la maison mère (mode "par maison mère")
			'T260901-ID400.pdf',								// tiers sans code client
			'T260901.pdf',										// ancien PDF global : aucun destinataire
			'autre-chose.pdf',									// fichier déposé à la main : aucun destinataire
		]));
		$this->assertSame(100, $map['T260901-CU2609-0006.pdf']['id']);
		$this->assertSame(200, $map['Bon_de_prelevement_T260901-CU2609-00065.pdf']['id'], 'CU2609-0006 ne doit pas capter le fichier de CU2609-00065');
		$this->assertSame(200, $map['T260901-CU2609-00065_Bon.pdf']['id']);
		$this->assertSame(300, $map['T260901-CU-PARENT.pdf']['id']);
		$this->assertSame(400, $map['T260901-ID400.pdf']['id']);
		$this->assertArrayNotHasKey('T260901.pdf', $map);
		$this->assertArrayNotHasKey('autre-chose.pdf', $map);
	}

	public function testUneAutreReferenceNestPasRapprochee(): void
	{
		$map	= $this->order()->infrasfilesGetFileAddressees($this->files(['T260902-CU2609-0006.pdf', 'XT260901-CU2609-0006.pdf']));
		$this->assertArrayNotHasKey('T260902-CU2609-0006.pdf', $map, 'autre référence de bon');
		$this->assertSame(100, $map['XT260901-CU2609-0006.pdf']['id'], 'un préfixe collé à la référence reste accepté (préfixe de modèle)');
	}

	public function testObjetSansDestinataires(): void
	{
		global $db;

		$order	= new InfrasFilesWithdraw($db);
		$order->id	= 21;
		$order->ref	= 'T260901';
		$order->specimen	= 1;	// pas de chargement des lignes en base
		$this->assertSame([], $order->infrasfilesGetFileAddressees($this->files(['T260901-CU2609-0006.pdf'])));
	}

	public function testLiensDeLaColonneTiers(): void
	{
		$links	= infrasfiles_get_file_thirdparty_links($this->order(), $this->files(['T260901-CU2609-0006.pdf', 'T260901-CU-PARENT.pdf', 'T260901.pdf']));
		$this->assertCount(2, $links);
		$this->assertStringContainsString('socid=100', $links['T260901-CU2609-0006.pdf']);
		$this->assertStringContainsString('Tiers A', $links['T260901-CU2609-0006.pdf']);
		$this->assertStringContainsString('socid=300', $links['T260901-CU-PARENT.pdf']);
		$this->assertSame('', infrasfiles_get_thirdparty_column_script('#tablelines', []), 'aucun script sans lien');
		$this->assertStringContainsString('#tablelines', infrasfiles_get_thirdparty_column_script('#tablelines', $links));
	}
}
