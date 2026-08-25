# Changelog
Le format du fichier est basé sur [Tenez un ChangeLog](http://keepachangelog.com/fr/1.0.0/).

## [Non Distribué]

## [14.5.0] - 25-08-2026
- Ajout d'un nouvel onglet d'administration "SMTP2GO" : saisie de la clé API, création/édition des utilisateurs SMTP2GO liés à un utilisateur Dolibarr, création de sous-comptes.
- Ajout d'une vérification de la robustesse du mot de passe à la création d'un utilisateur SMTP2GO.
- Correction (sécurité) : les mots de passe SMTP et IMAP ainsi que les secrets OAuth2 enregistrés pour chaque utilisateur sont désormais chiffrés en base de données au lieu d'être stockés en clair ; la lecture reste compatible avec les valeurs déjà enregistrées en clair, qui sont rechiffrées automatiquement à la prochaine sauvegarde
- Correction (sécurité) : durcissement du contrôle d'accès du point d'entrée AJAX de configuration OAuth2 (`ajax/oauthsetup.php`)

## [14.4.18] - 19-03-2026
- FIX : Multismtp bloquait le fait de déplacer une ligne de propal/commande/etc.

## [14.4.17] - 20-02-2026
- Remplacement de la sélection AJAX du dossier IMAP par un formulaire standard Dolibarr (édition en ligne avec validation)
- Suppression du point d'entrée AJAX `ajax/updateImapFolder.php`
- Chargement des dossiers IMAP en amont de la page (avant les actions) pour une meilleure gestion des erreurs
- Correction de l'utilisation de `setEventMessage` remplacé par `setEventMessages` (standard Dolibarr)

## [14.4.16] - 17-02-2026
- Correction de la selection d'un fournisseur OAuth2 Dolibarr (pré-enregistré) lors de la modification personalisé des paramètres de connection email de l'utilisateur (si authorisé)
- Correction de l'autorisation de modifier les paramètres email de l'utilisateur
- Correction du chargement du token OAuth2 pour la fiche utilisateur et l'utilisateur connecté 
- Ajout d'un CRON pour rafraîchir tous les tokens OAuth2 (Dolibarr et Multismtp) expirés
- Ajout de la constante `MULTISMTP_CRON_REFRESH_TOKEN_DAYS` (nombre de jours après expiration, défaut 30) configurable dans la page d'administration du module
- Chargement systématique des traductions du module dans le hook `updateSession` (correction de l'affichage des traductions sur les pages CRON)

## [14.4.15] - 11-02-2026
- Correction du support de l'OAuth2 (Microsoft et autre)

## [14.4.14] - 10-12-2025
- Ajout du support de l'OAuth2

## [14.4.13] - 12-02-2026
- Nouvelle page About.

## [14.4.12] - 07-06-2024
- Ajout version de PHP.

## [14.4.11] - 04-06-2024
- Changement marque, page support, ajout CI.

## [14.4.10] - 26-02-2024
- Ajout de l'option MULTISMTP_SENT_ONLY_FROM_CARD pour fix certains comportements fautifs

v14.4.9 (26 fevrier 2024)
=====================
- Changement de numérotation

v1.4.8 (19 fevrier 2024)
=====================
- Correctif: gestion des tokens CSRF dans les formulaires utilisateur.

v1.4.7 (08 decembre 2023)
=====================
- Correctif: autoriser les envois d'emails depuis RequestManager.

v1.4.6 (6 juin 2023)
=====================
- Change les informations smtp seulement si l'emeteur de l'envoi du mail est de type 'user'

v1.4.5 (31 mars 2022)
=====================
- Prise en compte de swiftmailer dansla sauvegarde des mail dans IMAP

v1.4.4 (28 mai 2021)
=====================
- Prise en compte de réception IMAP des mails sur les fiches utilisateurs, contacts, contrats, ...

v1.4.3 (13 janvier 2021)
=====================
- Correction d'un fichier requis non inclus

v1.4.2 (21 juil. 2020)
=====================
- Compatibilité Easya 2020

v1.4.1 (25 mar. 2019)
=====================
- Fixed escape text in javascript on users config page

v1.4 (14 apr. 2017)
=====================
- Added support for supplier quotes emails
- Little cosmetic changes to adapt to Dolibarr 5.0 interface
- Fixed warning error message when trying to enable the module if the SMTP socket library was enabled

v1.3 (14 sep. 2016)
=====================
- Ensure compatibility with Dolibarr 4.0
- Added support for STARTTLS
- Removed SMTP server check when editing the configuration due to bug #5750 that caused the web server to hang
- Fixed a problem that prevented from showing up IMAP folders to the user.
- Reduced IMAP connection timeouts and changed the way users select their IMAP mailbox folder.

v1.2 (2 apr. 2016)
=====================
- Added support for connecting to IMAP servers with self-signed certificates
- Added support for IMAP function working with "PHP mail function" email method
- Fixed an error that caused SMTP errors when an user with no SMTP user or password tried to send an email
- Fixed some cosmetic errors
- Ensure compatibility with Dolibarr 3.9
- Added some configuration checks in module configuration page

v1.1.1 (29 jun. 2015)
=====================
- Corrected an error when loading user configuration page if module was placed in custom folder
- Corrected SQL errors when upgrading from previous versions

v1.1 (28 jun. 2015)
=====================
- New IMAP function that allows saving several emails to the mail server
- Corrected some XSS problems present in previous versions

v1.0.3 (16 may. 2015)
=====================
- Manually adding MAIN_ACTIVATE_UPDATESESSIONTRIGGER constant is no longer necessary
- Added support for 3.7.1 Dolibarr version

v1.0.2 (5 mar. 2015)
=====================
- Fixed an error when loading from a non-custom directory
- Administrators can now change SMTP credentials for every user

v1.0.1 (28 feb. 2015)
=====================
- Fixed a problem when activating the module for the 2nd time
- Fixed module load when loading from custom directory
- Moved module to "Other modules" category


[Non Distribué]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/compare/14.4.18...HEAD
[14.4.18]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.18
[14.4.17]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.17
[14.4.16]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.16
[14.4.15]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.15
[14.4.14]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.14
[14.4.13]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.13
[14.4.12]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.12
[14.4.11]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.11
[14.4.10]: https://git.open-dsi.fr/dolibarr-extension/dolibarr_module_multismtp/commits/14.4.10