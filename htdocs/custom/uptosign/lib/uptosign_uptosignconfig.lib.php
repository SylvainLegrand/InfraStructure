<?php
/* Copyright 2022-2023 ---Eric Seigne <eric.seigne@cap-rel.fr>---
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/uptosign_uptosignconfig.lib.php
 * \ingroup uptosign
 * \brief   Library files with common functions for UptoSignConfig
 */
dol_include_once('/uptosign/lib/uptosign.lib.php');

/**
 * Prepare array of tabs for UptoSignConfig
 *
 * @param	UptoSignConfig	$object		UptoSignConfig
 * @return 	array					Array of tabs
 */
function uptosignconfigPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("uptosign@uptosign");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/uptosign/uptosignconfig_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/uptosign/uptosignconfig_note.php', 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->uptosign->dir_output . "/uptosignconfig/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', ['(\.meta|_preview.*\.png)$']));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/uptosign/uptosignconfig_document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/uptosign/uptosignconfig_agenda.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@uptosign:/uptosign/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@uptosign:/uptosign/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'uptosignconfig@uptosign');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'uptosignconfig@uptosign', 'remove');

	return $head;
}

/**
 * get specimen file for a type of object then return as base64
 *
 * @param   string $objectType         [$objectType description]
 * @param   string $modele             [$modele description]
 * @param   bool   $defaultIfNotFound  [$defaultIfNotFound description]
 *
 * @return  string|int                 Base64 of the specimen PDF, -1 when no builder was found
 */
function uptoSignGetSpecimen($objectType, $modele, $defaultIfNotFound = false)
{
	// print "<p>Demande du specimen de $modele, objectType = $objectType</p>";
	dol_syslog("uptosign: uptoSignGetSpecimen ask for objectType=$objectType, modele=$modele", LOG_DEBUG);
	global $conf, $db, $langs;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	dol_syslog("uptosign: uptoSignGetSpecimen dirmodels=" . json_encode($dirmodels), LOG_DEBUG);

	$b64 = null;

	$hallobj = uptosign_handle_all_type_of_objects($objectType);
	$objectSync = $hallobj['object'];
	$modulepart = $hallobj['modulepart'];
	$pdfpath = $hallobj['pdfpath'];

	//too easy with dolibarr
	if ($modulepart == "propal") {
		dol_syslog("uptosign: uptoSignGetSpecimen race condition for propal ...", LOG_DEBUG);
		$modulepart = "propale";
	}


	//what about custom models from other modules ?
	$filefound = 0;
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir . "core/modules/" . $modulepart . "/doc/pdf_" . $modele . ".modules.php");
		dol_syslog("uptosign: uptoSignGetSpecimen search in $file for document builder ...");
		if (is_file($file)) {
			$filefound = 1;
			require_once $file;
			$classname = "pdf_" . $modele;
			$module = new $classname($db);
			try {
				if (!is_dir($pdfpath . "/SPECIMEN")) {
					dol_mkdir($pdfpath . "/SPECIMEN");
				}
				$objectSync->initAsSpecimen();
				if ($module->write_file($objectSync, $langs) > 0) {
					//documents/propale/SPECIMEN.pdf
					$filepdf = $pdfpath . '/SPECIMEN.pdf';
					// print "<p>Demande du specimen fichier $filepdf</p>";
					$b64 = base64_encode(file_get_contents($filepdf));
					// dol_syslog("uptosign: uptoSignGetSpecimen $file found as specimen builder :-) ...");
					break;
				}
			} catch (Error $e) {
				dol_syslog("uptosign: uptoSignGetSpecimen can't call initAsSpecimen for $file");
			}
		} else {
			// print "<p>Can't find $file as specimen builder ...</p>";
			dol_syslog("uptosign: uptoSignGetSpecimen can't find $file as specimen builder...");
		}
	}

	if ($filefound == 0) {
		return -1;
	}

	if (empty($b64) && $defaultIfNotFound) {
		dol_syslog("uptosign: uptoSignGetSpecimen b64 is empty, use no_template.pdf ...");
		$filepdf = DOL_DOCUMENT_ROOT . '/custom/uptosign/admin/no_template.pdf';
		$b64 = base64_encode(file_get_contents($filepdf));
	}
	return $b64;
}

/**
 * disable all impossible templates (for example template available on dolibarr 11 but not after 14)
 *
 */
function uptosignDisableAllImpossibleTemplates()
{
	global $db, $user;
	$utscs = new UptoSignConfig($db);
	$res = $utscs->fetchAll('', '', 0, 0, array(), '', 1); //special case we would check EVERY model
	foreach ($res as $utsc) {
		$t = explode(':', $utsc->model_pdf);
		$b64 = uptoSignGetSpecimen($t[0], $t[1]);
		if ($b64 == -1) {
			$utsc->status = UptoSignConfig::STATUS_DISABLED;
		} else {
			$utsc->status = UptoSignConfig::STATUS_VALIDATED;
		}
		$utsc->update($user, 1);
	}
}
