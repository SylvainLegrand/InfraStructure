<?php
	/************************************************
	* Copyright (C) 2016-2025	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.  If not, see <http://www.gnu.org/licenses/>.
	************************************************/

	/************************************************
	*	\file		./infrassearch/css/infrassearch.css.php
	*	\ingroup	InfraS
	*	\brief		CSS for the module InfraS
	************************************************/

	// Dolibarr environment *************************
	if (! defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', '1');
	}
	if (! defined('NOCSRFCHECK')) {
		define('NOCSRFCHECK', 1);
	}
	if (! defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}
	if (! defined('NOLOGIN')) {
		define('NOLOGIN', 1);	// File must be accessed by logon page so without login
	}
	if (! defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', 1);
	}
	if (! defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (! defined('ISLOADEDBYSTEELSHEET')) {
		define('ISLOADEDBYSTEELSHEET', '1');
	}
	session_cache_limiter('public');

	require '../config.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	// Define css type
	header('Content-type: text/css');
	// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
	// You can use CTRL+F5 to refresh your browser cache.
	if (empty($dolibarr_nocache)) {
		header('Cache-Control: max-age=3600, public, must-revalidate');
	} else {
		header('Cache-Control: no-cache');
	}
?>
@font-face {
	font-family: 'puentebold';
	src: url('<?php print dol_buildpath('/infrassearch/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrassearch/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

#results
{
	position:relative;
	margin-top: 15px;
}

#results span.loading
{
	padding : 20px;
	background-color: #f64f1c;
	border-radius: 10px;
	top: 50px;
	left: 50px;
	position: relative;
}

#results div.result
{
	width: 32%;
	float: left;
	box-shadow: 3px 3px 4px #ddd;
	margin: 0 0.5% 1% 0.5%;
}

.highlight
{
	font-weight: bold;
}

#topmenu-breadcrumb-dropdown-body {
	left: 12px;
}

input#sew_keyword {
	/*background-color: #fff !important;*/
	line-height: 1.3em;
	padding: 2px 0px 2px 7px;
}

.infrassearchNoBCollapse {
	border-collapse: separate;
}

.infrassearchinfrasModal {
	margin: -3px -0 0 -2px;
	width: calc(100% + 4px) !important;
	height: 30px;
}

.infrassearchModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infrassearchwidthpictotitle {
	max-width: 48px;
}

.infrassearchDivTitre {
	<?php if (isModEnabled('oblyon')) { ?>
		color: var(--colorftitle) !important;
	<?php } else { ?>
		color: var(--colortexttitle) !important;
	<?php } ?>
	font-weight: bold;
	font-size: 1.1em;
	text-decoration: none;
	padding-top: 5px;
	padding-bottom: 5px;
}

.infrassearchHR {
	text-align: center;
	margin: 5px 0px !important;
	padding: 0px !important;
}

.infrassearchFinal {
	line-height: 1px;
	border: none !important;
}

.infrassearchScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	color: #19052d;
	font-size: xx-large;
}

.infrassearchcaution {
	color: red;
}

.infrassearchbgtrans {
	background-color: transparent;
	<?php if (getDolGlobalString('MAIN_MENU_INVERT')) { ?>
		color: var(--bgnavleft_txt);
	<?php } else { ?>
		color: var(--bgnavtop_txt);
	<?php } ?>
	border-radius: 2px;
	font-family: var(--fontfamilydol);
	outline: none;
	margin: 0px 0px 0px 0px;
	border-bottom: solid 1px #888 !important;
}

.infrassearchbgtrans.ui-autocomplete-loading {
	color: #000;
	background: white url(<?php echo dol_buildpath('/theme/'.getDolGlobalString('theme', 'eldy').'/img/working.gif', 1) ?>) right center no-repeat;
}

.infrassearchbggreen {
	background: lightgreen;
}

.infrassearchbgorange {
	background: orange;
}

.infrassearchbgred {
	background: red;
}

.infrassearchgreen {
	color: green;
}

.infrassearchblue {
	color: #0088ff;
}

.infrassearchblack {
	color: black;
}

.infrassearchslogan {
	font-size: 16px;
}

.infrassearchTitleparam {
	font-size: 14px;
}

.infrassearchSubtitleparam {
	font-size: 12px;
}

.infrassearchColor {
	color: #19052d;
}

.login_block .classfortooltip:hover {
	background-color: transparent;
}

.infrassearchformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infrassearchchangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.infrassearchwidthquatrevingtdixpercent {
	width: 90%;
}

.infrassearchwidth110 {
	width: 110px;
}

.infrassearchwidth120 {
	width: 120px;
}

.infrassearchwidth180 {
	width: 180px
}

.infrassearchwidth220 {
	width: 220px;
}

.infrassearchwidth270 {
	width: 270px;
}

.infrassearchminwidth700imp {
	min-width: 700px !important;
}

.infrassearchwidthtrentepercent {
	width: 30%;
}

.infrassearchheight75 {
	height: 75px;
}

.infrassearchheight32 {
	height: 32px;
}

.infrassearchheight50 {
	height: 50px;
}

.infrassearchheight25 {
	height: 25px;
}

.infrassearchheight20 {
	height: 20px;
}

.infrassearchnomargin {
	margin: 0px;
}

.infrassearchnopadding {
	padding: 0px !important;
}

.infrassearchnoborder {
	border: none;
}

.infrassearchnopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrassearchmargintop10imp {
	margin-top: 10px !important;
}

.infrassearchfontsizeinherit {
	font-size: inherit;
}

.infrassearchdropdown-item a img {
	max-height: 16px;
}

.infrassearchdropdown-breadcrumb-item {
	display: block !important;
	box-sizing: border-box;
	width: 100%;
	clear: both;
	font-weight: 400;
	<?php if (isModEnabled('oblyon')) { ?>
		color: var(--colorfline) !important;
	<?php } else { ?>
		color: var(--colortext) !important;
	<?php } ?>
	text-align: inherit;
	background-color: transparent;
	border: 0;
	-webkit-box-shadow: none;
	-moz-box-shadow: none;
	box-shadow: none;
}

div.login_block div.infrassearchdropdown-breadcrumb-item a {
	color: inherit;
}
