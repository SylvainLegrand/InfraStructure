<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	* Copyright (C) 2016-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasdiscount/css/infrasdiscount.css.php
	* 	\ingroup	InfraS
	* 	\brief		CSS for the module InfraS
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
	src: url('<?php print dol_buildpath('/infrasdiscount/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrasdiscount/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrasdiscountneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infrasdiscountpuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

#InfraSDiscount {
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

.formInfraSDiscount {
	text-align: center;
}

.formInfraSDiscount input {
	margin-left: 2px;
}

label.titre {
	font-family: roboto,arial,tahoma,verdana,helvetica;
	font-weight: bold;
	color: rgb(90,90,90);
	text-decoration: none;
}

.infrasdiscountNoBCollapse {
	border-collapse: separate;
}

.infrasdiscountModal {
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrasdiscountModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infrasdiscountwidthpictotitle {
	max-width: 48px;
}

.infrasdiscountDivTitre {
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

.infrasdiscountHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.infrasdiscountFinal {
    line-height: 1px;
	border: none !important;
}

.infrasdiscountScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.infrasdiscountcaution {
	color: red;
}

.infrasdiscountbgtrans {
	background: transparent !important;
}

.infrasdiscountbggreen {
	background: lightgreen;
}

.infrasdiscountbgorange {
	background: orange;
}

.infrasdiscountbgred {
	background: red;
}

.infrasdiscountgreen {
	color: green;
}

.infrasdiscountblue {
	color: #0088ff;
}

.infrasdiscountblack {
	color: black;
}

.infrasdiscountslogan {
	color: #ffffff;
	font-size: 16px;
}

.infrasdiscounttitleparam {
	font-size: 14px;
}

.infrasdiscountsubtitleparam {
	font-size: 12px;
}

.infrasdiscountcolor {
	color: #19052d;
}

.infrasdiscountformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infrasdiscountchangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.infrasdiscountwidth110 {
	width: 110px;
}

.infrasdiscountwidth120 {
	width: 120px;
}

.infrasdiscountwidth180 {
	width: 180px;
}

.infrasdiscountwidth220 {
	width: 220px;
}

.infrasdiscountwidth270 {
	width: 270px;
}

.infrasdiscountminwidth700imp {
	min-width: 700px !important;
}

.infrasdiscountwidthtrentepercent {
	width: 30%;
}

.infrasdiscountheight75 {
	height: 75px;
}

.infrasdiscountheight32 {
	height: 32px;
}

.infrasdiscountheight50 {
	height: 50px;
}

.infrasdiscountheight25 {
	height: 25px;
}

.infrasdiscountheight20 {
	height: 20px;
}

.infrasdiscountnomargin {
	margin: 0px;
}

.infrasdiscountnopadding {
	padding: 0px !important;
}

.infrasdiscountnoborder {
	border: none;
}

.infrasdiscountnopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrasdiscountmargintop10imp {
	margin-top: 10px !important;
}

.infrasdiscountfontsizeinherit {
	font-size: inherit;
}

