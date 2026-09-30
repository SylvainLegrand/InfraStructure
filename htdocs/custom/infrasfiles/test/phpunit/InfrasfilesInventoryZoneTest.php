<?php
/**
 * NIVEAU 3 — InfrasFilesInventory::infrasfilesFetchLines(), infrasfiles_inventory_zone_config() et
 * infrasfiles_inventory_zone_list() : lecture réelle en base (inventaire de test ZZTEST-INV-02, attribut
 * produit "zone", catégorie parente des zones de localisation). La configuration de la zone vient du module
 * infrasworkflow (colonne "Zone" de l'inventaire) : ses constantes sont posées en mémoire puis restaurées.
 * Lecture seule, encadrée par une transaction annulée. Tests ignorés sans infrasworkflow ou sans jeu de test.
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

final class InfrasfilesInventoryZoneTest extends TestCase
{
	/** @var int Id de l'inventaire de test, 0 si absent */
	private static $inventoryId = 0;
	/** @var int Id de la catégorie parente des zones (sous-catégories NORD / SUD du jeu de test), 0 si absente */
	private static $parentCategoryId = 0;
	/** @var array<string,string|null> Constantes infrasworkflow d'origine */
	private static $saved = [];

	private const KEYS = ['INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 'INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', 'INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY'];

	public static function setUpBeforeClass(): void
	{
		global $db, $conf, $user;

		$db->begin();
		if (empty($user->id)) {
			$user->fetch(1);
			$user->loadRights();
		}
		$resql = $db->query('SELECT rowid FROM '.$db->prefix().'inventory WHERE ref = "ZZTEST-INV-02" AND entity = '.((int) $conf->entity));
		if ($resql && ($obj = $db->fetch_object($resql))) {
			self::$inventoryId = (int) $obj->rowid;
		}
		$resql = $db->query('SELECT c.fk_parent FROM '.$db->prefix().'categorie AS c WHERE c.label = "NORD" AND c.type = 0 AND c.fk_parent > 0 LIMIT 1');
		if ($resql && ($obj = $db->fetch_object($resql))) {
			self::$parentCategoryId = (int) $obj->fk_parent;
		}
		foreach (self::KEYS as $key) {
			self::$saved[$key] = isset($conf->global->$key) ? $conf->global->$key : null;
		}
	}

	public static function tearDownAfterClass(): void
	{
		global $db, $conf;
		foreach (self::$saved as $key => $value) {
			if ($value === null) {
				unset($conf->global->$key);
			} else {
				$conf->global->$key = $value;
			}
		}
		$db->rollback();
	}

	private function configure(int $display, string $extrafield, int $parentCategory): void
	{
		global $conf;
		$conf->global->INFRASWORKFLOW_DISPLAY_ZONE_COLUMN				= $display;
		$conf->global->INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD			= $extrafield;
		$conf->global->INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY	= $parentCategory;
	}

	private function requireTestData(): void
	{
		if (! isModEnabled('infrasworkflow')) {
			$this->markTestSkipped('Module infrasworkflow inactif : la zone de stockage n\'est pas configurable.');
		}
		if (self::$inventoryId <= 0) {
			$this->markTestSkipped('Inventaire de test ZZTEST-INV-02 absent : jeu de données non installé sur cette instance.');
		}
	}

	public function testAttributListeLesZonesSuiventLOrdreDeLaListe(): void
	{
		$this->requireTestData();
		$this->configure(1, 'zone', 0);
		$config = infrasfiles_inventory_zone_config();
		if ($config['source'] !== 'extrafield') {
			$this->markTestSkipped('Attribut produit "zone" absent : regroupement par attribut non testable.');
		}
		$zones = infrasfiles_inventory_zone_list($config);
		$this->assertSame(['A', 'B', 'C', 'D'], array_keys($zones), 'Ordre des valeurs = ordre défini dans l\'attribut supplémentaire');
		$this->assertSame('Zone A', $zones['A']['label'], 'Le code est traduit en libellé');
		$this->assertSame([0, 1, 2, 3], array_column($zones, 'rank'));
	}

	public function testAttributLesLignesSontTrieesParZonePuisReference(): void
	{
		$this->requireTestData();
		$this->configure(1, 'zone', 0);
		if (infrasfiles_inventory_zone_config()['source'] !== 'extrafield') {
			$this->markTestSkipped('Attribut produit "zone" absent.');
		}
		$inventory = infrasfiles_load_object('inventory', self::$inventoryId);
		$this->assertNotNull($inventory);
		$nb = $inventory->infrasfilesFetchLines();
		$this->assertGreaterThan(0, $nb);

		$previousRank	= -1;
		$previousRef	= '';
		$zones			= [];
		foreach ($inventory->infrasfiles_lines as $line) {
			$this->assertGreaterThanOrEqual($previousRank, $line['zone_rank'], 'Rang de zone jamais décroissant');
			if ($line['zone_rank'] === $previousRank) {
				$this->assertLessThanOrEqual(0, strnatcasecmp($previousRef, $line['product_ref']), 'Dans une zone, tri naturel par référence produit');
			} else {
				$previousRef = '';
			}
			$previousRank	= $line['zone_rank'];
			$previousRef	= $line['product_ref'];
			$zones[$line['zone']] = ($zones[$line['zone']] ?? 0) + 1;
		}
		$this->assertSame(['A', 'B', 'C', 'D'], array_keys($zones), 'Les quatre zones du jeu de test, dans l\'ordre');
		$this->assertSame($nb, array_sum($zones));
	}

	public function testCategoriesLesZonesSontLesSousCategoriesParOrdreDeCreation(): void
	{
		global $db;
		$this->requireTestData();
		if (self::$parentCategoryId <= 0) {
			$this->markTestSkipped('Catégorie parente des zones (sous-catégorie NORD) absente.');
		}
		$this->configure(1, '', self::$parentCategoryId);
		$config = infrasfiles_inventory_zone_config();
		$this->assertSame('category', $config['source'], 'Sans attribut : repli sur les catégories, comme la colonne "Zone" d\'infrasworkflow');
		$zones = infrasfiles_inventory_zone_list($config);
		$this->assertNotEmpty($zones);
		$ids = array_map('intval', array_keys($zones));
		$sorted = $ids;
		sort($sorted);
		$this->assertSame($sorted, $ids, 'Ordre de création (rowid croissant) des sous-catégories');
		$this->assertSame(range(0, count($zones) - 1), array_values(array_column($zones, 'rank')));
		// Les lignes ne portent que des zones de la liste (ou aucune)
		$inventory = infrasfiles_load_object('inventory', self::$inventoryId);
		$inventory->infrasfilesFetchLines();
		foreach ($inventory->infrasfiles_lines as $line) {
			$this->assertTrue($line['zone'] === '' || isset($zones[$line['zone']]), 'Zone = id d\'une sous-catégorie de la liste, ou vide');
			if ($line['zone'] !== '') {
				$this->assertSame($zones[$line['zone']]['label'], $line['zone_label']);
			}
		}
	}

	public function testColonneZoneDesactiveeAucuneZone(): void
	{
		$this->requireTestData();
		$this->configure(0, 'zone', self::$parentCategoryId);
		$inventory = infrasfiles_load_object('inventory', self::$inventoryId);
		$inventory->infrasfilesFetchLines();
		$this->assertNotEmpty($inventory->infrasfiles_lines);
		foreach ($inventory->infrasfiles_lines as $line) {
			$this->assertSame('', $line['zone'], 'Colonne "Zone" désactivée : zone vide pour toutes les lignes');
			$this->assertSame(PHP_INT_MAX, $line['zone_rank']);
		}
	}
}
