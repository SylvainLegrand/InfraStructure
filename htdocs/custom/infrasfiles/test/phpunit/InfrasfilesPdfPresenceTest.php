<?php
/**
 * MODE B — présence d'éléments attendus dans les PDF réellement générés par les modèles du module,
 * à partir des objets SPÉCIMEN (infrasfilesInitAsSpecimen : aucune écriture en base, seul le premier
 * compte bancaire est lu). Sortie redirigée vers un répertoire temporaire supprimé en tearDown().
 * Vérification de texte par pdftotext (jamais de jugement visuel).
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

use PHPUnit\Framework\TestCase;

final class InfrasfilesPdfPresenceTest extends TestCase
{
	private const SKILL_DIR = '/etc/claude-code/skills/infras-module-selftest';

	/** @var string */
	private $tmpDir = '';
	/** @var array<string,mixed> */
	private $savedConf = [];
	/** @var array<string,string|null> */
	private $savedConstants = [];

	protected function setUp(): void
	{
		global $conf, $user, $langs;
		if (! is_executable('/usr/bin/pdftotext') && ! shell_exec('command -v pdftotext')) {
			$this->markTestSkipped('pdftotext absent');
		}
		if (empty($user->id)) {
			$user->fetch(1);
			$user->loadRights();
		}
		$langs->loadLangs(['main', 'stocks', 'infrasfiles@infrasfiles']);
		$this->tmpDir = sys_get_temp_dir().'/infrasfiles-selftest-'.uniqid('', true);
		mkdir($this->tmpDir, 0770, true);
		if (empty($conf->infrasfiles)) {
			$conf->infrasfiles = new \stdClass();
		}
		$this->savedConf = ['dir_output' => $conf->infrasfiles->dir_output ?? null, 'multidir_output' => $conf->infrasfiles->multidir_output ?? null, 'models' => $conf->modules_parts['models'] ?? null];
		$conf->infrasfiles->dir_output		= $this->tmpDir;
		$conf->infrasfiles->multidir_output	= [$conf->entity => $this->tmpDir];
		if (empty($conf->modules_parts['models']) || ! in_array('/infrasfiles/', (array) $conf->modules_parts['models'])) {
			$conf->modules_parts['models'][] = '/infrasfiles/';
		}
	}

	protected function tearDown(): void
	{
		global $conf;
		foreach ($this->savedConstants as $key => $value) {
			if ($value === null) {
				unset($conf->global->$key);
			} else {
				$conf->global->$key = $value;
			}
		}
		$this->savedConstants = [];
		$conf->infrasfiles->dir_output		= $this->savedConf['dir_output'];
		$conf->infrasfiles->multidir_output	= $this->savedConf['multidir_output'];
		$conf->modules_parts['models']		= $this->savedConf['models'];
		if ($this->tmpDir && is_dir($this->tmpDir)) {
			exec('rm -rf '.escapeshellarg($this->tmpDir));
		}
	}

	private function setConst(string $key, $value): void
	{
		global $conf;
		if (! array_key_exists($key, $this->savedConstants)) {
			$this->savedConstants[$key] = isset($conf->global->$key) ? $conf->global->$key : null;
		}
		$conf->global->$key = $value;
	}

	/**
	 * Génère le PDF spécimen d'un modèle et renvoie son chemin.
	 *
	 * @param	string	$element	Clé du registre
	 * @param	string	$model		Nom du modèle (pdf_<model>.modules.php)
	 * @param	callable|null	$prepare	Modification du spécimen avant génération (ex. inventaire d'un seul entrepôt, sans lot)
	 * @return	string				Chemin du PDF
	 */
	private function generateSpecimen(string $element, string $model, int $unitIndex = 0, ?callable $prepare = null): string
	{
		global $db, $langs;
		$specimen = infrasfiles_load_specimen($element);
		$this->assertNotNull($specimen, 'Spécimen '.$element);
		if ($prepare !== null) {
			$prepare($specimen);
		}
		$models = infrasfiles_get_models($element);
		$this->assertArrayHasKey($model, $models, 'Modèle '.$model.' trouvé pour '.$element);
		require_once $models[$model]['file'];
		$classname	= $models[$model]['classname'];
		$module		= new $classname($db);
		$units		= method_exists($specimen, 'infrasfilesGetUnits') ? $specimen->infrasfilesGetUnits() : [];
		$unit		= isset($units[$unitIndex]) ? $units[$unitIndex] : ['suffix' => '', 'label' => ''];
		$result		= $module->write_file($specimen, $langs, '', 0, 0, 0, ['infrasfiles_unit' => $unit]);
		$this->assertSame(1, $result, 'write_file : '.$module->error);
		$this->assertFileExists($module->result['fullpath']);
		$this->assertStringStartsWith($this->tmpDir, $module->result['fullpath'], 'Le PDF est écrit dans le répertoire temporaire, jamais dans les documents de l\'instance');
		return $module->result['fullpath'];
	}

	private function assertPdfContains(string $pdf, string $needle, bool $present = true): void
	{
		exec(sprintf('%s %s %s%s', escapeshellarg(self::SKILL_DIR.'/scripts/check-pdf-text.sh'), escapeshellarg($pdf), escapeshellarg($needle), $present ? '' : ' --absent'), $out, $status);
		$this->assertSame(0, $status, ($present ? 'Texte attendu absent : ' : 'Texte inattendu présent : ').$needle);
	}

	public function testBordereauContientEnTeteBlocsEtTexteLibre(): void
	{
		global $langs;
		$this->setConst('INFRASFILES_WIDTHDRAW_FREE_TEXT', 'ZZFREETEXT-BORDEREAU');
		$this->setConst('INFRASFILES_WIDTHDRAW_WATERMARK', 'ZZWATERMARK');
		$pdf = $this->generateSpecimen('widthdraw', 'bordereau');

		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfTitleDebit'));
		$this->assertPdfContains($pdf, 'SPECIMEN');
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfDebtor'));
		$this->assertPdfContains($pdf, 'RUM-SPECIMEN');
		$this->assertPdfContains($pdf, 'ZZFREETEXT-BORDEREAU');
		// Le filigrane est un texte pivoté : pdftotext -layout ne le restitue pas, -raw oui
		$raw = shell_exec('pdftotext -raw '.escapeshellarg($pdf).' - 2>/dev/null');
		$this->assertStringContainsString('ZZWATERMARK', (string) $raw, 'Filigrane présent sur un document brouillon');
	}

	public function testBordereauParTiersMentionneLaMaisonMereEcheanceEtAvoir(): void
	{
		global $langs;
		$this->setConst('INFRASFILES_WIDTHDRAW_SPLIT_MODE', 'thirdparty');
		// Spécimen : unité 0 = tiers A (sans maison mère), unité 1 = tiers B (filiale cochée, échéance, avoir sur la 2e facture)
		$pdf = $this->generateSpecimen('widthdraw', 'bordereau', 1);

		$this->assertPdfContains($pdf, $langs->transnoentities('ParentCompany').' : ', true);
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfColDueDate'));
		$this->assertPdfContains($pdf, $langs->transnoentities('CreditNote').' AV-SPECIMEN-1');
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-2');
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-1', false);	// la facture du tiers A n'est pas dans le PDF du tiers B
	}

	public function testBordereauParMaisonMereEstAdresseALaMaisonMere(): void
	{
		global $langs;
		$this->setConst('INFRASFILES_WIDTHDRAW_SPLIT_MODE', 'parent');
		// Spécimen en mode maison mère : unité 0 = tiers A, unité 1 = la maison mère (lignes 2 et 3 du tiers B)
		$pdf = $this->generateSpecimen('widthdraw', 'bordereau', 1);

		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesSpecimen').' '.$langs->transnoentities('ParentCompany'), true);
		$this->assertPdfContains($pdf, $langs->transnoentities('ParentCompany').' : ', false);	// pas de mention "Maison mère :" quand le PDF lui est adressé
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-2');
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-3');
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-1', false);
	}

	public function testModeleInfraSPlusBonDInfraspackplus(): void
	{
		global $langs;
		// Modèle fourni par infraspackplus dans core/modules/infrasfiles/widthdraw/doc/ : même contenu que « bordereau », mise en page InfraSPlus
		$models = infrasfiles_get_models('widthdraw');
		if (! isset($models['InfraSPlus_Bon'])) {
			$this->markTestSkipped('Modèle InfraSPlus_Bon absent (module infraspackplus inactif ou ancien) : test ignoré.');
		}
		$this->setConst('INFRASFILES_WIDTHDRAW_SPLIT_MODE', 'parent');
		$this->setConst('INFRASFILES_WIDTHDRAW_WATERMARK', 'ZZWATERMARK');
		$pdf = $this->generateSpecimen('widthdraw', 'InfraSPlus_Bon', 1);

		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfTitleDebit'));
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesSpecimen').' '.$langs->transnoentities('ParentCompany'));
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfColDueDate'));
		$this->assertPdfContains($pdf, $langs->transnoentities('CreditNote').' AV-SPECIMEN-1');
		$this->assertPdfContains($pdf, 'RUM-SPECIMEN');
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-3');
		$this->assertPdfContains($pdf, 'FA-SPECIMEN-1', false);
		$raw = shell_exec('pdftotext -raw '.escapeshellarg($pdf).' - 2>/dev/null');
		$this->assertStringContainsString('ZZWATERMARK', (string) $raw, 'Filigrane brouillon d\'InfraSFiles conservé sur le modèle InfraSPlus');
	}

	public function testFeuilleDeComptageZoneEnColonneEtMasqueLaQuantiteParDefaut(): void
	{
		global $langs;
		$this->setConst('INFRASFILES_INVENTORY_SHOW_QTY', 0);
		$pdf = $this->generateSpecimen('inventory', 'comptage', 0, $this->typicalInventory());

		$this->assertCountingSheetLayout($pdf);
		$this->assertPdfContains($pdf, $langs->transnoentities('PhysicalStock'), false);
	}

	/**
	 * Spécimen d'inventaire « courant » : un seul entrepôt, aucun produit à lot. Le spécimen brut affiche toutes les colonnes
	 * optionnelles (entrepôt, lot) : sur une page portrait, ses colonnes sont alors resserrées et certains textes passent à la ligne.
	 *
	 * @return	callable
	 */
	private function typicalInventory(): callable
	{
		return function ($specimen) {
			$specimen->fk_warehouse = 1;
			foreach ($specimen->infrasfiles_lines as $i => $line) {
				$specimen->infrasfiles_lines[$i]['batch']	= '';
				$specimen->infrasfiles_lines[$i]['tobatch']	= 0;
			}
		};
	}

	/**
	 * Mise en page commune des deux feuilles de comptage (spécimen : zones A et B) : zone en colonne (plus de bandeau « Zone : … »),
	 * colonnes « Quantité relevée » puis « Recomptage », lignes « Compté par » et « Recompté par », zones dans l'ordre A puis B.
	 *
	 * @param	string	$pdf	Chemin du PDF
	 * @return	void
	 */
	private function assertCountingSheetLayout(string $pdf): void
	{
		global $langs;
		$zone = $langs->transnoentities('InfraSFilesPdfZone');
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfTitleCounting'));
		$this->assertPdfContains($pdf, $zone.' : '.$zone.' A', false);	// plus de bandeau de zone
		$this->assertPdfContains($pdf, $zone.' A');
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfColCounted'));
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfColRecount'));
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfCountedBy'));
		$this->assertPdfContains($pdf, $langs->transnoentities('InfraSFilesPdfRecountedBy'));
		$text = (string) shell_exec('pdftotext -layout '.escapeshellarg($pdf).' - 2>/dev/null');
		$this->assertMatchesRegularExpression('/'.preg_quote($langs->transnoentities('InfraSFilesPdfColCounted'), '/').'\s+'.preg_quote($langs->transnoentities('InfraSFilesPdfColRecount'), '/').'/u', $text, 'Colonne « Recomptage » juste après « Quantité relevée »');
		$this->assertLessThan(strrpos($text, $zone.' B'), strpos($text, $zone.' A'), 'Lignes triées par zone : A avant B');
		$this->assertLessThan(strpos($text, $langs->transnoentities('InfraSFilesPdfRecountedBy')), strpos($text, $langs->transnoentities('InfraSFilesPdfCountedBy')), '« Recompté par » après « Compté par »');
	}

	public function testModeleInfraSPlusINVDInfraspackplus(): void
	{
		global $langs;
		// Modèle fourni par infraspackplus dans core/modules/infrasfiles/inventory/doc/ : même contenu que « comptage », mise en page InfraSPlus
		$models = infrasfiles_get_models('inventory');
		if (! isset($models['InfraSPlus_INV'])) {
			$this->markTestSkipped('Modèle InfraSPlus_INV absent (module infraspackplus inactif ou ancien) : test ignoré.');
		}
		$this->setConst('INFRASFILES_INVENTORY_SHOW_QTY', 0);
		$pdf = $this->generateSpecimen('inventory', 'InfraSPlus_INV', 0, $this->typicalInventory());

		$this->assertCountingSheetLayout($pdf);
		$this->assertPdfContains($pdf, 'PROD-SPECIMEN-5');
		$this->assertPdfContains($pdf, $langs->transnoentities('PhysicalStock'), false);
	}

	public function testFeuilleDeComptageAfficheLaQuantiteAvecLOption(): void
	{
		global $langs;
		$this->setConst('INFRASFILES_INVENTORY_SHOW_QTY', 1);
		$pdf = $this->generateSpecimen('inventory', 'comptage', 0, $this->typicalInventory());
		$this->assertPdfContains($pdf, $langs->transnoentities('PhysicalStock'));
	}

	/**
	 * Feuilles de comptage toujours en portrait, même avec toutes les colonnes optionnelles du spécimen brut (zone, entrepôt, lot, stock physique) :
	 * les textes longs passent à la ligne dans leur cellule (décision du 2026-10-07, pas de bascule en paysage).
	 */
	public function testFeuillesDeComptageToujoursEnPortrait(): void
	{
		$this->setConst('INFRASFILES_INVENTORY_SHOW_QTY', 1);
		$models = infrasfiles_get_models('inventory');
		foreach (['comptage', 'InfraSPlus_INV'] as $model) {
			if (! isset($models[$model])) {
				continue;
			}
			$pdf  = $this->generateSpecimen('inventory', $model);
			$info = (string) shell_exec('pdfinfo '.escapeshellarg($pdf).' 2>/dev/null');
			$this->assertMatchesRegularExpression('/Page size:\s+([\d.]+) x ([\d.]+)/', $info, 'pdfinfo indisponible ou sortie inattendue');
			preg_match('/Page size:\s+([\d.]+) x ([\d.]+)/', $info, $m);
			$this->assertLessThan((float) $m[2], (float) $m[1], 'Modèle '.$model.' : page en portrait (largeur < hauteur)');
		}
	}
}
