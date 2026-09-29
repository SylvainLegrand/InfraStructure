<?php
	/************************************************
	* Copyright (C) 2025-2026	Sylvain Legrand - <contact@infras.fr>		InfraS - <https://www.infras.fr>
	* Copyright (C) 2025-2026	Lucky Ranasolonirina - <contact@infras.fr>	InfraS - <https://www.infras.fr>
	*
	* This program is free software: you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation, either version 3 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program.	If not, see <http://www.gnu.org/licenses/>.
	*
	************************************************/

	/************************************************
	* The following vars must be defined:
	* $form
	************************************************/

	/************************************************
	*	\file		./infrasworkflow/core/tpl/adminextrafields/actions_v24.inc.php
	*	\ingroup	InfraS
	*	\brief		Code for actions on InfraS extrafields admin pages (Dolibarr v24)
	************************************************/

	$mesg		= '';
	$mesgs		= array();
	$attrname	= GETPOST('attrname', 'aZ09');
	$label		= GETPOST('label', 'alphanohtml');
	$type		= GETPOST('type', 'alphanohtml');
	$pos		= GETPOSTINT('pos');
	$extrasize	= GETPOST('size', 'alphanohtml');
	if ($type == 'double' && strpos($extrasize, ',') === false) {
		$extrasize	= '24,8';
	} elseif ($type == 'date') {
		$extrasize	= '';
	} elseif ($type == 'datetime') {
		$extrasize	= '';
	} elseif ($type == 'select') {
		$extrasize	= '';
	}
	$elementtype			= GETPOST('elementtype', 'aZ09');
	$unique					= GETPOST('unique', 'alpha') ? 1 : 0;
	$required				= GETPOST('required', 'alpha') ? 1 : 0;
	$default_value			= GETPOST('default_value', 'alphanohtml');
	$param					= GETPOST('param', 'alphanohtml');
	$alwayseditable			= GETPOST('alwayseditable', 'alpha') ? 1 : 0;
	$perms					= GETPOST('perms', 'alphanohtml') ? GETPOST('perms', 'alphanohtml') : '';
	$list					= GETPOST('list', 'alphanohtml');
	$help					= GETPOST('help', 'alphanohtml');
	$computed_value			= GETPOST('computed_value', 'alphanohtml');
	$entitycurrentorall		= GETPOST('entitycurrentorall', 'alpha') ? 0 : '';
	$langfile				= GETPOST('langfile', 'alphanohtml');
	$enabled				= GETPOST('enabled', 'alphanohtml');
	$totalizable			= GETPOST('totalizable', 'alpha') ? 1 : 0;
	$printable				= GETPOST('printable', 'alphanohtml');
	$css					= GETPOST('css', 'alphanohtml');
	$cssview				= GETPOST('cssview', 'alphanohtml');
	$csslist				= GETPOST('csslist', 'alphanohtml');
	$ai_prompt				= GETPOST('ai_prompt', 'alphanohtml');
	$emptyonclone			= GETPOST('emptyonclone', 'alpha') ? 1 : 0;
	$showintooltip			= GETPOST('showintooltip', 'alpha') ? 1 : 0;
	$personal_data			= GETPOSTINT('personal_data');
	$height					= 144;	// Height of the form
	$confirm				= GETPOST('confirm', 'alpha');

	// DEBUG: Log all GETPOST values for extrafields actions
	dol_syslog('InfraSWorkflow::actions_extrafields - action='.$action.' attrname='.$attrname.' elementtype='.$elementtype.' type='.$type.' label='.$label.' param='.substr($param, 0, 100).'...', LOG_DEBUG);
	// for add and update mode
	$formquestion1	= array(array('type' => 'hidden', 'name' => 'attrname',				'value' => $attrname),
							array('type' => 'hidden', 'name' => 'label',				'value' => $label),
							array('type' => 'hidden', 'name' => 'type',					'value' => $type),
							array('type' => 'hidden', 'name' => 'pos',					'value' => $pos),
							array('type' => 'hidden', 'name' => 'size',					'value' => $extrasize),
							array('type' => 'hidden', 'name' => 'elementtype',			'value' => $elementtype),
							array('type' => 'hidden', 'name' => 'unique',				'value' => $unique),
							array('type' => 'hidden', 'name' => 'required',				'value' => $required),
							array('type' => 'hidden', 'name' => 'default_value',		'value' => $default_value),
							array('type' => 'hidden', 'name' => 'param',				'value' => $param),
							array('type' => 'hidden', 'name' => 'alwayseditable',		'value' => $alwayseditable),
							array('type' => 'hidden', 'name' => 'perms',				'value' => $perms),
							array('type' => 'hidden', 'name' => 'list',					'value' => $list),
							array('type' => 'hidden', 'name' => 'help',					'value' => $help),
							array('type' => 'hidden', 'name' => 'computed_value',		'value' => $computed_value),
							array('type' => 'hidden', 'name' => 'entitycurrentorall',	'value' => $entitycurrentorall),
							array('type' => 'hidden', 'name' => 'langfile',				'value' => $langfile),
							array('type' => 'hidden', 'name' => 'enabled',				'value' => $enabled),
							array('type' => 'hidden', 'name' => 'totalizable',			'value' => $totalizable),
							array('type' => 'hidden', 'name' => 'printable',			'value' => $printable),
							array('type' => 'hidden', 'name' => 'css',					'value' => $css),
							array('type' => 'hidden', 'name' => 'cssview',				'value' => $cssview),
							array('type' => 'hidden', 'name' => 'csslist',				'value' => $csslist),
							array('type' => 'hidden', 'name' => 'showintooltip',		'value' => $showintooltip),
							array('type' => 'hidden', 'name' => 'personal_data',		'value' => $personal_data)
							);
	// for clone and delete mode
	$formquestion2	= array(array('type' => 'hidden', 'name' => 'attrname',		'value' => $attrname),
							array('type' => 'hidden', 'name' => 'elementtype',	'value' => $elementtype),
							);
	if ($action == 'add') {
		dol_syslog('InfraSWorkflow::actions_extrafields - Entering ADD action - attrname='.$attrname.' type='.$type.' elementtype='.$elementtype, LOG_DEBUG);
		$error	= infrasworkflow_checkValues($attrname, $type, $param, $extrasize, $mesgs, $action);
		dol_syslog('InfraSWorkflow::actions_extrafields - After checkValues - error='.$error.' action='.$action.' mesg='.json_encode($mesgs), LOG_DEBUG);
		if (!$error && GETPOST('button') != $langs->trans('Cancel')) {
			$linked_objects	= infrasworkflow_getPropagationChains($elementtype, true);	// Récupérer la liste des propagation possibles
			$checkboxes		= infrasworkflow_buildCheckBoxes($elementtype, $attrname,$linked_objects, 'InfraSWorkflowExf_', false, 'add');	// Générer la liste des cases à cocher
			infrasworkflow_appendCheckboxesToForm($formquestion1, $height, $checkboxes, $langs->trans('InfraSWorkflowCreateOnDerivedObjects'), 'add');	// Affichage uniquement s’il y a des checkbox valides
			print '<br/>';
			print load_fiche_titre($langs->trans('NewAttribute'));
			$confirm		= 1;	// We are in confirm mode
			include dol_buildpath('/infrasworkflow/core/tpl/admin_extrafields_add.tpl.php');
			$formconfirm	= $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSWorkflowConfirmAddAttribute', $attrname).' '.$langs->trans('InfraSWorkflowExf_'.$elementtype), $langs->trans('InfraSWorkflowConfirmAddAttributeQuestion'), 'confirm_add', $formquestion1, '', 1, $height, 600, 0, 'yes', 'no');
			print $formconfirm;
		} elseif ($error) {
			setEventMessages($langs->trans('Error'), $mesgs, 'errors');
		}
	} elseif ($action == 'confirm_add' && $confirm == 'yes') {
		dol_syslog('InfraSWorkflow::actions_extrafields - Entering CONFIRM_ADD action - attrname='.$attrname.' type='.$type.' elementtype='.$elementtype, LOG_DEBUG);
		// attrname must be alphabetical and lower case only
		$regex_match = preg_match('/^[a-z0-9_]+$/', $attrname);
		dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD checks: attrname_not_empty='.(!empty($attrname)?'1':'0').' regex_match='.$regex_match.' is_not_numeric='.(!is_numeric($attrname)?'1':'0'), LOG_DEBUG);
		if (!empty($attrname) && $regex_match && !is_numeric($attrname)) {
			$params			= infrasworkflow_getArrayParams($type, $param);	// Construct array for parameter (value of select list)
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD params='.json_encode($params), LOG_DEBUG);
			$linked_objects	= infrasworkflow_getPropagationChains($elementtype, true);	// Récupérer la liste des propagation possibles

			// S'assurer que l'elementtype courant est toujours inclus // Lucky add
			if (empty($linked_objects) || !in_array($elementtype, $linked_objects)) {
				$linked_objects[]	= $elementtype;
			}
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD linked_objects='.json_encode($linked_objects), LOG_DEBUG);
			foreach ($linked_objects as $elementtype_target) {
				if ($elementtype == $elementtype_target || GETPOST('check_'.$elementtype_target, 'alpha')) {
					$res	= $extrafields->addExtraField(
								$attrname,
								$label,
								$type,
								$pos,
								$extrasize,
								$elementtype_target,
								$unique,
								$required,
								$default_value,
								$params,
								$alwayseditable,
								$perms,
								in_array($type, ['separate', 'point', 'linestrg', 'polygon']) ? 3 : $list,	// Visibility: -1=not visible by default in list, 1=visible, 0=hidden
								$help,
								$computed_value,
								$entitycurrentorall,
								$langfile,
								$enabled,
								$totalizable,
								$printable,
								array('css' => $css, 'cssview' => $cssview, 'csslist' => $csslist),
								$ai_prompt,
								$emptyonclone,
								$showintooltip,
								$personal_data
								);
					dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD addExtraField result res='.$res.' for elementtype_target='.$elementtype_target, LOG_DEBUG);
					if ($res < 0) {
						dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD ERROR: '.$extrafields->error.' errors='.json_encode($extrafields->errors), LOG_ERR);
						$mesg	= $extrafields->error;
						$mesgs	= array_merge($mesgs, $extrafields->errors);
						setEventMessages($mesg, $mesgs, 'errors');
					}
				}
			}
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD completed successfully', LOG_DEBUG);
			setEventMessages($langs->trans('InfraSWorkflowAddSaved'), null, 'mesgs');
			print '<script type = "text/javascript">window.location.href = "'.$_SERVER['PHP_SELF'].'";</script>';
			exit;
		} else {
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_ADD FAILED validation: attrname='.$attrname, LOG_WARNING);
			$langs->load('errors');
			$mesg	= $langs->trans('ErrorFieldCanNotContainSpecialNorUpperCharacters', $langs->transnoentities('AttributeCode'));
			setEventMessages($mesg, null, 'errors');
			$action	= 'create';
		}
	} elseif ($action == 'clone') {
		$linked_objects	= infrasworkflow_getPropagationChains($elementtype, true);	// Récupérer la liste des propagation possibles
		$checkboxes		= infrasworkflow_buildCheckBoxes($elementtype, $attrname,$linked_objects, 'InfraSWorkflowExf_', false, 'clone');	// Générer la liste des cases à cocher
		infrasworkflow_appendCheckboxesToForm($formquestion2, $height, $checkboxes, $langs->trans('InfraSWorkflowCloneOnDerivedObjects'), 'clone');	// Affichage uniquement s’il y a des checkbox valides
		$formconfirm	= $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSWorkflowConfirmCloneAttribute', $attrname), $langs->trans('InfraSWorkflowConfirmCloneAttributeQuestion'), 'confirm_clone', $formquestion2, '', 1, $height, 600, 0, 'yes', 'no');
		print $formconfirm;
	} elseif ($action == 'confirm_clone' && $confirm == 'yes') {
		// Charger l'extrafield existant à cloner
		$sql	= 'SELECT * FROM '.$db->prefix().'extrafields WHERE name = "'.$db->escape($attrname).'" AND elementtype = "'.$elementtype.'"';
		$resql	= $db->query($sql);
		if ($resql && $db->num_rows($resql)) {
			$obj			= $db->fetch_object($resql);
			$linked_objects	= infrasworkflow_getPropagationChains($obj->elementtype, false);	// Récupérer la liste des propagation possibles
			foreach ($linked_objects as $elementtype_target) {
				if (GETPOST('check_'.$elementtype_target, 'alpha')) {
					$res	= $extrafields->addExtraField(
								$obj->name,
								$obj->label,
								$obj->type,
								$obj->pos,
								$obj->size,
								$elementtype_target,
								$obj->fieldunique,
								$obj->fieldrequired,
								$obj->fielddefault,
								$obj->param,
								$obj->alwayseditable,
								$obj->perms,
								$obj->list,
								$obj->help,
								$obj->fieldcomputed,
								$obj->entity,
								$obj->langs,
								$obj->enabled,
								$obj->totalizable,
								$obj->printable,
								array('css' => $obj->css,'cssview' => $obj->cssview,'csslist' => $obj->csslist),
								$ai_prompt,
								$emptyonclone,
								(int) $obj->showintooltip,
								(int) $obj->personal_data
								);
					if ($res < 0) {
						$mesg	= $extrafields->error;
						$mesgs	= array_merge($mesgs, $extrafields->errors);
						setEventMessages($mesg, $mesgs, 'errors');
					}
				}
			}
		}
		setEventMessages($langs->trans('InfraSWorkflowCloneSaved'), null, 'mesgs');
		print '<script type = "text/javascript">window.location.href = "'.$_SERVER['PHP_SELF'].'";</script>';
		exit;
	} elseif ($action == 'update') {
		dol_syslog('InfraSWorkflow::actions_extrafields - Entering UPDATE action - attrname='.$attrname.' type='.$type.' elementtype='.$elementtype, LOG_DEBUG);
		$error	= infrasworkflow_checkValues($attrname, $type, $param, $extrasize, $mesgs, $action);
		dol_syslog('InfraSWorkflow::actions_extrafields - After checkValues for UPDATE - error='.$error.' action='.$action.' mesg='.json_encode($mesgs), LOG_DEBUG);
		if (!$error && GETPOST('button') != $langs->trans('Cancel')) {
			$linked_objects	= infrasworkflow_getPropagationChains($elementtype, true);
			$checkboxes		= infrasworkflow_buildCheckBoxes($elementtype, $attrname,$linked_objects, 'InfraSWorkflowExf_', true, 'update');	// Générer la liste des cases à cocher
			infrasworkflow_appendCheckboxesToForm($formquestion1, $height, $checkboxes, $langs->trans('InfraSWorkflowUpdateOnDerivedObjects'), 'update');	// Affichage uniquement s’il y a des checkbox valides
			print '<br/>';
			print load_fiche_titre($langs->trans('FieldEdition'));
			include dol_buildpath('/infrasworkflow/core/tpl/admin_extrafields_edit.tpl.php');
			$formconfirm	= $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSWorkflowConfirmEditAttribute', $attrname).' '.$langs->trans('InfraSWorkflowExf_'.$elementtype), $langs->trans('InfraSWorkflowConfirmEditAttributeQuestion'), 'confirm_update', $formquestion1, '', 1, $height, 600, 0, 'yes', 'no');
			print $formconfirm;
		} elseif ($error) {
			setEventMessages($mesgs, null, 'errors');
		}
	} elseif ($action == 'confirm_update' && $confirm == 'yes') {
		dol_syslog('InfraSWorkflow::actions_extrafields - Entering CONFIRM_UPDATE action - attrname='.$attrname.' type='.$type.' elementtype='.$elementtype, LOG_DEBUG);
		$regex_match = preg_match('/^\w[a-zA-Z0-9-_]*$/', $attrname);
		dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE checks: attrname_not_empty='.(!empty($attrname)?'1':'0').' regex_match='.$regex_match.' is_not_numeric='.(!is_numeric($attrname)?'1':'0'), LOG_DEBUG);
		if (!empty($attrname) && $regex_match && !is_numeric($attrname)) {
			$params			= infrasworkflow_getArrayParams($type, $param);	// Construct array for parameter (value of select list)
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE params='.json_encode($params), LOG_DEBUG);
			$linked_objects	= infrasworkflow_getPropagationChains($elementtype, true);	// Récupérer la liste des propagation possibles
			// S'assurer que l'elementtype courant est toujours inclus
			if (empty($linked_objects) || !in_array($elementtype, $linked_objects)) {
				$linked_objects[] = $elementtype;
			}
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE linked_objects='.json_encode($linked_objects), LOG_DEBUG);
			foreach ($linked_objects as $elementtype_target) {
				if ($elementtype == $elementtype_target || GETPOST('check_'.$elementtype_target, 'alpha')) {
					$res	= $extrafields->update(
								$attrname,
								$label,
								$type,
								$extrasize,
								$elementtype_target,
								$unique,
								$required,
								$pos,
								$params,
								$alwayseditable,
								$perms,
								in_array($type, ['separate', 'point', 'linestrg', 'polygon']) ? 3 : $list,	// Visibility: -1=not visible by default in list, 1=visible, 0=hidden
								$help,
								$default_value,
								$computed_value,
								$entitycurrentorall,
								$langfile,
								$enabled,
								$totalizable,
								$printable,
								array('css' => $css, 'cssview' => $cssview, 'csslist' => $csslist),
								$ai_prompt,
								$emptyonclone,
								$showintooltip,
								$personal_data
								);
					dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE update result res='.$res.' for elementtype_target='.$elementtype_target, LOG_DEBUG);
					if ($res < 0) {
						dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE ERROR: '.$extrafields->error.' errors='.json_encode($extrafields->errors), LOG_ERR);
						$mesg	= $extrafields->error;
						$mesgs	= $extrafields->errors;
						setEventMessages($mesg, $mesgs, 'errors');
					}
				}
			}
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE completed successfully', LOG_DEBUG);
			setEventMessages($langs->trans('InfraSWorkflowUpdateSaved'), null, 'mesgs');
			print '<script type = "text/javascript">window.location.href = "'.$_SERVER['PHP_SELF'].'";</script>';
			exit;
		} else {
			dol_syslog('InfraSWorkflow::actions_extrafields - CONFIRM_UPDATE FAILED validation: attrname='.$attrname, LOG_WARNING);
			$langs->load('errors');
			$mesg	= $langs->trans('ErrorFieldCanNotContainSpecialCharacters', $langs->transnoentities('AttributeCode'));
			setEventMessages($mesg, null, 'errors');
		}
	} elseif ($action == 'delete') {
		$linked_objects	= infrasworkflow_getPropagationChains($elementtype, true);	// Récupérer la liste des propagation possibles
		$checkboxes		= infrasworkflow_buildCheckBoxes($elementtype, $attrname,$linked_objects, 'InfraSWorkflowExf_', true, 'delete');	// Générer la liste des cases à cocher
		// Générer la liste des cases à cocher
		infrasworkflow_appendCheckboxesToForm($formquestion2, $height, $checkboxes, $langs->trans('InfraSWorkflowDeleteOnDerivedObjects'), 'delete');	// Affichage uniquement s’il y a des checkbox valides
		$formconfirm	= $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans('InfraSWorkflowConfirmDeleteAttribute', $attrname).' '.$langs->trans('InfraSWorkflowExf_'.$elementtype), $langs->trans('InfraSWorkflowConfirmDeleteAttributeQuestion'), 'confirm_delete', $formquestion2, '', 1, $height, 600, 0, 'yes', 'no');
		print $formconfirm;
	} elseif ($action == 'confirm_delete' && $confirm == 'yes') {
		if (!empty($attrname) && preg_match('/^\w[a-zA-Z0-9-_]*$/', $attrname)) {
			$sql1		= 'SELECT * FROM '.$db->prefix().'extrafields WHERE name = "'.$db->escape($attrname).'"';
			$resql1		= $db->query($sql1);
			if ($resql1 && $db->num_rows($resql1) > 0) {
				while ($obj	= $db->fetch_object($resql1)) {
					$targettype	= $obj->elementtype;
					// On traite si c'est l'élément principal ou une checkbox cochée
					if ($targettype === $elementtype || GETPOST('check_'.$targettype, 'alpha')) {
						$isVisible	= ((int) $obj->enabled !== 0);
						if (getDolGlobalInt('INFRASWORKFLOW_EXTRAFIELDS_TRASHMODE', 0)) {
							// Mode TRASH actif → on masque d’abord si visible
							if ($isVisible) {
								$res	= $extrafields->update(
									$attrname,
									$obj->label,
									$obj->type,
									$obj->size,
									$targettype,
									$obj->fieldunique,
									$obj->fieldrequired,
									$obj->pos,
									$obj->param,
									$obj->alwayseditable,
									$obj->perms,
									$obj->list,
									$obj->help,
									$obj->fielddefault,
									$obj->fieldcomputed,
									$obj->entity,
									$obj->langs,
									0,
									$obj->totalizable,
									$obj->printable,
									array('css' => $obj->css,'cssview' => $obj->cssview,'csslist' => $obj->csslist),
									$ai_prompt,
									$emptyonclone,
									(int) $obj->showintooltip,
									(int) $obj->personal_data
								);
								if ($res <= 0) {
									$mesg	= $extrafields->error;
									$mesgs	= $extrafields->errors;
									setEventMessages($mesg, $mesgs, 'errors');
								}
							} else {
								// Déjà masqué → suppression définitive
								$res	= $extrafields->delete($obj->name, $targettype);
								if ($res <= 0) {
									$mesg	= $extrafields->error;
									$mesgs	= $extrafields->errors;
									setEventMessages($mesg, $mesgs, 'errors');
								}
							}
						} else {
							// Mode TRASH désactivé → suppression définitive
							$res	= $extrafields->delete($obj->name, $targettype);
							if ($res <= 0) {
								$mesg	= $extrafields->error;
								$mesgs	= $extrafields->errors;
								setEventMessages($mesg, $mesgs, 'errors');
							}
						}
					}
				}
			}
			setEventMessages($langs->trans('InfraSWorkflowDeleteSaved'), null, 'mesgs');
			print '<script type="text/javascript">window.location.href = "' . $_SERVER['PHP_SELF'] . '";</script>';
			exit;
		} else {
			$langs->load('errors');
			$mesg	= $langs->trans('ErrorFieldCanNotContainSpecialCharacters', $langs->transnoentities('AttributeCode'));
		}
	} elseif ($action == 'encrypt') {
		// Load $extrafields->attributes
		$extrafields->fetch_name_optionals_label($elementtype);
		$attributekey	= GETPOST('attrname', 'aZ09');
		if (!empty($extrafields->attributes[$elementtype]['type'][$attributekey]) && $extrafields->attributes[$elementtype]['type'][$attributekey] == 'password') {
			if (!empty($extrafields->attributes[$elementtype]['param'][$attributekey]['options'])) {
				if (array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$attributekey]['options'])) {
					// We can encrypt data with dolCrypt()
					$arrayofelement	= getElementProperties($elementtype);
					if (!empty($arrayofelement['table_element'])) {
						if ($extrafields->attributes[$elementtype]['entityid'][$attributekey] == $conf->entity || empty($extrafields->attributes[$elementtype]['entityid'][$attributekey])) {
							dol_syslog('Loop on each extafields of table '.$arrayofelement['table_element']);
							$sql	= 'SELECT te.rowid, te.'.$attributekey;
							$sql	.= ' FROM '.$db->prefix($arrayofelement['table_element']).' AS t, '.$db->prefix($arrayofelement['table_element'].'_extrafields').' AS te';
							$sql	.= ' WHERE te.fk_object = t.rowid';
							$sql	.= ' AND te.'.$attributekey.' NOT LIKE "dolcrypt:%"';
							$sql	.= ' AND te.'.$attributekey.' IS NOT NULL';
							$sql	.= ' AND te.'.$attributekey.' <> ""';
							if ($extrafields->attributes[$elementtype]['entityid'][$attributekey] == $conf->entity) {
								$sql	.= ' AND t.entity = '.getEntity($arrayofelement['element'], 0);
							}
							$nbupdatedone	= 0;
							$resql			= $db->query($sql);
							if ($resql) {
								$num_rows	= $db->num_rows($resql);
								for ($i= 0; $i<$num_rows; $i++) {
									$objtmp	= $db->fetch_object($resql);
									$id		= $objtmp->rowid;
									$pass	= $objtmp->$attributekey;
									if ($pass) {
										$newpassword	= dolEncrypt($pass);
										$sqlupdate		= 'UPDATE '.$db->prefix($arrayofelement['table_element'].'_extrafields');
										$sqlupdate		.= ' SET '.$attributekey.' = "'.$db->escape($newpassword).'"';
										$sqlupdate		.= ' WHERE rowid = '.((int) $id);
										$resupdate		= $db->query($sqlupdate);
										if ($resupdate) {
											$nbupdatedone++;
										} else {
											setEventMessages($db->lasterror(), null, 'errors');
											$error++;
											break;
										}
									}
								}
							}

							if ($nbupdatedone > 0) {
								setEventMessages($langs->trans('PasswordFieldEncrypted', $nbupdatedone), null, 'mesgs');
							} else {
								setEventMessages($langs->trans('PasswordFieldEncrypted', $nbupdatedone), null, 'warnings');
							}
						}
					}
				}
			}
		}
	}
