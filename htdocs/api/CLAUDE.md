# htdocs/api — API REST

API RESTful utilisant le framework Restler. Authentification via l'en-tête DOLAPIKEY.

## Structure des répertoires

| Fichier | Objectif |
|---------|----------|
| index.php | Point d'entrée de l'API (bootstrap Restler) |
| class/api.class.php | Classe de base (DolibarrApi) |
| class/api_access.class.php | Gestionnaire d'authentification (DolibarrApiAccess) |

## Création de points d'entrée API

Utilisez la skill `/dolibarr-api-development` pour les motifs et exemples complets.

Les classes API sont auto-découvertes depuis `{module}/class/api_{module}.class.php`.

## Points d'entrée courants

- `/invoices`, `/orders`, `/thirdparties`, `/products`, `/contacts`, `/users`

## Documentation

Explorateur API : `{dolibarr_url}/api/index.php/explorer`
