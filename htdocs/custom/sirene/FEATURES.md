# Fonctionnalités du module Sirene

Ce document décrit les fonctionnalités du module **Sirene** pour Dolibarr.

- **Identifiant** : 163027
- **Éditeur** : Open-DSI (support@open-dsi.fr)
- **Licence** : GNU General Public License v3
- **Dépendances** : modSociete, modAdvanceDictionaries
- **Conflit** : modCodeNaf

---

## Recherche et import depuis l'API SIRENE

Intégration avec l'API SIRENE v3.11 de l'INSEE pour rechercher et récupérer les données officielles des entreprises françaises.

### Critères de recherche

- Raison sociale (avec caractères génériques : `*`, `?`, `~`)
- Numéro SIRET
- Numéro SIREN
- Code NAF
- Numéro RNA (associations)
- Commune / code postal
- Filtre : établissements actifs uniquement
- Filtre : sièges sociaux uniquement

**Priorité au SIREN/SIRET** : lorsqu'un numéro SIREN ou SIRET exact est renseigné, il est utilisé comme unique critère de recherche ; les autres critères (raison sociale, code NAF, RNA, commune, code postal) sont ignorés côté API et désactivés dans le formulaire de recherche, y compris lorsqu'ils sont pré-remplis automatiquement à partir des données déjà connues du tiers (`sirene_search.tpl.php`).

### Données récupérées

- Raison sociale et noms alternatifs (enseignes, marques)
- Numéros SIREN et SIRET
- Code NAF avec libellé
- Adresse complète (voie, code postal, commune, région, pays)
- Coordonnées géographiques (latitude/longitude) — pour intégration ProspectingMap
- Forme juridique
- Numéro TVA intracommunautaire
- Tranche d'effectif
- Statut de l'établissement (actif / fermé)

---

## Création de tiers assistée par SIRENE

- **Formulaire intégré** : affichage d'un formulaire de recherche SIRENE directement sur la page de création du tiers
- **Pré-remplissage automatique** : les champs du formulaire de création sont remplis avec les données récupérées
- **Recherche obligatoire configurable** : possibilité de rendre la recherche SIRENE obligatoire avant la création d'un tiers (prospect, client, fournisseur), configurable par type d'entité
- **Détection des doublons** : avertissement si une entreprise portant le même nom existe déjà dans Dolibarr
- **Génération de nom unique** : ajout automatique de la ville et du NIC au nom si un doublon est détecté (option configurable)

---

## Vérification et mise à jour d'un tiers existant

- **Bouton « Vérification informations tiers »** : ajouté sur la fiche tiers, permet de comparer les données Dolibarr avec celles de l'API SIRENE
- **Comparaison champ par champ** : interface visuelle indiquant pour chaque champ : valeur actuelle / valeur SIRENE / état (identique, sera mis à jour, ne sera pas mis à jour)
- **Mise à jour sélective** : l'utilisateur choisit les champs à mettre à jour avant de valider
- **Détection fermeture** : alerte si l'établissement est signalé fermé auprès de l'INSEE ; relance automatique de la recherche sur le SIREN pour détecter un éventuel déménagement
- **Cohérence SIREN/SIRET** : vérification de la cohérence lors du remplacement d'un tiers (`replaceThirdparty`)

Champs pouvant être mis à jour :
- Raison sociale et noms alternatifs
- Adresse complète
- SIREN, SIRET, NAF, RNA
- Numéro TVA
- Tranche d'effectif
- Forme juridique
- Coordonnées géographiques

---

## Intégration RNA (associations)

- Recherche et récupération de données depuis l'API Asso (registre national des associations)
- Endpoint RNA configurable séparément
- Option de désactivation de la vérification SSL (mode debug)

---

## Gestion des codes NAF

- **Import CSV** : import des codes NAF depuis le fichier `install/data/codenaf.csv`
- **Dictionnaire** : table complète des codes NAF avec libellés
- **Paramétrage CSV** : séparateur, délimiteur de texte et caractère d'échappement configurables
- **Affichage enrichi** : le libellé du code NAF est affiché sur la fiche tiers

---

## Tâches Cron

### `checkAndUpdateSireneInfos` — Vérification automatique des tiers

- **Classe** : `Sirene` (`class/sirene.class.php`)
- **Fréquence recommandée** : toutes les 10 minutes (désactivée par défaut)
- **Fonctionnement** :
  1. Sélectionne les tiers actifs (statut = 1) disposant d'un SIRET, de nationalité française (ou sans pays renseigné), et dont la dernière vérification SIRENE dépasse le seuil de fréquence configuré (défaut : 30 jours)
  2. Interroge l'API SIRENE pour chaque tiers sélectionné
  3. Met à jour automatiquement les champs configurés
  4. Détecte les entreprises fermées et les entreprises introuvables
- **Limitation des requêtes** : traitement par lots avec contrôle du débit (nombre max de requêtes par lot, par fenêtre temporelle)
- **Notifications par e-mail** : envoi d'un récapitulatif si au moins une société est fermée et que la constante `SIRENE_MAIL_TO_SEND` est configurée
- **Journalisation** : toutes les opérations sont tracées avec gestion complète des erreurs

**Champs mis à jour automatiquement (selon configuration)** :
- Raison sociale (`SIRENE_CRON_COMPANY_NAME`)
- Noms alternatifs (`SIRENE_CRON_COMPANY_NAME_ALIAS`)
- Adresse (`SIRENE_CRON_ADRESS`)
- SIREN (`SIRENE_CRON_SIREN`)
- SIRET (`SIRENE_CRON_SIRET`)
- Code NAF (`SIRENE_CRON_NAF`)
- RNA (`SIRENE_CRON_RNA`)
- TVA (`SIRENE_CRON_TVA`)
- Effectif (`SIRENE_CRON_STAFF`)
- Forme juridique (`SIRENE_CRON_JURI_STATUS`)
- Coordonnées géographiques

---

## Triggers (Déclencheurs)

### `COMPANY_CREATE`

- **Fichier** : `core/triggers/interface_99_modSirene_SireneTriggers.class.php`
- **Condition** : création d'un tiers
- **Actions** :
  - Initialise le champ `sirene_update_date` (date du dernier appel SIRENE)
  - Initialise le suivi du statut administratif SIRENE (`sirene_company_admin_status`)

---

## Extra-fields créés automatiquement

Ces champs complémentaires sont créés sur l'objet **tiers** (`societe`) lors de l'activation du module :

| Code | Type | Description | Visibilité |
|------|------|-------------|------------|
| `sirene_update_date` | Date/heure | Date du dernier appel à l'API SIRENE | Liste uniquement |
| `sirene_company_admin_status` | Liste de sélection | Statut administratif de l'entreprise (A = En activité, F = Fermé) | Liste et fiche |
| `sirene_cron_date` | Date/heure | Date du dernier traitement par la tâche planifiée | Liste uniquement |

> **Note** : les champs à visibilité « liste uniquement » sont visibles partout si la version d'Osden est inférieure à 2022.5.3 ou la version de Dolibarr inférieure à 18.0.5.

---

## Dictionnaires

| Dictionnaire | Description |
|--------------|-------------|
| Codes NAF | Liste complète des codes NAF (importée depuis `install/data/codenaf.csv`) |
| Correspondance pays | Mapping entre les pays Dolibarr et les codes pays de l'API SIRENE |
| Correspondance effectifs | Mapping entre les tranches d'effectifs Dolibarr et les codes de l'API SIRENE |

---

## Configuration

### API SIRENE

| Constante | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| `SIRENE_API_URI` | URL de l'API SIRENE INSEE | `https://api.insee.fr/api-sirene/3.11/` |
| `SIRENE_API_BEARER_KEY` | Jeton d'authentification Bearer | — |
| `SIRENE_API_VERIFY_SSL` | Activer la vérification SSL | Désactivé |
| `SIRENE_API_TIMEOUT` | Délai d'expiration des requêtes (secondes) | 10 |
| `SIRENE_API_DEBUG` | Mode debug | Désactivé |
| `SIRENE_VERIFICATION_SIRET_URL` | URL publique de vérification SIRET | — |

### API RNA

| Constante | Description |
|-----------|-------------|
| `SIRENE_API_RNA_URI` | URL de l'API Asso (RNA) |
| `SIRENE_API_RNA_VERIFY_SSL` | Vérification SSL pour l'API RNA |

### Comportement à la création

| Constante | Description |
|-----------|-------------|
| `SIRENE_SEARCH_MANDATORY_FOR` | Liste (prospect, customer, supplier) des types de tiers pour lesquels la recherche SIRENE est obligatoire |
| `SIRENE_ADD_NIC_TOWN_IN_NAME_IF_DUPLICATE` | Ajout automatique de la ville et du NIC au nom si doublon détecté |

### Tâche planifiée

| Constante | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| `SIRENE_MAIL_TO_SEND` | Adresse e-mail destinataire des alertes cron | — |
| `SIRENE_CRON_CHECK_FREQUENCY` | Fréquence de vérification (jours) | 30 |
| `SIRENE_NB_REQUEST_BY_GROUP` | Nombre max de requêtes par lot | 100 |
| `SIRENE_NB_REQUEST_BY_TIME_LIMIT` | Nombre max de requêtes par fenêtre temporelle | 20 |
| `SIRENE_TIME_LIMIT_ALL_REQUEST` | Durée de la fenêtre temporelle (secondes) | 60 |
| `SIRENE_CRON_COMPANY_NAME` | Mettre à jour la raison sociale | — |
| `SIRENE_CRON_COMPANY_NAME_ALIAS` | Mettre à jour les noms alternatifs | — |
| `SIRENE_CRON_ADRESS` | Mettre à jour l'adresse | — |
| `SIRENE_CRON_SIREN` | Mettre à jour le SIREN | — |
| `SIRENE_CRON_SIRET` | Mettre à jour le SIRET | — |
| `SIRENE_CRON_NAF` | Mettre à jour le code NAF | — |
| `SIRENE_CRON_RNA` | Mettre à jour le RNA | — |
| `SIRENE_CRON_TVA` | Mettre à jour le numéro TVA | — |
| `SIRENE_CRON_STAFF` | Mettre à jour l'effectif | — |
| `SIRENE_CRON_JURI_STATUS` | Mettre à jour la forme juridique | — |

### Import codes NAF

| Constante | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| `CODENAF_CSV_SEPARATOR_TO_USE` | Séparateur de champs du CSV | `;` |
| `CODENAF_CSV_ENCLOSURE_TO_USE` | Délimiteur de texte du CSV | `"` |
| `CODENAF_CSV_ESCAPE_TO_USE` | Caractère d'échappement du CSV | `\` |

---

## Intégrations avec d'autres modules

### ProspectingMap

- Conversion automatique des coordonnées Lambert 93 (EPSG:2154) reçues de l'API SIRENE en WGS84
- Prise en charge des projections spécifiques (Martinique UTM 20N, Réunion UTM 40S)
- Renseignement automatique des coordonnées géographiques sur le tiers

### AdvanceDictionaries

- Utilisation du module AdvanceDictionaries pour gérer les dictionnaires de correspondance pays et effectifs

---

## Compatibilité

- **Dolibarr** : à partir de la version 14.0.0
- **PHP** : 7.1 à 8.4
- Wrappers de compatibilité pour `getDolGlobalString()` / `getDolGlobalInt()` sur les versions antérieures à Dolibarr 15.0.0

---

## Gestion des évolutions d'API

Système d'alerte intégré pour prévenir les utilisateurs lors des changements de version ou de serveur de l'API INSEE (exemple : migration du 28/02/2025 vers le nouveau serveur).
