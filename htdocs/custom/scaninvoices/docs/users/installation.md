---
title: "Installation"
weight: 10
description: "Installer et activer le module ScanInvoices dans votre Dolibarr."
---

# Installation

## Téléchargement

Le module est disponible sur le DoliStore CAP-REL : https://shop.cap-rel.fr/

Téléchargez l'archive `module_scaninvoices-x.y.z.zip` correspondant à la dernière version compatible avec votre Dolibarr.

## Pré-requis serveur

Avant l'installation, vérifiez que votre serveur dispose des éléments suivants. Le module les contrôle au premier accès à la page de configuration et affiche un message d'erreur explicite si l'un d'eux manque.

- PHP 7.0 minimum
- Extensions PHP : `intl`, `fileinfo`, `gd`, `mbstring`
- Fonction PHP : `finfo_open`
- Le module Dolibarr **Tâches planifiées (Cron)** doit être activé pour le traitement en arrière-plan
- Les modules **Tiers**, **Fournisseurs**, **Produits** et **Services** doivent être activés
- Une connexion sortante vers `https://ocr.cap-rel.fr` (ou votre propre serveur d'OCR) doit être autorisée

## Installation du module

1. Connectez-vous à Dolibarr avec un compte administrateur.
2. Rendez-vous dans **Accueil > Configuration > Modules/Applications**.
3. Cliquez sur l'onglet **Déployer/Installer un module externe**.
4. Sélectionnez l'archive `.zip` téléchargée puis validez.
5. Dolibarr décompresse l'archive et l'installation se fait automatiquement.

![Onglet Deployer ou installer un module externe avec le champ d'envoi de l'archive ZIP](screenshots/deploiement-zip.webp)

## Activation

1. Dans **Accueil > Configuration > Modules/Applications**, recherchez **ScanInvoices** dans la liste (catégorie Financier).
2. Cliquez sur l'interrupteur pour activer le module.
3. Au premier clic, Dolibarr crée les tables nécessaires en base et installe les permissions.

Le module est désormais accessible depuis le menu **Facturation > Factures fournisseur** où trois nouvelles entrées apparaissent : **Imports**, **Import manuel** et **Import automatique**.

![Liste des modules avec ScanInvoices activé et l'icone d'engrenage de configuration visible](screenshots/module-active.webp)

## Vérification post-installation

1. Sur la ligne du module, cliquez sur l'icône d'engrenage pour ouvrir la configuration.
2. La page de configuration affiche votre adresse IP publique (utile pour le support en cas de blocage réseau).
3. Renseignez votre adresse email et un mot de passe puis cliquez sur **Vérifier la connexion**. Si tout va bien, un message **Connexion avec le serveur établie** apparaît et une clé d'API est créée pour votre compte.
4. Si une erreur de blacklist s'affiche, suivez le lien proposé pour demander le déblocage de votre adresse IP.

Pour le détail complet des paramètres, voir [Configuration](/scaninvoices/configuration).

## Permissions utilisateur

Le module définit trois permissions :

- **Lire** les données ScanInvoices
- **Créer ou modifier** des objets ScanInvoices
- **Supprimer** des objets ScanInvoices

Pour qu'un utilisateur non administrateur puisse importer des factures, attribuez-lui ces droits ainsi que ceux nécessaires pour gérer les tiers, fournisseurs, produits et factures fournisseur dans **Accueil > Utilisateurs et groupes**.

## Mise à jour

1. Téléchargez la nouvelle archive depuis le DoliStore.
2. Désactivez ScanInvoices dans **Accueil > Configuration > Modules/Applications**.
3. Déployez la nouvelle archive comme à l'installation initiale.
4. Réactivez le module : Dolibarr applique les éventuelles mises à jour de base de données et enregistre les nouveaux hooks.

> **Important :** la désactivation puis réactivation est nécessaire à chaque mise à jour pour que les évolutions du schéma de base soient appliquées et que les nouveaux menus apparaissent.

## Désinstallation

Désactivez simplement le module depuis la liste des modules. Les données importées (factures, tiers créés) restent dans Dolibarr et ne sont pas supprimées.
