<?php if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>
/* <style type="text/css" > */
/* ================================================================================================
   oblyon/themeoblyon/fixes.inc.php
   Role      : Options globales, en-tetes / colonnes / totaux collants (FIX_*), petits ecrans
   Inclus par : global.inc.php | Garde : ISLOADEDBYSTEELSHEET | Variables PHP : portee de style.css.php / theme_vars.inc.php
   Regle     : une regle, un endroit (pas de copie d'un selecteur present dans un autre fichier ; verifier avec dev/csscompare.php)
   ================================================================================================ */

/* ============================================================================== */
/* Global options	  															  */
/* ============================================================================== */
div.fiche>form>div.div-table-responsive, div.fiche>form>div.div-table-responsive-no-min {
	overflow-x: unset;
}

/* ============================================================================== */
/* Sticky table headers columns													  */
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_STICKY_HEADER_CARD')) { ?>
	div.fiche>form>div.div-table-responsive, div.fiche>form>div.div-table-responsive-no-min {
		overflow-x: unset;
	}
	.div-table-responsive-no-min, div.div-table-responsive {
		 overflow-x: unset;
	}
	tr.liste_titre th:not(#ajaxloaded_tablelines th) {
		position: sticky;
		<?php if (getDolGlobalString('THEME_STICKY_TOPMENU')) { ?>
			<?php if (getDolGlobalString('MAIN_MENU_INVERT')) { ?>
				top: 40px;
			<?php } else { ?>
				top: 54px;
			<?php } ?>
		<?php } else { ?>
			top: 0;
		<?php } ?>
		background-color: var(--colorbtitle);
		z-index: 1;
	}
	tr.liste_titre.box_titre th {
		top: unset !important;
	}
	.publicnewticketform2 th {
		top: unset !important;
	}
	.fichehalfleft  tr.liste_titre th:not(#ajaxloaded_tablelines th),
	.fichehalfright  tr.liste_titre th:not(#ajaxloaded_tablelines th) {
		top: unset;
	}

	<?php if (getDolGlobalString('FIX_STICKY_TABS_CARD')) { ?>
	#id-right > .fiche > form[action*="perday.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > .tabBar > form[action*="perday.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > form[action*="perweek.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > .tabBar > form[action*="perweek.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > form[action*="permonth.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > .tabBar > form[action*="permonth.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > form[action*="bankrecords.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > .tabBar > form[action*="bankrecords.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > form[action*="prelink.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th),
	#id-right > .fiche > .tabBar > form[action*="prelink.php"] tr.liste_titre th:not(#ajaxloaded_tablelines th) {
		top: 104px !important;
	}
	<?php } ?>
<?php } ?>

/* ============================================================================== */
/* Sticky tabs card WIP	 													  */
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_STICKY_TABS_CARD')) { ?>
div.tabs:first-of-type, .fiche > div.tabs
{
	position: sticky;
	<?php if (getDolGlobalString('THEME_STICKY_TOPMENU')) { ?>
		top: 41px;
	<?php } else { ?>
		top: 0;
	<?php } ?>
	border-bottom: solid 1px #cccccc !important;
	background-color: var(--bgcolor);
	margin: 0 auto 0 0 !important;
	height: auto;
	z-index: 50;
}

#dialogforpopup .tabs {
	top: unset !important;
}
<?php } ?>

/* ============================================================================== */
/* Sticky table 1st column  													  */
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_STICKY_COLUMN_FIRST') || getDolGlobalString('OBLYON_STICKY_COLUMN_FIRST')) { ?>
@media (min-width: 768px) {
	#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > table > tbody > * > :first-of-type:not(.actioncolumn),
	#id-right > .fiche > .tabBar > form[action*="list.php"] div.div-table-responsive > table > tbody > * > :first-of-type,
	#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > div.div-table-responsive-inside > table > tbody > * > :first-of-type,
	#id-right > .fiche > .tabBar > form[action*="list.php"] div.div-table-responsive > div.div-table-responsive-inside > table > tbody > * > :first-of-type,
	#id-right > .fiche > form[action*="bankrecords.php"] div.div-table-responsive > table > tbody > * > :first-of-type,
	#id-right > .fiche > .tabBar > form[action*="bankrecords.php"] div.div-table-responsive > table > tbody > * > :first-of-type,
	#id-right > .fiche > form[action*="prelink.php"] div.div-table-responsive > table > tbody > * > :first-of-type,
	#id-right > .fiche > .tabBar > form[action*="prelink.php"] div.div-table-responsive > table > tbody > * > :first-of-type {
		position: sticky;
		<?php if (getDolGlobalString('OBLYON_STICKY_LEFTBAR') && !getDolGlobalString('OBLYON_EFFECT_REDUCE_LEFTMENU')) { ?>
			left: 230px;
		<?php } elseif (getDolGlobalString('OBLYON_STICKY_LEFTBAR') && getDolGlobalString('OBLYON_EFFECT_REDUCE_LEFTMENU')) { ?>
			left: 38px;
		<?php } else { ?>
			left: 0;
		<?php } ?>
		z-index: 2;
		background-color: var(--colorbtitle);
		/* background: #<?php print colorArrayToHex(colorStringToArray($colorbacklineimpair1)); ?>;*/
		border-right: 1px solid var(--colorBorderActionColumn);
	}

	#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > table > tbody > * > :first-of-type.actioncolumn,
	#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > table > tbody > * > :first-of-type:has(.checkforselect) {
		position: sticky;
		<?php if (getDolGlobalString('OBLYON_STICKY_LEFTBAR') && !getDolGlobalString('OBLYON_EFFECT_REDUCE_LEFTMENU')) { ?>
			left: 230px;
		<?php } elseif (getDolGlobalString('OBLYON_STICKY_LEFTBAR') && getDolGlobalString('OBLYON_EFFECT_REDUCE_LEFTMENU')) { ?>
			left: 38px;
		<?php } else { ?>
			left: 0;
		<?php } ?>
		z-index: 1;
		background-color: var(--colorbtitle);
		/* background: #<?php print colorArrayToHex(colorStringToArray($colorbacklineimpair1)); ?>;*/
		border-right: 1px solid var(--colorBorderActionColumn);
	}

	.multichoicedoc {
		left: 240px !important;
		top: -10px;
	}

	<?php if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) { ?>
		.dropdown dd ul:not(.ulselectedfields ) {
			left: 60px;
		}
	<?php } else { ?>
		.dropdown dd ul {
			right: 30px;
		}
	<?php } ?>
}
<?php } ?>

/* ============================================================================== */
/* Sticky table last column												  */
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_STICKY_COLUMN_LAST') || getDolGlobalString('OBLYON_STICKY_COLUMN_LAST')) { ?>
#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > table > tbody > * > :last-of-type,
#id-right > .fiche > .tabBar > form[action*="list.php"] div.div-table-responsive > table > tbody > * > :last-of-type,
#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > div.div-table-responsive-inside > table > tbody > * > :last-of-type,
#id-right > .fiche > .tabBar > form[action*="list.php"] div.div-table-responsive > div.div-table-responsive-inside > table > tbody > * > :last-of-type,
#id-right > .fiche > form[action*="bankrecords.php"] div.div-table-responsive > table > tbody > * > :last-of-type,
#id-right > .fiche > .tabBar > form[action*="bankrecords.php"] div.div-table-responsive > table > tbody > * > :last-of-type,
#id-right > .fiche > form[action*="prelink.php"] div.div-table-responsive > table > tbody > * > :last-of-type,
#id-right > .fiche > .tabBar > form[action*="prelink.php"] div.div-table-responsive > table > tbody > * > :last-of-type {
	position: sticky;
	right: 0;
	z-index: 1;
	background-color: var(--colorbtitle);
	/* background: #<?php print colorArrayToHex(colorStringToArray($colorbacklineimpair1)); ?>; */
	border-left: 1px solid #bbbbbb;
}
<?php } ?>

/* ============================================================================== */
/* Sticky total bar															   */
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_STICKY_TOTAL_BAR')) { ?>
#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > .tabBar > form[action*="list.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > form[action*="perday.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > .tabBar > form[action*="perday.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > form[action*="perweek.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > .tabBar > form[action*="perweek.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > form[action*="permonth.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > .tabBar > form[action*="permonth.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > form[action*="bankrecords.php"] div.div-table-responsive > table tr.liste_total,
#id-right > .fiche > .tabBar > form[action*="bankrecords.php"] div.div-table-responsive > table tr.liste_total {
		position: sticky;
		<?php if (getDolGlobalString('FIX_STICKY_GRANDTOTAL_BAR')) { ?>
			bottom: 42px;
		<?php } else { ?>
			bottom: 0;
		<?php } ?>
		z-index: 2;
	}
<?php } ?>

/* ============================================================================== */
/* Sticky grand total bar  WIP													*/
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_STICKY_GRANDTOTAL_BAR')) { ?>
	#id-right > .fiche > form[action*="list.php"] div.div-table-responsive > table tr.liste_grandtotal,
	#id-right > .fiche > .tabBar > form[action*="list.php"] div.div-table-responsive > table tr.liste_grandtotal {
		position: sticky;
		bottom: 0;
		z-index: 2;
	}
<?php } ?>

/* ============================================================================== */
/* Fix title in list															  */
/* ============================================================================== */
<?php if (getDolGlobalString('FIX_TITLE_IN_LIST') && (float) DOL_VERSION >= 18.0) { ?>
	td.nobordernopadding.widthpictotitle.valignmiddle.col-picto {
		position: sticky;
		left: 10px;
	}
	td.nobordernopadding.valignmiddle.col-title {
		position: sticky;
		left: 40px;
	}
	td.nobordernopadding.center.valignmiddle.col-center {
		position: sticky;
		left: 300px;
		right: 200px;
		z-index: 1;
	}
	td.nobordernopadding.valignmiddle.right.col-right {
		position: sticky;
		right: 10px;
	}
<?php } ?>

/* ============================================================================== */
/* Option to remove Kanban view in list										   */
/* ============================================================================== */
<?php if (getDolGlobalString('DISABLE_KANBAN_VIEW_IN_LIST')) { ?>
	.paginationafterarrows > .reposition {
		display: none;
	}
	/* pour éviter que le bouton d'ajout de consommation de temps soit masqué par la ligne précédente (/projet/tasks/time.php) */
	.paginationafterarrows > .reposition.btnTitlePlus {
		display: inline-block;
	}
<?php } ?>

/* ============================================================================== */
/* CSS style used for small screen												  */
/* ============================================================================== */
.topmenuimage {
	background-size: 22px auto;
	top: 2px;
}
.imgopensurveywizard
{
	padding: 0 4px 0 4px;
}

/* rule to reduce inverted top menu */
@media only screen and (max-width: 1200px)
{
	#tmenu_tooltipinvert .sec-nav__item {
		max-width: 120px;
	}
	#tmenu_tooltipinvert .sec-nav__item .icon {
		display: none;
	}
	.sec-nav__link {
		overflow: hidden;
		text-overflow: ".";
	}
}

/* rule to reduce inverted top menu */
@media only screen and (max-width: 1024px)
{
	#tmenu_tooltipinvert .sec-nav__item {
		max-width: 100px;
	}
	#tmenu_tooltipinvert .sec-nav__item .icon {
		display: none;
	}
	.sec-nav__sub-item {
		overflow-wrap: break-word;
	}

	div.vmenu {
	<?php if (getDolGlobalString('OBLYON_REDUCE_LEFTMENU')) { ?>
		max-width: 40px;
	<?php } else { ?>
		min-width: 210px;
		max-width: 100%;
	<?php } ?>
	}

	.vmenusearchselectcombo {
		 min-width: 150px;
		 max-width: 100%;
	 }
	.sec-nav.is-inverted {
	<?php if (getDolGlobalString('OBLYON_FULLSIZE_TOPBAR') || !empty($conf->dol_optimize_smallscreen)) { ?>
			margin-<?php print $left; ?>: 10px;
		<?php } else { ?>
			margin-<?php print $left; ?>: 10px;
		<?php } ?>
	}

	<?php if (!getDolGlobalString('MAIN_MENU_INVERT')) { ?>
		#id-left {
			z-index: 96;
		}
	<?php } ?>
}

/* rule to reduce inverted top menu */
@media only screen and (max-width: 905px)
{
	#tmenu_tooltip {
		padding-<?php print $right; ?>: 92px;
	}

	#tmenu_tooltipinvert .sec-nav__item {
		max-width: 80px;
	}
}

/* rule to reduce top menu */
@media only screen and (max-width: 767px)
{
	#tmenu_tooltip .main-nav__item {
		max-width: 66px;
	}
	.main-nav__link {
		overflow: hidden;
		text-overflow: '.';
	}

	#tmenu_tooltipinvert .sec-nav__item {
		max-width: 60px;
	}
	#tmenu_tooltipinvert .sec-nav__item .icon {
		display: none;
	}

	div.vmenu {
	<?php if (getDolGlobalString('OBLYON_REDUCE_LEFTMENU')) { ?>
		max-width: 40px;
	<?php } else { ?>
		min-width: 130px;
	<?php } ?>
	}

	.vmenusearchselectcombo {
		min-width: 110px;
	}

	.sec-nav.is-inverted {
		<?php if (getDolGlobalString('OBLYON_FULLSIZE_TOPBAR') || !empty($conf->dol_optimize_smallscreen)) { ?>
			margin-<?php print $left; ?>: 5px;
		<?php } else { ?>
			margin-<?php print $left; ?>: 5px;
		<?php } ?>
	}
	.div-table-responsive {
		line-height: 120%;
	}
	.imgopensurveywizard, .imgautosize { width:95%; height: auto; }

	#tooltip {
		position: absolute;
		width: <?php print dol_size(350, 'width'); ?>px;
	}

	div.tabBar {
		padding-left: 0px;
		padding-right: 0px;
		-webkit-border-radius: 0;
		border-radius: 0px;
		border-right: none;
		border-left: none;
	}

	.box-flex-container {
		margin: 0 0 0 -8px !important;
	}

	#tmenu_tooltipinvert .pushy-btn,
	#tmenu_tooltip .pushy-btn { /* for v3.5 */
	<?php if (getDolGlobalString('MAIN_MENU_INVERT')) { ?>
		font-size: 18px !important;
		height: 40px;
		line-height: 40px;
	<?php } else { ?>
		font-size: 18px !important;
		height: 54px;
		line-height: 54px;
	<?php } ?>
	}

	div.login_block {
		font-size: 16px !important;
		padding-right: 5px;
	}

	.menulogocontainer {
		display: none;
	}

	.main-nav .icon {
		font-size:14px;
	}

	#tmenu_tooltip .main-nav__link {
		padding: 0 1px;
		max-width: 40px;
	}
}

/* nboftopmenuentries = <?php print (!empty($nbtopmenuentries) ? $nbtopmenuentries : 0) ?>, fontsize=<?php print (!empty($fontsize) ? $fontsize : 0) ?> */
/* rule to reduce top menu - 1st reduction */
@media only screen and (max-width: <?php print (!empty($nbtopmenuentries) && !empty($fontsize) ? round($nbtopmenuentries * $fontsize * 6.7, 0) + 8 : 8); ?>px)
{
	div.tmenucenter {
		max-width: <?php print (!empty($fontsize) ? round($fontsize * 4) : 0); ?>px;	/* size of viewport */
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		color: var(--colortextbackhmenu);
	}
	.mainmenuaspan {
		font-size: 10px;
	}
	.topmenuimage {
		background-size: 26px auto;
		margin-top: 0px;
	}
	li.tmenu, li.tmenusel {
		min-width: 32px;
	}
	div.mainmenu {
		min-width: auto;
	}
	div.tmenuleft {
		display: none;
	}
	#tmenu_tooltipinvert .sec-nav__item .icon {
		display: none;
	}
}

/* rule to reduce top menu - 2nd reduction */
@media only screen and (max-width: <?php print (!empty($nbtopmenuentries) && !empty($fontsize) ? round($nbtopmenuentries * $fontsize * 4.5, 0) + 8 : 8); ?>px)
{
	div.tmenucenter {
		max-width: <?php print (!empty($fontsize) ? round($fontsize * 2) : 0); ?>px;	/* size of viewport */
		text-overflow: clip;
	}
	.mainmenuaspan {
		font-size: 10px;
		padding-left: 0;
		padding-right: 0;
	}
	.topmenuimage {
		background-size: 20px auto;
		margin-top: 2px;
	}
	#tmenu_tooltipinvert .sec-nav__item .icon {
		display: none;
	}
}

/* rule to reduce top menu - 3rd reduction */
@media only screen and (max-width: 570px)
{
	#id-right {
		padding-left: unset;
	}
	/* Reduce login top right info */
	.usertext.atoplogin {
		display: none;
	}
	div#tmenu_tooltip, #tmenu_tooltipinvert {
		<?php if (GETPOST("optioncss", "aZ09") == 'print') {	?>
			display:none;
		<?php } else { ?>
			padding-<?php print $right; ?>: 92px;
		<?php } ?>
	}
	div.login_block {
		vertical-align: middle;
		max-width: 120px;
		padding-right: 3px;
		display: inline-flex;
	}
	div.login_block_other {
		display: block;
		width: auto;
		min-width: 40px;
	}
	div.login_block_other .inline-block {
		display: block;
		width: auto;
	}
	li.tmenu, li.tmenusel {
		min-width: 30px;
	}

	div.tmenucenter {
		text-overflow: clip;
	}
	.topmenuimage {
		background-size: 20px auto;
		margin-top: 2px !important;
	}
	div.mainmenu {
		min-width: 20px;
	}
	.div-table-responsive {
		line-height: 120%;
	}
	#tooltip {
		position: absolute;
		width: <?php print dol_size(300,'width'); ?>px;
	}

	select {
		width: 98%;
		min-width: 0 !important;
	}
	div.divphotoref {
		padding-right: 5px;
	}
	img.photoref, div.photoref {
		border: none;
		-webkit-box-shadow: none;
		box-shadow: none;
		padding: 4px;
		height: 20px;
		width: 20px;
		object-fit: contain;
	}

	.titlefield {
		width: auto !important;		/* We want to ignore the 30%, try to use more if you can */
	}
	.tableforfield>tr>td:first-child {
		max-width: 100px;			/* but no more than 100px */
	}

	.main-nav .icon {
		font-size:12px;
	}

	#tmenu_tooltipinvert .sec-nav__item {
		max-width: 50px;
	}

	#tmenu_tooltipinvert .sec-nav__item .icon {
		display: none;
	}

	.sec-nav.is-inverted {
	<?php if (getDolGlobalString('OBLYON_SHOW_COMPNAME') || getDolGlobalString('OBLYON_FULLSIZE_TOPBAR') || !empty($conf->dol_optimize_smallscreen)) { ?>
		margin-<?php print $left; ?>: 1px;
	<?php } else { ?>
		margin-<?php print $left; ?>: 1px;
	<?php } ?>
	}

	#tmenu_tooltipinvert div.menu_contenu {
		display: none;
	}

	div.fiche {
		margin: 0 3px 0 3px;
	}

	table.table-fiche-title .col-title div.titre{
		line-height: unset;
	}

	input#addedfile {
		width: 95%;
	}
}
