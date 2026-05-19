# htdocs/admin — Administration système

Pages d'administration pour la configuration système, les modules et les paramètres.

## Fichiers clés

| Fichier | Objectif |
|---------|----------|
| modules.php | Activation/désactivation des modules |
| const.php | Éditeur de constantes système |
| company.php | Configuration de la société principale |
| dict.php | Gestion des tables de dictionnaire |
| security.php | Paramètres de sécurité |
| mails.php | Configuration des emails |

## Développement de pages admin

Consultez la skill `/dolibarr-page-patterns` pour les templates et motifs de pages admin.

Les pages admin des modules se trouvent généralement à `htdocs/[module]/admin/setup.php`.

## Définition de constantes

```php
// Définir une constante
dolibarr_set_const($db, 'CONSTANT_NAME', $value, 'chaine', 0, '', $conf->entity);

// Supprimer une constante
dolibarr_del_const($db, 'CONSTANT_NAME', $conf->entity);
```

## Gestion des dictionnaires

Les dictionnaires (tables de recherche) sont gérés via `admin/dict.php` avec des entrées dans les tables `llx_c_*`.
