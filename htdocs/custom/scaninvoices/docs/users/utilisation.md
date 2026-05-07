---
title: "Utilisation"
weight: 30
description: "Importer vos factures fournisseur PDF au quotidien : import manuel, automatique et depuis un partage réseau."
---

# Utilisation au quotidien

Une fois ScanInvoices configuré, vous disposez de trois modes d'import dans le menu **Facturation > Factures fournisseur** :

- **Import manuel** : assistant pas à pas avec aperçu, pour les cas particuliers et le rodage des zones d'analyse d'un nouveau fournisseur.
- **Import automatique** : glisser-déposer multi-fichiers, pour le traitement quotidien.
- **Imports** : liste des fichiers déjà reçus (en attente, en cours, traités, en erreur).

![Sous-menu Imports, Import manuel et Import automatique sous Facturation, Factures fournisseur](screenshots/menu-imports.webp)

## Import manuel d'une facture

L'import manuel guide l'utilisateur sur deux étapes. Il est particulièrement utile pour la première facture d'un nouveau fournisseur, car il permet de positionner les zones d'analyse qui seront ensuite mémorisées.

### Étape 1 : choix du fournisseur

1. Ouvrez **Facturation > Factures fournisseur > Import manuel**.
2. Si le fournisseur existe déjà dans Dolibarr, sélectionnez-le dans la liste déroulante.
3. S'il n'existe pas, deux options :
   - Cliquez sur le **+** à droite de la liste pour créer la fiche manuellement.
   - Laissez la liste vide : si la facture comporte un numéro de TVA français, suisse ou belge, le module créera automatiquement le tiers à partir de ce numéro de TVA.
4. Cliquez sur **Suivant**.

> **Note :** la création automatique de fournisseur ne fonctionne que pour les numéros de TVA français, belges et suisses. Pour les autres pays, créez le tiers manuellement avant l'import.

![Etape 1 de l'import manuel avec selection du fournisseur dans la liste deroulante](screenshots/import-manuel-etape1.webp)

### Étape 2 : analyse du document

1. Cliquez sur **Fichier...** pour choisir le PDF à analyser (ou **Photo...** pour prendre une photo depuis un appareil mobile).
2. Le PDF s'affiche à gauche de l'écran. Déplacez les zones rectangulaires sur les emplacements à analyser : numéro de TVA, date, numéro de facture, total HT, total TTC.
3. Si le fournisseur a déjà été utilisé, les zones sont pré-positionnées : vérifiez et ajustez si besoin.
4. Indiquez la **Langue du document** (par défaut français) et le **Produit/Service** à utiliser.
5. Cliquez sur **Lancer l'OCR**. L'analyse prend quelques secondes. Les champs détectés se remplissent à droite.
6. Vérifiez les valeurs extraites. Si une donnée est incorrecte, ajustez la zone et relancez l'OCR.
7. Une fois les valeurs correctes, cliquez sur **Importer dans dolibarr**. La facture fournisseur est créée et le PDF y est attaché.

![Etape 2 de l'import manuel avec apercu PDF a gauche et formulaire de donnees extraites a droite](screenshots/import-manuel-etape2.webp)

### Aide intégrée

Le bouton **Aide** en bas de l'écran déclenche une visite guidée en cinq étapes qui rappelle les actions principales : choix du fichier, positionnement des zones, lancement de l'OCR, vérification, import.

## Import automatique par glisser-déposer

L'import automatique traite plusieurs fichiers d'affilée sans assistant.

1. Ouvrez **Facturation > Factures fournisseur > Import automatique**.
2. Glissez-déposez un ou plusieurs PDF dans la zone prévue, ou cliquez pour ouvrir le sélecteur de fichiers (taille maximale 8 Mo par fichier).
3. Deux files d'attente sont disponibles :
   - **Détection automatique** : analyse immédiate, idéal en journée pour quelques factures.
   - **Plus tard** : les fichiers sont stockés et analysés la nuit prochaine par la tâche planifiée. Utile pour traiter un gros lot sans surcharger le serveur d'OCR pendant les heures ouvrées.
4. Pendant l'analyse, un tableau récapitule l'état de chaque fichier (en attente, fournisseur détecté, facture créée, erreur).
5. Vous pouvez aussi choisir un fournisseur à associer en lot avant le dépôt, si vous savez que tous les fichiers concernent le même tiers.

![Zone de glisser deposer pour l'import automatique avec la table de progression des fichiers](screenshots/import-automatique.webp)

## Import depuis un partage réseau (Nextcloud, Synology DAV)

Si vous avez activé l'import distant dans la [configuration](/scaninvoices/configuration), il vous suffit de déposer les PDF dans le dossier partagé : la tâche planifiée Dolibarr les importe automatiquement toutes les 3 heures et envoie un compte rendu par email.

1. Connectez-vous à votre Nextcloud (ou Synology) et déposez les factures PDF dans le dossier configuré.
2. Patientez la prochaine exécution de la tâche planifiée (par défaut toutes les 3 heures).
3. Consultez la liste **Imports** dans Dolibarr ou ouvrez le compte rendu reçu par email.
4. Les fichiers traités sont marqués comme importés, ceux en erreur sont signalés pour traitement manuel.

## Liste des imports

L'écran **Facturation > Factures fournisseur > Imports** liste tous les fichiers traités ou en cours par le module : numéro, fichier, fournisseur détecté, facture créée, statut, date d'envoi à l'OCR, date de réponse.

Vous pouvez :

- Filtrer et trier la liste par fournisseur, statut, date.
- Cliquer sur une ligne pour voir le détail (PDF d'origine, données extraites, facture créée, erreurs éventuelles).
- Relancer un import en erreur après correction (par exemple, après avoir créé manuellement le fournisseur).

![Liste des fichiers a importer avec colonnes Num, Fichier, Fournisseur, Facture et Statut](screenshots/liste-imports.webp)

## Onglet ScanInvoices sur la fiche tiers

Sur chaque fiche fournisseur, l'onglet **ScanInvoices** permet d'optimiser la reconnaissance pour ce tiers spécifique.

- Définir un **produit/service par défaut** propre à ce fournisseur, qui sera utilisé en priorité lors des imports.
- Consulter et ajuster les **zones d'analyse** mémorisées par le serveur d'OCR à partir des imports précédents.
- Voir l'historique des imports pour ce fournisseur.

Plus vous importez de factures d'un même fournisseur, mieux le module reconnaît ses spécificités (mise en page, position des champs, références produits).

![Onglet ScanInvoices sur la fiche d'un fournisseur avec produit par defaut et zones memorisees](screenshots/onglet-tiers.webp)

## Onglet ScanInvoices sur la fiche facture fournisseur

Une fois la facture créée, un onglet **ScanInvoices** est aussi disponible sur la fiche de la facture fournisseur. Il rappelle :

- Le PDF source utilisé pour l'import
- Les valeurs brutes extraites par l'OCR
- La date d'envoi et la date de réponse du serveur

C'est utile pour comprendre, après coup, comment le module a interprété un document.

## Bonnes pratiques

- Importez les premières factures d'un nouveau fournisseur en mode **manuel** pour caler les zones d'analyse, puis basculez en automatique.
- Si la facture comporte plusieurs taux de TVA, vérifiez que l'option **Désactiver l'import automatique des lignes** correspond à votre besoin (lignes détaillées ou globalisation par taux).
- Sur les serveurs mutualisés, surveillez les messages de blacklist en cas d'échec d'analyse : le bouton de demande de déblocage figure dans le message d'erreur.
- Conservez une copie de vos PDF dans le dossier réseau partagé même après import : ils servent de sauvegarde indépendante de Dolibarr.

## Questions fréquentes

Voir la page [FAQ](/scaninvoices/faq) pour les problèmes courants et leurs solutions.
