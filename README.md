![](img/infrastructure_small.png)



# ***InfraStructure***
#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d'expertise.
* Le module ***InfraStructure*** simplifie la structuration de vos documents commerciaux :
	* Vous pouvez insérer des titres pour organiser vos documents en sections claires et professionnelles
	* Les titres supportent plusieurs niveaux d'imbrication (titre, sous-titre, sous-sous-titre, jusqu'à 9 niveaux) avec numérotation automatique optionnelle
	* Des sous-totaux sont automatiquement calculés pour chaque section (Total HT, quantité, TVA, réductions, marge)
	* Les lignes de texte libre permettent d'ajouter des descriptions, conditions ou informations complémentaires entre vos lignes de produits/services
	* Un dictionnaire de textes libres prédéfinis permet de réutiliser rapidement vos textes récurrents
	* Les lignes se réorganisent facilement par glisser-déposer (drag & drop)
	* Vous pouvez masquer le détail des lignes contenues dans un titre pour une présentation synthétique
	* Trois modes d'impression sont disponibles : standard, en liste et condensé
	* Un sommaire rapide flottant permet de naviguer entre les titres dans les documents longs
	* Les structures (titres, sous-totaux, textes libres) sont préservées lors des transformations de documents (devis → commande → facture)
	* La gestion des attributs supplémentaires (ExtraFields) est supportée sur les lignes de titre
	* La compatibilité multi-entités est assurée (module Multi-Société)
	* Un document récapitulatif (PDF) peut être généré et fusionné avec le document principal
	* Les factures de situation (avancement de travaux) sont supportées avec préservation des structures
	* Etc...



## Licence

***InfraStructure*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure.

Copyright (C) 2013-2026 ATM Consulting
Copyright (C) 2016-2026 Sylvain Legrand - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README


## Ce qu'est ***InfraStructure***

***InfraStructure*** est un module optionnel de Dolibarr ERP & CRM enrichissant la gestion des documents commerciaux par un système de structuration avancé (titres, sous-totaux, textes libres).
***InfraStructure*** est disponible pour les documents suivants :
* Chaîne des ventes
	* Propositions commerciales (devis)
	* Commandes clients
	* Factures clients (standards, d'acompte, de situation, avoir)
* Chaîne des achats
	* Demandes de prix fournisseurs
	* Commandes fournisseurs
	* Factures fournisseurs
* Documents techniques
	* Bons de livraison / Expéditions
	* Bons de réception



## Déploiement / installation

* Utilisez de préférence l'outil de déploiement des modules externes



## Activation des modifications

Pour le bon fonctionnement du module ***InfraStructure*** :
* Après toute mise à jour du module
	* Il est IMPERATIF de désactiver puis réactiver le module pour appliquer les modifications nécessaires



## Fonctionnalités (toutes optionnelles)

Les options sont regroupées en trois sections dans la page d'administration du module (Outils admin > Modules > InfraStructure > Configuration). L'ordre ci-dessous reproduit fidèlement l'ordre de la page d'administration.

* Onglet Paramètres ***InfraS***
	* PARAMÈTRES DU MODULE INFRASTRUCTURE
		* ***1*** Afficher les marges sur les lignes de sous-totaux
		* ***2*** Activer la gestion des blocs "Non Compris" pour exclusion du total
		* ***3*** Champs à conserver sur les lignes "Non Compris" (Qté, TVA, PU HT, Total HT, Total TTC, Unité, Remise)
		* ***4*** Vider aussi le prix de revient sur les lignes "Non Compris"
		* ***5*** Ajouter automatiquement les sous-totaux manquants à l'ajout d'un nouveau titre
		* ***6*** Texte modèle des titres lors de la facturation groupée de commandes (clés `__REFORDER__`, `__REFCUSTOMER__`)
		* ***7*** Autoriser l'affichage des ExtraFields sur les titres
		* ***8*** ExtraFields disponibles sur les titres dans les propositions commerciales clients
		* ***9*** ExtraFields disponibles sur les titres dans les commandes clients
		* ***10*** ExtraFields disponibles sur les titres dans les factures clients
		* ***11*** Ne pas reporter les lignes de titre lors de la génération d'expédition
		* ***12*** Pré-cocher « Inclure la liste des expéditions » à l'ajout d'un titre
		* ***13*** Cocher par défaut « Cacher le prix des lignes des ensembles » lors de la génération PDF
		* ***14*** Masquer les totaux du document
		* ***15*** Gestion des lignes du module pour les commandes expédiables (si module `shippableorder` actif)
		* ***16*** Afficher la quantité sur les lignes de sous-total (produits virtuels, si module `clilacevenements` actif)
		* ***17*** Masquer uniquement les prix des sous-produits dans un ensemble
		* ***18*** Une seule ligne titre + total quand `hideInnerLines` est activé (expérimental)
		* ***19*** Remplacer par le détail des TVA quand `hideInnerLines` est activé (expérimental)
		* ***20*** Désactiver l'enrobage transactionnel SQL des actions sur blocs
	* PARAMÈTRES D'AFFICHAGE DU MODULE INFRASTRUCTURE
		* ***1*** Autoriser l'ajout de titres et sous-totaux
		* ***2*** Autoriser l'édition des titres et sous-totaux
		* ***3*** Autoriser la suppression des titres et sous-totaux
		* ***4*** Autoriser la duplication d'un bloc complet
		* ***5*** Autoriser la duplication d'une ligne
		* ***6*** Permettre l'ajout d'une ligne libre / produit directement sous un titre
		* ***7*** L'ajout sous un titre se fait en fin de section (au lieu du début)
		* ***8*** Plier les dossiers par défaut
		* ***9*** Cacher les options de titre
		* ***10*** Cacher l'option de saut de page avant
		* ***11*** Forcer les boutons d'action en mode éclaté hors dropdown (Dolibarr ≥ 20)
		* ***12*** Style des textes libres (B = gras, U = souligné, I = italique)
		* ***13*** Style des titres (B / U / I)
		* ***14*** Style des sous-totaux (B / U / I)
		* ***15*** Pourcentage de réduction de luminosité entre niveaux d'imbrication
		* ***16*** Désactiver le menu « sommaire rapide » (bouton flottant)
		* ***17*** Mode de pliage des blocs (`default` / `keepTitle` / `hideAll`)
		* ***18*** Afficher la somme des quantités sur les sous-totaux par type de document (devis, commande, facture, propal/commande/facture fournisseur)
		* ***19*** Couleur de fond des titres
		* ***20*** Couleur des icônes de titre
		* ***21*** Couleur des icônes d'action sur les blocs
		* ***22*** Couleur de fond des sous-totaux
		* ***23*** Couleur des icônes de sous-total
		* ***24*** Cacher les options de génération du document
	* PARAMÈTRES D'IMPRESSION PDF
		* ***1*** Activer la numérotation automatique sur le PDF
		* ***2*** Imprimer les totaux directement sur les lignes de titre
		* ***3*** Taille de police des titres (défaut 9 si vide)
		* ***4*** Style des titres lorsque le détail du bloc est caché (B / U / I, ex. « BI »)
		* ***5*** Style des titres dans les PDF (écrase le style écran)
		* ***6*** Style des sous-totaux dans les PDF (écrase le style écran)
		* ***7*** Couleur de fond des titres dans les PDF
		* ***8*** Couleur des titres dans les PDF (écrase la couleur automatique)
		* ***9*** Couleur de fond des sous-totaux dans les PDF
		* ***10*** Couleur des sous-totaux dans les PDF (écrase la couleur automatique)
		* ***11*** Concaténer le libellé du titre rattaché dans le libellé du sous-total
		* ***12*** Pourcentage de luminosité par niveau d'imbrication dans les PDF
		* ***13*** Augmentation de hauteur du fond des titres dans les PDF
		* ***14*** Décalage vertical du fond des titres dans les PDF
		* ***15*** Augmentation de hauteur du fond des sous-totaux dans les PDF
		* ***16*** Décalage vertical du fond des sous-totaux dans les PDF
		* ***17*** Afficher les quantités sur les lignes produit lorsque les prix sont cachés
		* ***18*** Affichage des quantités sur les sous-totaux dans les PDF par type de document (fallback sur la sélection écran si vide)
		* ***19*** Afficher le taux de TVA sur les sous-totaux quand toutes les lignes du bloc ont le même taux
		* ***20*** Limiter l'affichage du taux de TVA aux blocs imprimés en condensé ou en liste (si InfraSPackPlus actif)

#### Génération d'un récapitulatif par titre

* ***22*** Conserver le PDF de récapitulation après fusion avec le document principal
* ***23*** Activer la génération du récapitulatif sur les propositions commerciales
* ***24*** Activer la génération du récapitulatif sur les commandes
* ***25*** Activer la génération du récapitulatif sur les factures



## Compatibilité

***InfraStructure*** est compatible avec les modules tiers suivants :
* Module ***InfraSPackPlus*** (InfraS) - Modèles PDF avancés (support natif)
* Module ***InfraSDiscount*** (InfraS) - Gestion des remises (exclusion automatique des lignes spéciales)
* Module ***Ouvrage / Forfait*** (Inovea)
* Module ***Équipement*** (Patas-Monkey)
* Module ***Custom Link*** (Patas-Monkey)
* Module ***Note de Frais Plus*** (Mikael Carlavan)
* Module ***Ultimate*** (ATM Consulting)
* Thème ***Oblyon*** (Inovea / InfraS) - Sommaire flottant adapté automatiquement (compensation des barres sticky)


**ATTENTION** : Ce module est **incompatible** avec le module ***Milestone/Jalon*** (iNodbox). Les deux modules ne peuvent pas être activés simultanément (blocage à l'activation).



## CE QUI EST NOUVEAU

Voir fichier ChangeLog (onglet « Changelog » dans l'administration du module) ou `docs/changelog.xml`.



## DOCUMENTATION

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=InfraStructure "wiki InfraS").
