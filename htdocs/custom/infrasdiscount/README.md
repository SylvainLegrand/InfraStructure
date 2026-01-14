![](img/object_infrasdiscount.png)



# ***InfraSMultiDiscount***

* La saisie des remises ***InfraS*** simplifie votre gestion commerciale :
	 * Vous pouvez saisir des remises en pourcentage ou en valeur (monétaire)
	 * Les remises en pourcentage s'appliquent en cascade sur le total du document (ces remises sont cumulables : 10% + 5%)
	 * Le calcul de la valeur des remises en pourcentage peut être fait à partir des produits uniquement (les lignes de services sont alors exclues du calcul) et inversement pour les remises sur les services uniquement
	 * Le calcul de la valeur des remises en pourcentage est dynamique (toute modification du document, ajout/suppression de lignes, modification de prix et/ou de quantité, entraîne un recalcule du montant des remises présentes)
	 * La gestion des marges est respectée
	 * Vous pouvez choisir d'appliquer les remises sur les produits, sur les services ou sur les deux types d'articles
	 * Les remises en valeur (monétaire) sont équitablement réparties entre les produits et les services (prorata) pour conserver la cohérence des calculs de marge
	 * Une valeur cible (en TTC) peut être demandée, les remises générées automatiquement pour atteindre cette valeurs sont équitablement réparties entre les produits et les services par taux de TVA (prorata) pour conserver la cohérence des calculs de marge
	 * Vous pouvez saisir des remises monétaires en devise étrangères (module natif multi-devises activé)
	 * Les remises en pourcentage, en valeur et/ou via un calcul de valeur cible sont modifiables
	 * Un mode de remise automatique (sur les commandes) peut être paramétré
	 * Etc...



## Licence

***InfraSMultiDiscount*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![](img/gplv3.png)

Copyright (C) 2016-2025 Sylvain Legrand - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README



## Ce qu'est ***InfraSMultiDiscount***

***InfraSMultiDiscount*** est un module optionnel de Dolibarr ERP & CRM simplifiant la gestion des remises commerciales
***InfraSMultiDiscount*** est disponibles pour les documents suivant :
* Chaîne des ventes
	 * Devis
	 * Commandes client
	 * Factures



## Déploiement / installation

* Utilisez de préférence l'outil de déploiement des modules externes



## Activation des modifications

Pour le bon fonctionnement de la saisie des remises ***InfraSMultiDiscount*** :
* Après toute mise à jour du module
	* Il est IMPERATIF de désactivez puis réactivez le module pour appliquer les modifications nécessaires



## Fonctionnalités (toutes optionnelles)

* Paramètres d'activation
	 * ***1*** Activer les remises (ou pas) pour les devis
	 * ***2*** Définir le libellé ou la clé de traduction des remises par défaut sur les devis
	 * ***3*** Activer les remises (ou pas) pour les commandes clients
	 * ***4*** Définir le libellé ou la clé de traduction des remises par défaut sur les commandes
	 * ***5*** Activer les remises (ou pas) pour les factures
	 * ***6*** Définir le libellé ou la clé de traduction des remises par défaut sur les factures
	 * ***7*** Choisir le service associé aux remises sur les services (permet de lier un compte comptable spécifique aux remises)
	 * ***8*** Choisir le produit associé aux remises sur les produits (permet de lier un compte comptable spécifique aux remises)
* Paramètres de remise automatique (uniquement pour les commandes)
	 * ***1*** Choisir le nombre de commandes client sur lesquelles appliquer la remise automatique (Permet de limiter les remises automatiques aux "x" premières commandes validées du client)
	 * ***2*** Choisir la quantité de produits gratuits par référence (Si la quantité de produits pour une référence est inférieure à cette valeur, la remise sera : "Quantité demandée pour cette référence" x par son Prix Unitaire. Si la quantité de produits pour une référence est supérieure à cette valeur, la remise sera : "Cette valeur" x "PU de la référence")
	 * ***3*** Choisir les références sur lesquelles appliquer la remise automatique
	 * ***4*** Définir un article dont la quantité sert de pondérateur pour les calculs de la remise automatique
		 * ***a*** Le calcule de remise automatique commencera par ce produit. Pour celui-ci, la quantité de produit gratuit par référence s'applique intégralement
			* ***Exemple 1 :*** La quantité de ce produit est de 1500, la quantité de produit gratuit par référence est de 650, la remise calculée sera : "650" x PU de la référence
			* ***Exemple 2 :*** La quantité de ce produit est de 330, la quantité de produit gratuit par référence est de 650, la remise calculée sera : "330" x "PU de la référence"
		 * ***b*** Pour les autres références selectionnées : la quantité de produit gratuit par référence sera pondérée par la quantité de ce produit
			* ***Exemple :*** a quantité de la référence est de 10 000, la quantité du produit gratuit par référence est de 650, la quantité de ce produit est de 250, la remise sera : ("quantité de produit gratuit" - "quantité du produit pondérateur") x "PU de la référence". Soit ("650" - "250") x "PU de la référence"
	 * ***5*** Enregistrer des des informations à ajouter systématiquement dans la description de la remise automatique



## CE QUI EST NOUVEAU

Voir fichier ChangeLog.



## DOCUMENTATION

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=InfraSMultiDiscount "wiki InfraS").


