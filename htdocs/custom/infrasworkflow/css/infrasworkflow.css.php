<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
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
	* 	\file		./infrasworkflow/css/infrasworkflow.css.php
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
	src: url('<?php print dol_buildpath('/infrasworkflow/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrasworkflow/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrasworkflowneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infrasworkflowpuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust: 0.6;
}

#InfraSWorkflow {
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

.formInfraSWorkflow {
	text-align: center;
}

.formInfraSWorkflow input {
	margin-left: 2px;
}

label.titre {
	font-family: roboto,arial,tahoma,verdana,helvetica;
	font-weight: bold;
	color: rgb(90,90,90);
	text-decoration: none;
}

.infrasworkflowNoBCollapse {
	border-collapse: separate;
}

.infrasworkflowModal {
	margin: -3px -0 0 -2px;
	width: calc(100% + 4px) !important;
	height: 30px;
}

.infrasworkflowModalTitle {
	padding-left: 10px;
	line-height: 30px;
}

img.infrasworkflowwidthpictotitle {
	max-width: 48px;
}

.infrasworkflowDivTitre {
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

.infrasworkflowHR {
	text-align: center;
	margin: 5px 0px !important;
	padding: 0px !important;
}

.infrasworkflowFinal {
	line-height: 1px;
	border: none !important;
}

.infrasworkflowScrollUp {
	position: fixed;
	bottom : 30px;
	right: -100px;
	/*color: #19052d;*/
	font-size: xx-large;
}

.infrasworkflowcaution {
	color: red !important;
}

.infrasworkflowcautionbold {
	color: red !important;
	font-weight: bold !important;
}

.infrasworkflowbgtrans {
	background: transparent !important;
}

.infrasworkflowbggreen {
	background: lightgreen;
}

.infrasworkflowbgorange {
	background: orange;
}

.infrasworkflowbgred {
	background: red;
}

.infrasworkflowgreen {
	color: green;
}

.infrasworkflowblue {
	color: #0088ff;
}

.infrasworkflowblack {
	color: black;
}

.infrasworkflowslogan {
	color: #ffffff;
	font-size: 16px;
}

.infrasworkflowtitleparam {
	font-size: 14px;
}

.infrasworkflowsubtitleparam {
	font-size: 12px;
}

.infrasworkflowcolor {
	color: #19052d;
}

.infrasworkflowformabout {
	/*background-color: var(--colorbacktabcard1);*/
	padding: 20px;
}

.infrasworkflowchangelogbase {
	border: none;
	padding-top: 0;
	padding-bottom: 0
}

.infrasworkflowchexbox {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.infrasworkflowidth110 {
	width: 110px;
}

.infrasworkflowidth120 {
	width: 120px;
}

.infrasworkflowidth180 {
	width: 180px;
}

.infrasworkflowidth220 {
	width: 220px;
}

.infrasworkflowidth270 {
	width: 270px;
}

.infrasworkflowminwidth700imp {
	min-width: 700px !important;
}

.infrasworkflowidthtrentepercent {
	width: 30%;
}

.infrasworkflowheight75 {
	height: 75px;
}

.infrasworkflowheight32 {
	height: 32px;
}

.infrasworkflowheight50 {
	height: 50px;
}

.infrasworkflowheight25 {
	height: 25px;
}

.infrasworkflowheight20 {
	height: 20px;
}

.infrasworkflowwidth110 {
	width: 110px;
}

.infrasworkflownomargin {
	margin: 0px;
}

.infrasworkflownopadding {
	padding: 0px !important;
}

.infrasworkflownoborder {
	border: none;
}

.infrasworkflownopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrasworkflowmargintop10imp {
	margin-top: 10px !important;
}

/* Drag-and-drop documents joints (INFRASWORKFLOW_DOCUMENTS_DRAGDROP) */
/* Force foreground display of the "Drop file" message above other layers */
.dragDropAreaMessage {
	z-index: 99999 !important;
}

/* Visible hint added next to the table title to inform users about the drop zone */
.infrasworkflowDragdropHint {
	display: inline-block !important;
	margin-left: 0.7em !important;
	font-style: italic;
}

.infrasworkflowDragdropHint i {
	margin-right: 0.3em;
}

.infrasworkflowfontsizeinherit {
	font-size: inherit;
}