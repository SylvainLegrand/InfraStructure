![](img/object_dolinfras.png)

## ***DolInfraS***

#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d'expertise.

***DolInfraS*** est un squelette de module pour **Dolibarr ERP & CRM** permettant de créer rapidement un nouveau module personnalisé en respectant les bonnes pratiques Dolibarr. Il fournit une structure complète et fonctionnelle prête à être adaptée à vos besoins.



## LICENCE

***DolInfraS*** est distribué sous les termes de la licence GNU General Public License v3 ou supérieure. ![](img/gplv3.png)

Copyright (C) 2016-2026 Sylvain Legrand - InfraS

Voir le fichier LICENSE pour plus d'informations.

### Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README.


## Ce qu'est DolInfraS

***DolInfraS*** est un **modèle de module** (squelette) pour Dolibarr. Il contient toute l'architecture nécessaire à la création d'un module externe fonctionnel :

* Un **descripteur de module** complet (`moddolinfras.class.php`)
* Un **gestionnaire de hooks** avec exemples (`actions_dolinfras.class.php`)
* Des **pages d'administration** avec onglets (paramètres, à propos, changelog)
* Un système de **gestion de version** basé sur XML (`docs/changelog.xml`)
* Un système de **sauvegarde / restauration** des paramètres du module
* Des **fichiers de traduction** multilingues (FR, EN, ES)
* Une feuille de style **CSS dynamique**
* Un fichier de **données SQL** initiales
* Le support **multi-société** (entité)



## Structure du module

```
dolinfras/
|-- config.php                              Chargement de l'environnement Dolibarr
|-- LICENSE                                 Licence GPL v3
|-- README.md                               Ce fichier
|-- CLAUDE.md                               Documentation technique détaillée
|
|-- admin/                                  Pages d'administration
|   |-- dolinfrassetup.php             Page de paramétrage
|   |-- about.php                           Page À propos (affiche le README)
|   +-- changelog.php                       Page de changelog et vérification de MàJ
|
|-- class/                                  Classes PHP
|   +-- actions_dolinfras.class.php    Gestionnaire de hooks
|
|-- core/
|   |-- lib/
|   |   |-- dolinfras.lib.php          Fonctions utilitaires du module
|   |   +-- dolinfrasAdmin.lib.php     Fonctions d'admin (onglets, version, backup)
|   +-- modules/
|       +-- moddolinfras.class.php     Descripteur de module Dolibarr
|
|-- css/
|   +-- dolinfras.css.php              Feuille de style dynamique
|
|-- docs/
|   +-- changelog.xml                       Historique des versions (XML)
|
|-- js/
|   +-- dolinfras.js 
|
|-- img/                                    Icônes et images du module
|
|-- langs/                                  Traductions
|   |-- en_US/dolinfras.lang
|   |-- es_ES/dolinfras.lang
|   +-- fr_FR/dolinfras.lang
|
+-- sql/
    +-- data.sql                            Données initiales
```



## Comment utiliser ce squelette

Pour créer un nouveau module à partir de ce template :

1. **Copier** le dossier `dolinfras` et le renommer avec le nom de votre module (en minuscules, ex : `monmodule`)

2. **Renommer les fichiers** contenant `dolinfras` dans leur nom

3. **Rechercher et Remplacer** dans tous les fichiers :
    * `dolinfras` par `monmodule` (minuscules)
    * `DolInfraS` par `MonModule` (nom d'affichage)
    * `DOLINFRAS` par `MONMODULE` (majuscules, préfixe des constantes)
    * `500045` par votre identifiant de module unique (voir la [liste officielle](https://wiki.dolibarr.org/index.php/List_of_modules_id))

4. **Adapter** les informations éditeur dans le descripteur de module :
    * `editor_name`, `editor_email`, `editor_url`

5. **Définir** vos permissions, menus, constantes, hooks et tables SQL

6. **Mettre à jour** `docs/changelog.xml` avec votre première version



## Fonctionnalités incluses

### Descripteur de module
* Numéro de module, nom, famille, description
* Déclaration des permissions utilisateur
* Déclaration des menus (menu gauche sous Outils / InfraS)
* Déclaration des hooks et du CSS
* Chargement automatique des tables SQL
* Gestion de version depuis `docs/changelog.xml`
* Vérification de compatibilité Dolibarr et PHP (désactivation automatique si incompatible)
* Vérification de la présence de l'extension PHP `xml`

### Hooks (exemples)
* `printTopRightMenu` : injection de contenu dans le menu haut à droite
* `doActions` : traitement d'actions personnalisées
* `printCommonFooter` : injection de contenu en pied de page

### Pages d'administration
* **Paramètres** : page de configuration avec gestion on/off et formulaires
* **À propos** : affichage du README.md en HTML
* **Changelog** : historique des versions, informations de support, vérification de mise à jour

### Système de sauvegarde et restauration
* Sauvegarde des constantes du module en base de données
* Restauration complète des paramètres

### Gestion de version
* Version lue depuis `docs/changelog.xml` (pas de fichier VERSION)
* Compatibilité min/max Dolibarr et PHP déclarées dans le XML
* Désactivation automatique du module si la version de Dolibarr est trop ancienne

### Permissions

| Clé de permission       | Description                                   | Par défaut |
|-------------------------|-----------------------------------------------|:----------:|
| `paramMenu`             | Voir le menu de paramétrage                   | Activé     |
| `paramDolInfraS`   | Modifier les paramètres du module             | Désactivé  |
| `paramBkpRest`          | Sauvegarder / Restaurer les paramètres        | Désactivé  |



## Prérequis

* **PHP** : extension `xml` requise (pour le parsing du changelog)
* **Dolibarr** : le module se place dans le dossier `htdocs/custom/`



## Installation

1. Copier le dossier du module dans `htdocs/custom/`
2. Se connecter à Dolibarr en tant qu'administrateur
3. Aller dans **Accueil / Configuration / Modules/Applications**
4. Rechercher le module et l'activer
5. Configurer via le menu **Outils / InfraS**



## CE QUI EST NOUVEAU

Voir le fichier `docs/changelog.xml` ou l'onglet **Changelog** dans l'administration du module.



## DOCUMENTATION

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=DolInfraS "wiki InfraS").

Pour la documentation technique détaillée, consulter le fichier `CLAUDE.md` à la racine du module.
