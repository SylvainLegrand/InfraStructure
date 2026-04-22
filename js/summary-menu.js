if (typeof subtotalSummaryJsConf === undefined) {
	var subtotalSummaryJsConf = {
		langs: {
			'SubtotalSummaryTitle': 'Quick summary'
		},
		useOldSplittedTrForLine: 0
	};
}

/**
 * SOMMAIRE DES TITRE (du module sous total)
 */
$( document ).ready(function() {

	let $tablelines = $('#tablelines tr[data-issubtotal="title"]');
	let summaryLines = [];

	if($tablelines.length > 0){
		$tablelines.each(function( index ) {
			let $subTotalLabel = $( this ).find('.subtotal_label:first');
			if($subTotalLabel.length > 0){
				summaryLines.push({
					id: $( this ).attr('data-id'),
					label: $subTotalLabel.text(),
					level: $( this ).attr('data-level')
				})
			}
		});
	}

	if(summaryLines.length>0){
		let summaryMenu = document.createElement('div');
		summaryMenu.id = 'subtotal-summary-let-menu-contaner';

		let summaryMenuTitle = document.createElement('h6');
		summaryMenuTitle.id = 'subtotal-summary-title';
		summaryMenuTitle.innerHTML = subtotalSummaryJsConf.langs.SubtotalSummaryTitle;
		summaryMenu.appendChild(summaryMenuTitle);


		summaryLines.forEach(function(item){
			let link = document.createElement('a');


			let paddingChars = ''
			for (let i = 1; i < parseInt(item.level); i++) {
				paddingChars+= ' - ';
			}

			link.innerText = paddingChars + ' ' + item.label;

			// link.style.paddingLeft = ((parseInt(item.level)-1)*5) + 'px';


			link.classList.add('subtotal-summary-link');
			link.href = '#row-'+ item.id;
			link.setAttribute('data-id', item.id);
			link.setAttribute('data-level', item.level);
			link.setAttribute('title', item.label);

			link.addEventListener('click', function(e) {
				e.preventDefault();

				let targetItem = document.getElementById( 'row-' + this.getAttribute('data-id') );
				let $topHeader = $('#id-top');
				let headerOffset = $topHeader.length > 0 ? $topHeader.innerHeight() : 0;

				// Compensate sticky bars added by oblyon FIX_AREAREF_CARD / FIX_STICKY_TABS_CARD
				if (subtotalSummaryJsConf.isOblyon) {
					if (subtotalSummaryJsConf.fixArearefCard) {
						let $arearef = $('div.arearef').first();
						if ($arearef.length > 0 && $arearef.css('position') === 'sticky') {
							headerOffset += $arearef.outerHeight();
						}
					}
					if (subtotalSummaryJsConf.fixStickyTabsCard) {
						let $stickyTabs = $('.fiche > div.tabs').first();
						if ($stickyTabs.length === 0) {
							$stickyTabs = $('div.tabs').first();
						}
						if ($stickyTabs.length > 0 && $stickyTabs.css('position') === 'sticky') {
							headerOffset += $stickyTabs.outerHeight();
						}
					}
				}

				window.scroll({
					behavior: 'smooth',
					left: 0,
					top: $(targetItem).offset().top - headerOffset - 50
				});
			});

			summaryMenu.appendChild(link);
		});

		let floatingWrap = document.createElement('div');
		floatingWrap.id = 'subtotal-summary-floating';

		let toggleBtn = document.createElement('button');
		toggleBtn.type = 'button';
		toggleBtn.id = 'subtotal-summary-toggle';
		toggleBtn.setAttribute('title', subtotalSummaryJsConf.langs.SubtotalSummaryTitle);
		toggleBtn.innerHTML = '<span class="fa fa-list"></span>';
		toggleBtn.addEventListener('click', function (e) {
			e.stopPropagation();
			if (!hasDragged) {
				floatingWrap.classList.toggle('--open');
			}
		});

		document.addEventListener('click', function (e) {
			if (!floatingWrap.contains(e.target)) {
				floatingWrap.classList.remove('--open');
			}
		});

		floatingWrap.appendChild(toggleBtn);
		floatingWrap.appendChild(summaryMenu);
		document.body.appendChild(floatingWrap);

		// Draggable behavior
		let isDragging = false;
		let hasDragged = false;
		let dragStartX, dragStartY, elemStartX, elemStartY;
		const DRAG_THRESHOLD = 5;
		const STORAGE_KEY = 'subtotal_summary_pos';

		// Restore saved position
		try {
			let savedPos = JSON.parse(localStorage.getItem(STORAGE_KEY));
			if (savedPos && typeof savedPos.left === 'number' && typeof savedPos.top === 'number') {
				let maxLeft = window.innerWidth - floatingWrap.offsetWidth;
				let maxTop = window.innerHeight - floatingWrap.offsetHeight;
				floatingWrap.style.right = 'auto';
				floatingWrap.style.bottom = 'auto';
				floatingWrap.style.left = Math.min(Math.max(0, savedPos.left), maxLeft) + 'px';
				floatingWrap.style.top = Math.min(Math.max(0, savedPos.top), maxTop) + 'px';
			}
		} catch (e) {}

		function startDrag(clientX, clientY) {
			let rect = floatingWrap.getBoundingClientRect();
			elemStartX = rect.left;
			elemStartY = rect.top;
			dragStartX = clientX;
			dragStartY = clientY;
			isDragging = true;
			hasDragged = false;
			floatingWrap.style.transition = 'none';
			floatingWrap.style.right = 'auto';
			floatingWrap.style.bottom = 'auto';
			floatingWrap.style.left = elemStartX + 'px';
			floatingWrap.style.top = elemStartY + 'px';
			floatingWrap.classList.add('--dragging');
		}

		function moveDrag(clientX, clientY) {
			if (!isDragging) { return; }
			let dx = clientX - dragStartX;
			let dy = clientY - dragStartY;
			if (!hasDragged && (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD)) {
				hasDragged = true;
				floatingWrap.classList.remove('--open');
			}
			if (hasDragged) {
				let newLeft = Math.max(0, Math.min(window.innerWidth - floatingWrap.offsetWidth, elemStartX + dx));
				let newTop = Math.max(0, Math.min(window.innerHeight - floatingWrap.offsetHeight, elemStartY + dy));
				floatingWrap.style.left = newLeft + 'px';
				floatingWrap.style.top = newTop + 'px';
			}
		}

		function endDrag() {
			if (!isDragging) { return; }
			isDragging = false;
			floatingWrap.style.transition = '';
			floatingWrap.classList.remove('--dragging');
			if (hasDragged) {
				try {
					localStorage.setItem(STORAGE_KEY, JSON.stringify({
						left: parseFloat(floatingWrap.style.left),
						top: parseFloat(floatingWrap.style.top)
					}));
				} catch (e) {}
			}
		}

		toggleBtn.addEventListener('mousedown', function (e) {
			if (e.button !== 0) { return; }
			startDrag(e.clientX, e.clientY);
		});
		document.addEventListener('mousemove', function (e) {
			moveDrag(e.clientX, e.clientY);
		});
		document.addEventListener('mouseup', function (e) {
			endDrag();
		});
		toggleBtn.addEventListener('touchstart', function (e) {
			let t = e.touches[0];
			startDrag(t.clientX, t.clientY);
		}, { passive: true });
		document.addEventListener('touchmove', function (e) {
			if (!isDragging) { return; }
			let t = e.touches[0];
			moveDrag(t.clientX, t.clientY);
		}, { passive: false });
		document.addEventListener('touchend', function () {
			endDrag();
		});
	}

	/**
	 * Update menu active on scroll and resize
	 */

	let isInViewport = function isInViewport(element) {
		const rect = element.getBoundingClientRect();
		return ( rect.top >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight));
	}

	let checkMenuActiveInViewPort = function (){
		$('.subtotal-summary-link').each(function(i) {
			let targetId = $(this).attr('data-id');
			let targetElem = document.getElementById('row-' + targetId);
			if(targetElem != null){
				if(isInViewport(targetElem)){
					$(this).addClass('--target-in-viewport');
				}else{
					let atLeastOneChildInViewPort = false;

					let children = getSubtotalTitleChilds($('#row-' + targetId));
					if(children.length > 0){
						children.forEach(function(item){
							let targetChildElem= document.getElementById(item);
							if(targetChildElem != null){
								if(isInViewport(targetChildElem)){
									atLeastOneChildInViewPort = true;
									return true;
								}
							}
						});
					}

					if(atLeastOneChildInViewPort) {
						$(this).addClass('--child-in-viewport');
					}else{
						$(this).removeClass('--target-in-viewport --child-in-viewport');
					}
				}
			}
		});
	};

	// on page load
	checkMenuActiveInViewPort();

	// on page scroll or resize
	$(window).on('resize scroll', function() {
		checkMenuActiveInViewPort();
	});


});
