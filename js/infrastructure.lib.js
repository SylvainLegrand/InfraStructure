

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

				// Add extraffield support for dolibarr > 7
				let thisId = $(this).attr('data-id');
				let thisElement = $(this).attr('data-element');

				if (thisId != undefined && thisElement != undefined && infrastructureSummaryJsConf.useOldSplittedTrForLine) {
					$('[data-targetid="'+thisId+'"][data-element="extrafield"][data-targetelement="'+thisElement+'"]').each(function (index) {
						TcurrentChilds.push($(this).attr('id'));
					});
				}
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