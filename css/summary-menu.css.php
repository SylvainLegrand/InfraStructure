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
	* 	\file		../infrastructure/css/infrastructure.css.php
	* 	\ingroup	InfraS
	* 	\brief		CSS file for the Infrastructure module
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
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	// Define css type
	header('Content-type: text/css');
	// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
	// You can use CTRL+F5 to refresh your browser cache.
	if (empty($dolibarr_nocache)) {
		header('Cache-Control: max-age=3600, public, must-revalidate');
	} else {
		header('Cache-Control: no-cache');
	}

	// Theme-specific CSS variables (oblyon uses bgnavtop*, others use colorbackhmenu1 / colortextbackhmenu)
	$isOblyon			= isModEnabled('oblyon') && isset($conf->theme) && $conf->theme == 'oblyon';
	if ($isOblyon) {
		$cssBg			= 'var(--bgnavtop)';
		$cssBgHover		= 'var(--bgnavtop_hover)';
		$cssTxt			= 'var(--bgnavtop_txt)';
		$cssTxtHover	= 'var(--bgnavtop_txt_hover)';
		$cssTxtActive	= 'var(--bgnavtop_txt_active)';
	} else {
		$cssBg			= 'var(--colorbackhmenu1, #2b3850)';
		$cssBgHover		= 'var(--colorbackhmenu1, #2b3850)';
		$cssTxt			= 'var(--colortextbackhmenu, #fff)';
		$cssTxtHover	= 'var(--colortextbackhmenu, #fff)';
		$cssTxtActive	= 'var(--colortextbackhmenu, #fff)';
	}
	dol_syslog('ici summary-menu css.php : $isOblyon='.$isOblyon.' / $cssBg='.$cssBg.' $conf->theme '.$conf->theme, LOG_DEBUG);
?>
/*html {*/
/*	scroll-behavior: smooth;*/
/*}*/
#infrastructure-summary-let-menu-contaner{

	--left-menu-width: 188px;

	position: relative;
	display: block;
	box-sizing: border-box;
	max-width: calc(var(--left-menu-width) + 15px); /* see div.menu_titre */
	position: sticky;
	top: 60px;
	padding: 15px !important;
	max-height: calc(100vh - 200px);
	overflow: hidden;
	overflow-y: auto;
}

#infrastructure-summary-title{
	box-sizing: border-box;
	color: var(--colortextbackhmenu, #fff);
	font-weight: bold;
	font-size: 14px;
	text-transform: capitalize;
	text-align: center;
}

a.infrastructure-summary-link{
	box-sizing: border-box;
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	width: 100%;
	font-size: 12px;
	line-height: 2em;
	color: var(--colortextbackhmenu, #fff);
	text-align: left;
	
	/*color: var(--colortextbackvmenu, #666);*/
}
a.infrastructure-summary-link.--target-in-viewport, a.infrastructure-summary-link.--child-in-viewport{
	font-weight: bold;
	color: var(--colortextbackhmenu, #fff);
	/*color : var(--colortextbackvmenu, #666);*/
}

#infrastructure-summary-floating {
	position: fixed;
	right: 30px;
	bottom: 85px;
	z-index: 1000;
}

#infrastructure-summary-toggle {
	width: 48px;
	height: 48px;
	border-radius: 50%;
	border: none;
	background: <?php echo $cssBg; ?>;
	color: <?php echo $cssTxt; ?>;
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

#infrastructure-summary-floating.--dragging #infrastructure-summary-toggle {
	cursor: grabbing;
}

#infrastructure-summary-toggle:hover {
	background: <?php echo $cssBgHover; ?>;
	color: <?php echo $cssTxtHover; ?>;
}

#infrastructure-summary-floating #infrastructure-summary-let-menu-contaner {
	display: none;
	position: absolute;
	right: 0;
	bottom: 60px;
	top: auto;
	width: 300px;
	max-width: 85vw;
	max-height: calc(100vh - 140px);
	padding: 0 0 8px 0;
	background: <?php echo $cssBg; ?>;
	box-shadow: 0 6px 24px rgba(0, 0, 0, 0.28);
	border-radius: 8px;
	overflow-x: hidden;
	overflow-y: auto;
	transform: translateY(8px);
	opacity: 0;
	transition: opacity .15s ease, transform .15s ease;
}

#infrastructure-summary-floating.--open #infrastructure-summary-let-menu-contaner {
	display: block;
	transform: translateY(0);
	opacity: 1;
}

#infrastructure-summary-floating #infrastructure-summary-title {
	position: sticky;
	top: 0;
	z-index: 1;
	padding: 12px 16px;
	margin: 0 0 4px 0;
	border-bottom: 1px solid color-mix(in srgb, <?php echo $cssTxt; ?> 15%, transparent);
	background: <?php echo $cssBg; ?>;
	color: <?php echo $cssTxt; ?>;
	font-size: 13px;
	letter-spacing: .3px;
}

#infrastructure-summary-floating .infrastructure-summary-link {
	padding: 4px 16px;
	line-height: 1.8em;
	color: <?php echo $cssTxt; ?>;
	border-left: 3px solid transparent;
	transition: background .12s ease, color .12s ease, border-color .12s ease;
}

#infrastructure-summary-floating .infrastructure-summary-link:hover {
	background: <?php echo $isOblyon ? $cssBgHover : 'color-mix(in srgb, '.$cssTxt.' 10%, transparent)'; ?>;
	color: <?php echo $cssTxtHover; ?>;
	text-decoration: none;
}

#infrastructure-summary-floating .infrastructure-summary-link.--target-in-viewport,
#infrastructure-summary-floating .infrastructure-summary-link.--child-in-viewport {
	background: <?php echo $isOblyon ? $cssBgHover : 'color-mix(in srgb, '.$cssTxt.' 14%, transparent)'; ?>;
	color: <?php echo $cssTxtActive; ?>;
	border-left-color: <?php echo $cssTxtActive; ?>;
}

#infrastructure-summary-floating #infrastructure-summary-let-menu-contaner::-webkit-scrollbar {
	width: 6px;
}

#infrastructure-summary-floating #infrastructure-summary-let-menu-contaner::-webkit-scrollbar-thumb {
	background: color-mix(in srgb, <?php echo $cssTxt; ?> 25%, transparent);
	border-radius: 3px;
}

#infrastructure-summary-floating #infrastructure-summary-let-menu-contaner::-webkit-scrollbar-thumb:hover {
	background: color-mix(in srgb, <?php echo $cssTxt; ?> 40%, transparent);
}