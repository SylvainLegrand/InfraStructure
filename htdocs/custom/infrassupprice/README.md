![](img/object_infrassupprice.png)



## ***InfraSSupPrice***
#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d’expertise.
Le pack prix fournisseur ***InfraS*** facilite la mise à jour des tarifs fournisseurs à partir des commandes ou des factures. Il permet de maintenir facilement la base de prix des produits référencés.



## LICENCE

***InfraSSupPrice*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![](img/gplv3.png)

Copyright (C) 2016-2024 Sylvain Legrand - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README



## Ce qu'est ***InfraSSupPrice***

***InfraSSupPrice*** est un module optionnel de Dolibarr ERP & CRM qui simplifie et automatise la mise à jour des tarifs fournisseurs.
***InfraSSupPrice*** fonctionne à partir des éléments suivants :
* ***Demande de prix / Commande / Facture fournisseur***
	 * Listing de l’ensemble des produits présents ayant un tarif fournisseur associé
	 * Choix du / des produit(s) à mettre à jour
	 * Mise à jour du tarif, de la TVA ou de la quantité minimum
	 * Si utilisation du module multi-devises mise à jour du tarif en devise et du taux de change produit par produit



## Déploiement / installation

* Utilisez de préférence l'outil de déploiement des modules externes



## Activation des modifications

Pour le bon fonctionnement des modèles ***InfraS*** (chaîne des achats, gestion de l'email et / ou de l'url associé à chaque adresse, gestion des polices de caractères, etc...) :
* Après toute mise à jour du module
	* Il est IMPERATIF de désactivez puis réactivez le module pour appliquer les modifications nécessaires



## Fonctionnalités (toutes optionnelles)

* Fonctions générales
	* Création d’un menu utilisateur non administrateur pour gérer les paramètres du module
	* Gérer les droits de modification d’un utilisateur non administrateur onglet par onglet
	* Sauvegarder automatiquement les paramètres spécifiques du module lors de la désactivation et réinjecter lesdits paramètres à la réactivation du module
* Onglet Paramétrage de la gestion des tarif fournisseur
	* Télécharger le fichier de sauvegarde des paramètres
	* Sauvegarder / Restaurer l'ensemble des paramètres du module (une copie de sécurité de la sauvegarde est systématiquement créée dans le répertoire d'administration des documents)
	* OPTIONS DES FONCTIONS DE GESTION DES TARIFS FOURNISSEURS
		* ***1*** Choisir de ne pas utiliser la quantité produit renseignée dans le document comme quantité minimum pour le tarif (La valeur de la quantité minimum est modifiable ligne par ligne)



## CE QUI EST NOUVEAU

Voir fichier ChangeLog.



## DOCUMENTATION

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=InfraSupPrice "wiki InfraS").


