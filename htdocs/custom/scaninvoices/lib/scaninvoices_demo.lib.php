<?php
/* Copyright (C) 2026 Éric Seigne <eric.seigne@cap-rel.fr>
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
 * \file    scaninvoices/lib/scaninvoices_demo.lib.php
 * \ingroup scaninvoices
 * \brief   Demonstration data set of the module: generation and purge.
 *
 * The page admin/demo.php is only a form around these two functions, so that the
 * integration tests can generate and purge without simulating an HTTP request.
 *
 * Nothing here ever calls the OCR server. The documents are drawn locally, both
 * as PDF and as the JPEG preview the record page displays: without that preview
 * api.php asks the OCR server to produce it, and every visitor opening a record
 * would spend a credit of the shared quota.
 */

// Marker written in the import_key column of every row this generator creates,
// and the only thing the purge goes by. The column is 14 characters wide.
if (!defined('SCANINVOICES_DEMO_IMPORT_KEY')) {
	define('SCANINVOICES_DEMO_IMPORT_KEY', 'SCANINVDEMO');
}
if (!defined('SCANINVOICES_DEMO_GROUP')) {
	define('SCANINVOICES_DEMO_GROUP', 'ScanInvoices Demo');
}
if (!defined('SCANINVOICES_DEMO_PRODUCT_REF')) {
	define('SCANINVOICES_DEMO_PRODUCT_REF', 'SRV-DEMO-ACHAT');
}
if (!defined('SCANINVOICES_DEMO_PASSWORD')) {
	define('SCANINVOICES_DEMO_PASSWORD', 'demo1234');
}
if (!defined('SCANINVOICES_DEMO_SEED')) {
	define('SCANINVOICES_DEMO_SEED', 20260924);
}

/**
 * Whether the two mutating actions of the demonstration page may run here.
 *
 * Three doors, and not one less. DEMO_INSTANCE is the one the public
 * demonstration service sets, and it is the only one it sets: its hardening
 * block also forces MAIN_FEATURES_LEVEL to 0, so a module that only knows the
 * first two conditions gets refused on the very instance meant to show it.
 *
 * @return	bool	True when the data set may be written or removed
 */
function scaninvoicesDemoAllowed()
{
	if (scaninvoicesGetDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
		return true;
	}
	if (scaninvoicesGetDolGlobalString('SCANINVOICES_ALLOW_DEMO_DATA') === '1') {
		return true;
	}

	return scaninvoicesGetDolGlobalString('DEMO_INSTANCE') === '1';
}

/**
 * Number of demonstration documents currently in the database.
 *
 * @param	DoliDB	$db		Database handler
 * @return	int				Count of marked rows, 0 when nothing was generated
 */
function scaninvoicesDemoCount($db)
{
	$sql = 'SELECT COUNT(*) as nb FROM ' . MAIN_DB_PREFIX . 'scaninvoices_filestoimport';
	$sql .= " WHERE import_key = '" . $db->escape(SCANINVOICES_DEMO_IMPORT_KEY) . "'";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog('ScanInvoices demo: cannot count the demonstration documents: ' . $db->lasterror(), LOG_ERR);

		return 0;
	}

	$obj = $db->fetch_object($resql);

	return empty($obj) ? 0 : (int) $obj->nb;
}

/**
 * Collect every error message an object carries.
 *
 * ->errors holds what ->error alone leaves out, and Dolibarr regularly fills
 * both: a generator that only reads ->error reports "(no error message)" on a
 * failure that did say why.
 *
 * @param	object	$object		Any Dolibarr object after a failed operation
 * @return	string				Messages joined, never an empty string
 */
function scaninvoicesDemoErrors($object)
{
	global $langs;

	$msgs = array();

	if (!empty($object->error)) {
		$msgs[] = $object->error;
	}
	if (!empty($object->errors) && is_array($object->errors)) {
		foreach ($object->errors as $one) {
			if (!empty($one) && $one !== $object->error) {
				$msgs[] = $one;
			}
		}
	}

	return $msgs ? implode(' | ', $msgs) : $langs->transnoentities('ScanInvoicesDemoNoErrorMessage');
}

/**
 * Next number of the fixed seed pseudo random generator.
 *
 * A linear congruential generator rather than mt_rand(): a demonstration whose
 * shape changes at every generation cannot be commented, and a test counting
 * rows would become unstable.
 *
 * @param	int		$seed	Current state, updated in place
 * @param	int		$min	Lowest value wanted
 * @param	int		$max	Highest value wanted
 * @return	int				Value between $min and $max
 */
function scaninvoicesDemoRandom(&$seed, $min, $max)
{
	$seed = ($seed * 1103515245 + 12345) % 2147483648;

	return $min + ($seed % (($max - $min) + 1));
}

/**
 * Suppliers of the demonstration data set.
 *
 * @return	array	List of suppliers, keyed by their position in the set
 */
function scaninvoicesDemoSuppliers()
{
	return array(
		array(
			'name' => 'Papeteries du Rhône',
			'address' => '14 quai Saint-Vincent',
			'zip' => '69001',
			'town' => 'Lyon',
			'email' => 'facturation@papeteries-rhone.demo',
			'tva' => 'FR40123456824',
			'label' => 'Fournitures de bureau',
		),
		array(
			'name' => 'Télécom Atlantique',
			'address' => "8 rue de l'Écluse",
			'zip' => '44000',
			'town' => 'Nantes',
			'email' => 'compta@telecom-atlantique.demo',
			'tva' => 'FR41234568249',
			'label' => 'Abonnements téléphonie',
		),
		array(
			'name' => 'Énergies Vertes SAS',
			'address' => '23 avenue des Peupliers',
			'zip' => '33000',
			'town' => 'Bordeaux',
			'email' => 'factures@energies-vertes.demo',
			'tva' => 'FR42345682491',
			'label' => 'Électricité et gaz',
		),
		array(
			'name' => 'Transports Bellevue',
			'address' => '5 zone de la Gare',
			'zip' => '31000',
			'town' => 'Toulouse',
			'email' => 'adv@transports-bellevue.demo',
			'tva' => 'FR43456824912',
			'label' => 'Frais de transport',
		),
		array(
			'name' => 'Informatique Cévennes',
			'address' => "12 rue de l'Aigoual",
			'zip' => '30100',
			'town' => 'Alès',
			'email' => 'facturation@info-cevennes.demo',
			'tva' => 'FR44568249123',
			'label' => 'Matériel informatique',
		),
	);
}

/**
 * Documents of the demonstration data set, one row of the import queue each.
 *
 * The statuses are spread on purpose: the list page is what a visitor lands on,
 * and a queue where every line reads "Imported" shows none of the work the
 * module actually does.
 *
 * @return	array	List of documents, in the order they are created
 */
function scaninvoicesDemoDocuments()
{
	return array(
		array('supplier' => 0, 'days' => 58, 'status' => 2, 'invoice' => 'FA-2026-0417', 'amount' => 248.90, 'lines' => 3),
		array('supplier' => 1, 'days' => 54, 'status' => 2, 'invoice' => 'TA-114520',    'amount' => 89.00,  'lines' => 1),
		array('supplier' => 2, 'days' => 47, 'status' => 2, 'invoice' => 'EV-2026-1188', 'amount' => 612.45, 'lines' => 2),
		array('supplier' => 3, 'days' => 41, 'status' => 5, 'invoice' => '',             'amount' => 156.20, 'lines' => 1),
		array('supplier' => 4, 'days' => 35, 'status' => 2, 'invoice' => 'IC-2026-0903', 'amount' => 1340.00, 'lines' => 4),
		array('supplier' => 0, 'days' => 29, 'status' => 3, 'invoice' => '',             'amount' => 0.00,   'lines' => 0),
		array('supplier' => 1, 'days' => 24, 'status' => 1, 'invoice' => '',             'amount' => 89.00,  'lines' => 1),
		array('supplier' => 2, 'days' => 18, 'status' => 5, 'invoice' => '',             'amount' => 588.10, 'lines' => 2),
		array('supplier' => 3, 'days' => 12, 'status' => 1, 'invoice' => '',             'amount' => 203.75, 'lines' => 1),
		array('supplier' => 4, 'days' => 7,  'status' => 0, 'invoice' => '',             'amount' => 429.00, 'lines' => 2),
		array('supplier' => 0, 'days' => 3,  'status' => 0, 'invoice' => '',             'amount' => 74.30,  'lines' => 1),
		array('supplier' => 1, 'days' => 1,  'status' => 0, 'invoice' => '',             'amount' => 89.00,  'lines' => 1),
	);
}

/**
 * Demonstration users, created in the group the generator makes.
 *
 * @return	array	List of users, the first one being the one to log in with
 */
function scaninvoicesDemoUsers()
{
	return array(
		array('login' => 'demo.compta',  'firstname' => 'Claire', 'lastname' => 'Bonnet'),
		array('login' => 'demo.assistante', 'firstname' => 'Malik', 'lastname' => 'Ferrand'),
	);
}

/**
 * Directory the generated documents are written into.
 *
 * Resolved once through realpath(): DOL_DATA_ROOT is regularly mounted as
 * htdocs/../documents, and dol_delete_file() refuses without a word any path
 * carrying "..", so the purge would report directories it never removed.
 *
 * @return	string	Absolute path, with a trailing slash
 */
function scaninvoicesDemoUploadDir()
{
	$resolved = realpath(DOL_DATA_ROOT);
	$root = ($resolved === false) ? DOL_DATA_ROOT : $resolved;

	return $root . '/scaninvoices/uploads/now/';
}

/**
 * File name of the document of one entry of the data set.
 *
 * @param	array	$supplier	Supplier the document comes from
 * @param	int		$index		Position of the document in the data set
 * @return	string				Base name, extension included
 */
function scaninvoicesDemoFilename($supplier, $index)
{
	return 'demo-' . sprintf('%02d', $index + 1) . '-' . dol_sanitizeFileName(scaninvoicesSlugify($supplier['name'])) . '.pdf';
}

/**
 * Draw the PDF of a demonstration invoice.
 *
 * TCPDF ships with Dolibarr, so nothing is added to the module for this. The
 * layout is deliberately plain: this is a document to be scanned, not a model
 * to be proud of.
 *
 * @param	string	$path		Absolute path of the file to write
 * @param	array	$supplier	Supplier the invoice comes from
 * @param	array	$document	Entry of the data set
 * @param	int		$date		Date of the invoice, as a timestamp
 * @return	int					1 when written, 0 when not
 */
function scaninvoicesDemoWritePdf($path, $supplier, $document, $date)
{
	if (!file_exists(DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php')) {
		dol_syslog('ScanInvoices demo: TCPDF is not available, no PDF drawn for ' . basename($path), LOG_WARNING);

		return 0;
	}

	require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

	$reference = $document['invoice'] !== '' ? $document['invoice'] : 'PROV-' . dol_print_date($date, '%Y%m%d');
	$amount = (float) $document['amount'];
	$vat = round($amount * 0.2, 2);

	try {
		$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->SetCreator('ScanInvoices');
		$pdf->SetAuthor($supplier['name']);
		$pdf->SetTitle($reference);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->AddPage();

		$pdf->SetFont('helvetica', 'B', 16);
		$pdf->Cell(0, 10, $supplier['name'], 0, 1);
		$pdf->SetFont('helvetica', '', 10);
		$pdf->Cell(0, 5, $supplier['address'], 0, 1);
		$pdf->Cell(0, 5, $supplier['zip'] . ' ' . $supplier['town'], 0, 1);
		$pdf->Cell(0, 5, 'TVA ' . $supplier['tva'], 0, 1);
		$pdf->Ln(10);

		$pdf->SetFont('helvetica', 'B', 13);
		$pdf->Cell(0, 8, 'FACTURE ' . $reference, 0, 1);
		$pdf->SetFont('helvetica', '', 10);
		$pdf->Cell(0, 6, 'Date : ' . dol_print_date($date, '%d/%m/%Y'), 0, 1);
		$pdf->Cell(0, 6, 'Objet : ' . $supplier['label'], 0, 1);
		$pdf->Ln(8);

		$pdf->SetFont('helvetica', '', 10);
		$lines = max(1, (int) $document['lines']);
		$unit = $lines > 0 ? round($amount / $lines, 2) : $amount;
		for ($i = 1; $i <= $lines; $i++) {
			$pdf->Cell(120, 7, $supplier['label'] . ' - ligne ' . $i, 'B', 0);
			$pdf->Cell(0, 7, number_format($unit, 2, ',', ' ') . ' EUR', 'B', 1, 'R');
		}
		$pdf->Ln(6);

		$pdf->SetFont('helvetica', '', 11);
		$pdf->Cell(120, 7, 'Total HT', 0, 0);
		$pdf->Cell(0, 7, number_format($amount, 2, ',', ' ') . ' EUR', 0, 1, 'R');
		$pdf->Cell(120, 7, 'TVA 20 %', 0, 0);
		$pdf->Cell(0, 7, number_format($vat, 2, ',', ' ') . ' EUR', 0, 1, 'R');
		$pdf->SetFont('helvetica', 'B', 11);
		$pdf->Cell(120, 7, 'Total TTC', 0, 0);
		$pdf->Cell(0, 7, number_format($amount + $vat, 2, ',', ' ') . ' EUR', 0, 1, 'R');

		$pdf->Output($path, 'F');
	} catch (Exception $e) {
		dol_syslog('ScanInvoices demo: cannot draw the PDF ' . basename($path) . ': ' . $e->getMessage(), LOG_ERR);

		return 0;
	}

	return file_exists($path) ? 1 : 0;
}

/**
 * TrueType font the preview is drawn with.
 *
 * Taken from the fonts Dolibarr ships, so nothing outside the installation is
 * needed and no font is added to the module. An empty answer is not an error:
 * the caller falls back on the bitmap fonts of GD.
 *
 * @return	string	Absolute path of the font, empty when none was found
 */
function scaninvoicesDemoPreviewFont()
{
	if (!function_exists('imagettftext')) {
		return '';
	}

	$candidates = array(
		DOL_DOCUMENT_ROOT . '/includes/fonts/Roboto-Regular.ttf',
		DOL_DOCUMENT_ROOT . '/includes/fonts/DejaVuSans.ttf',
		DOL_DOCUMENT_ROOT . '/includes/fonts/Aerial.ttf',
	);

	foreach ($candidates as $candidate) {
		if (is_file($candidate)) {
			return $candidate;
		}
	}

	dol_syslog('ScanInvoices demo: no TrueType font found, the previews will be drawn without accents', LOG_WARNING);

	return '';
}

/**
 * Draw the JPEG preview beside a generated PDF.
 *
 * The record page shows that file, and api.php asks the OCR server to produce
 * it when it is missing: drawn here, a demonstration costs no credit of the
 * shared quota however many records a visitor opens.
 *
 * @param	string	$path		Absolute path of the PDF the preview belongs to
 * @param	array	$supplier	Supplier the invoice comes from
 * @param	array	$document	Entry of the data set
 * @param	int		$date		Date of the invoice, as a timestamp
 * @return	int					1 when written, 0 when not
 */
function scaninvoicesDemoWritePreview($path, $supplier, $document, $date)
{
	if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
		dol_syslog('ScanInvoices demo: GD is not available, no preview drawn for ' . basename($path), LOG_WARNING);

		return 0;
	}

	$destination = preg_replace('/\.pdf$/i', '.jpg', $path);
	$reference = $document['invoice'] !== '' ? $document['invoice'] : 'PROV-' . dol_print_date($date, '%Y%m%d');
	$amount = (float) $document['amount'];

	$image = imagecreatetruecolor(595, 842);
	if ($image === false) {
		dol_syslog('ScanInvoices demo: cannot allocate the preview of ' . basename($path), LOG_ERR);

		return 0;
	}

	$white = imagecolorallocate($image, 255, 255, 255);
	$black = imagecolorallocate($image, 20, 20, 20);
	$grey = imagecolorallocate($image, 150, 150, 150);
	imagefilledrectangle($image, 0, 0, 594, 841, $white);

	$font = scaninvoicesDemoPreviewFont();
	$vat = round($amount * 0.2, 2);

	/**
	 * Write one line, with the font when there is one.
	 *
	 * imagestring() draws ASCII only, so without a TrueType font the preview
	 * reads "Materiel informatique" while the PDF beside it is correct. The
	 * transliteration is the fallback, not the normal path.
	 *
	 * @param	int		$x		Left position
	 * @param	int		$y		Baseline position
	 * @param	string	$text	Text to draw, in UTF-8
	 * @param	int		$size	Point size wanted
	 * @return	void
	 */
	$write = function ($x, $y, $text, $size) use ($image, $black, $font) {
		if ($font !== '') {
			imagettftext($image, $size, 0, $x, $y, $black, $font, $text);

			return;
		}

		$converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
		imagestring($image, $size >= 13 ? 5 : ($size >= 11 ? 4 : 3), $x, $y - 12, ($converted === false) ? $text : $converted, $black);
	};

	$write(40, 55, $supplier['name'], 15);
	$write(40, 85, $supplier['address'], 10);
	$write(40, 103, $supplier['zip'] . ' ' . $supplier['town'], 10);
	$write(40, 121, 'TVA ' . $supplier['tva'], 10);
	$write(40, 175, 'FACTURE ' . $reference, 13);
	$write(40, 205, 'Date : ' . dol_print_date($date, '%d/%m/%Y'), 10);
	$write(40, 223, 'Objet : ' . $supplier['label'], 10);
	imageline($image, 40, 245, 555, 245, $grey);

	$lines = max(1, (int) $document['lines']);
	$unit = round($amount / $lines, 2);
	$offset = 275;
	for ($i = 1; $i <= $lines; $i++) {
		$write(40, $offset, $supplier['label'] . ' - ligne ' . $i, 10);
		$write(420, $offset, number_format($unit, 2, ',', ' ') . ' EUR', 10);
		$offset += 24;
	}

	imageline($image, 40, $offset, 555, $offset, $grey);
	$write(40, $offset + 26, 'Total HT', 11);
	$write(420, $offset + 26, number_format($amount, 2, ',', ' ') . ' EUR', 11);
	$write(40, $offset + 50, 'TVA 20 %', 11);
	$write(420, $offset + 50, number_format($vat, 2, ',', ' ') . ' EUR', 11);
	$write(40, $offset + 78, 'Total TTC', 12);
	$write(420, $offset + 78, number_format($amount + $vat, 2, ',', ' ') . ' EUR', 12);

	$written = @imagejpeg($image, $destination, 85);
	imagedestroy($image);

	if (!$written) {
		dol_syslog('ScanInvoices demo: cannot write the preview ' . basename($destination), LOG_ERR);

		return 0;
	}

	return 1;
}

/**
 * Generate the demonstration data set.
 *
 * Re-entrant: it is meant to be replayed on an instance that already holds the
 * data, which is what happens on a demonstration one and in the test suites.
 * Objects already there are looked up and reused rather than duplicated.
 *
 * Fatal errors are kept for the group and the users, without which the
 * demonstration cannot be used at all. Everything else is a warning: a supplier
 * refusing to be written must not roll the accounts back.
 *
 * @param	DoliDB	$db		Database handler
 * @param	User	$user	User the objects are created by
 * @return	array			array('error' => int, 'warnings' => int, 'results' => string[])
 */
function scaninvoicesDemoGenerate($db, $user)
{
	global $conf, $langs;

	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
	require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
	dol_include_once('/scaninvoices/lib/scaninvoices_compat.lib.php');
	dol_include_once('/scaninvoices/lib/scaninvoices.lib.php');
	dol_include_once('/scaninvoices/lib/scaninvoices_demo_home.lib.php');
	dol_include_once('/scaninvoices/class/filestoimport.class.php');
	dol_include_once('/scaninvoices/class/settings.class.php');

	// Called from a test or from the command line, nothing has loaded the file
	// yet and the journal would show raw keys.
	$langs->loadLangs(array('scaninvoices@scaninvoices'));

	$error = 0;
	$warnings = 0;
	$results = array();
	$marker = SCANINVOICES_DEMO_IMPORT_KEY;
	$entity = $conf->entity;

	$db->begin();

	// Password policy first: Standard refuses anything under twelve characters,
	// and the accounts of a demonstration are told to the visitor.
	dolibarr_set_const($db, 'USER_PASSWORD_GENERATED', 'None', 'chaine', 0, '', $entity);
	$conf->global->USER_PASSWORD_GENERATED = 'None';

	// -----------------------------------------------------------------
	// 1. Default service, the one the recognition settings point to
	// -----------------------------------------------------------------
	$productId = 0;
	$product = new Product($db);
	if ($product->fetch(0, SCANINVOICES_DEMO_PRODUCT_REF) > 0) {
		$productId = $product->id;
		scaninvoicesDemoAdopt($db, 'product', $productId, $marker);
		$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogReused', 'ScanInvoicesDemoObjectProduct', $product->ref, $productId);
	} else {
		$product = new Product($db);
		$product->ref = SCANINVOICES_DEMO_PRODUCT_REF;
		$product->label = $langs->transnoentities('ScanInvoicesDemoProductLabel');
		$product->type = Product::TYPE_SERVICE;
		$product->status = 1;
		$product->status_buy = 1;
		$product->entity = $entity;
		if ($product->create($user) > 0) {
			$check = new Product($db);
			if ($check->fetch($product->id) > 0) {
				$productId = $check->id;
				// Product::create() does not write import_key although the column exists.
				scaninvoicesDemoAdopt($db, 'product', $productId, $marker);
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogCreated', 'ScanInvoicesDemoObjectProduct', $check->ref, $productId);
			} else {
				$warnings++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectProduct', SCANINVOICES_DEMO_PRODUCT_REF, $langs->transnoentities('ScanInvoicesDemoNotFoundAfterCreate'));
			}
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectProduct', SCANINVOICES_DEMO_PRODUCT_REF, scaninvoicesDemoErrors($product));
		}
	}

	// -----------------------------------------------------------------
	// 2. Suppliers
	// -----------------------------------------------------------------
	// The readable code is only forced where codes are free: every other
	// numbering model applies a mask and refuses anything else, and a supplier
	// that fails to be written takes the whole data set down with it.
	$freeCode = (scaninvoicesGetDolGlobalString('SOCIETE_CODECLIENT_ADDON', 'mod_codeclient_leopard') === 'mod_codeclient_leopard');
	$supplierIds = array();

	foreach (scaninvoicesDemoSuppliers() as $position => $data) {
		$existing = scaninvoicesDemoFindSupplier($db, $data['name'], $marker, $entity);
		if ($existing > 0) {
			$supplierIds[$position] = $existing;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogReused', 'ScanInvoicesDemoObjectSupplier', $data['name'], $existing);
			continue;
		}

		$supplier = new Societe($db);
		$supplier->name = $data['name'];
		$supplier->address = $data['address'];
		$supplier->zip = $data['zip'];
		$supplier->town = $data['town'];
		$supplier->email = $data['email'];
		$supplier->tva_intra = $data['tva'];
		$supplier->country_id = 1;
		$supplier->client = 0;
		$supplier->fournisseur = 1;
		$supplier->status = 1;
		$supplier->entity = $entity;
		$supplier->code_client = $freeCode ? 'CU-DEMO-' . sprintf('%03d', $position + 1) : -1;
		$supplier->code_fournisseur = $freeCode ? 'SU-DEMO-' . sprintf('%03d', $position + 1) : -1;
		$supplier->import_key = $marker;

		if ($supplier->create($user) > 0) {
			$check = new Societe($db);
			if ($check->fetch($supplier->id) > 0) {
				$supplierIds[$position] = $check->id;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogCreated', 'ScanInvoicesDemoObjectSupplier', $check->name, $check->id);
			} else {
				$warnings++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectSupplier', $data['name'], $langs->transnoentities('ScanInvoicesDemoNotFoundAfterCreate'));
			}
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectSupplier', $data['name'], scaninvoicesDemoErrors($supplier));
		}
	}

	// -----------------------------------------------------------------
	// 3. Recognition settings, one per supplier
	// -----------------------------------------------------------------
	foreach (scaninvoicesDemoSuppliers() as $position => $data) {
		if (empty($supplierIds[$position])) {
			continue;
		}

		// Not translated on purpose: the label is what the re-entrance looks the
		// setting up by, so a second generation made in another language would
		// find nothing and write the whole set a second time.
		$label = scaninvoicesDemoSettingLabel($data);
		$existing = scaninvoicesDemoFindSetting($db, $label, $marker);
		if ($existing > 0) {
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogReused', 'ScanInvoicesDemoObjectSetting', $label, $existing);
			continue;
		}

		$setting = new Settings($db);
		$setting->label = $label;
		$setting->fk_soc = $supplierIds[$position];
		$setting->fk_default_product = $productId ? $productId : null;
		$setting->yml = scaninvoicesDemoYaml($data);
		$setting->date_creation = dol_now();
		$setting->status = 1;
		$setting->import_key = $marker;

		if ($setting->create($user) > 0) {
			$check = new Settings($db);
			if ($check->fetch($setting->id) > 0) {
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogCreated', 'ScanInvoicesDemoObjectSetting', $label, $check->id);
			} else {
				$warnings++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectSetting', $label, $langs->transnoentities('ScanInvoicesDemoNotFoundAfterCreate'));
			}
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectSetting', $label, scaninvoicesDemoErrors($setting));
		}
	}

	// -----------------------------------------------------------------
	// 4. Group, then users. Fatal: without them there is nothing to log in with
	// -----------------------------------------------------------------
	$groupId = scaninvoicesDemoFindGroup($db, $entity);

	if ($groupId > 0) {
		$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogReused', 'ScanInvoicesDemoObjectGroup', SCANINVOICES_DEMO_GROUP, $groupId);
	} else {
		$group = new UserGroup($db);
		// Both forms: ->nom is what Dolibarr 14 writes, ->name what the recent
		// versions expect, and the module still supports the former.
		$group->name = SCANINVOICES_DEMO_GROUP;
		$group->nom = SCANINVOICES_DEMO_GROUP;
		$group->note = $langs->transnoentities('ScanInvoicesDemoGroupNote');
		$group->entity = $entity;

		// create() takes a notrigger flag, not the current user: handing it $user
		// works by accident and silently disables the triggers.
		if ($group->create() > 0) {
			$check = new UserGroup($db);
			if ($check->fetch($group->id) > 0) {
				$groupId = $check->id;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogCreated', 'ScanInvoicesDemoObjectGroup', SCANINVOICES_DEMO_GROUP, $groupId);
			} else {
				$error++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFatal', 'ScanInvoicesDemoObjectGroup', SCANINVOICES_DEMO_GROUP, $langs->transnoentities('ScanInvoicesDemoNotFoundAfterCreate'));
			}
		} else {
			$error++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFatal', 'ScanInvoicesDemoObjectGroup', SCANINVOICES_DEMO_GROUP, scaninvoicesDemoErrors($group));
		}
	}

	if ($groupId > 0) {
		$group = new UserGroup($db);
		if ($group->fetch($groupId) > 0) {
			$rights = array(
				array('societe', 'lire'),
				array('fournisseur', 'facture'),
				array('produit', 'lire'),
				array('scaninvoices', 'filestoimport'),
				array('scaninvoices', 'settings'),
			);
			foreach ($rights as $right) {
				if ($group->addrights(0, $right[0], $right[1], $entity) < 0) {
					$warnings++;
					$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectRight', $right[0] . '/' . $right[1], scaninvoicesDemoErrors($group));
				}
			}
		}
	}

	$createdUsers = 0;
	foreach (scaninvoicesDemoUsers() as $data) {
		$existing = scaninvoicesDemoFindUser($db, $data['login']);
		if ($existing > 0) {
			$createdUsers++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogReused', 'ScanInvoicesDemoObjectUser', $data['login'], $existing);
			continue;
		}

		$newUser = new User($db);
		$newUser->login = $data['login'];
		$newUser->firstname = $data['firstname'];
		$newUser->lastname = $data['lastname'];
		// @demo.local is not routable: no demonstration account can collide with
		// a real address, and the module recognises its own accounts by it.
		$newUser->email = $data['login'] . '@demo.local';
		$newUser->entity = $entity;
		$newUser->statut = 1;

		if ($newUser->create($user) > 0) {
			$check = new User($db);
			if ($check->fetch($newUser->id) > 0) {
				$createdUsers++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogCreated', 'ScanInvoicesDemoObjectUser', $data['login'], $check->id);

				// setPassword() answers with the password string on success, not 1.
				// Never test it with <= 0: PHP casts the string to 0 for that.
				$passwordResult = $check->setPassword($user, SCANINVOICES_DEMO_PASSWORD);
				if (!is_string($passwordResult) || $passwordResult === '') {
					$warnings++;
					$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectPassword', $data['login'], scaninvoicesDemoErrors($check));
				}

				if ($groupId > 0 && $check->SetInGroup($groupId, $entity) < 0) {
					$warnings++;
					$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectGroupMember', $data['login'], scaninvoicesDemoErrors($check));
				}
			} else {
				$error++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFatal', 'ScanInvoicesDemoObjectUser', $data['login'], $langs->transnoentities('ScanInvoicesDemoNotFoundAfterCreate'));
			}
		} else {
			$error++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFatal', 'ScanInvoicesDemoObjectUser', $data['login'], scaninvoicesDemoErrors($newUser));
		}
	}

	// -----------------------------------------------------------------
	// 5. The import queue, and the supplier invoices behind it
	// -----------------------------------------------------------------
	$seed = SCANINVOICES_DEMO_SEED;
	$suppliers = scaninvoicesDemoSuppliers();
	$uploadDir = scaninvoicesDemoUploadDir();
	$documents = 0;
	$invoices = 0;
	$files = 0;
	$previews = 0;

	if (!is_dir($uploadDir) && dol_mkdir($uploadDir) < 0) {
		$warnings++;
		$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectFile', $uploadDir, $langs->transnoentities('ScanInvoicesDemoCannotCreateDir'));
	}

	foreach (scaninvoicesDemoDocuments() as $index => $entry) {
		$supplier = $suppliers[$entry['supplier']];
		$supplierId = isset($supplierIds[$entry['supplier']]) ? $supplierIds[$entry['supplier']] : 0;
		$filename = scaninvoicesDemoFilename($supplier, $index);
		$date = dol_now() - ($entry['days'] * 86400);
		// Kept for the jitter of the OCR round trip, so the dates do not all
		// line up on the same minute.
		$delay = scaninvoicesDemoRandom($seed, 40, 600);

		// The document is drawn on every run, reused rows included: the second
		// generation is the one that has the files at hand, and it creates
		// nothing.
		$path = $uploadDir . $filename;
		if (is_dir($uploadDir)) {
			if (scaninvoicesDemoWritePdf($path, $supplier, $entry, $date)) {
				$files++;
				if (scaninvoicesDemoWritePreview($path, $supplier, $entry, $date)) {
					$previews++;
				}
			} else {
				$warnings++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectFile', $filename, $langs->transnoentities('ScanInvoicesDemoCannotWriteFile'));
			}
		}

		$existing = scaninvoicesDemoFindDocument($db, $filename, $marker, $entity);
		if ($existing > 0) {
			$documents++;
			continue;
		}

		$invoiceId = 0;
		if ($entry['invoice'] !== '' && $supplierId > 0) {
			$invoiceId = scaninvoicesDemoCreateInvoice($db, $user, $supplierId, $supplier, $entry, $date, $productId, $marker, $results, $warnings);
			if ($invoiceId > 0) {
				$invoices++;
			}
		}

		$document = new Filestoimport($db);
		$document->ref = 'DEMO-' . sprintf('%04d', $index + 1);
		$document->entity = $entity;
		$document->filename = $filename;
		$document->sha1 = file_exists($path) ? sha1_file($path) : sha1($filename);
		$document->message = scaninvoicesDemoMessage($entry, $supplier);
		$document->date_creation = $date;
		$document->date_ocr_send = $date + 30;
		$document->date_ocr_return = $date + 30 + $delay;
		$document->fk_supplier = $supplierId ? $supplierId : null;
		$document->fk_invoice = $invoiceId ? $invoiceId : null;
		$document->queue = Filestoimport::QUEUE_MANUAL;
		$document->status = (int) $entry['status'];
		$document->import_key = $marker;

		if ($document->create($user) > 0) {
			$check = new Filestoimport($db);
			if ($check->fetch($document->id) > 0) {
				$documents++;
			} else {
				$warnings++;
				$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectDocument', $filename, $langs->transnoentities('ScanInvoicesDemoNotFoundAfterCreate'));
			}
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectDocument', $filename, scaninvoicesDemoErrors($document));
		}
	}

	// One aggregated line rather than one per row: a screen of a hundred [OK]
	// lines is a screen nobody reads.
	$results[] = '[OK] ' . $langs->transnoentities('ScanInvoicesDemoSummary', $documents, $invoices, count($supplierIds), $createdUsers);
	$results[] = '[OK] ' . $langs->transnoentities('ScanInvoicesDemoSummaryFiles', $files, $previews);

	// Last, and only once the data is there: from now on a visitor landing on
	// Dolibarr arrives on the module instead of a dashboard that means nothing
	// to them.
	if (scaninvoicesDemoActivate($db, $entity) > 0) {
		$results[] = '[OK] ' . $langs->transnoentities('ScanInvoicesDemoLogModeActivated');
	} else {
		$warnings++;
		$results[] = '[WARN] ' . $langs->transnoentities('ScanInvoicesDemoLogModeActivationFailed');
	}

	if (!scaninvoicesGetDolGlobalString('SCANINVOICES_KEY_API')) {
		$results[] = '[WARN] ' . $langs->transnoentities('ScanInvoicesDemoNoOcrAccount');
	}

	if ($error) {
		$db->rollback();
		dol_syslog('ScanInvoices demo: ' . $error . ' fatal error(s), the data set was rolled back', LOG_ERR);
	} else {
		$db->commit();
	}

	return array('error' => $error, 'warnings' => $warnings, 'results' => $results);
}

/**
 * Remove the demonstration data set.
 *
 * In the reverse order of the dependencies: the rows referencing a supplier go
 * before the supplier, otherwise Dolibarr refuses the deletion and the purge
 * reports a success it did not obtain.
 *
 * @param	DoliDB	$db		Database handler
 * @param	User	$user	User the deletions are made by
 * @return	array			array('error' => int, 'warnings' => int, 'results' => string[])
 */
function scaninvoicesDemoPurge($db, $user)
{
	global $conf, $langs;

	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
	require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
	dol_include_once('/scaninvoices/lib/scaninvoices_compat.lib.php');
	dol_include_once('/scaninvoices/lib/scaninvoices.lib.php');
	dol_include_once('/scaninvoices/lib/scaninvoices_demo_home.lib.php');
	dol_include_once('/scaninvoices/class/filestoimport.class.php');
	dol_include_once('/scaninvoices/class/settings.class.php');

	$langs->loadLangs(array('scaninvoices@scaninvoices'));

	$error = 0;
	$warnings = 0;
	$results = array();
	$marker = SCANINVOICES_DEMO_IMPORT_KEY;
	$entity = $conf->entity;

	$db->begin();

	// 1. Documents of the queue. Filestoimport::delete() removes the file and
	// its preview, so the disk is cleaned by the same call.
	$removed = 0;
	foreach (scaninvoicesDemoIdsOf($db, 'scaninvoices_filestoimport', $marker, $entity) as $id) {
		$document = new Filestoimport($db);
		if ($document->fetch($id) > 0 && $document->delete($user) > 0) {
			$removed++;
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectDocument', $id, scaninvoicesDemoErrors($document));
		}
	}
	$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectDocument', $removed);

	// 2. Supplier invoices
	$removed = 0;
	foreach (scaninvoicesDemoIdsOf($db, 'facture_fourn', $marker, $entity) as $id) {
		$invoice = new FactureFournisseur($db);
		if ($invoice->fetch($id) > 0 && $invoice->delete($user) > 0) {
			$removed++;
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectInvoice', $id, scaninvoicesDemoErrors($invoice));
		}
	}
	$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectInvoice', $removed);

	// 3. Recognition settings. The table carries no entity column.
	$removed = 0;
	foreach (scaninvoicesDemoIdsOf($db, 'scaninvoices_settings', $marker, 0) as $id) {
		$setting = new Settings($db);
		if ($setting->fetch($id) > 0 && $setting->delete($user) > 0) {
			$removed++;
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectSetting', $id, scaninvoicesDemoErrors($setting));
		}
	}
	$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectSetting', $removed);

	// 4. Users, then the group they belong to
	$removed = 0;
	foreach (scaninvoicesDemoUsers() as $data) {
		$id = scaninvoicesDemoFindUser($db, $data['login']);
		if ($id <= 0) {
			continue;
		}
		$target = new User($db);
		if ($target->fetch($id) > 0 && $target->delete($user) > 0) {
			$removed++;
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectUser', $data['login'], scaninvoicesDemoErrors($target));
		}
	}
	$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectUser', $removed);

	$groupId = scaninvoicesDemoFindGroup($db, $entity);
	if ($groupId > 0) {
		$group = new UserGroup($db);
		if ($group->fetch($groupId) > 0 && $group->delete($user) > 0) {
			$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectGroup', 1);
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectGroup', SCANINVOICES_DEMO_GROUP, scaninvoicesDemoErrors($group));
		}
	}

	// 5. Suppliers, now that nothing points at them any more
	$removed = 0;
	foreach (scaninvoicesDemoIdsOf($db, 'societe', $marker, $entity) as $id) {
		$supplier = new Societe($db);
		if ($supplier->fetch($id) > 0 && $supplier->delete($id, $user) > 0) {
			$removed++;
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectSupplier', $id, scaninvoicesDemoErrors($supplier));
		}
	}
	$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectSupplier', $removed);

	// 6. Default service
	$removed = 0;
	foreach (scaninvoicesDemoIdsOf($db, 'product', $marker, $entity) as $id) {
		$product = new Product($db);
		// Product::delete() checks the produit->supprimer permission, which a
		// test harness running without a login does not have: a failure here is
		// journalled and does not stop the purge.
		if ($product->fetch($id) > 0 && $product->delete($user) > 0) {
			$removed++;
		} else {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectProduct', $id, scaninvoicesDemoErrors($product));
		}
	}
	$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectProduct', $removed);

	// The landing page goes back to what the administrator had before, and the
	// flag goes down: an instance that returns to real work must not keep the
	// welcome page of a demonstration that no longer exists.
	if (scaninvoicesDemoDeactivate($db, $entity) > 0) {
		$results[] = '[OK] ' . $langs->transnoentities('ScanInvoicesDemoLogModeDeactivated');
	} else {
		$warnings++;
		$results[] = '[WARN] ' . $langs->transnoentities('ScanInvoicesDemoLogModeDeactivationFailed');
	}

	if ($error) {
		$db->rollback();
		dol_syslog('ScanInvoices demo: ' . $error . ' fatal error(s) during the purge, nothing was removed', LOG_ERR);
	} else {
		$db->commit();
	}

	// After the commit, and only for what the object deletion left behind: a
	// document row that had already lost its file, a preview of a run that
	// failed halfway.
	$leftovers = scaninvoicesDemoRemoveLeftoverFiles();
	if ($leftovers > 0) {
		$results[] = scaninvoicesDemoDeleted('ScanInvoicesDemoObjectFile', $leftovers);
	}

	return array('error' => $error, 'warnings' => $warnings, 'results' => $results);
}

/**
 * Remove the generated documents still on disk after the rows are gone.
 *
 * @return	int		Number of files removed
 */
function scaninvoicesDemoRemoveLeftoverFiles()
{
	$dir = scaninvoicesDemoUploadDir();
	if (!is_dir($dir)) {
		return 0;
	}

	$removed = 0;
	foreach (array('demo-*.pdf', 'demo-*.jpg') as $pattern) {
		$found = glob($dir . $pattern);
		if ($found === false) {
			continue;
		}
		foreach ($found as $file) {
			if (@unlink($file)) {
				$removed++;
			} else {
				dol_syslog('ScanInvoices demo: cannot remove ' . $file, LOG_WARNING);
			}
		}
	}

	return $removed;
}

/**
 * Create the supplier invoice a recognised document produced.
 *
 * Left as a draft on purpose: validating asks for a numbering model this
 * instance may not have set up, and a document that has just been read is
 * exactly what an accountant wants to check before validating.
 *
 * @param	DoliDB	$db			Database handler
 * @param	User	$user		User the invoice is created by
 * @param	int		$supplierId	Supplier of the invoice
 * @param	array	$supplier	Supplier data of the set
 * @param	array	$entry		Entry of the data set
 * @param	int		$date		Date of the invoice, as a timestamp
 * @param	int		$productId	Default service, 0 when it could not be created
 * @param	string	$marker		Value written in import_key
 * @param	array	$results	Journal of the run, appended to
 * @param	int		$warnings	Warning counter, incremented on failure
 * @return	int					Id of the invoice, 0 on failure
 */
function scaninvoicesDemoCreateInvoice($db, $user, $supplierId, $supplier, $entry, $date, $productId, $marker, &$results, &$warnings)
{
	global $langs;

	$existing = scaninvoicesDemoFindInvoice($db, $supplierId, $entry['invoice']);
	if ($existing > 0) {
		scaninvoicesDemoAdopt($db, 'facture_fourn', $existing, $marker);

		return $existing;
	}

	$invoice = new FactureFournisseur($db);
	$invoice->ref = $entry['invoice'];
	$invoice->ref_supplier = $entry['invoice'];
	$invoice->socid = $supplierId;
	// Same reason as the group above, and the same pair the module itself sets
	// when it creates an invoice out of a recognised document.
	$invoice->libelle = $supplier['label'];
	$invoice->label = $supplier['label'];
	$invoice->date = $date;
	$invoice->date_echeance = $date + (30 * 86400);

	if ($invoice->create($user) <= 0) {
		$warnings++;
		$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectInvoice', $entry['invoice'], scaninvoicesDemoErrors($invoice));

		return 0;
	}

	$lines = max(1, (int) $entry['lines']);
	$unit = round(((float) $entry['amount']) / $lines, 2);
	for ($i = 1; $i <= $lines; $i++) {
		$label = $supplier['label'] . ' - ' . $langs->transnoentities('ScanInvoicesDemoInvoiceLine', $i);
		// special_code passed rather than left empty: addline() otherwise falls
		// back on $this->special_code, which FactureFournisseur never declares,
		// and every line costs a PHP 8 notice. This module has already had an
		// import chain broken by a notice printed ahead of a JSON answer.
		$added = $invoice->addline($label, $unit, 20, 0, 0, 1, $productId ? $productId : 0, 0, '', '', 0, '', 'HT', 1, -1, false, 0, null, 0, 0, $entry['invoice'], 0);
		if ($added <= 0) {
			$warnings++;
			$results[] = scaninvoicesDemoLine('ScanInvoicesDemoLogFailed', 'ScanInvoicesDemoObjectInvoiceLine', $entry['invoice'], scaninvoicesDemoErrors($invoice));
		}
	}

	// FactureFournisseur::create() does not write import_key, and the purge has
	// nothing else to recognise its own rows by.
	scaninvoicesDemoAdopt($db, 'facture_fourn', $invoice->id, $marker);

	return (int) $invoice->id;
}

/**
 * Write the marker on a row that does not carry one yet.
 *
 * Only on the rows left unmarked, so that a row belonging to another data set
 * is never stolen.
 *
 * @param	DoliDB	$db		Database handler
 * @param	string	$table	Table name, without the prefix
 * @param	int		$id		Row to mark
 * @param	string	$marker	Value written in import_key
 * @return	int				1 when the statement ran, 0 when it failed
 */
function scaninvoicesDemoAdopt($db, $table, $id, $marker)
{
	$sql = 'UPDATE ' . MAIN_DB_PREFIX . $db->escape($table);
	$sql .= " SET import_key = '" . $db->escape($marker) . "'";
	$sql .= ' WHERE rowid = ' . ((int) $id);
	$sql .= " AND (import_key IS NULL OR import_key = '')";

	if (!$db->query($sql)) {
		dol_syslog('ScanInvoices demo: cannot mark ' . $table . ' ' . $id . ': ' . $db->lasterror(), LOG_ERR);

		return 0;
	}

	return 1;
}

/**
 * Ids of the rows of a table carrying the demonstration marker.
 *
 * @param	DoliDB	$db		Database handler
 * @param	string	$table	Table name, without the prefix
 * @param	string	$marker	Value looked for in import_key
 * @param	int		$entity	Entity to restrict to, 0 when the table has no such column
 * @return	int[]			Ids, empty when the query fails
 */
function scaninvoicesDemoIdsOf($db, $table, $marker, $entity)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . $db->escape($table);
	$sql .= " WHERE import_key = '" . $db->escape($marker) . "'";
	if ($entity > 0) {
		$sql .= ' AND entity = ' . ((int) $entity);
	}

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog('ScanInvoices demo: cannot list the rows of ' . $table . ': ' . $db->lasterror(), LOG_ERR);

		return array();
	}

	$ids = array();
	while ($obj = $db->fetch_object($resql)) {
		$ids[] = (int) $obj->rowid;
	}

	return $ids;
}

/**
 * Supplier of the data set already in the database.
 *
 * By marker and name: its supplier code is unique, so a create() would fail on
 * the duplicate and take with it every document attached to it.
 *
 * @param	DoliDB	$db		Database handler
 * @param	string	$name	Name of the supplier
 * @param	string	$marker	Value looked for in import_key
 * @param	int		$entity	Entity to restrict to
 * @return	int				Id, 0 when absent
 */
function scaninvoicesDemoFindSupplier($db, $name, $marker, $entity)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'societe';
	$sql .= " WHERE import_key = '" . $db->escape($marker) . "'";
	$sql .= " AND nom = '" . $db->escape($name) . "'";
	$sql .= ' AND entity = ' . ((int) $entity);

	return scaninvoicesDemoFirstId($db, $sql, 'societe');
}

/**
 * Recognition setting of the data set already in the database.
 *
 * @param	DoliDB	$db		Database handler
 * @param	string	$label	Label of the setting
 * @param	string	$marker	Value looked for in import_key
 * @return	int				Id, 0 when absent
 */
function scaninvoicesDemoFindSetting($db, $label, $marker)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'scaninvoices_settings';
	$sql .= " WHERE import_key = '" . $db->escape($marker) . "'";
	$sql .= " AND label = '" . $db->escape($label) . "'";

	return scaninvoicesDemoFirstId($db, $sql, 'scaninvoices_settings');
}

/**
 * Document of the queue already in the database.
 *
 * @param	DoliDB	$db			Database handler
 * @param	string	$filename	File name of the document
 * @param	string	$marker		Value looked for in import_key
 * @param	int		$entity		Entity to restrict to
 * @return	int					Id, 0 when absent
 */
function scaninvoicesDemoFindDocument($db, $filename, $marker, $entity)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'scaninvoices_filestoimport';
	$sql .= " WHERE import_key = '" . $db->escape($marker) . "'";
	$sql .= " AND filename = '" . $db->escape($filename) . "'";
	$sql .= ' AND entity = ' . ((int) $entity);

	return scaninvoicesDemoFirstId($db, $sql, 'scaninvoices_filestoimport');
}

/**
 * Supplier invoice already in the database.
 *
 * Looked up the way the module itself does when it imports: by supplier and
 * supplier reference, which is what makes an invoice a duplicate here.
 *
 * @param	DoliDB	$db			Database handler
 * @param	int		$supplierId	Supplier of the invoice
 * @param	string	$reference	Reference the supplier put on the invoice
 * @return	int					Id, 0 when absent
 */
function scaninvoicesDemoFindInvoice($db, $supplierId, $reference)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'facture_fourn';
	$sql .= ' WHERE fk_soc = ' . ((int) $supplierId);
	$sql .= " AND ref_supplier = '" . $db->escape($reference) . "'";
	$sql .= ' AND entity IN (' . getEntity('facture_fourn') . ')';

	return scaninvoicesDemoFirstId($db, $sql, 'facture_fourn');
}

/**
 * Demonstration user, looked up by its login.
 *
 * @param	DoliDB	$db		Database handler
 * @param	string	$login	Login of the user
 * @return	int				Id, 0 when absent
 */
function scaninvoicesDemoFindUser($db, $login)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'user';
	$sql .= " WHERE login = '" . $db->escape($login) . "'";

	return scaninvoicesDemoFirstId($db, $sql, 'user');
}

/**
 * Demonstration group, looked up by its name.
 *
 * @param	DoliDB	$db		Database handler
 * @param	int		$entity	Entity to restrict to
 * @return	int				Id, 0 when absent
 */
function scaninvoicesDemoFindGroup($db, $entity)
{
	$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'usergroup';
	$sql .= " WHERE nom = '" . $db->escape(SCANINVOICES_DEMO_GROUP) . "'";
	$sql .= ' AND entity = ' . ((int) $entity);

	return scaninvoicesDemoFirstId($db, $sql, 'usergroup');
}

/**
 * First row id a query answers, or 0.
 *
 * @param	DoliDB	$db		Database handler
 * @param	string	$sql	Query selecting a rowid column
 * @param	string	$what	Table name, for the journal
 * @return	int				Id, 0 when absent or on failure
 */
function scaninvoicesDemoFirstId($db, $sql, $what)
{
	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog('ScanInvoices demo: lookup in ' . $what . ' failed: ' . $db->lasterror(), LOG_ERR);

		return 0;
	}

	$obj = $db->fetch_object($resql);

	return empty($obj) ? 0 : (int) $obj->rowid;
}

/**
 * Label of the recognition setting of a supplier.
 *
 * Deliberately outside the translations: the purge and the re-entrance look a
 * setting up by this label, so a value depending on the language of whoever hit
 * the button would make a second generation write everything again.
 *
 * @param	array	$supplier	Supplier of the data set
 * @return	string				Label, the same in every language
 */
function scaninvoicesDemoSettingLabel($supplier)
{
	return $supplier['name'] . ' (demo)';
}

/**
 * Recognition rules shown on the setting of a supplier.
 *
 * @param	array	$supplier	Supplier of the data set
 * @return	string				YAML block, as the module stores it
 */
function scaninvoicesDemoYaml($supplier)
{
	$lines = array(
		'# ' . $supplier['name'],
		'supplier:',
		'  vat: ' . $supplier['tva'],
		'  town: ' . $supplier['town'],
		'invoice:',
		'  number: "(?:FACTURE|Facture)\\s+([A-Z0-9-]+)"',
		'  date: "([0-9]{2}/[0-9]{2}/[0-9]{4})"',
		'  total: "Total TTC\\s+([0-9 ]+,[0-9]{2})"',
	);

	return implode("\n", $lines);
}

/**
 * Message shown on a document of the queue, according to its status.
 *
 * @param	array	$entry		Entry of the data set
 * @param	array	$supplier	Supplier of the document
 * @return	string				Message, already translated
 */
function scaninvoicesDemoMessage($entry, $supplier)
{
	global $langs;

	switch ((int) $entry['status']) {
		case 2:
			return $langs->transnoentities('ScanInvoicesDemoMessageSuccess', $supplier['name'], $entry['invoice']);
		case 5:
			return $langs->transnoentities('ScanInvoicesDemoMessagePartial', $supplier['name']);
		case 3:
			return $langs->transnoentities('ScanInvoicesDemoMessageError');
		case 1:
			return $langs->transnoentities('ScanInvoicesDemoMessageAnalyzed', $supplier['name']);
		default:
			return $langs->transnoentities('ScanInvoicesDemoMessageWaiting');
	}
}

/**
 * One line of the journal of a run.
 *
 * transnoentities() and not trans(): the page renders these lines through
 * dol_escape_htmltag(), so entities would be escaped a second time and the
 * reader would get "Cat&eacute;gorie" on screen.
 *
 * The [OK] / [WARN] prefixes stay out of the translations: the page reads them
 * back to colour the line, and seed_module.php of the demonstration service
 * reads [WARN] to journal what went wrong.
 *
 * @param	string		$key	Translation key of the sentence
 * @param	string		$object	Translation key of the object kind
 * @param	string|int	$label	Name of the object, or its id when the purge only knows that
 * @param	mixed		$detail	Id, or the reason of the failure
 * @return	string			Line, prefix included
 */
function scaninvoicesDemoLine($key, $object, $label, $detail)
{
	global $langs;

	$prefix = '[OK] ';
	if ($key === 'ScanInvoicesDemoLogFailed') {
		$prefix = '[WARN] ';
	} elseif ($key === 'ScanInvoicesDemoLogFatal') {
		$prefix = '[ERREUR] ';
	}

	return $prefix . $langs->transnoentities($key, $langs->transnoentities($object), $label, $detail);
}

/**
 * Journal line of a deletion.
 *
 * @param	string	$object	Translation key of the object kind
 * @param	int		$count	Number of rows removed
 * @return	string			Line, prefix included
 */
function scaninvoicesDemoDeleted($object, $count)
{
	global $langs;

	return '[OK] ' . $langs->transnoentities('ScanInvoicesDemoLogDeleted', $langs->transnoentities($object), $count);
}
