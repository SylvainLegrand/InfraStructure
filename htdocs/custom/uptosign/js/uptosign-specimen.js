var pdfDoc = null,
	pdfData = null,
	pageNum = 1,
	pageRendering = false,
	pageNumPending = null,
	scale = 1,
	canvas = null,
	ctx = null;

//note: voir la taille du canvas par rapport au fichier ouvert (TODO: quid si pas a4?)
var pxTommX = null,
	pxTommY = null;

var labelExists = false;

var readonly = false;

$(document).ready(function () {
	$('#model_pdf').change(function () {
		// console.log("modification de modèle...");
	});

	readonly = $('#readonly').val();
	// console.log("Read Only : " + ro);

	canvas = $('#uptosignCanvas')[0];
	if (canvas) {
		ctx = canvas.getContext('2d');

		// The workerSrc property shall be specified.
		pdfjsLib.GlobalWorkerOptions.workerSrc = 'js/pdf.worker.min.js';

		pdfData = atob($('#pdfData').val());

		var loadingTask = pdfjsLib.getDocument({ data: pdfData });

		loadingTask.promise.then(function (pdf) {
			pdfDoc = pdf;
			// $('#page_count')[0].textContent = pdfDoc.numPages;
			// $('#prev')[0].style.display = "none";

			// if (pdfDoc.numPages == 1) {
			// 	$('#paramPages')[0].style.display = "none";
			// }

			// Initial/first page rendering
			renderPage(1);
		}, function (reason) {
			// PDF loading error
			console.error(reason);
		});

		// $("#prev").on("click", onPrevPage);
		// $("#next").on("click", onNextPage);

		// $('.tabsAction').width($('#uptosignCanvas').width() + $('#uptosignCanvas').offset().left);
	}
});

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
		// console.log('EVENT : drag leave');
		// remove the drop feedback style
		event.target.classList.remove('drop-target');
		event.relatedTarget.classList.remove('can-drop');
		event.relatedTarget.classList.add('dropped-out');
		//event.relatedTarget.textContent = 'Dragged out';
	},
	ondrop: function (event) {
		//event.relatedTarget.textContent = 'Dropped';
		// $(event.target).find(".description").html("    CLEAN");
		// console.log('EVENT : on drop');
		let objid = event.relatedTarget.id;
		let object = $('#' + objid);
		// console.log("on drop pour " + JSON.stringify(object));

		showCoordinates();
	},
	ondropdeactivate: function (event) {
		// console.log('EVENT : on drop deactivate');
		// remove active uptosignDropzone feedback
		event.target.classList.remove('drop-active');
		event.target.classList.remove('drop-target');

		let objid = event.relatedTarget.id;
		let object = $('#' + objid);
		if (object.hasClass("dropped-out")) {
			// console.log("Reset pour " + JSON.stringify(object));
			$('#' + objid).data('page', 0);
			$('#' + objid).data('x', 0);
			$('#' + objid).data('y', 0);
			$('#' + objid).data('xmm', 0);
			$('#' + objid).data('ymm', 0);
			$('#' + objid + '-page').val(0);
			$('#' + objid + '-signX').val(0);
			$('#' + objid + '-signY').val(0);
			// console.log("resultat du reset : " + JSON.stringify(object));
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
	let object = $('#' + event.target.id)
	var target = event.target,
		// keep the dragged position in the data-x/data-y attributes
		x = (parseFloat(object.data('x')) || 0) + event.dx,
		y = (parseFloat(object.data('y')) || 0) + event.dy;
	// translate the element
	target.style.webkitTransform =
		target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

	//	console.log("lache l'etiquette sur page num=" + pageNum);

	// update the posiion attributes
	object.data('x', x);
	object.data('y', y);
	object.data('page', pageNum);

	// update the posiion attributes
	target.setAttribute('data-x', x);
	target.setAttribute('data-y', y);
	target.setAttribute('data-page', pageNum);

	// console.log("dragMoveListener mouvement :: x=" + x + ", y=" + y);
	// console.log("dragMoveListener abs :: x=" + event.pageX + ", y=" + event.pageY);
	// var description = $(target).find(".description").text();
	// console.log("dragMoveListener 2..." + description + " x=" + x + " " + y);
}

// this is used later in the resizing demo
window.dragMoveListener = dragMoveListener;

$(document).bind('pagerendered', function (e) {
	//console.log("pagerendered bind point");
	$('#pdfManager').show();
	// console.log("nombre de blocs : " + params.length);
	if (!labelExists) {
		createLabels();
	}
	renderPlaceholder(1);
});

function createLabels()
{
	// console.log("createLabels");
	$('#paramContainer').empty();
	$('#paramContainerBis').empty();
	let params = JSON.parse($('#jsonparameters').val());

	let prepos = false;

	for (let i = 0; i < params.length; i++) {
		//default values
		let y = 0;
		let x = 0;
		let page = 0; //non placé sur une page

		// console.log("Ajout d'un bloc...");
		let param = params[i];

		let classStyle = "";
		if (readonly) {
			classStyle = "nodrag-nodrop";
		} else {
			classStyle = "drag-drop";
		}
		if (param.paramId == 'seal') {
			classStyle += " sealStamp"
		} else {
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
		// console.log("pour label " + param.description + ", defaultPage is " + page);
		if (page < 0) {
			page += pdfDoc.numPages + 1; //new first page is page=1, not zero
		}
		// console.log("pour label " + param.description + ", real page is " + page);
		// console.log("pour label " + param.description + ": x = " + pxTommX + ", y = " + pxTommY);

		//si x/y/page != 0 il faut calculer la position sur le canvas ...
		//et affecter les vals dans les champs hidden
		if (x > 0 && classStyle != "error") {
			x = Math.round(x / pxTommX) - $(dest).offset().left + $('#uptosignCanvas').offset().left;
			y = Math.round(y / pxTommY) - $(dest).offset().top + $('#uptosignCanvas').offset().top;
			if (readonly) {
				classStyle = "nodrag-nodrop";
			} else {
				classStyle = "drag-drop";
			}
			if (param.paramId == 'seal') {
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
		$(dest).append('<div id="' + param.paramId + '" class="' + classStyle + '" data-id="' + param.paramId + '" data-formtargetfield="' + param.formTargetField + '" data-page="' + page + '" data-x="' + x + '" data-y="' + y + '" style="font-size:0.7em; transform: translate(' + x + 'px, ' + y + 'px); display:block">  <span class="description">' + param.description + ' </span></div>');
	}
	labelExists = true;
	if (prepos == true) {
		showCoordinates();
	}
}

function renderPlaceholder(currentPage)
{
	//console.log("renderPlaceholder, page " + currentPage);

	let params = JSON.parse($('#jsonparameters').val());

	//chaque étiquette
	for (let i = 0; i < params.length; i++) {
		let param = params[i];
		let objid = param.paramId;
		let object = $('#' + objid);
		let onPage = object.data("page");
		//console.log("recherche objid = " + JSON.stringify(object) + " est sur la page " + onPage)

		// cas particulier de la préconfiguration "signature page -2" pour dire 2 pages avant la fin
		// note: le 0 étant réservé pour "sur aucune page"
		if (onPage < 0) {
			onPage = pdfDoc.numPages + onPage + 1;
		}

		if ((onPage != pageNum) && (onPage != 0)) {
			object.hide();
		} else {
			object.show();
		}
	}
	//console.log("renderPlaceholder");

	// console.log("apres le for : " + params.length);
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

function showCoordinates()
{
	// console.log("showCoordinates start...");
	//Pour chaque étiquette
	$('.drag-drop.can-drop').each(function (index) {
		// console.log($(this).data("id"));

		let page = $(this).data("page");
		let objid = $(this).data("id");
		let object = $('#' + objid)

		let posX = object.offset().left - $('#uptosignCanvas').offset().left;
		let posY = object.offset().top - $('#uptosignCanvas').offset().top;

		if (posX > 0 && posY > 0) {
			//recalcul pour TCPDF:
			let posXmm = Math.round(posX * pxTommX);
			let posYmm = Math.round(posY * pxTommY);

			//console.log("showCoordinates " + objid + " :: posX=" + posXmm + ", posY=" + posYmm + ", on page=" + page);

			//Affichage de la position dans l'étiquette
			// $(this).find(".description").html("Position copiée dans le<br />presse-papier, vous pouvez<br />faire 'coller' dans dolibarr...<br />(" + Math.round(posXmm) + "," + Math.round(posYmm) + ",150,70)");
			// navigator.clipboard.writeText(Math.round(posXmm) + "," + Math.round(posYmm) + ",150,70");
			object.data('xmm', posXmm);
			object.data('ymm', posYmm);

			formTargetField = object.data('formtargetfield');
			//console.log("Update field target = " + formTargetField);
			$('#' + formTargetField).val(posXmm + ',' + posYmm);

			//hidden fields for form submit
			$('#' + objid + '-page').val(page);
			$('#' + objid + '-signX').val(posXmm);
			$('#' + objid + '-signY').val(posYmm);
		}

		$('.drag-drop.dropped-out').each(function (index) {
			// console.log('OUT: ' + $(this).data("id"));
			let objid = $(this).data("id");
			$('#' + objid).data('page', 0);
			$('#' + objid + '-signX').val(0);
			$('#' + objid + '-signY').val(0);
			$('#' + objid + '-page').val(0);
		});

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
	// console.log("click on next page...");
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

		var viewport = pageDisplay.getViewport({
			scale: scale
		});
		canvas.height = viewport.height;
		canvas.width = viewport.width;
		$('#pageContainer').width(canvas.width);

		// console.log("Canvas w=" + canvas.width + ", h=" + canvas.height);
		let largeur = Math.round(25.4*canvas.width/72);
		let hauteur = Math.round(25.4*canvas.height/72);
		pxTommX = largeur / canvas.width;
		pxTommY = hauteur / canvas.height;

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
	// $('#page_num')[0].textContent = num;
	// if (num == 1) {
	// 	$('#prev')[0].style.display = "none";
	// 	if (pdfDoc.numPages > 1) {
	// 		$('#next')[0].style.display = "flex";
	// 	}
	// } else {
	// 	$('#prev')[0].style.display = "flex";
	// 	if (num == pdfDoc.numPages) {
	// 		$('#next')[0].style.display = "none";
	// 	} else {
	// 		$('#next')[0].style.display = "flex";
	// 	}
	// }
}

$('#leform').submit(function (event) {
	event.preventDefault();
});

function pdfFileChange()
{
	$("input[name=action]").val("pdffilechoose");
	leform.submit();
}
