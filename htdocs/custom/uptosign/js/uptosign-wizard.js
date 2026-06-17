var pdfDoc = null,
	pdfData = null,
	pageNum = 1,
	pageRendering = false,
	pageNumPending = null,
	scale = 1,
	canvas = null,
	ctx = null;

//gestion des documents multiformat
var pageWith = Array(),
	pageHeight = Array();

//liste des ratios à utiliser page par page
var pxTommX = Array(),
	pxTommY = Array();

var labelExists = false;

function uposignDebugJs(msg) {
	//debug mode activer / desactiver
	console.log(msg);
}

$(document).ready(function () {
	canvas = $('#uptosignCanvas')[0];
	if (canvas) {
		ctx = canvas.getContext('2d');

		// The workerSrc property shall be specified.
		pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/pdf.worker.min.js';

		pdfData = atob($('#pdfData').val());

		var loadingTask = pdfjsLib.getDocument({ data: pdfData });

		loadingTask.promise.then(function (pdf) {
			pdfDoc = pdf;
			$('#page_count')[0].textContent = pdfDoc.numPages;
			$('#countOfPages').val(pdfDoc.numPages);
			$('#prev')[0].style.display = "none";

			if (pdfDoc.numPages == 1) {
				$('#paramPages')[0].style.display = "none";
			}

			// Initial/first page rendering
			renderPage(1);
		}, function (reason) {
			// PDF loading error
			console.error(reason);
		});

		$("#prev").on("click", onPrevPage);
		$("#next").on("click", onNextPage);

		$('.tabsAction').width($('#uptosignCanvas').width() + $('#uptosignCanvas').offset().left);
	}
});

// si besoin un jour : chargement d'un fichier PDF externe
// document.getElementById('file').onchange = function (event) {
// 	// uposignDebugJs("File changed !");
// 	var file = event.target.files[0];
// 	var fileReader = new FileReader();

// 	fileReader.onload = function () {
// 		var typedarray = new Uint8Array(this.result);
// 		// uposignDebugJs(typedarray);
// 		// uposignDebugJs("Size :" + typedarray.byteLength);
// 		const loadingTask = pdfjsLib.getDocument(typedarray);
// 		loadingTask.promise.then(pdf => {
// 			// The document is loaded here...
// 			//This below is just for demonstration purposes showing that it works with the moderen api
// 			pdf.getPage(1).then(function (page) {
// 				// uposignDebugJs('Page loading ...');

// 				var scale = 1.0;
// 				var viewport = page.getViewport({
// 					scale: scale
// 				});

// 				var canvas = document.getElementById('uptosignCanvas');
// 				var context = canvas.getContext('2d');
// 				canvas.height = viewport.height;
// 				canvas.width = viewport.width;

// 				// Render PDF page into canvas context
// 				var renderContext = {
// 					canvasContext: context,
// 					viewport: viewport
// 				};
// 				var renderTask = page.render(renderContext);
// 				renderTask.promise.then(function () {
// 					// uposignDebugJs('Page rendered ...');
// 					$(document).trigger("pagerendered");
// 				}, function () {
// 					// uposignDebugJs("ERROR");
// 				});

// 			});
// 			//end of example code
// 		});

// 	}
// 	fileReader.readAsArrayBuffer(file);
// }
//
// Asynchronous download PDF
//

/* The dragging code for '.draggable' from the demo above
 * applies to this demo as well so it doesn't have to be repeated. */

// enable draggables to be dropped into this
interact('.uptosignDropzone').uptosignDropzone({
	// only accept elements matching this CSS selector
	accept: '.drag-drop',
	// Require a 100% element overlap for a drop to be possible
	overlap: 1,

	// listen for drop related events:

	ondropactivate: function (event) {
		// add active uptosignDropzone feedback
		event.target.classList.add('drop-active');
	},
	ondragenter: function (event) {
		var draggableElement = event.relatedTarget,
			dropzoneElement = event.target;

		// feedback the possibility of a drop
		dropzoneElement.classList.add('drop-target');
		draggableElement.classList.add('can-drop');
		draggableElement.classList.remove('dropped-out');
		//draggableElement.textContent = 'Dragged in';
	},
	ondragleave: function (event) {
		// uposignDebugJs('EVENT : drag leave');
		// remove the drop feedback style
		event.target.classList.remove('drop-target');
		event.relatedTarget.classList.remove('can-drop');
		event.relatedTarget.classList.add('dropped-out');
		//event.relatedTarget.textContent = 'Dragged out';
	},
	ondrop: function (event) {
		//event.relatedTarget.textContent = 'Dropped';
		// $(event.target).find(".description").html("    CLEAN");
		// uposignDebugJs('EVENT : on drop');
		let objid = event.relatedTarget.id;
		let obj = $('#' + objid);
		// uposignDebugJs("on drop pour " + JSON.stringify(obj));

		showCoordinates(obj, objid, 0);
	},
	ondropdeactivate: function (event) {
		// uposignDebugJs('EVENT : on drop deactivate');
		// remove active uptosignDropzone feedback
		event.target.classList.remove('drop-active');
		event.target.classList.remove('drop-target');

		let objid = event.relatedTarget.id;
		let obj = $('#' + objid);
		if (obj.hasClass("dropped-out")) {
			// uposignDebugJs("Reset pour " + JSON.stringify(obj));
			$('#' + objid).data('page', 0);
			$('#' + objid).data('x', 0);
			$('#' + objid).data('y', 0);
			$('#' + objid).data('xmm', 0);
			$('#' + objid).data('ymm', 0);
			$('#' + objid + '-page').val(0);
			$('#' + objid + '-signX').val(0);
			$('#' + objid + '-signY').val(0);
			// uposignDebugJs("resultat du ondropdeactivate : " + JSON.stringify(obj));
		}
	}
});

interact('.drag-drop')
	.draggable({
		inertia: true,
		restrict: {
			restriction: "#selectorContainer",
			endOnly: true,
			elementRect: { top: 0, left: 0, bottom: 1, right: 1 }
		},
		autoScroll: true,
		// dragMoveListener from the dragging demo above
		onmove: dragMoveListener,
	});


function dragMoveListener(event)
{
	let obj = $('#' + event.target.id)
	var target = event.target,
		// keep the dragged position in the data-x/data-y attributes
		x = (parseFloat(obj.data('x')) || 0) + event.dx,
		y = (parseFloat(obj.data('y')) || 0) + event.dy;
	// translate the element
	target.style.webkitTransform =
	target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

	//	uposignDebugJs("lache l'etiquette sur page num=" + pageNum);

	// update the posiion attributes
	obj.data('x', x);
	obj.data('y', y);
	obj.data('page', pageNum);

	// update the posiion attributes
	target.setAttribute('data-x', x);
	target.setAttribute('data-y', y);
	target.setAttribute('data-page', pageNum);

	// uposignDebugJs("dragMoveListener mouvement :: x=" + x + ", y=" + y);
	// uposignDebugJs("dragMoveListener abs :: x=" + event.pageX + ", y=" + event.pageY);
	// var description = $(target).find(".description").text();
	// uposignDebugJs("dragMoveListener 2..." + description + " x=" + x + " " + y);
}

// this is used later in the resizing demo
window.dragMoveListener = dragMoveListener;

$(document).bind('pagerendered', function (e) {
	//uposignDebugJs("pagerendered bind point");
	$('#pdfManager').show();
	// uposignDebugJs("nombre de blocs : " + params.length);
	if (!labelExists) {
		createLabels();
	}
	renderPlaceholder(1);
});

function createLabels()
{
	// uposignDebugJs("createLabels");
	$('#paramContainer').empty();
	$('#paramContainerBis').empty();
	let params = JSON.parse($('#jsonparameters').val());
	// uposignDebugJs("createLabels params is " + $('#jsonparameters').val());

	let prepos = false;

	let prevy = 0;

	for (let i = 0; i < params.length; i++) {
		//default values
		let y = 0;
		let x = 0;
		let page = 0; //non placé sur une page

		let param = params[i];
		uposignDebugJs("Ajout d'un bloc pour " + JSON.stringify(param));
		uposignDebugJs("Ajout de " + param.description);

		let classStyle = "drag-drop dropped-out";
		let paramBase = param.paramId.substring(0,4);
		if (paramBase == 'seal') {
			uposignDebugJs("Ajout du style sealStamp")
			classStyle += " sealStamp"
		} else {
			uposignDebugJs("Ajout du style signZone")
			classStyle += " signZone"
		}

		let dest = "#paramContainerBis";
		if (i == 0) {
			dest = "#paramContainer";
		}
		if (param.error) {
			dest = "#paramContainerBis";
			classStyle = "error";
		} else {
			x = parseFloat(param.defaultX);
		}
		y = parseFloat(param.defaultY);

		page = parseFloat(param.defaultPage);
		if (page < 0) {
			page += pdfDoc.numPages + 1; //new first page is page=1, not zero
		}

		uposignDebugJs("createLabels pour " + param.paramId + " label " + param.description.substring(0, 10) + ": page= " + page + ",x = " + x + ", y = " + y);
		uposignDebugJs("pour " + param.paramId + " label " + param.description.substring(0, 10) + ": pxTommX = " + pxTommX[page] + ", pxTommY = " + pxTommY[page]);

		//affecter les vals dans les champs hidden AVANT le calcul de position
		//Les champs hidden pour le post si on ne bouge pas les etiquettes
		$('#' + param.paramId + '-page').val(page);
		$('#' + param.paramId + '-signX').val(x);
		$('#' + param.paramId + '-signY').val(y);

		//si x/y/page != 0 il faut calculer la position sur le canvas ...
		if (x > 0 && classStyle != "error") {
			let px = pxTommX[page];
			if (typeof px == "undefined") {
				px = pxTommX[1];
			}
			let py = pxTommY[page];
			if (typeof py == "undefined") {
				py = pxTommY[1];
			}

			// uposignDebugJs("#uptosignCanvas " + $('#uptosignCanvas').offset().top);
			// uposignDebugJs("dest=" + dest + "val=" + $(dest).offset().top);

			x = Math.round(x / px) - $(dest).offset().left + $('#uptosignCanvas').offset().left;
			y = Math.round(y / py) - $(dest).offset().top + $('#uptosignCanvas').offset().top;
			classStyle = 'drag-drop can-drop';
			if (paramBase == 'seal') {
				classStyle += " sealStamp"
			} else {
				classStyle += " signZone"
			}
			prepos = true;
		}

		//rattrapage
		if (isNaN(x)) {
			x = 0;
		}
		if (isNaN(y)) {
			y = 0;
		}
		if (isNaN(page)) {
			page = 1;
		}

		//si a gauche dans le cartouche alors page=0 pour pouvoir l'envoyer sur n'importe quelle page
		if (x == 0) {
			page = 0;
		}

		uposignDebugJs("createLabels (2) pour " + param.paramId + " label " + param.description.substring(0, 10) + ": page= " + page + ",x = " + x + ", y = " + y);

		// if(i == 0) {
		$(dest).append('<div id="' + param.paramId + '" class="' + classStyle + '" data-id="' + param.paramId + '" data-page="' + page + '" data-x="' + x + '" data-y="' + y + '" style="touch-action: none; font-size:0.7em; transform: translate(' + x + 'px, ' + y + 'px); display:block">  <span class="description">' + param.description + ' </span></div>');

		// } else {
			// $(dest).append('<div id="' + param.paramId + '-' + i + '" class="' + classStyle + '" data-id="' + param.paramId + '" data-page="' + page + '" data-x="' + x + '" data-y="' + y + '" style="font-size:0.7em; transform: translate(' + x + 'px, ' + y + 'px); display:block">  <span class="description">' + param.description + ' </span></div>');
		// }
	}
	labelExists = true;
	// 20230921 : fail with recompute pos-x pos-y bad positions
	// if (prepos == true) {
	// 	showCoordinates(null,null,1);
	// }
}

function renderPlaceholder(currentPage)
{
	let params = JSON.parse($('#jsonparameters').val());

	//chaque étiquette
	for (let i = 0; i < params.length; i++) {
		let param = params[i];
		let objid = param.paramId;
		let obj = $('#' + objid);
		if (obj.length) {
			let onPage = obj.data("page");
			uposignDebugJs("wizzard recherche objid=" + objid + " : " + param.description.slice(0,20) + " -> c'est sur la page " + onPage)
			uposignDebugJs(obj);

			// cas particulier de la préconfiguration "signature page -2" pour dire 2 pages avant la fin
			// note: le 0 étant réservé pour "sur aucune page"
			if (onPage < 0) {
				onPage = pdfDoc.numPages + onPage + 1;
			}

			if ((onPage > 0) && (onPage != pageNum) && (onPage != 0)) {
				// uposignDebugJs("Hide obj " + objid + "onPage="+onPage + " et pageNum=" + pageNum);
				obj.hide();
			} else {
				obj.show();
			}
		}
	}
	//uposignDebugJs("renderPlaceholder");

	// uposignDebugJs("apres le for : " + params.length);
	var prevStyle = "";
	var nextStyle = "";
	var prevDisabled = false;
	var nextDisabled = false;
	if (currentPage == 1) {
		prevStyle = "disabled";
		prevDisabled = true;
	}

	if (currentPage >= pdfDoc.numPages || pdfDoc.numPages == 1) {
		nextDisabled = true;
		nextStyle = "disabled";
	}
}

function showCoordinates(objectOne, objidOne, all=0)
{
	// uposignDebugJs("showCoordinates start all="+all);
	//Pour chaque étiquette ...
	//attention big fail si les pages ne sont pas toutes de la même taille !!!!
	$('.drag-drop.can-drop').each(function (index) {
		// uposignDebugJs($(this).data("id"));
		let objid = $(this).data("id");
		let obj = $('#' + objid)
		uposignDebugJs("showCoordinates can-drop for objid " + objid + "(" + objidOne + ")");
		if (all == 0 && objid != objidOne) {
			return;
		}

		let page = $(this).data("page");
		if (page != pageNum) {
			$(this).hide();
		} else {
			$(this).show();
		}

		let posX = Math.abs(obj.offset().left - $('#uptosignCanvas').offset().left);
		let posY = Math.abs(obj.offset().top - $('#uptosignCanvas').offset().top);

		// uposignDebugJs("showCoordinates posX=" + posX + " :: posY=" + posY);
		// uposignDebugJs("obj is =" + objid);
		// uposignDebugJs("showCoordinates l=" + obj.offset().left + " :: t=" + obj.offset().top);
		// uposignDebugJs("showCoordinates cw=" + $('#uptosignCanvas').width() + " :: ch=" + $('#uptosignCanvas').height());

		if (posX > 0 && posY > 0) {
			let px = pxTommX[page];
			if (typeof px == "undefined") {
				px = pxTommX[1];
			}
			let py = pxTommY[page];
			if (typeof py == "undefined") {
				py = pxTommY[1];
			}

			// uposignDebugJs("showCoordinates posX=" + posX + ", posY=" + posY);
			// uposignDebugJs("showCoordinates px=" + px + ", py=" + py);

			//recalcul pour TCPDF:
			let posXmm = Math.round(posX * px);
			let posYmm = Math.round(posY * py);

			// uposignDebugJs("showCoordinates " + objid + " :: posXmm=" + posXmm + "(posX=" + Math.round(posX) + "), posYmm=" + posYmm + "(posY=" + Math.round(posY) + "), on page=" + page);

			//Affichage de la position dans l'étiquette
			// $(this).find(".description").html("Position copiée dans le<br />presse-papier, vous pouvez<br />faire 'coller' dans dolibarr...<br />(" + Math.round(posXmm) + "," + Math.round(posYmm) + ",150,70)");
			// navigator.clipboard.writeText(Math.round(posXmm) + "," + Math.round(posYmm) + ",150,70");
			obj.data('xmm', posXmm);
			obj.data('ymm', posYmm);

			//hidden fields for form submit
			$('#' + objid + '-page').val(page);
			$('#' + objid + '-signX').val(posXmm);
			$('#' + objid + '-signY').val(posYmm);
		}
	});

	$('.drag-drop.dropped-out').each(function (index) {
		// uposignDebugJs('OUT: ' + $(this).data("id"));
		let objid = $(this).data("id");
		// uposignDebugJs("showCoordinates dropped out for objid " + objid + "(" + objidOne + ")");
		if (all == 0 && objid != objidOne) {
			return;
		}
		$('#' + objid).data('page', 0);
		$('#' + objid + '-page').val(0);
		$('#' + objid + '-signX').val(0);
		$('#' + objid + '-signY').val(0);
	});
}

/**
 * Displays previous page.
 */
function onPrevPage(event)
{
	event.preventDefault();
	if (pageNum <= 1) {
		return;
	}
	pageNum--;
	queueRenderPage(pageNum);
}

/**
 * Displays next page.
 */
function onNextPage(event)
{
	event.preventDefault();
	// uposignDebugJs("click on next page...");
	if (pageNum >= pdfDoc.numPages) {
		return;
	}
	pageNum++;
	queueRenderPage(pageNum);
}

/**
 * If another page rendering in progress, waits until the rendering is
 * finised. Otherwise, executes rendering immediately.
 */
function queueRenderPage(num)
{
	if (pageRendering) {
		pageNumPending = num;
	} else {
		renderPage(num);
	}
}

/**
 * Get page info from document, resize canvas accordingly, and render page.
 * @param num Page number.
 */
function renderPage(num)
{
	pageRendering = true;
	// Using promise to fetch the page
	pdfDoc.getPage(num).then(function (pageDisplay) {
		devicePixelRatio = window.devicePixelRatio || 1;

		var viewport = pageDisplay.getViewport({
			scale: devicePixelRatio
		});

		canvas.height = viewport.height;
		canvas.width = viewport.width;
		//large pour stocker
		$('#pageContainer').width(canvas.width);

		// calcul pixels -> mm : mm = ( pixels * 25.4 ) / DPI
		let largeurPDF = Math.round((25.4*pageDisplay.view[2])/72);
		let hauteurPDF = Math.round((25.4*pageDisplay.view[3])/72);

		let largeur = Math.round(25.4*canvas.width/72);
		let hauteur = Math.round(25.4*canvas.height/72);

		// let ratioX = largeurPDF / largeur;
		// let ratioY = hauteurPDF / hauteur;

		pageWith[num] = largeurPDF;
		pageHeight[num] = hauteurPDF;

		// InfraS change begin
		// Utiliser la taille AFFICHEE du canvas (CSS, bornée par max-width/max-height)
		// et non sa taille interne (canvas.width = viewport * devicePixelRatio) :
		// quand devicePixelRatio > 1, le canvas est reduit par le CSS et les etiquettes
		// (affichage initial ET conversion drag-drop) etaient decalees du meme facteur.
		pxTommX[num] = largeurPDF / $('#uptosignCanvas').width();
		pxTommY[num] = hauteurPDF / $('#uptosignCanvas').height();
		// InfraS change end

		// uposignDebugJs("pour la page=" + num + ", largeurPDF=" + largeurPDF + ", hauteurPDF=" + hauteurPDF);
		// uposignDebugJs("viewport.width=" + viewport.width + ", viewport.height=" + viewport.height);
		// uposignDebugJs("canvas.width=" + canvas.width + ", canvas.height=" + canvas.height);
		// uposignDebugJs("largeur=" + largeur + ", hauteur=" + hauteur);
		// uposignDebugJs("pxTommX=" + pxTommX[num] + ", pxTommY=" + pxTommY[num]);
		// uposignDebugJs("ratioX=" + ratioX + ", ratioY=" + ratioY);
		//recale a la bonne largeur
		$('#pageContainer').width($('#uptosignCanvas').width);

		// Render PDF page into canvas context
		var renderContext = {
			canvasContext: ctx,
			viewport: viewport
		};
		var renderTask = pageDisplay.render(renderContext);

		// Wait for rendering to finish
		renderTask.promise.then(function () {
			pageRendering = false;
			if (pageNumPending !== null) {
				// New page rendering is pending
				renderPage(pageNumPending);
				pageNumPending = null;
			}
			$(document).trigger("pagerendered");
		});
	});

	// Update page counters
	$('#page_num')[0].textContent = num;
	if (num == 1) {
		$('#prev')[0].style.display = "none";
		if (pdfDoc.numPages > 1) {
			$('#next')[0].style.display = "flex";
		}
	} else {
		$('#prev')[0].style.display = "flex";
		if (num == pdfDoc.numPages) {
			$('#next')[0].style.display = "none";
		} else {
			$('#next')[0].style.display = "flex";
		}
	}
}

$('#leform').submit(function (event) {
	event.preventDefault();
});

function formSeal()
{
	let input = $("<input>").attr("type", "hidden")
		.attr("name", "action").val("uptoseal");
	$('#leform').append(input);
	leform.submit();
}

function formSign()
{
	let input = $("<input>").attr("type", "hidden")
		.attr("name", "action").val("uptosign");
	$('#leform').append(input);
	//debug time
	$('#pdfData')[0].value = '';  // InfraS change
	// uposignDebugJs("Debug pour Eric:");
	// uposignDebugJs($('#leform').serialize());
	// return false;
	leform.submit();
}

function pdfFileChange()
{
	$("input[name=action]").val("pdffilechoose");
	leform.submit();
}
