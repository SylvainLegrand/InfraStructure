# SCANINVOICES FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

ScanInvoices automates supplier invoice entry. Drop a PDF (invoice, receipt, expense document), the module sends it to an OCR server to extract the data, then creates the supplier invoice in Dolibarr. When the supplier does not exist yet, it is created on the fly from the VAT number found on the document.

- Manual import of a single PDF invoice, with a step by step wizard and on demand OCR
- Automatic multi-file import by drag and drop
- Overnight import from a Nextcloud or Synology DAV share
- Automatic supplier creation from the VAT number (FR, BE, CH)
- Detailed line extraction with VAT rates, or grouping by rate
- Automatic creation of products and services when importing lines (optional)
- ScanInvoices tab on the thirdparty card, to remember the analysis areas of a given supplier
- Scheduled job (Dolibarr cron) to process queued files in the background
- Import report by email
- Payment terms of the Dolibarr thirdparty card applied first (optional)

The module works with the OCR service hosted by CAP-REL (https://ocr.cap-rel.fr), which offers 5 free analysis per month, or with any compatible self-hosted OCR server.

Other modules are available on [Dolistore.com](https://www.dolistore.com).

## Requirements

- Dolibarr 14 or above
- PHP 7.4 or above
- PHP extensions: intl, fileinfo, gd, mbstring
- PHP function: finfo_open
- Enabled Dolibarr modules: Thirdparties, Suppliers, Products, Services, Scheduled jobs (Cron)
- Outgoing Internet access to the OCR server

## Documentation

The full user documentation is published on [doc.cap-rel.fr/scaninvoices/](https://doc.cap-rel.fr/scaninvoices/): installation, setup, daily use and frequently asked questions.

## Translations

Translations can be define manually by editing files into directories *langs*.

## Installation

### nginx

in case of nginx install; please note that special rules you have to add (that is an example, please adapt it)

```
location /custom/scaninvoices/api.php {
    include /etc/nginx/fastcgi_params;
    #note: configurez la ligne fastcgi_pass selon votre configuration générale de nginx...
    #fastcgi_pass  127.0.0.1:9004;
    #fastcgi_pass unix:/var/run/php-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param  SCRIPT_FILENAME  /where/is/your/dolibarr/htdocs/custom/scaninvoices/api.php;
    fastcgi_param  BASE_VERB  /scaninvoices/api.php;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
}
```

### From the ZIP file and GUI interface

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

## Support

Support is available from the "About" page of the module, which carries a direct contact form, or by email to commercial+scaninvoices@cap-rel.fr.

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
