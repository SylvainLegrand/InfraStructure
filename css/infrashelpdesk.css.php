<?php
	/************************************************
	* Copyright (C) 2026	Sylvain Legrand - <contact@infras.fr>	InfraS - <https://www.infras.fr>
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
	*	\file		./infrashelpdesk/css/infrashelpdesk.css.php
	*	\ingroup	InfraS
	*	\brief		CSS for the module InfraSHelpdesk
	************************************************/

	// Dolibarr environment *************************
	if (!defined('NOREQUIRESOC')) {
		define('NOREQUIRESOC', '1');
	}
	if (!defined('NOCSRFCHECK')) {
		define('NOCSRFCHECK', 1);
	}
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', 1);
	}
	if (!defined('NOLOGIN')) {
		define('NOLOGIN', 1);	// File must be accessed by logon page so without login
	}
	if (!defined('NOREQUIREHTML')) {
		define('NOREQUIREHTML', 1);
	}
	if (!defined('NOREQUIREAJAX')) {
		define('NOREQUIREAJAX', '1');
	}
	if (!defined('ISLOADEDBYSTEELSHEET')) {
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
	src: url('<?php print dol_buildpath('/infrashelpdesk/css/puentebold.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

@font-face {
	font-family: 'NeuropolRegular';
	src: url('<?php print dol_buildpath('/infrashelpdesk/css/NeuropolRegular.ttf', 1); ?>') format('truetype');
	font-weight: normal;
	font-style: normal;
}

.infrashelpdeskneuropolinfras {
	font-family: NeuropolRegular, sans-serif;
	font-weight: bold;
	font-style: italic;
	color: #19052d;
}

.infrashelpdeskpuentedolibarr {
	font-family: puentebold, sans-serif;
	color: #027991;
	font-size-adjust:0.6;
}

.infrashelpdeskCaution {
	color: red;
}

.infrashelpdeskwidth180 {
	width: 180px;
}

.infrashelpdeskwidth220 {
	width: 220px;
}

.infrashelpdeskwidth270 {
	width: 270px;
}

.infrashelpdeskheight32 {
	height: 32px;
}

.infrashelpdeskheight75 {
	height: 75px;
}

.infrashelpdeskheight50 {
	height: 50px;
}

.infrashelpdeskheight25 {
	height: 25px;
}

.infrashelpdesknoborder {
	border: none !important;
}

.infrashelpdeskwidthtrentepercent {
	width: 30%;
}

.infrashelpdeskminwidth700imp {
	min-width: 700px !important;
}

.infrashelpdeskmargintop10imp {
	margin-top: 10px !important;
}

.infrashelpdesknopaddingvert {
	padding-top: 0;
	padding-bottom: 0;
}

.infrashelpdeskslogan {
	font-size: 14px;
	color: white;
}

.infrashelpdeskcolor {
	color: #19052d;
}

.infrashelpdesktitleparam {
	font-size: 16px;
	font-weight: bold;
}

.infrashelpdesksubtitleparam {
	font-size: 14px;
	font-weight: bold;
}

/* Admin tables */
img.infrashelpdeskwidthpictotitle {
	max-width: 48px;
}

.infrashelpdeskDivTitre {
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

.infrashelpdeskHR {
	text-align: center;
	margin: 5px 0px !important;
	padding: 0px !important;
}

.infrashelpdeskFinal {
	line-height: 1px;
	border: none !important;
}

.infrashelpdesknopadding {
	padding: 0px !important;
}

.infrashelpdeskScrollUp {
	position: fixed;
	bottom: 30px;
	right: -100px;
	color: #19052d;
	font-size: xx-large;
}

/* Changelog table */
.infrashelpdeskchangelogbase {
	padding: 2px 5px;
	font-size: 12px;
}

.infrashelpdeskchangefix {
	color: red;
}

.infrashelpdeskchangeadd {
	color: green;
}

.infrashelpdeskchangechg {
	color: blue;
}

.infrashelpdeskchangedefault {
	color: #333;
}

.infrashelpdeskbgorange {
	background-color: #ffcc80;
}

.infrashelpdeskbggreen {
	background-color: #c8e6c9;
}

/* Dark background overrides for changelog */
.infrashelpdesk-dark-bg .infrashelpdeskchangedefault {
	color: #e0e0e0;
}

.infrashelpdesk-dark-bg .infrashelpdeskslogan {
	color: #c8b0e0;
}

.infrashelpdesk-dark-bg .infrashelpdeskcolor {
	color: #c8b0e0;
}

/* Dark background overrides (class set by infrashelpdesk.js) */
.infrashelpdesk-dark-bg .infrashelpdeskneuropolinfras {
	color: #c8b0e0;
}

.infrashelpdesk-dark-bg .infrashelpdeskcolor {
	color: #c8b0e0;
}

.infrashelpdesk-dark-bg .infrashelpdeskblack {
	color: #e0e0e0;
}

/* Floating context button (all pages) */
#infrashelpdesk-ctx-floating {
	position: fixed;
	right: 30px;
	bottom: 145px;
	z-index: 1000;
}

#infrashelpdesk-ctx-toggle {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	border: none;
	background: #ffbb00;
	color: #ffffff;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
	cursor: grab;
	font-size: 18px;
	line-height: 48px;
	padding: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: background .12s ease, color .12s ease;
}

#infrashelpdesk-ctx-floating.--dragging #infrashelpdesk-ctx-toggle {
	cursor: grabbing;
}

#infrashelpdesk-ctx-toggle:hover {
	background: #ca9400;
	color: #ffffff;
}

#infrashelpdesk-ctx-panel {
	display: none;
	position: absolute;
	right: 0;
	bottom: 60px;
	width: 400px;
	max-width: 85vw;
	max-height: calc(100vh - 140px);
	padding: 0 0 10px 0;
	background: #f6f6f8;
	color: #343a40;
	box-shadow: 0 6px 24px rgba(0, 0, 0, 0.28);
	border-radius: 8px;
	overflow-x: hidden;
	overflow-y: auto;
	transform: translateY(8px);
	opacity: 0;
	transition: opacity .15s ease, transform .15s ease;
}

#infrashelpdesk-ctx-floating.--open #infrashelpdesk-ctx-panel {
	display: block;
	transform: translateY(0);
	opacity: 1;
}

#infrashelpdesk-ctx-title {
	position: sticky;
	top: 0;
	z-index: 1;
	padding: 12px 16px;
	margin: 0;
	border-bottom: 1px solid color-mix(in srgb, #5b5b5b 15%, transparent);
	background: #f6f6f8;
	color: #343a40;
	font-size: 1.5rem;
	font-weight: bold;
	letter-spacing: .3px;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-section {
	padding: 10px 16px 4px 16px;
	font-size: 1.125rem;
	font-weight: bold;
	text-transform: uppercase;
	letter-spacing: .5px;
	opacity: 0.7;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-page {
	padding: 4px 16px;
	font-size: 1rem;
	font-family: monospace;
	word-break: break-all;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-chips {
	padding: 0 16px;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-chip {
	display: inline-block;
	margin: 2px 4px 2px 0;
	padding: 2px 8px;
	border-radius: 10px;
	background: color-mix(in srgb, #5b5b5b 14%, transparent);
	font-size: 0.875rem;
	font-family: monospace;
	white-space: nowrap;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-chip.--all {
	background: color-mix(in srgb, #f6f6f8 28%, transparent);
	font-style: italic;
	font-size: 0.875rem;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-func-chips {
	padding: 0 16px;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-func-chip {
	display: inline-block;
	margin: 2px 4px 2px 0;
	padding: 2px 8px;
	border-radius: 10px;
	background: color-mix(in srgb, #4d4d4d 14%, transparent);
	font-size: 0.9rem;
	font-family: monospace;
	white-space: nowrap;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-nomodule {
	padding: 0 16px;
	font-size: 1rem;
	font-style: italic;
	opacity: 0.8;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-module {
	padding: 3px 16px;
	font-size: 0.75rem;
	border-left: 3px solid transparent;
	transition: background .12s ease;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-module:hover {
	background: color-mix(in srgb, #f6f6f8 10%, transparent);
	border-left-color: #343a40;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-module-name {
	font-family: NeuropolRegular, sans-serif;
	display: inline-block;
	min-width: 140px;
	font-weight: bold;
	margin-right: 6px;
	font-size: 0.875rem;
}

#infrashelpdesk-ctx-panel a.infrashelpdesk-ctx-module-name {
	color: #343a40;
	text-decoration: none;
	cursor: pointer;
}

#infrashelpdesk-ctx-panel a.infrashelpdesk-ctx-module-name:hover {
	color: #5b5b5b;
}

#infrashelpdesk-ctx-panel::-webkit-scrollbar {
	width: 6px;
}

#infrashelpdesk-ctx-panel::-webkit-scrollbar-thumb {
	background: color-mix(in srgb, #f6f6f8 25%, transparent);
	border-radius: 3px;
}

#infrashelpdesk-ctx-panel::-webkit-scrollbar-thumb:hover {
	background: color-mix(in srgb, #f6f6f8 40%, transparent);
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-wikilink {
	display: block;
	padding: 4px 16px;
	font-size: 1rem;
	font-family: monospace;
	color: #343a40;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	text-decoration: underline;
	cursor: pointer;
	text-transform: capitalize;
}

#infrashelpdesk-ctx-panel .infrashelpdesk-ctx-wikilink:hover {
	background: color-mix(in srgb, #f6f6f8 10%, transparent);
	color: #5b5b5b;
}

/* Wiki popup dialog (jQuery UI) */
.infrashelpdesk-wiki-dialog {
	position: relative;
	padding: 0 !important;
	overflow: hidden !important;
}

.infrashelpdesk-wiki-dialog iframe {
	width: 100%;
	height: 100%;
	border: 0;
}

.infrashelpdesk-wiki-loader {
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 2.25rem;
	color: #5b5b5b;
	pointer-events: none;
}

.infrashelpdesk-wiki-dialog-wrap {
	z-index: 1500 !important;
}
