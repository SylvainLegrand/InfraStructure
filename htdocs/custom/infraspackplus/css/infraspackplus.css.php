<?php
	/************************************************
	* Copyright (C) 2016-2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	* 	\file		./infraspackplus/css/infraspackplus.css.php
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
.infrasplusNoBCollapse {
	border-collapse: separate;
}

.infrasplusModal {
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrasplusModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infraspluswidthpictotitle {
	max-width: 48px;
}

.infrasplusDivTitre {
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

.infrasplusHR {
    text-align: center;
    margin: 5px 0px !important;
	padding: 0px !important;
}

.infrasplusFinal {
    line-height: 1px;
	border: none !important;
}

.infrasplusScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.infrasplusModal
{
	margin: -3px -0 0 -2px;
    width: calc(100% + 4px) !important;
    height: 30px;
}

.infrasplusModalTitle
{
	padding-left: 10px;
	line-height: 30px;
}

.infraspluscaution {
	color: red;
}

.infrasplusbgtrans {
	background: transparent !important;
}

.infrasplusbggreen {
	background: lightgreen;
}

.infrasplusbgorange {
	background: orange;
}

.infrasplusbgred {
	background: red;
}

.infrasplusgreen {
	color: green;
}

.infrasplusblue {
	color: #0088ff;
}

.infrasplusblack {
	color: black;
}

.infrasplusslogan {
	color: white;
	font-size: 16px;
}

.infrasplustitleparam {
	font-size: 14px;
}

.infrasplussubtitleparam {
	font-size: 12px;
}

.infraspluscolor {
	color: #19052d;
}

.infrasplusformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infraspluschangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.tablelayoutfixed {
	table-layout: fixed;
}

.widthquatrevingtdixpercent {
	width: 90%;
}

.width90 {
	width: 90px;
}

.width110 {
	width: 110px;
}

.width120 {
	width: 120px;
}

.width180 {
	width: 180px
}

.width220 {
	width: 220px;
}

.width270 {
	width: 270px;
}
.widthquinzepercent {
	width: 15%;
}

.widthtrentepercent {
	width: 30%;
}

.widthtrentetroispercent {
	width: 33%;
}

.height75 {
	height: 75px;
}

.height50 {
	height: 50px;
}

.height25 {
	height: 25px;
}

.height20 {
	height: 20px;
}

.lineheight200percent {
	line-height: 200%;
}

.nomargin {
	margin: 0px;
}

.nopadding {
	padding: 0px !important;
}

.noborder {
	border: none;
}

.nopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.fontsizeinherit {
	font-size: inherit;
}
button.copyParamsBtn, .copyParamsBtn:hover {
	padding: 8px 25px 8px 25px;
}