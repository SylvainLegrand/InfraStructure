<?php
/**
 * NIVEAU 2.5 — InfrasFilesWithdraw::infrasfilesGetUnits() : découpage des PDF (un par tiers ou un par
 * maison mère). L'objet est instancié (constructeur natif sans SQL) mais ses lignes sont injectées à la
 * main dans ->infrasfiles_lines : aucune lecture ni écriture en base.
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

final class InfrasfilesWithdrawUnitsTest extends TestCase
{
	/** @var string|null Valeur d'origine de la constante de découpage */
	private $savedSplitMode = null;
	/** @var bool */
	private $splitModeWasSet = false;

	protected function setUp(): void
	{
		global $conf;
		$this->splitModeWasSet	= isset($conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE);
		$this->savedSplitMode	= $this->splitModeWasSet ? $conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE : null;
	}

	protected function tearDown(): void
	{
		global $conf;
		if ($this->splitModeWasSet) {
			$conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE = $this->savedSplitMode;
		} else {
			unset($conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE);
		}
	}

	/**
	 * Ligne factice d'un bon.
	 *
	 * @param	int		$rowid		Id de ligne
	 * @param	int		$socid		Id du tiers
	 * @param	string	$name		Nom du tiers
	 * @param	string	$code		Code client ('' = aucun)
	 * @param	array	$parent		Maison mère (id, name, code...) ou [] si aucune
	 * @param	int		$toParent	Attribut "adresser à la maison mère" coché (1) ou non (0)
	 * @return	array
	 */
	private function line(int $rowid, int $socid, string $name, string $code, array $parent = [], int $toParent = 0): array
	{
		return [
			'rowid'		=> $rowid,
			'fk_soc'	=> $socid,
			'name'		=> $name,
			'code'		=> $code,
			'address'	=> '',
			'zip'		=> '',
			'town'		=> '',
			'amount'	=> 10.0 * $rowid,
			'fk_parent'	=> $parent ? (int) $parent['id'] : 0,
			'to_parent'	=> $toParent,
			'parent'	=> $parent,
			'documents'	=> [['ref' => 'FA-'.$rowid]],
		];
	}

	/**
	 * Bon factice : maison mère P ; tiers A et B rattachés à P et cochés (A a deux factures) ; tiers C rattaché à P
	 * mais NON coché ; tiers D coché mais SANS maison mère ; tiers E sans rien et sans code client ; et P elle-même
	 * a une facture dans le bon.
	 *
	 * @return InfrasFilesWithdraw
	 */
	private function makeFakeOrder(): InfrasFilesWithdraw
	{
		global $db;
		$parent	= ['id' => 100, 'name' => 'Maison mere P', 'code' => 'CU-P', 'address' => '', 'zip' => '', 'town' => ''];
		$order	= new InfrasFilesWithdraw($db);
		$order->id		= 0;
		$order->ref		= 'ZZTEST';
		$order->type	= 'debit-order';
		$order->infrasfiles_lines = [
			$this->line(1, 10, 'Tiers A', 'CU-A', $parent, 1),
			$this->line(2, 10, 'Tiers A', 'CU-A', $parent, 1),
			$this->line(3, 20, 'Tiers B', 'CU-B', $parent, 1),
			$this->line(4, 30, 'Tiers C', 'CU-C', $parent, 0),
			$this->line(5, 40, 'Tiers D', 'CU-D', [], 1),
			$this->line(6, 50, 'Tiers E', ''),
			$this->line(7, 100, 'Maison mere P', 'CU-P'),
		];
		return $order;
	}

	public function testModeUnPdfParTiersIgnoreLaMaisonMere(): void
	{
		global $conf;
		$conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE = 'thirdparty';
		$units = $this->makeFakeOrder()->infrasfilesGetUnits();

		$this->assertCount(6, $units, 'Six tiers = six PDF, même rattachés et cochés');
		$this->assertSame('CU-A', $units[0]['suffix']);
		$this->assertSame([1, 2], $units[0]['lineids'], 'Les deux factures du tiers A dans la même unité');
		$this->assertFalse($units[0]['toparent'], 'Jamais adressé à la maison mère dans ce mode');
		$this->assertSame('Tiers A', $units[0]['addressee']['name']);
		$this->assertSame('ID50', $units[4]['suffix'], 'Sans code client : repli sur ID<fk_soc>');
		$this->assertSame([7], $units[5]['lineids'], 'La maison mère n\'a que sa propre facture');
	}

	public function testModeUnPdfParMaisonMereRegroupeLesTiersCochesSeulement(): void
	{
		global $conf;
		$conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE = 'parent';
		$units = $this->makeFakeOrder()->infrasfilesGetUnits();

		// P (A + B + sa propre facture), C, D, E = 4 PDF
		$this->assertCount(4, $units, 'Une unité pour la maison mère, une par tiers restant');
		$this->assertTrue($units[0]['toparent']);
		$this->assertSame('CU-P', $units[0]['suffix'], 'Suffixe = code de la maison mère');
		$this->assertSame(100, $units[0]['fk_soc'], 'Destinataire = la maison mère');
		$this->assertSame('Maison mere P', $units[0]['addressee']['name']);
		$this->assertSame([1, 2, 3, 7], $units[0]['lineids'], 'Toutes les factures de A et B sous la maison mère, plus la sienne : un seul PDF pour elle');
		$this->assertSame('CU-C', $units[1]['suffix'], 'Rattaché mais non coché : son propre PDF');
		$this->assertFalse($units[1]['toparent']);
		$this->assertSame('CU-D', $units[2]['suffix'], 'Coché mais sans maison mère : son propre PDF');
		$this->assertFalse($units[2]['toparent']);
		$this->assertSame('ID50', $units[3]['suffix']);
	}

	public function testLaRegleMaisonMereExigeLeRattachementEtLaCoche(): void
	{
		$parent = ['id' => 100, 'name' => 'P', 'code' => 'CU-P', 'address' => '', 'zip' => '', 'town' => ''];
		$this->assertTrue(infrasfiles_line_addressed_to_parent($this->line(1, 10, 'A', 'CU-A', $parent, 1)));
		$this->assertFalse(infrasfiles_line_addressed_to_parent($this->line(1, 10, 'A', 'CU-A', $parent, 0)), 'Maison mère sans coche');
		$this->assertFalse(infrasfiles_line_addressed_to_parent($this->line(1, 10, 'A', 'CU-A', [], 1)), 'Coche sans maison mère');
		$this->assertFalse(infrasfiles_line_addressed_to_parent(['rowid' => 1, 'fk_soc' => 10]), 'Ligne sans les clés (ancien format) : jamais');
	}

	public function testUnAncienModeInconnuRetombeSurUnPdfParTiers(): void
	{
		global $conf;
		$conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE = 'line';	// valeur d'une version antérieure, retirée
		$units = $this->makeFakeOrder()->infrasfilesGetUnits();
		$this->assertCount(6, $units);
		$this->assertSame([1, 2], $units[0]['lineids']);
	}

	public function testLesSuffixesSontDesNomsDeFichiersSurs(): void
	{
		global $conf;
		$conf->global->INFRASFILES_WIDTHDRAW_SPLIT_MODE = 'thirdparty';
		$order = $this->makeFakeOrder();
		$order->infrasfiles_lines[0]['code']	= 'CU/A B';
		$order->infrasfiles_lines[1]['code']	= 'CU/A B';
		$units = $order->infrasfilesGetUnits();
		$this->assertSame(dol_sanitizeFileName('CU/A B'), $units[0]['suffix']);
		$this->assertStringNotContainsString('/', $units[0]['suffix']);
	}
}
