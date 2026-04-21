![](img/object_subtotal.png)



# ***Subtotal***
#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d'expertise.
* Le module ***Subtotal*** simplifie la structuration de vos documents commerciaux :
	* Vous pouvez insérer des titres pour organiser vos documents en sections claires et professionnelles
	* Les titres supportent plusieurs niveaux d'imbrication (titre, sous-titre, sous-sous-titre, etc.) avec numérotation automatique optionnelle
	* Des sous-totaux sont automatiquement calculés pour chaque section (Total HT, quantité, TVA, réductions)
	* Les lignes de texte libre permettent d'ajouter des descriptions, conditions ou informations complémentaires entre vos lignes de produits/services
	* Un dictionnaire de textes libres prédéfinis permet de réutiliser rapidement vos textes récurrents
	* Les lignes se réorganisent facilement par glisser-déposer (drag & drop)
	* Vous pouvez masquer le détail des lignes contenues dans un titre pour une présentation synthétique
	* Trois modes d'impression sont disponibles : standard, en liste et condensé
	* Les structures (titres, sous-totaux, textes libres) sont préservées lors des transformations de documents (devis → commande → facture)
	* La gestion des attributs supplémentaires (ExtraFields) est supportée sur les lignes de titre
	* La compatibilité multi-entités est assurée (module Multi-Société)
	* Un document récapitulatif (PDF) peut être généré et fusionné avec le document principal
	* Etc...



## Licence

***Subtotal*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![](img/gplv3.png)

Copyright (C) 2013-2026 ATM Consulting
Copyright (C) 2016-2026 Sylvain Legrand - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README



## Ce qu'est ***Subtotal***

***Subtotal*** est un module optionnel de Dolibarr ERP & CRM enrichissant la gestion des documents commerciaux par un système de structuration avancé (titres, sous-totaux, textes libres).
***Subtotal*** est disponible pour les documents suivants :
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

Pour le bon fonctionnement du module ***Subtotal*** :
* Après toute mise à jour du module
	* Il est IMPERATIF de désactivez puis réactivez le module pour appliquer les modifications nécessaires



## Fonctionnalités (toutes optionnelles)

* Fonctions générales
	* ***1*** Insérer des titres pour structurer vos documents en sections (avec support multi-niveaux : titre, sous-titre, sous-sous-titre, etc.)
	* ***2*** Ajouter des sous-totaux pour afficher les montants intermédiaires par section
	* ***3*** Insérer des lignes de texte libre entre les lignes de produits/services
	* ***4*** Réorganiser les lignes par simple glisser-déposer (drag & drop)
	* ***5*** Gérer un dictionnaire de textes libres prédéfinis et réutilisables
	* ***6*** Dupliquer un bloc complet (titre + contenu + sous-total)
	* ***7*** Dupliquer une ligne individuelle
	* ***8*** Ajouter une ligne de produit/service directement sous un titre
* Options de titre
	* ***1*** Masquer le contenu des lignes contenues dans un titre (présentation synthétique)
	* ***2*** Afficher l'en-tête du tableau juste avant un titre spécifique
	* ***3*** Imprimer le contenu sous forme de liste à puces
	* ***4*** Imprimer le contenu de manière condensée
	* ***5*** Personnaliser le style des titres (gras, italique, souligné)
	* ***6*** Personnaliser le style des titres lorsque le détail est masqué (par défaut : italique)
	* ***7*** Activer la numérotation automatique des titres dans les PDF
	* ***8*** Gérer la taille de police des titres
	* ***9*** Définir une couleur de fond pour les titres
	* ***10*** Ajuster la luminosité des couleurs de fond en fonction du niveau d'imbrication
* Options de sous-total
	* ***1*** Afficher ou masquer le Total HT sur la ligne de sous-total
	* ***2*** Afficher ou masquer la réduction sur la ligne de sous-total
	* ***3*** Afficher la quantité totale du sous-total
	* ***4*** Définir une couleur de fond pour les sous-totaux
	* ***5*** Concaténer le libellé du titre correspondant dans le libellé du sous-total
	* ***6*** Afficher le taux de TVA sur les lignes de sous-total
	* ***7*** Afficher les informations de marge sur les sous-totaux
* Options d'affichage
	* ***1*** Masquer les blocs (titres repliés) par défaut
	* ***2*** Masquer les prix lorsque le détail est caché (avec option d'affichage des quantités)
	* ***3*** Masquer le total général du document
	* ***4*** Désactiver le menu sommaire rapide
	* ***5*** Limiter l'affichage de la TVA aux blocs condensés ou en liste
	* ***6*** Ne pas afficher les titres lors de la génération d'expéditions
* Options PDF et impression
	* ***1*** Générer un document récapitulatif (PDF) pour les devis, commandes et/ou factures
	* ***2*** Conserver le fichier récapitulatif après fusion avec le document principal
	* ***3*** Définir un texte modèle pour les titres lors de la facturation groupée de commandes
	* ***4*** Ajouter la référence d'expédition dans la description des titres
* Gestion avancée
	* ***1*** Gérer les blocs "Non Compris" (exclusion de lignes du calcul)
	* ***2*** Choisir les champs à conserver sur les lignes "Non Compris"
	* ***3*** Mettre à zéro le prix d'achat sur les lignes "Non Compris"
	* ***4*** Autoriser les attributs supplémentaires (ExtraFields) sur les lignes de titre
	* ***5*** Choisir les attributs supplémentaires disponibles par type de document (devis, commandes, factures)
	* ***6*** Ajouter automatiquement un sous-total lors de l'insertion d'un nouveau titre
	* ***7*** Insérer les nouvelles lignes à la fin du bloc (plutôt qu'au début)
	* ***8*** Support des commandes expédiables
	* ***9*** Afficher les quantités sur les lignes de titre
	* ***10*** Masquer uniquement les prix des sous-produits
* Actions massives
	* ***1*** Créer des factures depuis les commandes avec préservation de la structure
	* ***2*** Inclure la référence d'expédition dans les titres des factures générées



## Compatibilité

***Subtotal*** est compatible avec les modules tiers suivants :
* Module ***InfraSPackPlus*** (InfraS) - Modèles PDF avancés
* Module ***InfraSMultiDiscount*** (InfraS) - Gestion des remises
* Module ***Ultimate*** (ATM Consulting) - Module complémentaire

**ATTENTION** : Ce module est incompatible avec le module ***Milestone/Jalon*** (iNodbox). Les deux modules ne peuvent pas être activés simultanément.



## CE QUI EST NOUVEAU

Voir fichier ChangeLog.



## DOCUMENTATION

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=Subtotal "wiki InfraS").
