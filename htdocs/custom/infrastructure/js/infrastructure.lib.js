

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
	 * @param {JQuery} $item
	 * @param {bool} removeLastInfrastructure remove last infrastructure if it is the infrastructure of the title
	 * @returns {*[]}
	 */
	function getInfrastructureTitleChilds($item, removeLastInfrastructure = false) {
		let TcurrentChilds = []; // = JSON.parse(item.attr('data-childrens'));
		let level = $item.attr('data-level');

		let indexOfFirstInfrastructure = -1;
		let indexOfFirstTitle = -1;

		$item.nextAll('[id^="row-"]').each(function (index) {

			let dataLevel = $(this).attr('data-level');
			let dataIsInfrastructure = $(this).attr('data-isinfrastructure');

			if (dataIsInfrastructure != 'undefined' && dataLevel != 'undefined') {

				if (dataLevel <= level && indexOfFirstInfrastructure < 0 && dataIsInfrastructure == 'infrastructure') {
					indexOfFirstInfrastructure = index;
					if (indexOfFirstTitle < 0) {
						TcurrentChilds.push($(this).attr('id'));
					}
				}

				if (dataLevel <= level && indexOfFirstInfrastructure < 0 && indexOfFirstTitle < 0 && dataIsInfrastructure == 'title') {
					indexOfFirstTitle = index;
				}
			}

			if (indexOfFirstTitle < 0 && indexOfFirstInfrastructure < 0) {
				TcurrentChilds.push($(this).attr('id'));
			}
		});

		// remove last infrastructure if it is the infrastructure of the title
		if(removeLastInfrastructure && TcurrentChilds.length > 0){
			let lastChildId= TcurrentChilds.slice(-1);
			let $lastChild = $('#'+lastChildId);
			if($lastChild.length > 0 && $lastChild.attr('data-isinfrastructure') != undefined && $lastChild.attr('data-isinfrastructure') == 'infrastructure'){
				if(level == $lastChild.attr('data-level') ){
					TcurrentChilds.pop();
				}
			}
		}

		return TcurrentChilds;
	}
}