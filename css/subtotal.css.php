<?php
	/************************************************
	* Copyright (C) 2016-2026 Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		../subtotal/css/subtotal.css.php
	* 	\ingroup	InfraS
	* 	\brief		CSS file for the SubTotal module
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
	src: url('<?php print dol_buildpath('/subtotal/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/subtotal/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.subtotalneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.subtotalpuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

#SubTotal {
	padding: 8px 8px 16px 8px;
	height: 100%;
	width: 50%;
	margin-left: auto;
	margin-right: auto;
	margin-top: 8px;
	margin-bottom: 8px;
	border: solid #ddd 2px;
	background-color: #efefef;
}

.formSubTotal {
	text-align: center;
}

.formSubTotal input {
	margin-left: 2px;
}

label.titre {
	font-family: roboto,arial,tahoma,verdana,helvetica;
	font-weight: bold;
	color: rgb(90,90,90);
	text-decoration: none;
}

.subtotalNoBCollapse {
	border-collapse: separate;
}

.subtotalModal {
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.subtotalModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.subtotalwidthpictotitle {
	max-width: 48px;
}

.subtotalDivTitre {
	color: var(--colortexttitle) !important;
	font-weight: bold;
	font-size: 1.1em;
	text-decoration: none;
	padding-top: 5px;
	padding-bottom: 5px;
}

.subtotalHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.subtotalFinal {
    line-height: 1px;
	border: none !important;
}

.subtotalScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.subtotalcaution {
	color: red;
}

.subtotalbgtrans {
	background: transparent !important;
}

.subtotalbggreen {
	background: lightgreen;
}

.subtotalbgorange {
	background: orange;
}

.subtotalbgred {
	background: red;
}

.subtotalgreen {
	color: green;
}

.subtotalblue {
	color: #0088ff;
}

.subtotalblack {
	color: black;
}

.subtotalslogan {
	color: #ffffff;
	font-size: 16px;
}

.subtotaltitleparam {
	font-size: 14px;
}

.subtotalsubtitleparam {
	font-size: 12px;
}

.subtotalcolor {
	color: #19052d;
}

.subtotalformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.subtotalchangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.subtotalwidth110 {
	width: 110px;
}

.subtotalwidth120 {
	width: 120px;
}

.subtotalwidth180 {
	width: 180px;
}

.subtotalwidth220 {
	width: 220px;
}

.subtotalwidth270 {
	width: 270px;
}

.subtotalminwidth700imp {
	min-width: 700px !important;
}

.subtotalwidthtrentepercent {
	width: 30%;
}

.subtotalheight75 {
	height: 75px;
}

.subtotalheight32 {
	height: 32px;
}

.subtotalheight50 {
	height: 50px;
}

.subtotalheight25 {
	height: 25px;
}

.subtotalheight20 {
	height: 20px;
}

.subtotalnomargin {
	margin: 0px;
}

.subtotalnopadding {
	padding: 0px !important;
}

.subtotalnoborder {
	border: none;
}

.subtotalnopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.subtotalmargintop10imp {
	margin-top: 10px !important;
}

.subtotalfontsizeinherit {
	font-size: inherit;
}
