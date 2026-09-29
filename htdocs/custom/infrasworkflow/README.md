![](img/object_infrasworkflow.png)



## ***InfraSWorkflow***
#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d’expertise.
* Le module ***InfraSWorkflow*** apporte de nombreuses améliorations aux flux inter-modules de base :
	* Création d'accomptes automatiques lors de la signature d'une proposition commerciale
	* Association automatique des acomptes créés à partir d'un devis à la facture finale
	* Génération d'un relevé de facture avec le modèle InfraSPlus_FR (document PDF regroupant les factures d'acomptes, la facture finale et l'état des paiements) à la validation d'une facture finale (nécessite le module externe ***InfraSPackPlus***)
	* Automatisation des changements d'état des documents clients (factures, devis) en fonction des paiements effectués
	* Gestion centralisée des attributs supplémentaires
		* Création, modification, suppression et clonage des attributs supplémentaires pour tous les éléments d'une chaine de propagation (automatiquement)
		* Mode poubelle => remplace la suppression de l'attribut par sa désactivation
		* Contrôle de compatibilité des attributs supplémentaires pour Dolibarr v20+ (préparation à la migration)
		* Test de compatibilité des attributs supplémentaires lors des mises à jour dans une chaine de propagation
	* Etc...



## Licence
***InfraSWorkflow*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![](img/gplv3.png)
	Copyright (C) 2016-2025 Sylvain Legrand - InfraS
	voir le fichier LICENSE pour plus d'informations

## Autres Licences
	Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README



## Ce qu'est ***InfraSWorkflow***
***InfraSWorkflow*** est un module optionnel de Dolibarr ERP & CRM complétant les fonctionnalités du module Workflow inter-modules.



## Déploiement / installation
* Utilisez de préférence l'outil de déploiement des modules externes



## Activation des modifications
Pour le bon fonctionnement de  ***InfraSWorkflow*** :
* Après toute mise à jour du module
	* Il est IMPERATIF de désactivez puis réactivez le module pour appliquer les modifications nécessaires



## Fonctionnalités (toutes optionnelles)
* Onglet Paramètres
	* Gestion du glisser-déposer (drag and drop) pour les documents joints
		* ***1*** Activer une zone de glisser-déposer (drag and drop) sur la barre de titre du tableau "Fichiers joints" des onglets "Documents joints" pour ajouter rapidement un fichier
		* ***2*** Sous-option : Lors d'un glisser-déposer sur un produit ou service, conserver le nom original du fichier (désactiver le préfixage automatique par la référence)
	* Gestion des factures d'acomptes
		* ***3*** Suggérer la création d'une facture d'acompte lors de la signature d'une proposition commerciale (Suivant l'utilisation des attributs supplémentaires de gestion des acomptes monétaires la première facture d'acompte sera créée automatiquement ou non et calculé en % du montant TTC du devis ou en valeur fixe)
		* ***4*** Code de l'attribut supplémentaire utilisé pour la gestion du premier acompte (cet attribut doit contenir une valeur monétaire)
		* ***5*** Code de l'attribut supplémentaire utilisé pour la gestion du deuxième acompte (cet attribut doit contenir une valeur monétaire)
		* ***6*** Création automatique de la première facture d'acompte suivant le montant prévu dans l'attribut supplémentaire correspondant
		* ***7*** Sous-option : Valider automatiquement la première facture d'acompte dès sa création
		* ***8*** Liste des attributs supplémentaires à ne pas transférer du devis vers les factures d'acompte (le transfert vers les factures standards reste actif)
		* ***9*** Associer les factures d'acomptes liées au devis, et qui ont été transformées en crédit disponible, à la facture finale (ces crédits disponibles seront automatiquement insérés dans la facture finale)
		* ***10*** Transférer la note publique de la facture vers l'avoir associé
		* ***11*** Afficher le type de tiers dans les commandes (client/fournisseur)
	* Gestion des factures
		* ***12*** Création automatique de la facture avec le modèle InfraSPlus_FR à la validation (nécessite le module externe ***InfraSPackPlus***)
		* ***13*** Transférer les notes publiques du devis vers la facture (nécessite le module externe ***InfraSPackPlus***)
		* ***14*** Transférer des mentions complémentaires du devis vers la facture (nécessite le module InfraSPackPlus)
		* ***15*** Marquer automatiquement les factures "payées" quand "le reste à payer" est égale à 0 (correction d'une erreur de fonctionnement native)
		* ***16*** Régénérer automatiquement la facture lors de la classification "payée" (le fichier PDF contiendra la mention "Payée" et la liste des paiements associés)
		* ***17*** Marquer automatiquement les devis comme "facturés" quand le montant total des factures liées à ce devis est égale au montant total du devis
		* ***18*** Créer une commande fournisseur à partir de la facture fournisseur
		* ***19*** Ajouter une action de masse « Classer payée » sur la liste des factures fournisseurs
		* ***20*** Passer automatiquement la commande fournisseur à « Reçue complètement » lorsqu'elle est classée « Facturé » alors qu'elle est au statut « Commandé » Attention : option incompatible avec la gestion de stock (aucun mouvement de stock ne sera généré).
	* Facturation en masse
		* ***21*** Copier les notes publiques de la commande vers la facture
		* ***22*** Sélectionner automatiquement l'utilisateur connecté comme créateur du document
		* ***23*** Dé-duplication du numéro de la commande d'origine (dans les notes + dans l'en-tête)
	* Gestion des tiers
		* ***24*** Fusion de tiers : supprimer automatiquement les prix fournisseurs en doublon du tiers absorbé avant la fusion (évite l'échec de la fusion sur la clé unique des prix fournisseurs)
		* ***25*** Contrôle d'ouverture des comptes clients
		* ***26*** Sous-option : Rendre le champ Code client obligatoire pour l'ouverture des comptes clients
		* ***27*** Sous-option : Rendre le champ Assujetti à la TVA obligatoire pour l'ouverture des comptes clients
		* ***28*** Sous-option : Rendre le champ Numéro de TVA obligatoire pour l'ouverture des comptes clients
		* ***29*** Sous-option : Rendre le champ Code comptable obligatoire pour l'ouverture des comptes clients
		* ***30*** Sous-option : Rendre le champ Mode de reglement obligatoire pour l'ouverture des comptes clients
		* ***31*** Sous-option : Rendre le champ Condition de reglement obligatoire pour l'ouverture des comptes clients
		* ***32*** Sous-option : Rendre le champ Date de signature obligatoire pour l'ouverture des comptes clients
		* ***33*** Sous-option : Rendre le champ Siret obligatoire pour l'ouverture des comptes clients
		* ***34*** Sous-option : Rendre le champ EMail client obligatoire pour l'ouverture des comptes clients
		* ***35*** Sous-option : Rendre le champ Pays client obligatoire pour l'ouverture des comptes clients
		* ***36*** Sous-option : Rendre le champ Maison mère obligatoire pour l'ouverture des comptes clients
		* ***37*** Sous-option : Rendre le champ Contact / Adresse client obligatoire pour l'ouverture des comptes clients
	* Gestion des articles (produits/services)
		* ***38*** Contrôle de validation d'un produit
		* ***39*** Sous-option : Obliger la saisie du champ Code comptable (vente) pour valider une fiche produit
		* ***40*** Sous-option : Obliger la saisie du champ Code comptable (vente intra-communautaire) pour valider une fiche produit
		* ***41*** Sous-option : Obliger la saisie du champ Code comptable (vente à l'export) pour valider une fiche produit
		* ***42*** Sous-option : Obliger la saisie du champ Code comptable (achat) pour valider une fiche produit
		* ***43*** Sous-option : Obliger la saisie du champ Code comptable (achat intra-communautaire) pour valider une fiche produit
		* ***44*** Sous-option : Obliger la saisie du champ Code comptable (achat import) pour valider une fiche produit
		* ***45*** Sous-option : Obliger la saisie du champ Nomenclature douanière ou Code SH pour valider une fiche produit
		* ***46*** Sous-option : Obliger la saisie du champ Poids pour valider une fiche produit
		* ***47*** Sous-option : Obliger la saisie du champ Pays d'origine pour valider une fiche produit
		* ***48*** Sous-option : Obliger la saisie du champ Zone de Stockage pour valider une fiche produit
		* ***49*** Sous-option : Obliger la saisie du champ Entrepôt par défaut pour valider une fiche produit
	* Gestion des stocks
		* ***50*** Identification améliorée des lignes de commandes clients (non expédiables)
		* ***51*** Choix de la couleur de texte des lignes non expédiables (Rouge par défaut)
	* Gestion des inventaires
		* ***52*** Afficher les produits répondant aux filtres (entrepôt, produit, catégories) mais sans stock (stock = 0)
		* ***53*** Masquer les produits déclarés "hors vente" et "hors achat" ou les produits tagués (voir la liste ci-dessous) à l'initialisation d'un inventaire
		* ***54*** Tag / catégorie des produits à exclure de l'inventaire
		* ***55*** Afficher une colonne supplémentaire "Zone" sur les lignes de l'inventaire (liée à un attribut supplémentaire du produit ou à sa catégorie de localisation dans le stock)
		* ***56-57*** Attribut supplémentaire du produit contenant la zone Ou catégorie parente des zones de localisation (chaque sous-catégorie est une zone)
	* Gestion des contrats
		* ***58*** Afficher les lignes produits de la source (devis, commande, facture) vers le contrat lors de la création du contrat depuis cette source
		* ***59*** Autoriser la création d'un contrat depuis le devis dès qu'il est au statut validé (sans attendre la signature)
		* ***60*** Autoriser l'envoi d'email pour les contrats provisoires
		* ***61*** Activer automatiquement les services à la validation du contrat
		* ***62*** Signataire par défaut des contrats (utilisateur sélectionné par défaut dans le champ de signature commerciale des contrats)
		* ***63*** Liste des attributs supplémentaires à copier de la facture vers le contrat lors de la liaison d'une facture standard avec ce contrat
		* ***64*** Normaliser automatiquement les rangs des lignes de produits (réorganise automatiquement les numéros de rang 1, 2, 3... lors du chargement pour garantir un ordre cohérent)
		* ***65*** Liste des extrafields des lignes de contrat à afficher dans les produits de contrats
		* ***66*** Catégorie parente pour la gestion des produits dans les contrats (alternative à la sélection d'extrafields de lignes — la sous-catégorie de chaque produit sera affichée dans l'onglet Produits du contrat)
		* ***67*** Permettre de changer le tiers associé au contrat lors de sa création depuis un devis ou une commande
	* Gestion des attributs supplémentaires
		* ***68*** Activer la duplication inter-module des attributs supplémentaires
		* ***69*** Activer le mode poubelle pour les attributs supplémentaires (remplace la suppression par la désactivation de l'attribut)
	* Propagation automatique des attributs supplémentaires
		* ***70*** Propager automatiquement les attributs supplémentaires du tiers vers la facture (option cachée de Dolibarr)
		* ***71*** Propager automatiquement les attributs supplémentaires du tiers vers la commande client (option cachée de Dolibarr)
		* ***72*** Propager automatiquement les attributs supplémentaires du tiers vers la commande fournisseur (option cachée de Dolibarr)
		* ***73*** Propager automatiquement les attributs supplémentaires des produits dans les lignes de documents (option cachée de Dolibarr)
	* Gestion des médias
		* ***74*** Autoriser les utilisateurs non administrateurs disposant du droit "Accéder aux médias" à utiliser le navigateur de médias de l'éditeur WYSIWYG (bouton "Parcourir le serveur") — par défaut, Dolibarr réserve ce navigateur aux administrateurs et aux utilisateurs ayant le droit d'écriture sur le module Sites Web
* Onglet Attributs Supplémentaires
	* Paramètres
		* ***1*** Choisir le type d'affichage des listes d'attributs supplémentaires (Exhaustif => tous les attributs sont affichés, Visible => seul les attributs visibles sont affichés, Caché => seul les attributs cachés sont affichés, Inactif => seul les attributs inactifs sont affichés)
		* ***2*** Activer un test de compatibilité des attributs supplémentaires pour Dolibarr v20+ (affiche la ligne en rouge si les paramètres de filtre SQL d'une liste issue d'une table ne sont pas compatibles avec la méthode USC - Universal Search Criteria)
		* ***3*** Scanner les attributs supplémentaires des modules internes et externes pour détecter les anomalies (orphelins, modules désactivés, modules supprimés) et les nettoyer
	* Gestion des attributs supplémentaires (par type d'élément)
		* ***4-62*** Gérer les listes d'attributs supplémentaires (création, modification, clonage inter-module, suppression, réorganisation, etc.) affichées par modules actifs
			* les actions de création, modification, clonage et suppression demandent une confirmation avant leur exécution
			* les confirmations d'actions de création, modification, clonage et suppression proposent des options d'action multiples (inter-module) pour effectuer l'action simultanément sur les modules demandés


## Ce qui est nouveau
Voir fichier ChangeLog.



## Documentation
La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=InfraSWorkflow "wiki InfraS").


