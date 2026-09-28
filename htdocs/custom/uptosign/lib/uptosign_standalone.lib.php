<?php
/* Copyright (C) 2026 Eric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/uptosign_standalone.lib.php
 *	\ingroup	uptosign
 *	\brief		Chrome-less rendering of the signature position wizard.
 *
 * The wizard is rendered outside of llxHeader() on purpose. top_htmlhead() is the
 * only place where Dolibarr runs the 'addHtmlHeader' hook, prints MAIN_HTML_HEADER
 * and loads the active theme plus every module_parts CSS and JS. Any of those can
 * move the PDF canvas or the drag targets a few pixels away, which is enough to
 * drop a signature at the wrong place. Not calling it makes such interference
 * structurally impossible: the page only ever loads what this file prints.
 */

/**
 * Tell whether the current request asks for the chrome-less wizard.
 *
 * @return	bool	True when standalone=1 was passed (GET or POST)
 */
function uptosign_standalone_active()
{
	return (GETPOSTINT('standalone') == 1);
}

/**
 * Print the opening HTML of a chrome-less page.
 *
 * Mirrors what top_htmlhead() does for $arrayofjs / $arrayofcss (dol_buildpath on
 * relative paths) but loads nothing else apart from jQuery, which the wizard needs.
 *
 * @param	string	$title			Page title, already translated
 * @param	array	$arrayofjs		Module JS files, relative paths starting with /
 * @param	array	$arrayofcss		Module CSS files, relative paths starting with /
 * @param	string	$subtitle		Optional second line in the top bar (document ref...)
 * @return	void
 */
function uptosign_standalone_header($title, $arrayofjs = array(), $arrayofcss = array(), $subtitle = '')
{
	global $langs;

	if (!headers_sent()) {
		header('Content-Type: text/html; charset=UTF-8');
	}

	$nonce = function_exists('getNonce') ? ' nonce="' . getNonce() . '"' : '';
	$lang = !empty($langs->defaultlang) ? substr($langs->defaultlang, 0, 2) : 'en';

	print '<!DOCTYPE html>' . "\n";
	print '<html lang="' . dol_escape_htmltag($lang) . '">' . "\n";
	print '<head>' . "\n";
	print '<meta charset="UTF-8">' . "\n";
	print '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
	print '<meta name="robots" content="noindex,nofollow">' . "\n";
	print '<title>' . dol_escape_htmltag($title) . '</title>' . "\n";

	// Our own stylesheet first, so the wizard sheet can still override it.
	$standaloneCss = '/uptosign/css/uptosign-standalone.css';
	$standaloneFile = dol_buildpath('/uptosign/css/uptosign-standalone.css', 0);
	if (dol_is_file($standaloneFile)) {
		$standaloneCss .= '?ver=' . filemtime($standaloneFile);
	}
	print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($standaloneCss, 1) . '">' . "\n";
	if (is_array($arrayofcss)) {
		foreach ($arrayofcss as $cssfile) {
			print '<link rel="stylesheet" type="text/css" href="' . dol_buildpath($cssfile, 1) . '">' . "\n";
		}
	}

	// jQuery is the only Dolibarr asset the wizard depends on.
	print '<script' . $nonce . ' src="' . DOL_URL_ROOT . '/includes/jquery/js/jquery.min.js?version=' . urlencode(DOL_VERSION) . '"></script>' . "\n";
	if (is_array($arrayofjs)) {
		foreach ($arrayofjs as $jsfile) {
			print '<script' . $nonce . ' src="' . dol_buildpath($jsfile, 1) . '"></script>' . "\n";
		}
	}

	print '</head>' . "\n";
	print '<body class="uts-standalone">' . "\n";
	print '<div class="uts-topbar">' . "\n";
	print '	<div class="uts-topbar-titles">' . "\n";
	print '		<span class="uts-topbar-title">' . dol_escape_htmltag($title) . '</span>' . "\n";
	if ($subtitle !== '') {
		print '		<span class="uts-topbar-subtitle">' . dol_escape_htmltag($subtitle) . '</span>' . "\n";
	}
	print '	</div>' . "\n";
	print '	<button type="button" id="utsStandaloneClose" class="uts-close" title="' . dol_escape_htmltag($langs->trans('UptoSignWizardClose')) . '">&times;</button>' . "\n";
	print '</div>' . "\n";

	uptosign_standalone_messages();

	print '<div class="uts-main">' . "\n";
}

/**
 * Print the pending Dolibarr event messages with our own markup.
 *
 * llxFooter() usually flushes $_SESSION['dol_events'] through dol_htmloutput_events().
 * Standalone pages never call it, so messages must be consumed here or they would
 * pop up on whatever page the user opens next.
 *
 * @return	void
 */
function uptosign_standalone_messages()
{
	$styles = array('errors' => 'error', 'warnings' => 'warning', 'mesgs' => 'ok');
	$out = '';

	foreach ($styles as $sessionKey => $cssSuffix) {
		if (empty($_SESSION['dol_events'][$sessionKey]) || !is_array($_SESSION['dol_events'][$sessionKey])) {
			continue;
		}
		foreach ($_SESSION['dol_events'][$sessionKey] as $message) {
			$out .= '	<div class="uts-message uts-message-' . $cssSuffix . '">' . dol_escape_htmltag($message) . '</div>' . "\n";
		}
		unset($_SESSION['dol_events'][$sessionKey]);
	}

	if ($out !== '') {
		print '<div class="uts-messages">' . "\n" . $out . '</div>' . "\n";
	}
}

/**
 * Print the closing HTML of a chrome-less page.
 *
 * @return	void
 */
function uptosign_standalone_footer()
{
	global $db;

	$nonce = function_exists('getNonce') ? ' nonce="' . getNonce() . '"' : '';

	print '</div> <!-- end of uts-main -->' . "\n";
	print '<script' . $nonce . '>' . "\n";
	print 'document.getElementById("utsStandaloneClose").addEventListener("click", function () {' . "\n";
	print '	if (window.parent && window.parent !== window) {' . "\n";
	print '		window.parent.postMessage({uptosign: "close"}, window.location.origin);' . "\n";
	print '	} else {' . "\n";
	print '		window.history.back();' . "\n";
	print '	}' . "\n";
	print '});' . "\n";
	print '</script>' . "\n";
	print '</body>' . "\n";
	print '</html>' . "\n";

	if (is_object($db)) {
		$db->close();
	}
}

/**
 * Leave the wizard and send the browser to $url.
 *
 * A header('Location:') would only reload the iframe, leaving the user stuck in a
 * modal showing the procedure card. Inside a frame we hand the URL over to the host
 * page, which closes the modal and navigates; outside one we fall back to a plain
 * redirect. Any buffered output is dropped first, exactly like the POST-Redirect-GET
 * path it replaces.
 *
 * @param	string	$url	Absolute or root-relative URL to open
 * @return	void			Never returns, exits
 */
function uptosign_standalone_leave($url)
{
	global $db;

	while (ob_get_level() > 0) {
		ob_end_clean();
	}

	if (!headers_sent()) {
		header('Content-Type: text/html; charset=UTF-8');
	}

	$nonce = function_exists('getNonce') ? ' nonce="' . getNonce() . '"' : '';

	print '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>...</title>' . "\n";
	print '<noscript><meta http-equiv="refresh" content="0;url=' . dol_escape_htmltag($url) . '"></noscript>' . "\n";
	print '</head><body>' . "\n";
	print '<noscript><a href="' . dol_escape_htmltag($url) . '">' . dol_escape_htmltag($url) . '</a></noscript>' . "\n";
	print '<script' . $nonce . '>' . "\n";
	print 'var utsTarget = ' . json_encode($url) . ';' . "\n";
	print 'if (window.parent && window.parent !== window) {' . "\n";
	print '	window.parent.postMessage({uptosign: "done", url: utsTarget}, window.location.origin);' . "\n";
	print '} else {' . "\n";
	print '	window.location.href = utsTarget;' . "\n";
	print '}' . "\n";
	print '</script>' . "\n";
	print '</body></html>' . "\n";

	if (is_object($db)) {
		$db->close();
	}
	exit;
}

/**
 * Build a link that takes the user out of the wizard.
 *
 * Client side counterpart of uptosign_standalone_leave(). Inside the modal a
 * window.location.href (or a plain link) only navigates the iframe, which strands the
 * user on a full Dolibarr page with neither the wizard top bar nor its close button:
 * from there every further click stays trapped in the frame. target="_top" navigates
 * the host window instead, so the overlay goes away with the page it belongs to, and
 * it keeps working without JavaScript. Outside the modal the link is an ordinary one.
 *
 * @param	string	$url	Destination, absolute or root-relative
 * @param	string	$label	Link label, not escaped yet
 * @param	string	$title	Optional title attribute, not escaped yet
 * @return	string			HTML of the link
 */
function uptosign_standalone_leave_link($url, $label, $title = '')
{
	$out = '<a class="butAction"';
	if (uptosign_standalone_active()) {
		$out .= ' target="_top"';
	}
	$out .= ' href="' . dol_escape_htmltag($url) . '"';
	if ($title !== '') {
		$out .= ' title="' . dol_escape_htmltag($title) . '"';
	}

	return $out . '>' . dol_escape_htmltag($label) . '</a>';
}

/**
 * Print the dead end screen: the requested object could not be loaded.
 *
 * Reached with a stale bookmark, a deleted document, or an id belonging to another
 * entity. Before this, neither the launcher nor the wizard was rendered and the user
 * got a blank page: nothing explaining why, and inside the modal not even a tab bar
 * to leave with.
 *
 * @return	void
 */
function uptosign_standalone_print_object_not_found()
{
	global $langs;

	print '<div class="center" id="uptosignObjectNotFound">' . "\n";
	print '	<p class="error">' . dol_escape_htmltag($langs->trans('UptoSignObjectNotFound')) . '</p>' . "\n";
	print '	' . uptosign_standalone_leave_link(
		dol_buildpath('/uptosign/uptosign_list.php', 1),
		$langs->trans('UptoSignBackToProcedures')
	) . "\n";
	print '</div>' . "\n";
}

/**
 * Print the launcher shown in the Dolibarr tab: a button that opens the wizard in
 * an isolated modal, plus the script that opens it right away.
 *
 * The heavy part of the wizard (the whole PDF base64 payload) is only built by the
 * standalone request, so the tab itself stays cheap. Without JavaScript the button
 * is a plain link to the same URL, which then renders full page.
 *
 * @param	string			$wizardUrl	URL of the standalone wizard (already contains standalone=1)
 * @param	string			$label		Button label
 * @param	CommonObject	$object		Object being signed, to link back to its card
 * @param	bool			$autoOpen	Open the modal as soon as the tab is displayed
 * @return	void
 */
function uptosign_standalone_print_launcher($wizardUrl, $label, $object = null, $autoOpen = true)
{
	global $langs;

	$nonce = function_exists('getNonce') ? ' nonce="' . getNonce() . '"' : '';

	print '<div class="center" id="uptosignWizardLauncher">' . "\n";
	// Link back to the card. Some object types have no prepare_head() to give this tab
	// the card tab bar, so this is the only way back that is always there.
	if (is_object($object) && method_exists($object, 'getNomUrl')) {
		print '	<p class="uptosignWizardLauncherRef">' . $object->getNomUrl(1) . '</p>' . "\n";
	}
	print '	<p>' . $langs->trans('UptoSignWizardLauncherHelp') . '</p>' . "\n";
	print '	<a class="butAction" id="uptosignWizardLauncherBtn" href="' . dol_escape_htmltag($wizardUrl) . '">' . dol_escape_htmltag($label) . '</a>' . "\n";
	print '</div>' . "\n";

	print '<script' . $nonce . '>' . "\n";
	print 'document.getElementById("uptosignWizardLauncherBtn").addEventListener("click", function (e) {' . "\n";
	print '	if (typeof uptosignOpenWizard === "function") {' . "\n";
	print '		e.preventDefault();' . "\n";
	print '		uptosignOpenWizard(this.getAttribute("href"));' . "\n";
	print '	}' . "\n";
	print '});' . "\n";
	if ($autoOpen) {
		print 'if (typeof uptosignOpenWizard === "function") {' . "\n";
		print '	uptosignOpenWizard(' . json_encode($wizardUrl) . ');' . "\n";
		print '}' . "\n";
	}
	print '</script>' . "\n";
}
