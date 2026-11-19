

/**
 * Fallback : injecte la colonne « Opt » (<th> + <td> par ligne) quand le rendu
 * serveur via les sous-hooks infrasprojectEnrich* n'est pas en mesure de la
 * produire (cas : infrasproject inactif). S'auto-désactive si la cellule est
 * déjà présente côté serveur (rendu par IPP sur les <tr> en mode view, ou par
 * le tpl infrastructureline_row_document.tpl.php pour les lignes spéciales
 * infrastructure). La config { lines: {id:checked}, thLabel, thTooltip } est
 * injectée par le hook addMoreActionsButtons côté PHP via la variable globale
 * `infrastructureOlFallbackConf`.
 */
if (typeof infrastructureOlFallbackConf !== "undefined") {
	jQuery(function ($) {
		var conf = infrastructureOlFallbackConf;
		// Cible toutes les tables de lignes du document — repérées par la
		// présence de th.linecolmove dans leur thead.
		$('th.linecolmove').each(function () {
			var $linecolmoveTh = $(this);
			var $table = $linecolmoveTh.closest('table');
			if (!$table.length || $table.data('infrastructureOlFallback')) {
				return;
			}
			$table.data('infrastructureOlFallback', 1);
			// Injection du <th>Opt</th> dans l'en-tête si absent
			var $thead = $linecolmoveTh.closest('tr');
			if (!$thead.find('th.infrastructure_ol').length) {
				var thHtml = '<th class="infrastructure_ol" title="' + conf.thTooltip + '">' + conf.thLabel + '</th>';
				$linecolmoveTh.before(thHtml);
			}
			// Injection des <td> sur les lignes standards si absent — les <tr>
			// avec rel="infrastructure" (lignes spéciales du module) sont
			// ignorés : leur cellule Opt est déjà rendue par le tpl du module.
			$table.find('tbody tr[id^="row-"]').each(function () {
				var $tr = $(this);
				if ($tr.attr('rel') === 'infrastructure') {
					return;
				}
				if ($tr.find('td.infrastructure_ol').length) {
					return;
				}
				var rowId = parseInt(($tr.attr('id') || '').replace('row-', ''), 10);
				if (!rowId || !(rowId in conf.lines)) {
					return;
				}
				var checked = conf.lines[rowId] ? ' checked="checked"' : '';
				var tdHtml = '<td class="infrastructure_ol">'
					+ '<input type="checkbox" id="infrastructure_ol-' + rowId + '"'
					+ ' class="infrastructure_ol_chkbx" data-lineid="' + rowId + '"'
					+ ' value="1"' + checked + ' />'
					+ '</td>';
				var $linecolmoveTd = $tr.find('td.linecolmove').first();
				if ($linecolmoveTd.length) {
					$linecolmoveTd.before(tdHtml);
				}
			});
			// Ajustement des colspan du formulaire d'ajout de ligne (tpl natif
			// objectline_create.tpl.php) : le natif Dolibarr ne connaît pas la
			// colonne Opt, donc ses cellules td.linecoledit[colspan] du mini-
			// header et de la ligne d'inputs sont trop courtes d'une colonne
			// après notre injection du <th>Opt</th>. Idem pour la cellule du
			// <tr id="trlinefordates"> (services / contrats avec dateSelector).
			// L'extension absorbe visuellement la colonne Opt sans cellule de
			// saisie dédiée (aligné sur le comportement du tpl infrasproject
			// qui se contente d'incrémenter $colspan).
			$table.find('td.linecoledit[colspan], tr#trlinefordates > td[colspan]').each(function () {
				var $td = $(this);
				var n = parseInt($td.attr('colspan'), 10);
				if (!isNaN(n) && n > 1) {
					$td.attr('colspan', n + 1);
				}
			});
		});
	});
}

if (typeof getInfrastructureTitleChilds !== "function") {
	/**
	 * Lignes du bloc d'un titre, même règle que côté PHP (TInfrastructure::getLinesFromTitle()) : le bloc s'arrête au premier titre
	 * ou sous-total de niveau égal ou inférieur à celui du titre. Seul le sous-total du même niveau fait partie du bloc ;
	 * un sous-total de niveau inférieur appartient au bloc englobant (avant : il était rendu avec le bloc, donc déplacé ou replié avec lui).
	 *
	 * @param {JQuery} $item
	 * @param {bool} removeLastInfrastructure remove last infrastructure if it is the infrastructure of the title
	 * @returns {*[]}
	 */
	function getInfrastructureTitleChilds($item, removeLastInfrastructure = false) {
		let TcurrentChilds = [];
		let level = parseInt($item.attr('data-level'), 10);	// niveaux comparés en nombres (les attributs sont des chaînes)

		$item.nextAll('[id^="row-"]').each(function () {
			let dataLevel = parseInt($(this).attr('data-level'), 10);
			let dataIsInfrastructure = $(this).attr('data-isinfrastructure');
			let isTitle = dataIsInfrastructure == 'title';
			let isTotal = dataIsInfrastructure == 'infrastructure';

			if ((isTitle || isTotal) && !isNaN(dataLevel) && dataLevel <= level) {
				// Fin du bloc : le sous-total du même niveau est celui du titre
				if (isTotal && dataLevel == level && !removeLastInfrastructure) {
					TcurrentChilds.push($(this).attr('id'));
				}
				return false;
			}
			TcurrentChilds.push($(this).attr('id'));
		});

		return TcurrentChilds;
	}
}