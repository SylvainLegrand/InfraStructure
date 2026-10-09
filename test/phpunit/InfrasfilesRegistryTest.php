<?php
/**
 * NIVEAU 2 — helpers du registre (core/lib/infrasfiles.lib.php) : fonctions Dolibarr globales
 * (isModEnabled, getDolGlobal*, $langs->trans) mais aucune écriture en base.
 * Les constantes sont posées en mémoire ($conf->global) et restaurées en tearDown().
 */

// IMPORTANT : sans ce "global" AVANT le require, $db/$conf/$user/$langs resteraient locaux à la
// méthode interne de PHPUnit qui charge ce fichier (cf. garde-fous du skill infras-module-selftest).
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

final class InfrasfilesRegistryTest extends TestCase
{
	/** @var array<string,string|null> Constantes modifiées par ce test, à restaurer après */
	private $savedConstants = [];

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
	}

	/**
	 * Pose (ou retire avec null) une constante globale en mémoire, en mémorisant sa valeur d'origine.
	 *
	 * @param	string		$key	Nom de la constante
	 * @param	mixed|null	$value	Valeur, null = retirer
	 * @return	void
	 */
	private function setConst(string $key, $value): void
	{
		global $conf;
		if (! array_key_exists($key, $this->savedConstants)) {
			$this->savedConstants[$key] = isset($conf->global->$key) ? $conf->global->$key : null;
		}
		if ($value === null) {
			unset($conf->global->$key);
		} else {
			$conf->global->$key = $value;
		}
	}

	public function testConstNameNormaliseLElementEtLeSuffixe(): void
	{
		$this->assertSame('INFRASFILES_WIDTHDRAW_ENABLED', infrasfiles_const_name('widthdraw', 'ENABLED'));
		$this->assertSame('INFRASFILES_INVENTORY_ADDON_PDF', infrasfiles_const_name('inventory', 'addon_pdf'));
		$this->assertSame('INFRASFILES_MY_OBJ_X', infrasfiles_const_name('my-obj', 'x'), 'Les caractères hors [a-z0-9] deviennent _');
	}

	public function testLeRegistreContientLesDeuxObjetsAvecLesClesAttendues(): void
	{
		$registry = infrasfiles_get_registry();
		$this->assertArrayHasKey('widthdraw', $registry);
		$this->assertArrayHasKey('inventory', $registry);
		foreach (['class', 'classpath', 'modelspath', 'docpart', 'modulepart', 'dirout', 'tabcontext', 'hookcontext', 'cardurl', 'permread', 'permwrite', 'mailtype', 'trigger', 'trackid', 'options'] as $key) {
			$this->assertArrayHasKey($key, $registry['widthdraw'], 'clé '.$key.' (widthdraw)');
			$this->assertArrayHasKey($key, $registry['inventory'], 'clé '.$key.' (inventory)');
		}
		$this->assertSame('infrasfiles', $registry['widthdraw']['modulepart'], 'Tout est servi avec modulepart = infrasfiles');
		$this->assertSame('infrasfileswidthdraw', $registry['widthdraw']['docpart']);
	}

	public function testSousRepertoireEtRepertoireDeSortie(): void
	{
		$object = new \stdClass();
		$object->ref = 'T 2026/01';
		$this->assertSame('widthdraw/'.dol_sanitizeFileName('T 2026/01'), infrasfiles_get_subdir('widthdraw', $object), 'La référence est nettoyée pour le système de fichiers');
		$this->assertSame('inventory', infrasfiles_get_subdir('inventory', null), 'Sans objet : répertoire de l\'élément seul (spécimen)');
		$this->assertStringStartsWith(infrasfiles_get_base_dir().'/widthdraw/', infrasfiles_get_output_dir('widthdraw', $object));
		// Le répertoire de base doit rester la valeur BRUTE de $conf (même chaîne que DOL_DATA_ROOT.'/'.filepath reconstruite par
		// l'index ECM natif) : une normalisation des doubles barres provoquerait une ré-indexation en doublon (uk_ecm_files)
		global $conf;
		$raw = !empty($conf->infrasfiles->multidir_output[$conf->entity]) ? $conf->infrasfiles->multidir_output[$conf->entity] : $conf->infrasfiles->dir_output;
		$this->assertSame(rtrim($raw, '/'), infrasfiles_get_base_dir());
		$this->assertSame(rtrim($raw, '/').'/inventory', infrasfiles_get_output_dir('inventory', null));
	}

	public function testCascadeDActivationEnabledPuisFeature(): void
	{
		$registry = infrasfiles_get_registry();
		if (! infrasfiles_module_available($registry['widthdraw'])) {
			$this->markTestSkipped('Module Prélèvement / Virement non actif sur cette instance : cascade non testable.');
		}
		$this->setConst('INFRASFILES_WIDTHDRAW_ENABLED', 0);
		$this->setConst('INFRASFILES_WIDTHDRAW_DOCUMENT', 1);
		$this->assertFalse(infrasfiles_is_enabled('widthdraw'), 'ENABLED à 0 : rien');
		$this->assertFalse(infrasfiles_is_enabled('widthdraw', 'DOCUMENT'), 'ENABLED à 0 masque aussi les fonctions');

		$this->setConst('INFRASFILES_WIDTHDRAW_ENABLED', 1);
		$this->setConst('INFRASFILES_WIDTHDRAW_DOCUMENT', 0);
		$this->setConst('INFRASFILES_WIDTHDRAW_EMAIL', 1);
		$this->assertTrue(infrasfiles_is_enabled('widthdraw'));
		$this->assertFalse(infrasfiles_is_enabled('widthdraw', 'DOCUMENT'));
		$this->assertTrue(infrasfiles_is_enabled('widthdraw', 'EMAIL'));

		$this->assertFalse(infrasfiles_is_enabled('objetinconnu'), 'Élément absent du registre : jamais actif');
	}

	public function testValeurParDefautDesOptions(): void
	{
		$this->setConst('INFRASFILES_WIDTHDRAW_SPLIT_MODE', null);
		$this->assertSame('thirdparty', infrasfiles_get_option('widthdraw', 'SPLIT_MODE'), 'Défaut du registre quand la constante est absente');
		$this->setConst('INFRASFILES_WIDTHDRAW_SPLIT_MODE', 'line');
		$this->assertSame('line', infrasfiles_get_option('widthdraw', 'SPLIT_MODE'));
		$this->assertSame('', infrasfiles_get_option('widthdraw', 'OPTION_INEXISTANTE'));
	}

	public function testValeursDesOptionsStatiquesEtParFonction(): void
	{
		global $langs;
		$langs->load('infrasfiles@infrasfiles');
		$registry = infrasfiles_get_registry();

		$values = infrasfiles_get_option_values($registry['widthdraw']['options']['SPLIT_MODE']);
		$this->assertSame(['thirdparty', 'parent'], array_keys($values), 'Un PDF par tiers ou par maison mère (le mode par ligne a été retiré)');
		$this->assertSame($langs->trans('InfraSFilesSplitParent'), $values['parent'], 'Libellés traduits');

		$this->assertArrayNotHasKey('ZONE_FIELD', $registry['inventory']['options'], 'La zone de stockage n\'est plus une option du module : elle suit la colonne "Zone" d\'infrasworkflow');
		$this->assertSame('on_off', $registry['inventory']['options']['SHOW_QTY']['type']);
	}

	public function testLaConfigurationDeZoneVientDInfrasworkflow(): void
	{
		global $conf;
		if (! isModEnabled('infrasworkflow')) {
			$config = infrasfiles_inventory_zone_config();
			$this->assertSame('', $config['source'], 'Sans infrasworkflow : aucune zone, sans erreur');
			$this->assertSame([], infrasfiles_inventory_zone_list($config));
			return;
		}
		$this->setConst('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 0);
		$this->assertSame('', infrasfiles_inventory_zone_config()['source'], 'Colonne "Zone" désactivée dans infrasworkflow : aucune zone');
		$this->setConst('INFRASWORKFLOW_DISPLAY_ZONE_COLUMN', 1);
		$this->setConst('INFRASWORKFLOW_INVENTORY_ZONE_EXTRAFIELD', 'zz_champ_inexistant');
		$this->setConst('INFRASWORKFLOW_INVENTORY_ZONE_PARENT_CATEGORY', 0);
		$this->assertSame('', infrasfiles_inventory_zone_config()['source'], 'Attribut inconnu et pas de catégorie : aucune zone (même règle qu\'infrasworkflow)');
	}

	public function testLesLibellesEtAidesDesOptionsSontDesClesTraduites(): void
	{
		global $langs;
		$langs->load('infrasfiles@infrasfiles');
		// $langs->trans() renvoie la clé elle-même quand elle n'existe dans aucun fichier de langue : une clé orpheline
		// s'afficherait brute dans la page de paramètres sans qu'aucun test ne le voie
		foreach (infrasfiles_get_registry() as $element => $definition) {
			$this->assertNotSame($definition['label'], $langs->trans($definition['label']), 'Libellé de l\'objet '.$element.' non traduit');
			foreach ($definition['options'] as $suffix => $option) {
				$this->assertNotSame($option['label'], $langs->trans($option['label']), 'Libellé de l\'option '.$element.'/'.$suffix.' non traduit');
				if (! empty($option['help'])) {
					$this->assertNotSame($option['help'], $langs->trans($option['help']), 'Aide de l\'option '.$element.'/'.$suffix.' non traduite');
				}
			}
		}
		$this->assertNotEmpty(infrasfiles_get_registry()['widthdraw']['options']['SPLIT_MODE']['help'], 'SPLIT_MODE porte une aide');
	}

	public function testElementProprietaireDUnFichier(): void
	{
		// Un fichier est toujours traité avec la permission de SON élément (suppression depuis une fiche native, téléchargement)
		$this->assertSame('widthdraw', infrasfiles_element_from_file('widthdraw/T260901/T260901-CU.pdf'));
		$this->assertSame('inventory', infrasfiles_element_from_file('/inventory/ZZTEST-INV-02/ZZTEST-INV-02.pdf'), 'Barre initiale tolérée');
		$this->assertSame('inventory', infrasfiles_element_from_file('inventory\\SPECIMEN.pdf'), 'Séparateur Windows normalisé');
		$this->assertSame('', infrasfiles_element_from_file('temp/x.pdf'), 'Répertoire hors registre : aucun élément');
		$this->assertSame('', infrasfiles_element_from_file('widthdrawx/T1/a.pdf'), 'Le préfixe doit être un répertoire complet');
		$this->assertSame('', infrasfiles_element_from_file(''), 'Chemin vide');
	}

	public function testElementDepuisLeTypeDeModeleDeMail(): void
	{
		$this->assertSame('inventory', infrasfiles_element_from_mailtype('infrasfiles_inventory'));
		$this->assertSame('widthdraw', infrasfiles_element_from_mailtype('infrasfiles_widthdraw'));
		$this->assertSame('', infrasfiles_element_from_mailtype('facture_send'));
	}
}
