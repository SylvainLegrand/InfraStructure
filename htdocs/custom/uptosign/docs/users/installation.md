---
title: "Installation"
weight: 10
description: "Téléchargement, installation, activation et mise à jour du module UptoSign pour Dolibarr."
---

# Installation

## Téléchargement

Téléchargez le module depuis le [DoliStore](https://www.dolistore.com/) ou depuis votre espace client CAP-REL.

## Installation du module

1. Décompressez l'archive téléchargée
2. Copiez le dossier `uptosign` dans le répertoire `htdocs/custom/` de votre installation Dolibarr
3. Installez les dépendances Composer :

    ```bash
    cd htdocs/custom/uptosign
    composer install --no-dev
    ```

> **Attention** : si le sous-dossier `vendor` est absent ou si la classe `SmalotPDF` n'est pas trouvée, le module affiche un message d'erreur sur la page de configuration. Lancez bien la commande `composer install` avant d'utiliser le module.

## Prérequis techniques

Avant d'activer le module, vérifiez que les extensions PHP suivantes sont installées et chargées :

| Extension | Usage |
|-----------|-------|
| `intl` | Internationalisation et formatage |
| `fileinfo` | Détection du type MIME des fichiers |
| `gd` | Manipulation d'images |
| `mbstring` | Gestion des chaînes de caractères multi-octets |

Le module ECM (Gestion Électronique de Documents) de Dolibarr doit également être activé. UptoSign en dépend pour le stockage et la gestion des fichiers joints.

## Sécurité Dolibarr

UptoSign vérifie que la configuration de sécurité de votre Dolibarr est correcte. Le système de chiffrement des mots de passe et les clés de sécurité doivent être configurés. Si ce n'est pas le cas, le module affiche un avertissement et bloque l'accès à la configuration.

Consultez la [documentation technique de sécurité](https://doc.cap-rel.fr/projet_dolibarr/securite) pour mettre votre Dolibarr en conformité.

## Activation

1. Connectez-vous en tant qu'administrateur Dolibarr
2. Allez dans **Accueil > Configuration > Modules/Applications**
3. Recherchez "UptoSign" dans la liste
4. Cliquez sur le bouton d'activation

![Page d'activation du module UptoSign dans la liste des modules Dolibarr](screenshots/activation-module.webp)

Le module crée automatiquement les tables nécessaires en base de données.

## Premier paramétrage

Après activation, rendez-vous dans **Accueil > Configuration > Modules > UptoSign** pour effectuer le paramétrage initial. Le module présente plusieurs onglets de configuration détaillés dans la page [Configuration](/uptosign/configuration).

Les étapes minimales pour démarrer sont :

1. **Configurer le serveur** (onglet Serveur) — Choisissez le serveur (production ou démonstration), renseignez vos identifiants et testez la connexion.
2. **Définir l'utilisateur par défaut** (onglet Paramètres) — Sélectionnez l'utilisateur Dolibarr qui sera associé aux actions techniques du module.
3. **Créer une configuration de document** (onglet Modèles de documents) — Associez un modèle de document PDF à un type d'objet Dolibarr avec les positions de sceau et de signature.

## Mise à jour

1. Remplacez le dossier `uptosign` par la nouvelle version
2. Relancez `composer install --no-dev`
3. Reconnectez-vous à Dolibarr : les mises à jour de base de données sont appliquées automatiquement
4. En cas d'incohérence de version, désactivez puis réactivez le module
