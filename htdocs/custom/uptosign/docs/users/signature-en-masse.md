---
title: "Signature en masse"
weight: 40
description: "Mode opératoire complet pour faire signer un même document par un grand nombre de signataires indépendants via UptoSign."
---

# Signature en masse

La signature en masse permet d'envoyer **un même document PDF** à plusieurs signataires, **chacun signant indépendamment** sur son propre exemplaire. Chaque destinataire reçoit son lien personnel, valide son identité (e-mail ou SMS) et signe sa copie sans voir les autres signataires.

Cette page détaille le mode opératoire complet, de la création de l'enveloppe jusqu'au suivi des destinataires.

## Avant de commencer

### Quand utiliser la signature en masse ?

- Faire signer un règlement intérieur, une charte ou un avenant à tous les salariés
- Faire signer un PV d'assemblée générale aux adhérents d'une association
- Faire signer une attestation aux participants d'une formation ou d'un événement
- Faire signer un document type (NDA, conditions générales) à un lot de contacts ou de tiers

### Différence avec la signature classique

| Signature classique (onglet d'un document) | Signature en masse (enveloppe dédiée) |
|---|---|
| Liée à un objet Dolibarr (devis, contrat, etc.) | Document libre, non rattaché à un objet métier |
| Un ou plusieurs signataires sur le **même** PDF | Un PDF par destinataire (copies indépendantes) |
| Signataires choisis parmi les contacts liés à la fiche | Signataires construits depuis 7 sources différentes |
| Lancée depuis l'onglet "Signature électronique" | Lancée depuis le menu **GED > Signatures multiples** |

### Limite par envoi

> **Important** : la limite est de **30 destinataires maximum** par enveloppe. Au-delà, le bouton d'envoi est masqué et un message d'avertissement est affiché. Si vous devez faire signer plus de 30 personnes, créez plusieurs enveloppes successives à partir du même PDF.

### Pré-requis

- Le module UptoSign est installé, activé et configuré (voir [Installation](installation.md) et [Configuration](configuration.md))
- Vous disposez du PDF à faire signer (fichier déjà préparé sur votre poste)
- Vous avez les droits **Lire les listes de signatures** et **Créer/modifier les listes** (voir la section Permissions de la page [Utilisation](utilisation.md))

## Vue d'ensemble du processus

Le processus se déroule en 5 étapes :

1. **Créer l'enveloppe** (statut : Brouillon)
2. **Téléverser le PDF** à signer
3. **Ajouter les destinataires** depuis une ou plusieurs sources
4. **Valider l'enveloppe** (Brouillon -> Validé)
5. **Positionner le sceau et la signature, puis envoyer**

Tant que l'enveloppe est en statut **Brouillon**, vous pouvez modifier le titre, ajouter ou retirer des destinataires, changer le PDF. Une fois **Validée**, plus aucune modification de la liste n'est possible. Après envoi, le statut passe à **Envoyé partiellement** puis **Envoyé complètement** lorsque toutes les procédures individuelles ont été lancées.

## Étape 1 : créer l'enveloppe

1. Dans le menu latéral gauche, ouvrez **GED > Signatures multiples**
2. Cliquez sur **Nouveau**
3. Renseignez le **titre** de l'enveloppe (sert de référence interne et apparaît dans l'historique)
4. Validez la création

L'enveloppe est créée en statut **Brouillon**. Vous arrivez sur sa fiche, qui affiche plusieurs onglets : **UptoSign** (fiche principale), **Documents**, **Destinataires**, **Position signature**, **Note**, **Contacts**, **Événements**.

## Étape 2 : téléverser le PDF à signer

1. Ouvrez l'onglet **Documents**
2. Téléversez le fichier PDF à faire signer (un seul PDF par enveloppe, vous pouvez en charger plusieurs et choisir lequel utiliser à l'étape 5)

> **Conseil** : le PDF doit être finalisé avant le téléversement. Il sera renvoyé tel quel à chaque destinataire, avec uniquement le sceau et la zone de signature ajoutés par UptoSign.

## Étape 3 : ajouter les destinataires

Ouvrez l'onglet **Destinataires**. La page liste les **sources** disponibles pour ajouter des signataires. Vous pouvez en combiner plusieurs sur la même enveloppe.

### Source : Contacts (de tiers)

Ajoute les contacts (prospects, clients, fournisseurs) qui ont une adresse e-mail valide.

- Filtres disponibles : par catégorie de contact, par catégorie de tiers, par fonction, etc.
- Module requis : **Tiers** (toujours actif)

### Source : Tiers

Ajoute directement les tiers (sociétés) dont la fiche porte une adresse e-mail.

- Filtres disponibles : par catégorie, par statut prospect/client/fournisseur, par pays, etc.
- Module requis : **Tiers**

### Source : Utilisateurs

Ajoute des utilisateurs internes Dolibarr (salariés). Utile pour les chartes internes, règlements, etc.

- Seuls les utilisateurs actifs et disposant d'une adresse e-mail sont éligibles
- Module requis : **Utilisateurs** (toujours actif)

### Source : Adhérents

Ajoute des adhérents d'une association ou d'un groupe.

- Filtres disponibles : par type d'adhérent, par statut (à jour, en attente, etc.)
- Module requis : **Adhérents** (Membres)

### Source : Participants à un événement

Ajoute les participants inscrits à un événement (module **Organisation d'événements**).

- Filtre obligatoire : l'événement à cibler
- Module requis : **Organisation d'événements** (eventorganization)

### Source : Import depuis un fichier

Téléverse un fichier texte (CSV) listant les destinataires.

**Format attendu** (un destinataire par ligne, séparateur point-virgule) :

```
email;nom;prenom;autre
```

- **email** : obligatoire, doit être un e-mail valide
- **nom** : optionnel
- **prenom** : optionnel
- **autre** : optionnel (champ libre, conservé tel quel pour mémo ou tri ultérieur)

Exemple :

```
jean.dupont@example.com;Dupont;Jean;
marie.martin@example.fr;Martin;Marie;service comptabilite
contact@societe.com;;;ligne sans nom ni prenom
```

Règles :

- Les lignes avec un e-mail invalide arrêtent l'import et signalent une erreur
- Les e-mails strictement consécutifs identiques sont dédoublonnés
- Le fichier doit être en encodage UTF-8 pour gérer correctement les accents

> **Note** : ce format ne contient pas de colonne mobile. Pour les destinataires importés par fichier, la double authentification se fait par e-mail uniquement (pas de SMS).

### Source : Saisie manuelle

Permet de coller ou saisir directement la liste dans une zone de texte (multi-ligne).

**Format attendu** (une ligne par destinataire) :

```
email;nom;prenom;autre
```

Le séparateur **tabulation** est aussi accepté, ce qui permet un copier-coller direct depuis un tableur (Excel, LibreOffice Calc) :

```
email	nom	prenom	autre
```

Les mêmes règles de validation s'appliquent : e-mail valide obligatoire, accents UTF-8, dédoublonnage des lignes identiques.

### Vérifier la liste

Après chaque ajout, le nombre de destinataires de l'enveloppe est mis à jour en haut de la fiche. Vous pouvez :

- Cliquer sur un destinataire pour voir le détail
- Supprimer un destinataire individuel
- Purger toute la liste via le bouton **Vider la liste**
- Exporter la liste au format CSV (utile pour archivage ou contrôle)

> **Rappel** : tant que vous n'avez pas dépassé 30 destinataires, le bouton d'envoi sera disponible à l'étape 5. Au-delà, créez plusieurs enveloppes.

## Étape 4 : valider l'enveloppe

Une fois la liste constituée, retournez sur l'onglet **UptoSign** (fiche principale) et cliquez sur **Valider**. L'enveloppe passe en statut **Validé**.

À ce stade :

- La liste des destinataires est **figée** (plus d'ajout ni de suppression possible)
- Le titre de l'enveloppe est figé
- Vous pouvez toujours revenir en arrière via **Remettre en brouillon** si vous avez les droits suffisants

## Étape 5 : positionner le sceau, la signature et envoyer

1. Ouvrez l'onglet **Position signature**
2. Si plusieurs PDF ont été téléversés, sélectionnez celui à utiliser dans le sélecteur en haut de page
3. **Positionnez le sceau UptoSign** : déplacez l'étiquette du sceau sur l'aperçu du document, ou saisissez les coordonnées (X, Y en millimètres depuis le coin supérieur gauche) et le numéro de page. Le sceau est obligatoire.
4. **Positionnez la zone de signature** : déplacez l'étiquette de signature à l'endroit où chaque destinataire devra signer. Cette position sera la même pour tous les signataires (chacun signe à cet emplacement sur sa copie).
5. Renseignez le **titre du dossier et du mail** (texte envoyé aux destinataires)
6. Cliquez sur **Lancer la demande de signature auprès de X destinataires**

> **Conseil** : le numéro de page peut être négatif pour compter en partant de la fin (`-1` = dernière page). Pratique si le nombre de pages varie selon le destinataire.

### Ce qui se passe lors de l'envoi

Pour chaque destinataire de la liste, le module crée **une procédure UptoSign indépendante** :

- Un fichier PDF spécifique est généré (nom suffixé par l'adresse e-mail du destinataire pour garantir l'unicité)
- Une demande de signature est envoyée au serveur UptoSign
- Le destinataire reçoit son e-mail avec son lien personnel
- L'enveloppe passe en statut **Envoyé partiellement** pendant l'envoi, puis **Envoyé complètement** une fois toutes les procédures lancées

Chaque procédure individuelle est ensuite suivie comme une signature classique, visible dans l'historique **GED > UptoSign**.

> **Important** : l'envoi est **idempotent**. Si vous rafraîchissez la page (F5) ou cliquez sur le bouton retour du navigateur après un envoi réussi, vous serez redirigé vers la fiche de l'enveloppe. Aucune procédure ne sera relancée en double.

## Suivi des destinataires

Après envoi, depuis la fiche de l'enveloppe :

- L'onglet **Destinataires** affiche le statut individuel de chaque procédure (envoyée, signée, refusée, expirée, erreur)
- Chaque ligne permet d'accéder à la procédure UptoSign correspondante via l'historique
- L'export CSV de la liste est conservé et peut être téléchargé pour archivage

Le suivi global passe ensuite par l'historique **GED > UptoSign** où chaque procédure individuelle apparaît avec :

- Le destinataire
- L'état actuel (En attente, Signé, Refusé, Expiré, etc.)
- Les dates de relance et de signature

## Cas particuliers

### Renvoyer la demande à un destinataire

Le renvoi se fait individuellement depuis l'historique **GED > UptoSign**. Ouvrez la procédure du destinataire concerné et utilisez les fonctions de relance habituelles (modèle e-mail "Rappel").

### Annuler une procédure individuelle

Depuis l'historique, ouvrez la procédure du destinataire et utilisez l'action **Annuler**. La permission **Annuler un processus de signature** est requise.

### Vérifier l'intégrité d'un document signé

Une fois un destinataire ayant signé, son document signé est rapatrié dans Dolibarr (selon la configuration du module). La vérification de l'empreinte (hash) se fait depuis l'historique, ligne par ligne.

### Que faire si un envoi échoue ?

Si un destinataire a une adresse e-mail invalide ou si le serveur UptoSign rejette la demande, l'envoi s'arrête pour ce destinataire et un message d'erreur est affiché. Les autres destinataires de la liste ne sont pas affectés (chaque envoi est indépendant).

L'enveloppe reste consultable et l'export CSV inclut la colonne **error_text** indiquant le motif d'erreur ligne par ligne.

## Bonnes pratiques

- **Vérifiez la qualité des adresses e-mail** avant la validation : un import depuis un export CSV mal nettoyé peut générer beaucoup d'erreurs
- **Faites un test avec 1 ou 2 destinataires** avant un envoi en lot (créez une première enveloppe de test)
- **Conservez l'export CSV** de la liste de destinataires comme trace de l'envoi (la liste est figée mais Dolibarr ne fait pas d'archivage automatique de cet export)
- **Préférez plusieurs enveloppes plus petites** plutôt qu'une grosse au plafond, pour faciliter le suivi et le renvoi sélectif en cas de besoin
- **Documentez le sujet** dans le titre de l'enveloppe (ex. : "Charte informatique 2026 - salaries siege") pour faciliter la recherche dans l'historique

## Voir aussi

- [Utilisation](utilisation.md) - Vue d'ensemble du module et signature classique
- [Configuration](configuration.md) - Paramétrage du module (signature locale, modèles, etc.)
- [FAQ](faq.md) - Questions fréquentes
