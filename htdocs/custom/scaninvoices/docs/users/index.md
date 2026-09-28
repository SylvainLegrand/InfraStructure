---
title: "ScanInvoices"
weight: 1
description: "Module Dolibarr d'import automatique des factures fournisseur PDF par OCR avec création du tiers et de la facture sans saisie."
category: "Finances; Comptabilité"
type: "module-dolibarr"
---

# ScanInvoices

ScanInvoices est un module Dolibarr qui automatise la saisie des factures fournisseur. Vous déposez un PDF (facture, ticket, justificatif), le module envoie le document à un serveur d'OCR pour en extraire les données puis crée automatiquement la facture fournisseur dans Dolibarr. Si le fournisseur n'existe pas encore, il est créé à la volée à partir du numéro de TVA détecté sur le document.

Le module fonctionne avec le service d'OCR hébergé par CAP-REL (https://ocr.cap-rel.fr) qui offre 5 analyses gratuites par mois, ou avec un serveur d'OCR auto-hébergé compatible. Trois modes d'import sont disponibles : un import manuel pas à pas avec aperçu du document, un import automatique par glisser-déposer multi-fichiers, et un import automatique nocturne depuis un dossier réseau partagé (Nextcloud, Synology DAV).

Une fois la facture créée, le PDF est attaché à la fiche, les lignes peuvent être détaillées ou globalisées par taux de TVA, et le module mémorise les zones d'analyse spécifiques à chaque fournisseur pour améliorer la reconnaissance lors des imports suivants.

## Fonctionnalités principales

- Import manuel d'une facture PDF avec assistant pas à pas et OCR à la demande
- Import automatique multi-fichiers par glisser-déposer
- Import nocturne depuis un partage Nextcloud ou Synology DAV
- Création automatique du tiers fournisseur à partir du numéro de TVA (FR, BE, CH)
- Extraction des lignes de facture détaillées avec taux de TVA, ou globalisation par taux
- Création automatique de produits/services lors de l'import des lignes (optionnel)
- Onglet ScanInvoices sur la fiche tiers pour mémoriser les zones d'analyse propres à un fournisseur
- Tâche planifiée (cron Dolibarr) pour traiter en différé les fichiers déposés en file d'attente
- Compte rendu d'import par email
- Application des conditions de règlement de la fiche tiers Dolibarr en priorité (option)

## Prérequis

- Dolibarr 14 ou version ultérieure
- PHP 7.4 ou version ultérieure
- Extensions PHP : intl, fileinfo, gd, mbstring
- Fonction PHP : finfo_open
- Modules Dolibarr activés : Tiers, Fournisseurs, Produits, Services, Tâches planifiées (Cron)
- Accès Internet sortant vers le serveur d'OCR (par défaut https://ocr.cap-rel.fr)

## Pour aller plus loin

- [Installation](/scaninvoices/installation)
- [Configuration](/scaninvoices/configuration)
- [Utilisation au quotidien](/scaninvoices/utilisation)
- [Questions fréquentes](/scaninvoices/faq)

![Page d'accueil de la liste des fichiers à importer dans Dolibarr](screenshots/accueil-liste-imports.webp)
