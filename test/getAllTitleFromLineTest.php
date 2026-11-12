<?php
	/***************************************************
	* Test de régression autonome (sans Dolibarr) : TInfrastructure::getAllTitleFromLine()
	*
	* Lancement : php test/getAllTitleFromLineTest.php  (code retour 0 = vert, 1 = rouge)
	*
	* Cas réel (Kytom Lyon, devis PR2610-3472, 2026-11-12) : le bloc optionnel « PLANTES » (niveau 1)
	* contient un sous-bloc « RIDEAUX » (niveau 2). Toute ligne ajoutée en fin de devis passait en option :
	* en remontant les lignes, le sous-total de niveau 2 écrasait le niveau 1 en attente et le titre
	* « PLANTES », pourtant fermé, était pris pour le parent de la ligne.
	***************************************************/

	define('DOL_DOCUMENT_ROOT', __DIR__.'/stubs');

	// Stubs minimaux de l'environnement Dolibarr
	function dol_include_once($path) { return true; }
	function dol_syslog($message, $level = 0) {}

	class modInfrastructure
	{
		const MODULE_NUMBER = 550090;
	}

	/** Lignes « en base » renvoyées par Propal::fetch(), indexées par id de devis */
	$GLOBALS['stub_propal_lines'] = [];

	class Propal
	{
		public $id;
		public $element	= 'propal';
		public $lines	= [];
		public function __construct($db) {}
		public function fetch($id)
		{
			$this->id		= $id;
			$this->lines	= $GLOBALS['stub_propal_lines'][$id] ?? [];
			return 1;
		}
	}

	require __DIR__.'/../class/infrastructure.class.php';

	/** Fabrique une ligne de devis */
	function mkLine($id, $type, $qty, $label = '', $ol = null)
	{
		$l					= new stdClass();
		$l->id				= $id;
		$l->element			= 'propaldet';
		$l->fk_propal		= 1;
		$l->label			= $label;
		$l->qty				= $qty;
		$l->product_type	= ($type === 'title' || $type === 'total') ? 9 : 0;
		$l->special_code	= ($type === 'title' || $type === 'total') ? 550090 : 0;
		$l->array_options	= ['options_infrastructure_ol' => $ol];
		return $l;
	}

	/** Structure réduite du devis Lyon PR2610-3472 (blocs de fin) */
	function devisLyon()
	{
		return [
			mkLine(1, 'title', 1, 'ESPACE INFORMEL'),
			mkLine(2, 'line', 2),
			mkLine(3, 'total', 99),
			mkLine(4, 'title', 1, 'PLANTES', '1'),		// bloc optionnel
			mkLine(5, 'line', 1),
			mkLine(6, 'title', 2, 'RIDEAUX'),			// sous-bloc imbriqué
			mkLine(7, 'line', 1),
			mkLine(8, 'total', 98),						// ferme RIDEAUX
			mkLine(9, 'total', 99),						// ferme PLANTES
			mkLine(10, 'title', 1, 'EVOLUTION OPENSPACE', '1'),
			mkLine(11, 'line', 1),
			mkLine(12, 'total', 99),
			mkLine(13, 'title', 1, 'LIVRAISON ET INSTALLATION'),
			mkLine(14, 'line', 1),
			mkLine(15, 'total', 99),
		];
	}

	function titles($origin, $documentLines, $freshLines = null)
	{
		$doc					= new Propal(null);
		$doc->id				= 1;
		$doc->lines				= $documentLines;
		$GLOBALS['object']		= $doc;
		$GLOBALS['stub_propal_lines'][1] = $freshLines ?? $documentLines;
		$res = [];
		foreach (TInfrastructure::getAllTitleFromLine($origin) as $t) {
			$res[] = $t->label;
		}
		return $res;
	}

	$failures = 0;
	function check($name, $expected, $actual)
	{
		global $failures;
		$ok = ($expected === $actual);
		echo ($ok ? 'OK    ' : 'ECHEC ').$name.' : attendu ['.implode(', ', $expected).'], obtenu ['.implode(', ', $actual).']'.PHP_EOL;
		if (!$ok) {
			$failures++;
		}
	}

	// 1. Ligne posée en fin de devis, hors de tout bloc, déjà présente dans le document
	$lines		= devisLyon();
	$new		= mkLine(16, 'line', 1);
	$lines[]	= $new;
	check('ligne en fin de devis, hors bloc', [], titles($new, $lines));

	// 2. Ligne qui vient d'être insérée : le document en mémoire ne la contient pas encore
	//    (trigger LINEPROPAL_INSERT) ; seule la base la connaît, dans le bloc LIVRAISON
	$stale		= devisLyon();
	$fresh		= devisLyon();
	$inserted	= mkLine(17, 'line', 1);
	array_splice($fresh, 14, 0, [$inserted]);
	check('ligne insérée absente du document en mémoire', ['LIVRAISON ET INSTALLATION'], titles($inserted, $stale, $fresh));

	// 3. Ligne dans le bloc optionnel EVOLUTION : reste rattachée à son titre optionnel
	$lines = devisLyon();
	check('ligne du bloc optionnel EVOLUTION', ['EVOLUTION OPENSPACE'], titles($lines[10], $lines));

	// 4. Ligne du sous-bloc RIDEAUX : titres RIDEAUX puis PLANTES
	$lines = devisLyon();
	check('ligne du sous-bloc RIDEAUX', ['RIDEAUX', 'PLANTES'], titles($lines[6], $lines));

	// 5. Ligne de PLANTES placée après le sous-bloc RIDEAUX
	$lines	= devisLyon();
	$after	= mkLine(18, 'line', 1);
	array_splice($lines, 8, 0, [$after]);
	check('ligne de PLANTES après le sous-bloc RIDEAUX', ['PLANTES'], titles($after, $lines));

	exit($failures ? 1 : 0);
