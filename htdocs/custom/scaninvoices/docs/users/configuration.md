---
title: "Configuration"
weight: 20
description: "Paramétrer ScanInvoices : connexion au serveur d'OCR, options d'import, partage réseau."
---

# Configuration

La configuration du module est organisée en quatre onglets accessibles depuis la fiche du module (icône d'engrenage dans la liste des modules) :

- **Compte** : connexion au serveur d'OCR
- **Réglages** : paramètres généraux d'import et nommage des fichiers
- **Partage (import distant)** : import automatique depuis un dossier réseau
- **Import des lignes** : extraction détaillée des lignes de facture

![Onglets de configuration du module ScanInvoices avec Compte, Reglages, Partage et Import des lignes](screenshots/onglets-configuration.webp)

## Onglet Compte

C'est l'onglet d'accueil de la configuration. Il sert à connecter votre Dolibarr au serveur d'OCR qui va analyser les PDF.

| Paramètre | Description |
|-----------|-------------|
| Adresse du serveur d'OCR | URL du serveur. Par défaut `https://ocr.cap-rel.fr` qui offre 5 analyses gratuites par mois. Vous pouvez aussi pointer vers un serveur auto-hébergé. |
| Adresse mail | Email du compte sur le serveur d'OCR. Reprend par défaut l'email de la société configuré dans Dolibarr. |
| Mot de passe du webservice | Au moins 6 caractères. Si l'adresse email n'a pas encore de compte, il sera créé avec ce mot de passe. Si le compte existe avec un autre mot de passe, un lien de réinitialisation s'affiche. |

Après avoir saisi ces trois champs, cliquez sur **Vérifier la connexion**. Le module tente de se connecter, crée le compte si besoin, et stocke automatiquement la clé d'API. Un message vert confirme la réussite.

L'adresse IP publique de votre serveur Dolibarr est affichée en haut de la page : conservez-la pour échanger avec le support en cas de problème de connexion.

![Formulaire de configuration du compte OCR avec les champs URI, email et mot de passe](screenshots/compte-ocr.webp)

## Onglet Réglages

Cet onglet rassemble les options de comportement général du module.

| Paramètre | Description |
|-----------|-------------|
| Référence à utiliser comme nom de pièce jointe | Choisissez **Référence de l'objet Dolibarr** (le PDF est renommé avec la référence interne, ex. `FACF2401-0001.pdf`) ou **Référence de la facture fournisseur** (le PDF prend le numéro de facture présent sur le document). |
| Préfixe à ajouter au nom du fichier | Texte ajouté au début du nom de la pièce jointe attachée à la facture. |
| Produit utilisé par défaut pour la création de facture | Produit ou service utilisé pour créer la ligne unique d'une facture quand l'extraction détaillée des lignes est désactivée ou échoue. |
| Produit utilisé pour les frais de livraison | Produit/service associé automatiquement quand des frais de transport sont détectés sur la facture. |
| Désactiver l'affichage des messages d'alertes | Masque les avertissements pendant l'import. À n'utiliser qu'une fois le module rodé : sans alertes, vous perdez le détail en cas d'échec. |
| Forcer la date de règlement, le moyen et le compte selon la fiche tiers | Si coché, les conditions de règlement enregistrées sur la fiche tiers Dolibarr sont prioritaires sur les informations présentes sur la facture (date de paiement, mode, compte). |

> **Astuce :** un produit/service par défaut spécifique à un fournisseur peut être défini sur l'onglet **ScanInvoices** de sa fiche tiers. Il prend le pas sur le produit par défaut global de cet onglet.

![Formulaire des reglages generaux avec produit par defaut et options diverses](screenshots/reglages-generaux.webp)

## Onglet Partage (import distant)

Cet onglet active l'import automatique de factures déposées dans un dossier réseau (Nextcloud, Synology DAV). Une tâche planifiée Dolibarr passe régulièrement chercher les nouveaux fichiers et les soumet à l'OCR.

| Paramètre | Description |
|-----------|-------------|
| Import de factures depuis un partage réseau | Case à cocher pour activer la fonctionnalité. |
| Type de partage | Nextcloud ou Synology DAV. |
| Adresse du partage réseau | URL complète du partage. Pour Nextcloud, format `https://cloud.exemple.fr/index.php/s/IDENTIFIANT`. |
| Port | Port de connexion si différent du port standard. |
| Identifiant et Mot de passe | Renseignez ces champs si le partage est protégé par authentification. |
| Adresse email | Adresse à laquelle envoyer les comptes rendus d'import automatique. |

Une fois les paramètres saisis, cliquez sur **Vérifier la connexion** : le module liste le contenu du partage pour valider la configuration. La tâche planifiée associée s'exécute toutes les 3 heures par défaut.

![Formulaire de configuration du partage reseau avec type Nextcloud et URL du partage](screenshots/partage-reseau.webp)

## Onglet Import des lignes

Cet onglet contrôle la façon dont les lignes détaillées de la facture sont extraites et importées.

| Paramètre | Description |
|-----------|-------------|
| Désactiver l'import automatique des lignes de factures | Si coché, la facture est créée avec une seule ligne globale (montant total + produit par défaut). Sinon, le module tente d'extraire ligne par ligne. |
| Création d'un produit dans la base Dolibarr | Si coché, un produit/service est créé automatiquement quand une ligne de facture mentionne une référence inconnue. |
| Mise à jour du libellé du produit à l'import | Si coché, le libellé du produit existant est remplacé par celui présent sur la facture lors de chaque import. |
| Mise à jour du prix d'achat fournisseur à l'import | Si coché, le prix unitaire HT de chaque ligne importée devient le prix d'achat du produit chez ce fournisseur (onglet **Fournisseurs** de la fiche produit), pour une quantité de 1. |
| Choix Produits ou Services comme type créé par défaut | Type appliqué aux produits/services créés automatiquement. |

> **Note :** la création automatique de produits est utile pour les fournisseurs récurrents (papeterie, hébergement) où vous voulez retrouver l'historique par référence. Pour les achats ponctuels, désactiver l'option et utiliser le produit par défaut suffit.

> **Note sur le prix d'achat :** seuls les produits identifiés par leur référence (ou créés pendant l'import) reçoivent le prix. Le produit/service par défaut du fournisseur, lui, n'est jamais modifié : c'est un article fourre-tout partagé par toutes les lignes non reconnues. Chaque nouvel import écrase le prix précédent pour ce couple produit/fournisseur, l'historique reste consultable sur la fiche produit. Enregistrer ce prix permet aussi aux imports suivants de retrouver le produit grâce à sa référence fournisseur.

![Formulaire de configuration de l'import des lignes avec la case desactiver import lignes](screenshots/import-lignes.webp)

## Tâches planifiées

Le module installe deux tâches planifiées dans **Accueil > Configuration > Tâches planifiées**.

- **ScanInvoices service d'import automatique** : exécute toutes les 3 heures, traite les fichiers déposés en file d'attente (mode différé) ou présents dans le dossier réseau partagé. Activée par défaut.
- **ScanInvoices auto pay supplier invoices (CB/LCR/PREV)** : tâche optionnelle de marquage automatique du règlement de certaines factures. Désactivée par défaut.

Vous pouvez ajuster la fréquence depuis l'écran des tâches planifiées Dolibarr.

## Onglet ScanInvoices sur la fiche tiers

Sur chaque fiche fournisseur, un onglet **ScanInvoices** est ajouté. Il permet de :

- Définir un produit/service par défaut pour ce fournisseur uniquement
- Visualiser et ajuster les zones d'analyse mémorisées par le serveur d'OCR pour ce fournisseur
- Améliorer la reconnaissance au fil des imports successifs

Voir [Utilisation au quotidien](/scaninvoices/utilisation) pour la prise en main.
