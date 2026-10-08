<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
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
 */

/**
 * \file    js/myjs.js.php
 * \ingroup mymodule
 * \brief   Example JavaScript.
 *
 * Put detailed description here.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');  // Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');  // Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) { // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

global $conf;

//if (! empty($conf->use_javascript_ajax))
//{

?>

/**
 * Returns a clone of the jquery collection $elem with all invisible (display:none) elements removed
 * @param $elem  A jQuery collection
 * @returns cloned jQuery collection
 */
function stripInvisible($elem) {
    // https://stackoverflow.com/a/28963556/11987795
    var $clone = $elem.clone();
    $('body').append($clone);
    $clone.find('*:not(:visible)').remove();
    $clone.remove();
    return $clone;
}

// Function found here : https://stackoverflow.com/questions/16078544/export-to-csv-using-jquery-and-html
function exportTableToCSV($table, filename) {
					
	var $rows = stripInvisible($table).find('tr:has(th),tr:has(td)'),

	// Temporary delimiter characters unlikely to be typed by keyboard
	// This is to avoid accidentally splitting the actual contents
	tmpColDelim = String.fromCharCode(11), // vertical tab character
	tmpRowDelim = String.fromCharCode(0), // null character

	// actual delimiter characters for CSV format
	colDelim = '"<?php echo (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)?$conf->global->EXPORT_CSV_SEPARATOR_TO_USE:';'); ?>"',
	rowDelim = '"\r\n"',

	// Grab text from table into CSV formatted string
	csv = '"' + $rows.map(function(i, row) {
		var $row = $(row),
		$cols = $row.find('th,td');

		return $cols.map(function(j, col) {
			// InfraS change begin
			// Texte de la cellule : listExportCellText(), commun aux exports CSV et PDF
			return listExportCellText($(col)).replace(/"/g, '""'); // escape double quotes
			// InfraS change end
		}).get().join(tmpColDelim);

	}).get().join(tmpRowDelim)
	.split(tmpRowDelim).join(rowDelim)
	.split(tmpColDelim).join(colDelim) + '"';

	// download file
	downloadFile(this, filename, csv, 'text/csv;charset=utf-8', 'data:application/csv;charset=utf-8,');
}

// InfraS add begin
/**
 * Texte d'une cellule exportée (règles communes aux exports CSV et PDF)
 * @param $col  Cellule th ou td (objet jQuery)
 * @returns string
 */
function listExportCellText($col) {
	var text = "";
	if ($col.find("span.linkobject:not(.hideobject)").length > 0) {
		// Fix sur liste produit si conf MAIN_DIRECT_STATUS_UPDATE active
		text = $col.find("span.linkobject:not(.hideobject)").children().first().attr('title').trim();
	} else if ($col.find('a').length > 0 && $col.find('a')[0].href.indexOf('mailto:') == 0) {
		// Fix mails tronqués dans les listes par dol_trunc dans la fonction dol_print_email
		var link = $col.find('a')[0].href;
		text = link.substr(7);
	} else if ($col.find('select').length > 0) {
		text = $("#"+$col.find('select')[0].id + " option:selected").text();
	} else {
		text = $col.text().trim();
	}

	// Spécifique pour "nettoyer" les données
	// Si texte vide, on cherche une image et on prend le title
	if (text == '' && $col.find('img').length > 0) {
		var imgtitle = $col.find('img').attr('title');
		if (imgtitle != undefined) text = imgtitle.trim();
	}

	return text;
}

/**
 * Export PDF généré par le serveur (TCPDF) : envoie le texte du tableau nettoyé à ajax/export_pdf.php
 * et enregistre le PDF reçu
 * @param $table    Tableau à exporter (objet jQuery, déjà nettoyé)
 * @param filename  Nom du fichier enregistré
 * @param title     Titre imprimé en haut du PDF
 * @param subtitle  Sous-titre imprimé sous le titre ('' si aucun)
 * @param url       URL de ajax/export_pdf.php avec le jeton CSRF
 * @param done      Appelée à la fin : sans argument si le PDF a été enregistré, sinon avec le message d'erreur ('' si aucun)
 */
function exportTableToServerPDF($table, filename, title, subtitle, url, done) {
	var $clean = stripInvisible($table);
	var table = $clean.get(0);
	var rows = [];
	$clean.find('tr').each(function() {
		var $row = $(this);
		// Lignes des tableaux imbriqués ignorées
		if ($row.closest('table').get(0) !== table) return;
		var $cols = $row.children('th,td');
		if ($cols.length == 0) return;
		// h = ligne de titre, t = ligne de total, b = ligne de données
		var kind = $row.hasClass('liste_total') ? 't' : (($row.hasClass('liste_titre') || $row.children('th').length > 0) ? 'h' : 'b');
		var aligns = '';
		var row = [kind, ''];
		$cols.each(function() {
			var $col = $(this);
			row.push(listExportCellText($col).replace(/\s+/g, ' ').trim());
			aligns += ($col.hasClass('right') || $col.attr('align') == 'right') ? 'R' : (($col.hasClass('center') || $col.attr('align') == 'center') ? 'C' : 'L');
		});
		row[1] = aligns;
		rows.push(row);
	});

	var xhr = new XMLHttpRequest();
	xhr.open('POST', url, true);
	xhr.setRequestHeader('Content-Type', 'application/json; charset=utf-8');
	xhr.responseType = 'blob';
	xhr.onload = function() {
		var type = xhr.response ? xhr.response.type : '';
		if (xhr.status == 200 && type.indexOf('application/pdf') == 0) {
			saveAs(xhr.response, filename);
			done();
		} else if (type.indexOf('text/plain') == 0) {
			// Message d'erreur envoyé par le serveur
			var reader = new FileReader();
			reader.onload = function() { done(String(reader.result)); };
			reader.readAsText(xhr.response);
		} else {
			done('');
		}
	};
	xhr.onerror = function() { done(''); };
	xhr.send(JSON.stringify({title: title, subtitle: subtitle, rows: rows}));
}
// InfraS add end

//serialize data function
function objectifyForm(formArray) {
	var returnArray = {};
	for (var i = 0; i < formArray.length; i++) {
		if (formArray[i]['name'].substring(formArray[i]['name'].length - 2) == "[]") {
            var name = formArray[i]['name'].substring(0, formArray[i]['name'].length - 2);
            if (!returnArray.hasOwnProperty(name)) returnArray[name] = [];
            returnArray[name].push(formArray[i]['value']);
        }
        else returnArray[formArray[i]['name']] = formArray[i]['value'];
	}
	return returnArray;
}

//function: download file
function downloadFile(self, filename, data, type, type2)
{
    try {
        blob = new Blob([data], {type: type});
        saveAs(blob, filename);
    }
    catch (e) {
        // Deliberate 'false', see comment below
	if (false && window.navigator.msSaveBlob) {

		var blob = new Blob([decodeURIComponent(data)], {
			type: type
		});

		// Crashes in IE 10, IE 11 and Microsoft Edge
		// See MS Edge Issue #10396033
		// Hence, the deliberate 'false'
		// This is here just for completeness
		// Remove the 'false' at your own risk
		window.navigator.msSaveBlob(blob, filename);

	} else if (window.Blob && window.URL) {
		// HTML5 Blob
		var blob = new Blob([data], {
			type: type
		});
		var url = URL.createObjectURL(blob);

		$(self).attr({
			'download': filename,
			'href': url
		});
	} else {
		// Data URI
		var newData = type2 + encodeURIComponent(data);

		$(self).attr({
			'download': filename,
			'href': newData,
			'target': '_blank'
		});
	}
    }
}

// Function found here : https://github.com/kayalshri/tableExport.jquery.plugin/blob/master/tableExport.js
/*
function exportTableToPDF($table, filename='') {
        var doc = new jsPDF('p','pt', 'a4', true);
        var defaults = {pdfFontSize:8, pdfLeftMargin:20, htmlContent:'false', escape:'false'};
        var startColPosition = defaults.pdfLeftMargin;
        var width = 80;
        
        doc.setFontSize(defaults.pdfFontSize);
        
        // Row Vs Column
        var startRowPosition = 20; var page =1;var rowPosition=0;
        $table.find('tbody').find('tr').each(function(index,data) {
                rowCalc = index+1;
                
                if (rowCalc % 26 == 0){
                        doc.addPage();
                        page++;
                        startRowPosition=startRowPosition+10;
                }
                rowPosition=(startRowPosition + (rowCalc * 10)) - ((page -1) * 280);
                
                $(this).find('th,td').each(function(index,data) {
                        if ($(this).css('display') != 'none'){
                                var colPosition = startColPosition+ (index * width);									
                                doc.text(colPosition,rowPosition, parseString($(this), defaults));
                        }
                });
        });
        
        // Output
        if (filename == '')
        {
            // Output as Data URI
            doc.output('dataurlnewwindow');
        }
        else
        {
            doc.save(filename);
        }
}

//parse string function
function parseString(data, defaults){
				
        if(defaults.htmlContent == 'true'){
                content_data = data.html().trim();
        }else{
                content_data = data.text().trim();
        }

        if(defaults.escape == 'true'){
                content_data = escape(content_data);
        }
        
        return content_data;
}
*/

// Function: export to file
function exportTableToFile(filetype, data, filename) {
        if (filetype == 'sql') {
            exportTableToSQL(data, filename);
        }
        else if (filetype == 'csvfromdb') {
            // download file
            downloadFile(this, filename, data, 'text/csv;charset=utf-8', 'data:application/csv;charset=utf-8,');
        }
}

// Function: export to sql
function exportTableToSQL(data, filename) {
        // download file
	downloadFile(this, filename, data, 'text/plain;charset=utf-8', 'data:application/sql;charset=utf-8,');
}

// Function: export to png
function exportTableToPNG(tablesel, filename='') {
        var self = this;
        var $table = $(tablesel);
        /*
        // hide filter row
        $table.find('tr.liste_titre_filter').hide();//.attr("data-html2canvas-ignore", "true"); // >= 6.0
        $table.find('tr:has(td.liste_titre)').hide();//.attr("data-html2canvas-ignore", "true"); // < 6.0
        */
        html2canvas($table.get(0), {
            onrendered: function (canvas) {
                var image = canvas.toDataURL("image/png");
                var byteString = atob(image.substring(22)); // remove data stuff
                var buffer     = new ArrayBuffer(byteString.length);
                var intArray   = new Uint8Array(buffer);
                
                for ( var i = 0; i < byteString.length; i++ ) {
                    intArray[i] = byteString.charCodeAt(i);
                }
                
                if (filename == '')
                {
                    window.open(image);
                    return;
                }
                /*
                // show filter row
                $table.find('tr.liste_titre_filter').show(); // >= 6.0
                $table.find('tr:has(td.liste_titre)').show(); // < 6.0
                */
                
                $('#dialogforpopup').dialog('close');
                
                // download file
                downloadFile(self, filename, buffer, 'image/png', 'data:image/png,');
            }
        });
}

/*
// Function: export to png from html
function exportTableToPNGFromHTML(table, filename='') {
        var iframe = document.createElement('iframe');
        iframe.width = "100%"; // set iframe width
        $('body').append($(iframe));
        
        setTimeout(function(){
            var iframedoc = iframe.contentDocument||iframe.contentWindow.document;
            $('body',$(iframedoc)).html(table);
            var $head = $(iframedoc).contents().find("head");
            $head.append($("<link/>", { rel: "stylesheet", href: "<?php echo DOL_URL_ROOT.$conf->css; ?>", type: "text/css" }));
            
            var self = this;
            
            //html2canvas(iframedoc.body, {
            //  onrendered: function (canvas) {
            html2canvas(iframedoc.body, {background: '#fff'}).then(
            function (canvas) {
                    var image = canvas.toDataURL();
                    var byteString = atob(image.substring(22)); // remove data stuff
                    var buffer     = new ArrayBuffer(byteString.length);
                    var intArray   = new Uint8Array(buffer);

                    for ( var i = 0; i < byteString.length; i++ ) {
                        intArray[i] = byteString.charCodeAt(i);
                    }
                    
                    // remove iframe
                    $(iframe).remove();
                    
                    // close export dialog
                    $('#dialogforpopup').dialog('close');
                    
                    if (filename == '')
                    {
                        window.open(image);
                        return;
                    }
                    
                    // download file
                    downloadFile(self, filename, buffer, 'image/png', 'data:image/png,');
              //}
            });
        }, 10);
}
*/

<?php

//}
