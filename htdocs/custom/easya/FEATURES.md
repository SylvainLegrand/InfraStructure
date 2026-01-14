Module Easya
============

Ce module accompagne la distribution 'Easya' de Dolibarr.

Constantes
----------
 
- EASYA_VERSION : Lors de l'activation du module, va lire le fichier `htdocs/VERSION` et remplit la constante EASYA_VERSION avec cette valeur.
- OBLYON_DISABLE_VERSION : 1 (Ne pas afficher la version DLB dans le coin haut droite)
- MAIN_MODULE_SETUP_ON_LIST_BY_DEFAULT : 1 (Afficher la liste des modules en liste et pas en kanban)
- INVOICE_CHECK_POSTERIOR_DATE : 1 (Activer la vérification des dates de facturation)

### Fontawesome

configuration de Fontawesome 6 'light'

- MAIN_FONTAWESOME_DIRECTORY : '/theme/common/fontawesome-6'
- MAIN_FONTAWESOME_FAMILY : 'Font Awesome 6 Pro'
- MAIN_FONTAWESOME_ICON_STYLE : 'fal'
- MAIN_FONTAWESOME_WEIGHT : '300'
  
### InfraspackPlus

désactivation des core changes/modules change de InfraspackPlus

- INFRASPACKPLUS_DISABLED_CORE_CHANGE : 1
- INFRASPACKPLUS_DISABLED_MODULE_CHANGE : 1

Maintenance
--------------

- Affichage d'un bandeau de maintenance lorsqu'un fichier configuré par `EASYA_MAINTENANCE_FILE` est présent. Par défaut (après enregistrement de la config) ce fichier est sur `htdocs/custom/.maintenance`.

Import de configuration par CSV
-------------------------------

Depuis la page d'admin, on peut importer un fichier CSV de constantes prédéfinies.