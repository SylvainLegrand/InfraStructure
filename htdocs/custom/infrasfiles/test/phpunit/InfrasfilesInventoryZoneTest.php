<?php
/**
 * NIVEAU 3 — InfrasFilesInventory::infrasfilesFetchLines(), infrasfiles_inventory_zone_config() et
 * infrasfiles_inventory_zone_list() : lecture réelle en base (inventaire de test ZZTEST-INV-02, attribut
 * produit "zone", catégorie parente des zones de localisation). La configuration de la zone vient du module
 * infrasworkflow (colonne "Zone" de l'inventaire) : ses constantes sont posées en mémoire puis restaurées.
 * Lecture seule, encadrée par une transaction annulée (seule l'arborescence de zones du test d'arborescence est créée,
 * dans cette transaction). Tests ignorés sans infrasworkflow ou sans jeu de test.
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

	/**
	 * Zone de chaque produit de l'inventaire telle que l'affiche la colonne "Zone" de l'onglet inventaire
	 * (même fragment SQL que la page substituée d'infrasworkflow), indexée par id produit
	 */
	private function zonesOfTheInventoryTab(int $inventoryId): array
	{
		global $db;
		$parts = infrasworkflow_inventoryZoneSqlParts();
		$sql = 'SELECT d.fk_product'.$parts['select'].' FROM '.$db->prefix().'inventorydet AS d';
		$sql .= ' LEFT JOIN '.$db->prefix().'product AS p ON p.rowid = d.fk_product'.$parts['join'];
		$sql .= ' WHERE d.fk_inventory = '.((int) $inventoryId);
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, (string) $db->lasterror());
		$result = [];
		while ($obj = $db->fetch_object($resql)) {
			$result[(int) $obj->fk_product] = (string) ($obj->infras_zone ?? '');
		}
		return $result;
	}

	private function createCategory(string $label, int $parentId): int
	{
		global $db, $user;
		$cat = new Categorie($db);
		$cat->label = $label;
		$cat->description = '';
		$cat->color = '';
		$cat->ref_ext = '';
		$cat->type = Categorie::TYPE_PRODUCT;
		$cat->fk_parent = $parentId;
		$id = $cat->create($user);
		$this->assertGreaterThan(0, $id, 'Création de la catégorie '.$label.' : '.$cat->error);
		return (int) $id;
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
		$this->assertSame(range(0, count($zones) - 1), array_values(array_column($zones, 'rank')), 'Rangs consécutifs dans l\'ordre de la liste');
		$this->assertSame(array_column($zones, 'label'), array_map('strval', array_keys($zones)), 'Zones indexées par le libellé affiché');
		// Les lignes ne portent que des zones de la liste (ou aucune), avec le libellé de la colonne "Zone" de l'onglet inventaire
		$inventory = infrasfiles_load_object('inventory', self::$inventoryId);
		$inventory->infrasfilesFetchLines();
		$tab = $this->zonesOfTheInventoryTab(self::$inventoryId);
		foreach ($inventory->infrasfiles_lines as $line) {
			$this->assertTrue($line['zone'] === '' || isset($zones[$line['zone']]), 'Zone de la liste, ou vide');
			$this->assertSame($tab[$line['fk_product']] ?? '', $line['zone_label'], 'Libellé identique à la colonne "Zone" de l\'onglet inventaire');
		}
	}

	public function testCategoriesArborescenceEmplacementCompletCommeLOnglet(): void
	{
		global $db;
		$this->requireTestData();
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		// Arborescence neuve, annulée en fin de classe : ZZTEST > B > 5, ZZTEST > A, B > 3 (dans cet ordre de création).
		// Catégorie parente jamais lue auparavant : hors du cache statique de infrasworkflow_inventoryZoneTree()
		$parent = $this->createCategory('ZZTEST-ZONES-'.dol_print_date(dol_now(), '%Y%m%d%H%M%S'), 0);
		$b = $this->createCategory('B', $parent);
		$b5 = $this->createCategory('5', $b);
		$this->createCategory('A', $parent);
		$this->createCategory('3', $b);
		// Un produit de l'inventaire rattaché à toute la branche (B et 5), comme le fait la saisie de la zone dans l'onglet
		$inventory = infrasfiles_load_object('inventory', self::$inventoryId);
		$inventory->infrasfilesFetchLines();
		$this->assertNotEmpty($inventory->infrasfiles_lines);
		$productId = $inventory->infrasfiles_lines[0]['fk_product'];
		$product = new Product($db);
		$this->assertGreaterThan(0, $product->fetch($productId));
		foreach ([$b, $b5] as $catId) {
			$cat = new Categorie($db);
			$cat->fetch($catId);
			$this->assertGreaterThan(0, $cat->add_type($product, Categorie::TYPE_PRODUCT), 'Rattachement du produit : '.$cat->error);
		}
		$this->configure(1, '', $parent);
		$zones = infrasfiles_inventory_zone_list(infrasfiles_inventory_zone_config());
		$this->assertSame(['B', 'B5', 'B3', 'A'], array_map('strval', array_keys($zones)), 'Ordre chronologique étendu à l\'arborescence, calculé par infrasfiles : chaque zone avant ses sous-emplacements');
		// Libellé attendu : celui de l'onglet, emplacement complet avec l'arborescence d'infrasworkflow, sous-catégorie directe avec une version plus ancienne
		$expected = function_exists('infrasworkflow_inventoryZoneTree') ? 'B5' : 'B';
		$inventory->infrasfilesFetchLines();
		$tab = $this->zonesOfTheInventoryTab(self::$inventoryId);
		$found = false;
		foreach ($inventory->infrasfiles_lines as $line) {
			$this->assertSame($tab[$line['fk_product']] ?? '', $line['zone_label'], 'Libellé identique à la colonne "Zone" de l\'onglet inventaire');
			if ($line['fk_product'] === $productId) {
				$found = true;
				$this->assertSame($expected, $line['zone_label'], 'Emplacement de l\'onglet (complet et le plus profond avec l\'arborescence)');
				$this->assertSame($zones[$expected]['rank'], $line['zone_rank'], 'Rang de la zone dans la liste');
			}
		}
		$this->assertTrue($found, 'Le produit rattaché figure dans les lignes');
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
