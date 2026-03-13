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
	* 	\file		./infrastechinfos/class/actions_infrastechinfos.class.php
	* 	\ingroup	InfraS
	* 	\brief		Hook to overload class file for the module InfraS
	************************************************/

	// Libraries ************************************
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	dol_include_once('/infrastechinfos/core/lib/infrastechinfosAdmin.lib.php');
	dol_include_once('/infrastechinfos/core/lib/infrastechinfos.lib.php');

	// Description and activation class *************
	class Actionsinfrastechinfos
	{
		private $db;	// @var DoliDB Database handler
		public $results	= array();	// @var array Hook results. Propagated to $hookmanager->resArray for later reuse
		public $resprints;	// @var string String displayed by executeHook() immediately after return
		public $error;	// @var string
		public $errors	= array();	// @var array Errors

		/**
		* Constructor
		*
		* @param	DATABASE	$db		db object
		* @return				void
		**/
		public function __construct($db)
		{
			$this->db	= $db;
		}

		/**
		* When login (../main.inc.php)
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	Hook manager propagated to allow calling another hook
		* @return	int								< 0 on error
		**/
		public function afterLogin($parameters, &$object, &$action, $hookmanager)
		{
			global $langs;

			$langs->load('infrastechinfos@infrastechinfos');

			$currentversion	= array();
			$currentversion	= infrastechinfos_getLocalVersionMinDoli('infrastechinfos');
			if (!getDolGlobalString('INFRASTECHINFOS_DISABLE_CHECK_VERSION_MAX', '') && version_compare(explode('.', DOL_VERSION)[0], explode('.', $currentversion[4])[0], '>')) {
				setEventMessages($langs->trans('InfraSTechInfosWarningMaxVersion', DOL_VERSION, $currentversion[0], $currentversion[4]), null, 'warnings');
			}
			return 0;
		}
		/**
		* Add new action button on document page
		*
		* @param	array()			$parameters		Hook metadatas (context, etc...)
		* @param	CommonObject	&$object		The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
		* @param	string			&$action		Current action (if set). Generally create or edit or null
		* @param	HookManager		$hookmanager	manager propagated to allow calling another hook
		* @return  int								< 0 on error, 0 on success, 1 to replace standard code
		**/
		function addMoreActionsButtons($parameters, $object, $action, $hookmanager)
		{
			global $db, $conf, $langs, $user;

			if (in_array($object->element, array('propal', 'commande', 'shipping', 'supplier_proposal', 'order_supplier'))) {
				$duration_workday	= getDolGlobalInt('MAIN_DURATION_OF_WORKDAY', 28800);	// in seconds
				$duration_workweek	= getDolGlobalInt('INFRASTECHINFOS_DURATION_OF_WORKWEEK', 5);	// in days
				$nblignes			= count($object->lines);
				$total_in_days		= getDolGlobalInt('INFRASTECHINFOS_TOTAL_TIME_IN_DAYS', 0);	// in days
				$only_total_time	= getDolGlobalInt('INFRASTECHINFOS_ONLY_TOTAL_TIME', 0);
				if ($nblignes > 0 && $user->hasRight('infrastechinfos', 'InfraSTechInfosView')) {
					$langs->load('infrastechinfos@infrastechinfos');
					$ligneTech		= '';
					$ligneTime		= '';
					$timedoc		= 0;
					$weightdoc		= 0;
					$voldoc			= 0;
					$surfdoc		= 0;
					$weightdoctxt	= '';
					$voldoctxt		= '';
					$surfdoctxt		= '';
					$timedoctxt		= '';
					$totalTimeDesc	= $langs->trans('InfraSTechInfosLines').' : ';
					for ($i = 0 ; $i < $nblignes ; $i++) {
						if (empty($object->lines[$i]->fk_product))	continue;
						$idprod			= (! empty($object->lines[$i]->fk_product) ? $object->lines[$i]->fk_product : false);
						$type			= $object->lines[$i]->product_type;
						$prodser		= new Product($db);
						$timetxt		= '';
						$timetottxt		= '';
						$dimtxt			= '';
						$weighttxt		= '';
						$voltxt			= '';
						$surftxt		= '';
						$weighttottxt	= '';
						$voltottxt		= '';
						$surftottxt		= '';
						if ($idprod) {
							$ref	= $object->lines[$i]->ref;
							$qty	= $object->lines[$i]->qty ? $object->lines[$i]->qty : 0;
							$prodser->fetch($idprod);
							if ($type == 1) {
								if ($prodser->duration_value) {
									switch ($prodser->duration_unit) {
										default:
										case 'h':
											$mult = 3600;
										break;
										case 'd':
											$mult = $duration_workday;
										break;
										case 'w':
											$mult = $duration_workday * $duration_workweek;
										break;
										case 'm':
											$mult = 0;
										break;
										case 'y':
											$mult = 0;
										break;
									}
											$timetxt	= ($mult > 0 ? '' : '<span class = "infrastechinfoscaution">'.$langs->trans('InfraSTechInfosCaution').'</span> '.$langs->trans('InfraSTechInfosCautionTimeUnit')).infrastechinfos_showDurationAndUnit($prodser->duration_value, $prodser->duration_unit);
									$timetottxt	= infrastechinfos_showDurationAndUnit($prodser->duration_value * $qty, $prodser->duration_unit);
									$timedoc	+= $mult > 0 ? $prodser->duration_value * $qty * $mult : 0;
									if (empty($only_total_time)) {
										$ligneTime	.= '<tr class = "oddeven">
															<td align = "center">'.($i+1).'</td>
															<td align = "left">'.$ref.'</td>
															<td align = "right" colspan = "4">'.$timetxt.'</td>
															<td align = "right">'.$qty.'</td>
															<td align = "right" colspan = "3">'.$timetottxt.'</td>
														</tr>';
									} else {
										$totalTimeDesc	.= (preg_match('/(\d+)$/', $totalTimeDesc, $reg) ? ', ' : '').($i+1);
									}
								}
							}
							if ($type == 0) {
								if ($prodser->length || $prodser->width || $prodser->height) {
									$txtDim	= '';
									$txtDim	= ($prodser->length ? $prodser->length : '-');
									$txtDim	.= ($prodser->length && ($prodser->width || $prodser->height) ? ' x ' : '-');
									$txtDim	.= ($prodser->width ? $prodser->width : '-');
									$txtDim	.= (($prodser->width && $prodser->height) ? ' x ' : '-');
									$txtDim	.= ($prodser->height ? $prodser->height : '-');
									$txtDim	.= ' '.measuring_units_string($prodser->length_units, 'size');
									$dimtxt	.= $txtDim;
								}
								if ($prodser->weight) {
									$weighttxt		= $prodser->weight.' '.measuring_units_string($prodser->weight_units, "weight");
									$weighttottxt	= infrastechinfos_showDimInBestUnit($prodser->weight * $qty, $prodser->weight_units, "weight", $langs);
									$weight_units	= ! empty($prodser->weight_units) ? $prodser->weight_units : 0;
									$trueWeightUnit	= -1;
									if ($weight_units < 50) {
										$trueWeightUnit	= pow(10, $weight_units);	// <50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit
									} elseif ($weight_units == 98) {
										$trueWeightUnit	= 0.0283495;	// conversion 1 Ounce = 0.0283495 Kg
									} elseif ($weight_units == 99) {
										$trueWeightUnit	= 0.45359237;	// conversion 1 Pound = 0.45359237 Kg
									}
									if ($trueWeightUnit > 0) {
										$weightdoc	+= $prodser->weight * $qty * $trueWeightUnit;
									}
								}
								if ($prodser->volume) {
									$voltxt			= $prodser->volume.' '.measuring_units_string($prodser->volume_units, "volume");
									$voltottxt		= infrastechinfos_showDimInBestUnit($prodser->volume * $qty, $prodser->volume_units, "volume", $langs);
									$volume_units	= ! empty($prodser->volume_units) ? $prodser->volume_units : 0;
									$trueVolumeUnit	= -1;
									if ($volume_units < 50) {
										$trueVolumeUnit	= pow(10, $volume_units);	// <50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit
									} elseif ($volume_units == 88) {
										$trueVolumeUnit	= 0.028316846592;	// conversion 1 Foot3 = 0.028316846592 m3
									} elseif ($volume_units == 89) {
										$trueVolumeUnit	= 0.000016387064;	// conversion 1 Inch3 = 0.000016387064 m3
									} elseif ($volume_units == 98) {
										$trueVolumeUnit	= 0.0000284130625;	// conversion 1 Ounce = 0.0000284130625 m3
									} elseif ($volume_units == 99) {
										$trueVolumeUnit	= 0.00454609;	// conversion 1 Gallon = 0.00454609 m3
									}
									if ($trueVolumeUnit > 0) {
										$voldoc	+= $prodser->volume * $qty * $trueVolumeUnit;
									}
								}
								if ($prodser->surface) {
									$surftxt			= $prodser->surface.' '.measuring_units_string($prodser->surface_units, "surface");
									$surftottxt			= infrastechinfos_showDimInBestUnit($prodser->surface * $qty, $prodser->surface_units, "surface", $langs);
									$surface_units		= ! empty($prodser->surface_units) ? $prodser->surface_units : 0;
									$trueSurfaceUnit	= -1;
									if ($surface_units < 50) {
										$trueSurfaceUnit	= pow(10, $surface_units);	// <50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit
									} elseif ($surface_units == 98) {
										$trueSurfaceUnit	= 0.09290304;	// conversion 1 Foot² = 0.09290304 m²
									} elseif ($surface_units == 99) {
										$trueSurfaceUnit	= 0.00064516;	// conversion 1 Inch² = 0.00064516 m²
									}
									if ($trueSurfaceUnit > 0) {
										$surfdoc	+= $prodser->surface * $qty * $trueSurfaceUnit;
									}
								}
								$ligneTech	.= '<tr class = "oddeven">
													<td align = "center">'.($i+1).'</td>
													<td align = "left">'.$ref.'</td>
													<td align = "right">'.$dimtxt.'</td>
													<td align = "right">'.$surftxt.'</td>
													<td align = "right">'.$voltxt.'</td>
													<td align = "right">'.$weighttxt.'</td>
													<td align = "right">'.$qty.'</td>
													<td align = "right">'.$surftottxt.'</td>
													<td align = "right">'.$voltottxt.'</td>
													<td align = "right">'.$weighttottxt.'</td>
												</tr>';
							}
						}
					}
					if ($weightdoc != 0) {
						$weightdoctxt	= infrastechinfos_showDimInBestUnit($weightdoc, 0, 'weight', $langs, 2);
					}
					if ($voldoc != 0) {
						$voldoctxt	= infrastechinfos_showDimInBestUnit($voldoc, 0, 'volume', $langs, 2);
					}
					if ($surfdoc != 0) {
						$surfdoctxt	= infrastechinfos_showDimInBestUnit($surfdoc, 0, 'surface', $langs, 2);
					}
					if ($timedoc != 0) {
						$timedoctxt	= convertSecondToTime($timedoc, (empty($total_in_days) ? 'allhourmin' : 'all'), $duration_workday, $duration_workweek);
					}
?>
					<script type = "text/javascript">
						$(document).ready(function() {
							$('.foldable_ti').each(function() {
								$(this).siblings().toggle();
							});
							$('.foldable_ti').click(function() {
								$(this).siblings().toggle();
							});
						});
					</script>
					<table class = "noborder noshadow centpercent">
						<tbody>
							<tr class = "liste_titre liste_titre_add nodrag nodrop foldable_ti">
								<td colspan = 10 class = "center"><?php echo $langs->trans("InfraSTechInfosTitreMaj") ?></td>
							</tr>
<?php
					if (!empty($ligneTech)) {
?>
							<tr class = "liste_titre nodrag nodrop">
								<th class = "center"><?php echo $langs->trans('InfraSTechInfosColLineN') ?></th>
								<th class = "left"><?php echo $langs->trans('InfraSTechInfosColRef') ?></th>
								<th class = "right"><?php echo $langs->trans('InfraSTechInfosColDim') ?></th>
								<th class = "right"><?php echo $langs->trans('Surface') ?></th>
								<th class = "right"><?php echo $langs->trans('ProductVolume') ?></th>
								<th class = "right"><?php echo $langs->trans('ProductWeight') ?></th>
								<th class = "right"><?php echo $langs->trans('Quantity') ?></th>
								<th class = "right"><?php echo $langs->trans('Surface').' '.$langs->trans('TotalWoman') ?></th>
								<th class = "right"><?php echo $langs->trans('Volume') ?></th>
								<th class = "right"><?php echo $langs->trans('Weight') ?></th>
							</tr>
							<?php echo $ligneTech ?>
							<tr class = "oddeven">
								<td colspan = 7>&nbsp;</td>
								<td class = "right"><?php echo $surfdoctxt ?></td>
								<td class = "right"><?php echo $voldoctxt ?></td>
								<td class = "right"><?php echo $weightdoctxt ?></td>
							</tr>
<?php
					}
					if (empty($only_total_time) && !empty($ligneTime)) {
?>
							<tr class = "liste_titre nodrag nodrop">
								<th class = "center"><?php echo $langs->trans('InfraSTechInfosColLineN') ?></th>
								<th class = "center"><?php echo $langs->trans('InfraSTechInfosColRef') ?></th>
								<th class = "center" colspan = "4"><?php echo $langs->trans('Duration') ?></th>
								<th class = "center"><?php echo $langs->trans('Quantity') ?></th>
								<th class = "center" colspan = "3"><?php echo $langs->trans('TotalDuration') ?></th>
							</tr>
							<?php echo $ligneTime ?>
							<tr class = "oddeven">
								<td class = "left" colspan = "7"><?php echo empty($only_total_time) ? '' : $totalTimeDesc; ?></td>
								<td class = "right" colspan = "3"><?php echo $timedoctxt ?></td>
							</tr>
<?php
					}
					if (!empty($only_total_time)) {
?>
							<tr class = "liste_titre nodrag nodrop">
								<th class = "left" colspan = "7"><?php echo $langs->trans('Description') ?></th>
								<th class = "right" colspan = "3"><?php echo $langs->trans('TotalDuration') ?></th>
							</tr>
							<tr class = "oddeven">
								<td class = "left" colspan = "7"><?php echo $totalTimeDesc ?></td>
								<td class = "right" colspan = "3"><?php echo $timedoctxt ?></td>
							</tr>
<?php
					}
?>
						</tbody>
					</table>
<?php
				}
			}
			return 0; // or return 1 to replace standard code
		}
	}
