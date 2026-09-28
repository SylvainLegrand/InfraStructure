# SCANINVOICES POUR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Fonctionnalités

ScanInvoices automatise la saisie des factures fournisseur. Vous déposez un PDF (facture, ticket, justificatif), le module envoie le document à un serveur d'OCR pour en extraire les données, puis crée automatiquement la facture fournisseur dans Dolibarr. Si le fournisseur n'existe pas encore, il est créé à la volée à partir du numéro de TVA détecté sur le document.

- Import manuel d'une facture PDF avec assistant pas à pas et OCR à la demande
- Import automatique multi-fichiers par glisser-déposer
- Import nocturne depuis un partage Nextcloud ou Synology DAV
- Création automatique du tiers fournisseur à partir du numéro de TVA (FR, BE, CH)
- Extraction des lignes détaillées avec taux de TVA, ou globalisation par taux
- Création automatique de produits et services lors de l'import des lignes (option)
- Onglet ScanInvoices sur la fiche tiers, pour mémoriser les zones d'analyse propres à un fournisseur
- Tâche planifiée (cron Dolibarr) pour traiter en différé les fichiers déposés en file d'attente
- Compte rendu d'import par email
- Application prioritaire des conditions de règlement de la fiche tiers Dolibarr (option)

Le module fonctionne avec le service d'OCR hébergé par CAP-REL (https://ocr.cap-rel.fr), qui offre 5 analyses gratuites par mois, ou avec un serveur d'OCR auto-hébergé compatible.

D'autres modules sont disponibles sur [Dolistore.com](https://www.dolistore.com).

## Prérequis

- Dolibarr 14 ou version ultérieure
- PHP 7.4 ou version ultérieure
- Extensions PHP : intl, fileinfo, gd, mbstring
- Fonction PHP : finfo_open
- Modules Dolibarr activés : Tiers, Fournisseurs, Produits, Services, Tâches planifiées (Cron)
- Accès Internet sortant vers le serveur d'OCR

## Documentation

La documentation utilisateur complète est publiée sur [doc.cap-rel.fr/scaninvoices/](https://doc.cap-rel.fr/scaninvoices/) : installation, configuration, utilisation au quotidien et questions fréquentes.

## Installation

### Depuis le fichier ZIP et l'interface graphique

- Si vous avez obtenu le module sous forme de fichier zip (par exemple en le téléchargeant depuis la place de marché [Dolistore](https://www.dolistore.com)), allez dans le menu ```Accueil - Configuration - Modules - Déployer un module externe``` et envoyez le fichier zip.

Note : si cet écran vous indique qu'il n'y a pas de répertoire custom, vérifiez votre configuration.

- Dans le répertoire d'installation de Dolibarr, éditez le fichier ```htdocs/conf/conf.php``` et vérifiez que les lignes suivantes ne sont pas commentées :

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Décommentez-les si nécessaire (supprimez les ```//``` en début de ligne) et donnez-leur une valeur cohérente avec votre installation Dolibarr.

    Par exemple :

    - UNIX :
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows :
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### nginx

Si votre Dolibarr tourne sous nginx, une règle particulière est nécessaire pour le point d'entrée ```api.php``` du module. Voici un exemple, à adapter à votre configuration :

```
location /custom/scaninvoices/api.php {
    include /etc/nginx/fastcgi_params;
    # note : configurez la ligne fastcgi_pass selon votre configuration generale de nginx
    #fastcgi_pass  127.0.0.1:9004;
    #fastcgi_pass unix:/var/run/php-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param  SCRIPT_FILENAME  /ou/est/votre/dolibarr/htdocs/custom/scaninvoices/api.php;
    fastcgi_param  BASE_VERB  /scaninvoices/api.php;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
}
```

### Dernières étapes

Depuis votre navigateur :

- Connectez-vous à Dolibarr en tant que super-administrateur
- Allez dans "Configuration" -> "Modules"
- Vous devriez maintenant pouvoir trouver et activer le module

## SAV / Aide / Support

Le support se fait depuis la page "À propos" du module, qui contient un formulaire de contact direct, ou par email à commercial+scaninvoices@cap-rel.fr.

## Licence

### Code principal

GPLv3 ou, à votre convenance, toute version ultérieure. Voir le fichier COPYING pour plus d'informations.

### Documentation

Tous les textes et fichiers readme sont sous licence GFDL.
