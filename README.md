![](img/object_dolinfras.png)

## ***DolInfraS***

#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d'expertise.

***DolInfraS*** est le **module structurant de la version LTS de Dolibarr** — la distribution « **Dolibarr LTS by InfraS** » : une base **Dolibarr 22.0.x** enrichie des **améliorations et stabilisations InfraS et Osden**, fournie avec un ensemble de **modules externes prêts à l'utilisation**.



## LICENCE

***DolInfraS*** est distribué sous les termes de la licence GNU General Public License v3 ou supérieure. ![](img/gplv3.png)

Copyright (C) 2016-2026 Sylvain Legrand - InfraS

Voir le fichier LICENSE pour plus d'informations.

### Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README.


## Ce que fait DolInfraS

***DolInfraS*** assure le branding, le suivi de version et la pré-configuration des instances « Dolibarr LTS by InfraS » :

* **Branding « Dolibarr LTS by InfraS »** : famille de modules dédiée sur la page des modules (polices spécifiques, détection automatique du thème sombre)
* **Suivi de version Dolibarr** : lecture et stockage de la version dans la constante `DOLINFRAS_VERSION`, utilisée par les autres modules InfraS
* **Avertissement à la connexion** si la version de Dolibarr dépasse la version maximum supportée
* **Chargement des constantes LTS à l'activation** (`sql/data.sql`) : constantes de configuration de la distribution (groupe « SaaS by InfraS », masquées) et options fonctionnelles Dolibarr préactivées (visibles)
* **Pages d'administration** : paramètres (avec application forcée des constantes LTS), à propos, changelog
* **Gestion de version** basée sur XML (`docs/changelog.xml`) avec vérification de compatibilité Dolibarr/PHP et détection de mises à jour en ligne



## Fonctionnalités

### Descripteur de module
* Famille « Dolibarr LTS by InfraS » avec branding dynamique
* Déclaration du hook `login`, du JS et du CSS du module
* Chargement automatique des constantes LTS (`sql/data.sql`) à l'activation
* Gestion de version depuis `docs/changelog.xml`
* Vérification de compatibilité Dolibarr et PHP (désactivation automatique si incompatible)
* Vérification de la présence de l'extension PHP `xml`

### Hook
* `afterLogin` : stocke la version Dolibarr dans `DOLINFRAS_VERSION` et affiche un avertissement si la version maximum supportée est dépassée

### Pages d'administration
* **Paramètres** : section « Options de gestion des paramètres LTS » avec le bouton **Forcer l'application des paramètres** — force la valeur des constantes du fichier `data.sql` dans la base de données (entité courante), en écrasant les valeurs modifiées
* **À propos** : affichage du README.md en HTML
* **Changelog** : historique des versions, vérification de mise à jour en ligne

### Constantes LTS (`sql/data.sql`)
* Insérées avec `INSERT IGNORE` à l'activation : posées une seule fois, jamais écrasées par une réactivation
* Deux groupes : constantes « SaaS by InfraS » (masquées) et options fonctionnelles Dolibarr (visibles)
* Réapplicables à tout moment via le bouton « Forcer l'application des paramètres »



## Prérequis

* **PHP** : extension `xml` requise (pour le parsing du changelog)
* **Dolibarr** : le module se place dans le dossier `htdocs/custom/`



## Installation

1. Copier le dossier du module dans `htdocs/custom/`
2. Se connecter à Dolibarr en tant qu'administrateur
3. Aller dans **Accueil / Configuration / Modules/Applications**
4. Rechercher le module et l'activer (les constantes LTS sont chargées à ce moment)
5. Configurer via le pictogramme de configuration du module ou les onglets de la page de paramètres



## CE QUI EST NOUVEAU

Voir le fichier `docs/changelog.xml` ou l'onglet **Changelog** dans l'administration du module.



## DOCUMENTATION

Pour la documentation technique détaillée, consulter le fichier `CLAUDE.md` à la racine du module.
