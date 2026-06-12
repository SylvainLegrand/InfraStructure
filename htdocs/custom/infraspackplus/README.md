![](img/object_infraspackplus.png)



## ***InfraSPackPlus***
#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d’expertise.
* Le pack de modèles ***InfraS*** apporte de nombreuses modifications aux modèles de base : c'est plus de 38 modèles pour 21 types de documents différents et 709 options (32 options sont modifiables directement sur la fiche du document) dont
	 * Les cadres arrondis, l’organisation des colonnes (ordre, affichage, largeur, …)
	 * Le choix des couleurs de texte, de fond, des images, des filigranes, des types épaisseur et couleurs des lignes, …
	 * L’affichage 'full' TTC
	 * L'affichage du total en toutes lettres
	 * Une gestion plus claire des options, une gestion complète des images (logo, image en pied, image des produits / services)
	 * L’activation des adresses multiples pour votre société comme pour les tiers et l'utilisation des adresses de livraison (y compris en saisie manuelle)
	 * Un en-tête simplifié pour les pages suivantes
	 * L’utilisation du nom commercial (marque) des tiers
	 * La gestion des attributs supplémentaires (attributs liés au document comme ceux liés aux lignes de document)
	 * Une réelle intégration des CGV (conditions générales de vente) et des éléments techniques concaténés automatiquement avec leur prise en compte dans la pagination
	 * La gestion des éléments légaux nécessaires à l’export
	 * Etc...



## Licence

***InfraSPackPlus*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![](img/gplv3.png)

Copyright (C) 2016-2024 Sylvain Legrand - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README

Utilise jSignature de Brinley Ang sous licence MIT pour la gestion des signatures en face à face (PAD)


## Ce qu'est ***InfraSPackPlus***

***InfraSPackPlus*** est un module optionnel de Dolibarr ERP & CRM enrichissant les modèles de document de base par une série de modèles configurables (on n'active que les modèles que l'on désire) .
***InfraSPackPlus*** ajoute un modèle aux modules suivants :
* Chaîne des ventes
	 * Devis, compatibilité module ***“Sous-Total” - ATM Consulting - version améliorée par InfraS***, ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
	 * Devis sans total général (liste de prix ou tarif), compatibilité module ***“Sous-Total” - ATM Consulting - version améliorée par InfraS***, ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
	 * Commandes client, compatibilité module ***“Sous-Total” - ATM Consulting - version améliorée par InfraS***, ***“Custom Link” - Patas-Monkey***,  ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
	 * Contrats
	 * Factures, compatibilité module ***“Sous-Total” - ATM Consulting - version améliorée par InfraS***, ***“Équipement” - Patas-Monkey***, ***“Milestone/Jalon” - iNodbox*** et ***“Ouvrage/Forfait” - Inovea***
* Chaîne des achâts
	 * Devis fournisseur
	 * Commandes fournisseur
	 * Factures fournisseur
* Documents techniques
	 * Fiches produit / services
	 * Page d'étiquettes de produit / services avec code-barre, libellé et tarif de base
	 * Fiches d’intervention, compatibilité module ***“Équipement” - Patas-Monkey***
	 * Bons de livraison / Expéditions, compatibilité module ***“Équipement” - Patas-Monkey***
	 * Projet, compatibilité module ***“Note de Frais Plus” - Mikael Carlavan***
	 * Fiche de Stock
* Documents administratifs
	 * Notes de frais
	 * Utilisateurs
* Trésorerie / Banque
	 * Bordereaux de remise de chèques




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
* Onglet Paramètres Dolibarr
	* ***1*** Gérer les marges et le format papier à appliquer aux documents
	* ***2*** Choisir la taille de police utilisée comme base
	* ***3*** Désactiver l'impression du logo de la société
	* ***4*** Utiliser le logo original (avec sa résolution plus élevée) dans le PDF au lieu de l'image réduite. ***ATTENTION !*** Cela peut augmenter considérablement la taille du fichier PDF !
	* ***5*** Intervertir les cadres “émetteur” et “destinataire”
	* ***6*** Inclure les alias dans le nom des tiers
	* ***7*** Utiliser la position standard française (La Poste) pour la position de l'adresse client
	* ***8*** Masquer les détails de l'émetteur sur les PDF générés (e-mail, fax, URL et téléphone)
	* ***9*** Ajouter les détails du destinataire sur les PDF générés (e-mail, fax, URL et téléphone)
	* ***10***	Cacher l'identifiant de TVA Intracommunautaire dans l'adresse du destinataire
	* ***11-15*** Afficher les identifiants professionnels dans l'adresse du destinataire (Id. prof. 1 “SIREN”, Id. prof. 2 “SIRET”, Id. prof. 3 “NAF – APE”, Id. prof. 4 “RCS/RM”, Id. prof. 5 “EORI”)
	* ***16*** Afficher une ligne de séparation entre chaque élément présent dans le document PDF
	* ***17*** Activer le mode 'full details' pour l'éditeur WYSIWYG de saisie des descriptions produits / services (permet l'insertion d'image à la volée - non enregistrée en bibliothèque)
	* ***18*** Améliorer la visibilité des éléments importants, comme la référence ou le numéro de série, dans la description
	* ***19-20*** Cacher la référence et/ou la description des produits
	* ***21*** Inverser la description longue des produits / services et leur libellé (à partir de Dolibarr 10+)
	* ***22*** Désactiver la copie des informations douanières (code SH et pays d'origine) dans le descriptif produit des lignes des documents de ventes
	* ***23*** Masquer la colonne 'Poids / Volume' sur les bons de livraison (expédition)
	* ***24*** Activer l'utilisation des unités (issues du dictionnaire) pour les produits / services (Vide => Désactive l'utilisation des unités. Sinon => Permet de definir l'unité proposé par défaut)
	* ***25*** Inclure la documentation produit / service aux devis (Les documents à inclure sont à sélectionner sur la fiche produit / service en bas de l'onglet 'fichiers joints' !)
	* ***26*** Afficher la description des catégories auxquelles appartient le produit / services (après sa propre description)
	* ***27*** Modifier le type de référence produit utilisé dans la chaîne des achats
		 * 0 = référence interne, puis référence fournisseur
		 * 1 = référence fournisseur seule
		 * 2 = référence fournisseur, puis référence interne
	* ***28*** Modifier le type de référence produit utilisé dans la chaîne des ventes => option prix par client activée
		 * 0 = référence interne, puis référence client
		 * 1 = référence client seule
		 * 2 = référence client, puis référence interne
	* ***29-30*** Imprimer un code QR sur les factures (ZATCA ou Suisse)
	* ***31*** Activer l'utilisation des "factures de situations" (avancement de travaux)
	* ***32*** Afficher la mention "catégorie d'opérations" sur la facture dans le tableau d'information (en bas à gauche) ou au dessus de l'en-tête du tableau (1ère page à gauche)
	* ***33*** Pour le mode de règlement par chèque n'afficher que l'ordre (l'adresse d'envoi du chèque est masquée)
	* ***34*** Pour le mode de règlement par virement n'afficher que l'IBAN / BIC
	* ***35*** Inclure un lien de paiement en ligne en pied de facture
	* ***36*** Afficher des montants positifs dans les factures d'avoir
	* ***37*** Considérer les factures d'acompte comme des règlements (Utilisées dans une facture finale elles n'apparaissent pas dans les lignes de détail mais sont incluses aux règlements déjà effectués)
	* ***38-39*** Cacher les conditions et / ou les modes de règlements dans les devis
	* ***40*** Masquer les détails des règlements effectués dans les factures
	* ***41*** Type d'affichage des totaux de TVA
		 * n'afficher que le taux
		 * n'afficher que le code
		 * n'afficher que la description (libellé)
		 * afficher le taux et le code
		 * afficher le taux et la description (libellé)
		 * afficher le code et la description (libellé)
		 * VIDE => affiche les trois (le taux, le code et la description)
	* ***42*** Ouvrir la page de configuration générale des éditions PDF (Options natives)
* Onglets Paramètres ***InfraS***, Images, Adresses, Attributs supplémentaires, Mentions complémentaires, Notes publiques et Options avant génération
	* Télécharger le fichier de sauvegarde des paramètres
	* Sauvegarder / Restaurer l'ensemble des paramètres du module (une copie de sécurité de la sauvegarde est systématiquement créée dans le répertoire d'administration des documents)
	* Copier les paramètres depuis une autre entité dans l'entité courante (Module Multi-Société)
* Onglet Paramètres ***InfraS***
	* OPTIONS DE GESTION DU COMPORTEMENT DES FONCTIONS D'IMPRESSION
		* ***1*** Activer les modèles InfraS comme modèles par défaut pour les documents
		* ***2*** Activer la génération semi-automatique ("à la validation du document") des PDF : ATTENTION ! Le module active nativement cette fonction pour des raisons d'ergonomie. Vous pouvez forcer ce choix
		* ***3*** Activer la génération semi-automatique des PDF à la modification des notes publiques manuelles
		* ***4*** Activer la génération semi-automatique des PDF à la modification des attributs supplémentaires de document
		* ***5*** Activer la génération semi-automatique des PDF à l'enregistrement des champs modifiables après validation  du document (Date de fin de validité, Conditions de règlement, Mode de règlement, Date d'expédittion / de livraison, Délai de livraison, Compte bancaire)
		* ***6*** Activer / Désactiver la génération automatique ("à la volée") des PDF
		* ***7*** Autoriser l'enregistrement de plusieurs fichiers PDF pour un même document quand plusieurs modèles sont disponibles (un fichier par modèle et par document)
		* ***8*** Horodater le nom du fichier des fiches projet pour garder un historique de l'évolution
		* ***9*** Proposer aussi les pièces jointes du projet (affaire) associé au document comme fichiers fusionnables
		* ***10*** Dans les ordres de fabrication proposer aussi les pièces jointes de la nomenclature associée au document comme fichiers fusionnables
		* ***11*** Devis => Création d'un document supplémentaire en prix brut (Les prix ne tiennent pas compte des remises et / ou des tarifs client ; ils sont issus du prix de vente par défaut de la bibliothèque articles)
		* ***12*** Gérer la fusion de la documentation produit / service avec les devis depuis les paramètres finaux (case à cocher)
		* ***13*** Vérifier la présence de fichier en double (fichiers ayant un nom identique associés à des références produit / service différents) => le fichier ne sera fusionné qu'une seule fois
		* ***14*** Dans les devis proposer aussi l'attestation de TVA associée au document comme fichier fusionnable (requiert le module externe Attestation de TVA - Iouston)
		* ***15-17*** Forcer le nombre de décimales  affichées pour les prix unitaires et/ou les prix totaux (lignes et document)
	* OPTIONS DE L'APPARENCE GÉNÉRALE DES DOCUMENTS
		* ***1*** Importer des fichiers True Type Font (ttf) comme nouvelle police de caractère à utiliser
		* ***2*** Tester la police sélectionnée (création d'un document test avec 84 caractères différents => affichage standard, gras, italique et gras italique => alphabet latin en miniuscules et majuscules, chiffres, ponctuation, accentuation, symboles monétaires, etc soit 98% d'un clavier AZERTY)
		* ***2*** Choisir la police de caractères désirée pour la génération des documents
		* ***3*** Choisir la couleur de texte de l’en-tête de page et / ou du corps du document indépendamment
		* ***4*** Afficher la référence du document et sa date en haut à droite des éléments concaténés (CGV, documentation technique, commerciale, etc...)
		* ***5*** Choisir la valeur du rayon des angles des tableaux et cadres (comprise entre 0 pour angles aigus et 5)
		* ***6*** Ajouter un texte à afficher en filigrane sur les factures réglées
		* ***7*** Ajouter un texte à afficher en filigrane sur tous les documents (à utiliser pour les instances de test)
		* ***8*** Ajouter un Texte à afficher en filigrane sur les devis validés provisoires
		* ***9*** Afficher le symbole monétaire dans les tableaux et détails du document
		* ***10*** Si l'option précédente est active, masquer les informations monétaires situées au dessus de l'en-tête des tableaux (à droite)
		* ***11*** Si l'option précédente est désactivé, Afficher le symbole monétaire dans le tableau des totaux
	* OPTIONS DES EN-TÊTES DE DOCUMENT
		* ***1*** Laisser la première page vide => seuls l'en-tête et le pied de page seront visibles (actif pour les : Devis et Devis sans total ; Commandes, Commande avec code-barres et Proforma ; Contrats ; Factures)
		* ***2*** Créer un en-tête personnalisé à partir d'un fichier PHP
		* ***3*** Activer un en-tête réduit après la première page
		* ***4*** Ajouter une mention (ou clé de traduction) au titre des factures en cas de présence d'acompte (exemple : 'Solde' pour avoir comme titre 'Facture Solde' ; 'Finale' pour 'Facture Finale' ; etc...)
		* ***5*** Choisir la couleur du texte à appliquer à l'en-tête du document
		* ***6*** Déplacer les informations de l'en-tête sous les blocs d'adresses (titre et référence du document, dates, références client, documents liés, etc...)
		* ***7*** Choisir la taille de la police du titre du document
		* ***8*** Aligner les informations de l'en-tête sur la gauche
		* ***9*** Afficher le nom du créateur du document dans l'entête
		* ***10*** Définir la hauteur de l'espace supplémentaire entre le bloc d'adresses et les informations de l'en-tête
		* ***11*** Afficher les dates du document sur des lignes séparées
		* ***12*** Afficher les dates du documents en gras
		* ***13*** Choisir la couleur de la date d'échéance des factures (si l'option d'affichage des dates du document sur des lignes séparées est active)
		* ***14*** Afficher la date de création des commandes fournisseurs
		* ***15*** Afficher la référence client comme référence du document dans les devis / Propositions commerciales
		* ***16*** Masquer la date des objets liés au document (seule le type et la référence apparaitront)
		* ***17*** Afficher les devis dans la liste des objets liés
		* ***18*** Afficher les commandes dans la liste des objets liés
		* ***19*** Afficher les références clients des commandes dans la liste des objets liés (entre parenthèses)
		* ***20*** Afficher les expéditions dans la liste des objets liés
		* ***21*** Afficher les contrats dans la liste des objets liés
		* ***22*** Afficher les fiches d'interventioàn dans la liste des objets liés
		* ***23*** Afficher les projets dans la liste des objets liés
		* ***24*** Afficher la description des projets en plus de leur référence
		* ***25*** Masquer les libellés “Émetteur” et “ Adressé à”
		* ***26*** Supprimer le cadre et les informations concernant l'émetteur de l'en-tête (ces informations seront obligatoirement disponibles dans le pied de page)
		* ***27-30*** Choisir l'épaisseur, le type de ligne, la couleur et l'opacité de la bordure du cadre d'adresse émetteur (choix graphique ou par code RVB, hexa, ou HSV pour la couleur)
		* ***31-32*** Choisir la couleur de fond et l'opacité du cadre d'adresse émetteur (choix graphique ou par code RVB, hexa, ou HSV pour la couleur)
		* ***33*** Choisir la couleur du texte dans le cadre d'adresse émetteur (choix graphique ou par code RVB, hexa, ou HSV)
		* ***34-37*** Choisir l'épaisseur, le type de ligne, la couleur et l'opacité de la bordure du cadre d'adresse destinataire (choix graphique ou par code RVB, hexa, ou HSV pour la couleur)
		* ***38-39*** Choisir la couleur de fond et l'opacité du cadre d'adresse destinataire (choix graphique ou par code RVB, hexa, ou HSV pour la couleur)
		* ***40*** Choisir la couleur du texte dans le cadre d'adresse destinataire (choix graphique ou par code RVB, hexa, ou HSV)
		* ***41*** Afficher les informations société dans l'en-tête des fiches produits
		* ***42*** Afficher le numéro client dans les documents de la chaîne des ventes (devis, commandes, fiches d'intervention, contrats, bons de livraison, factures client)
		* ***43*** Afficher le numéro client dans l'en-tête sous la référence client au lieu du cadre d'adresse destinataire (si l'option précédente est activée)
		* ***44*** Afficher le code comptable client dans les documents de la chaîne des ventes (devis, commandes, fiches d'intervention, contrats, bons de livraison, factures client)
		* ***45*** Afficher le code comptable client dans l'en-tête sous la référence client au lieu du cadre d'adresse destinataire (si l'option précédente est activée)
		* ***46*** Afficher la date d'ouverture du projet associé dans les notes des Ordres de Fabrication (OF) => modèle de commande client
		* ***47*** Afficher les informations concernant le commercial dans les notes
		* ***48*** Gérer l'apparence du nom du représentant commercial dans les notes (normal ou gras)
		* ***49*** Dans les fiches d'intervention afficher les notes (saisies sur le document) dans un tableau indépendant sous celui des consommations de pièces / services
		* ***50*** Afficher une marque de pliage à droite et à gauche de chaque page (choix de la longueur des marques)
		* ***51*** Choisir la position minimum (en hauteur) de l'en-tête du tableau (permet d'augmenter la taille de l'en-tête de la première page => utile pour la gestion des envelopes à fenêtre)
		* ***52*** Choisir la position en x (largeur) du coin supérieur gauche du cadre d'adresse destinataire
		* ***53*** Choisir la position en y (hauteur) du coin supérieur gauche du cadre d'adresse destinataire
	* OPTIONS DU CORPS DU DOCUMENT (COLONNAGE)
		* ***1*** Choisir la couleur de fond des éléments marqués du tableau (minimum => totaux) par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV)
		* ***2*** Appliquer la couleur de fond définie à l'en-tête du tableau
		* ***3-4*** Choisir le calcul automatique de la couleur (noire ou blanche) de police adaptée au choix précédent ou imposer une couleur choisie (choix graphique ou par code RVB, hexa, ou HSV)
		* ***5*** Choisier la hauteur de l'en-tête des colonnes du tableau (comprise entre 4 et 5 au pas de 0.1 => cette valeur doit être supérieur ou égale à 2 x la valeur du rayon des angles du tableau)
		* ***6*** Afficher / Cacher l’en-tête des colonnes du tableau après la 1ère page
		* ***7-8*** Choisir l'épaisseur et le type de ligne pour les cadres et lignes des tableaux
		* ***9-11*** Choisir la couleur pour les cadres, les lignes verticales et les lignes horizontales (indépendamment) des tableaux (choix graphique ou par code RVB, hexa, ou HSV)
		* ***12*** Choisir la hauteur de l'espace de séparation des lignes (éléments) d'un document (si l'option native d'afficage d'une ligne de séparation est désactivée)
		* ***13*** Choisir la couleur de fond des sous-titres et sous totaux indépendemment par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV)  (version améliorée par InfaS du module d'ATM)
		* ***14-16*** Choisir la couleur du texte et de la description longue des sous-titres et du texte des sous totaux indépendemment par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV)  (version améliorée par InfaS du module d'ATM)
		* ***17*** Couleur de fond à appliquer aux sous-totaux du module sous-total (choix graphique ou par code RVB, hexa, ou HSV) (version améliorée par InfaS du module d'ATM)
		* ***18*** Désactiver l'utilisation d'une couleur de fond (surlignage) pour les sous-totaux (version améliorée par InfaS du module d'ATM)
		* ***19*** Coordonner la couleur de fond (surlignage) des sous-totaux avec celle des sous-titres (version améliorée par InfaS du module d'ATM)
		* ***20*** Fusionner les sous-totaux avec les sous-titres (les montants des sous-totaux sont affichés sur les lignes de sous-titres) (version améliorée par InfaS)
		* ***21*** Choisir la couleur de fond des sous-titres par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module Milestone/Jalon d'iNodbox
		* ***22*** Choisir la couleur du texte des Ouvrages/Forfaits par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea
		* ***23*** Choisir la couleur de fond des Ouvrages/Forfaits par fenêtre de sélection (choix graphique ou par code RVB, hexa, ou HSV) => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea
		* ***24*** Choisir le style du texte des Ouvrages/Forfaits => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea
		* ***25*** Appliquer le style de texte standard aux descriptions longues des ouvrages / forfaits (sinon, le style défini ci-dessus sera appliqué au libellé comme à la description longue)
		* ***26*** Caractère (ou symbole) à utiliser comme puce marquant les détails d'ouvrage (si vide une simple indentation sera utilisée) => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea (version InfraS)
		* ***27*** Choisir la hauteur de l'espace de séparation entre les détails d'un ouvrage => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea (version InfraS)
		* ***28*** Regrouper les lignes de détail des ouvrages ayant le statut "Caché" => demande le module Ouvrages/ForfaitsOuvrages/Forfaits d'Inovea (version InfraS)
		* ***29-30*** Choisir la hauteur et la largeur des codes barres dans les documents commerciaux (hors fiche produit)
		* ***31*** Choisir la taille des codes 2D (code QR) dans les documents commerciaux (hors fiche produit)
		* ***32*** Afficher une colonne 'Num.' (numéro de ligne) dans les documents de la chaîne des ventes => désactive automatiquement l'affichage de la colonne référence de la chaîne des ventes
		* ***33-37*** Afficher une colonne “Réf.” Dans les documents de la chaîne des ventes et celle des achats indépendamment (Référence) => désactive automatiquement l'affichage de la référence avec la description
		* ***38*** Gérer les écotaxes dans les commandes fournisseur suivant le même processus que dans la chaîne des achats (l'attribut suppémentaire dédié à l'écotaxe des produits sera mentionné comme écotaxe incluse dans la description du produit, totalisé dans le document et affiché après le total TTC)
		* ***39*** Forcer l'alignement de la colonne 'Unité'
		* ***40*** Afficher la description sur toute la largeur d'une ligne
		* ***41*** Définir la largeur de la ligne de séparation quand l'option d'affichage de la description sur toute la largeur est active
		* ***42*** Choisir la couleur de la ligne de séparation quand l'option d'affichage de la description sur toute la largeur est active
		* ***43-44*** Choisir la taille et la couleur des périodes associées aux services présents sur le document
		* ***45*** Forcer l'utilisation de la police par défaut (type, taille et couleur) dans les description longues des produits / services
		* ***46*** Cacher les libellés courts des produits / services
		* ***47*** Afficher la description des produits seulement dans les devis / propositions commerciales (active l'option générale masquant la description des produits)
		* ***48*** Dans les documents de la chaine des ventes n'afficher qu'une fois la description longue d'un produit / service utilisé plusieurs fois dans le même document
		* ***49*** Afficher en gras les libellés courts des produits / services (fonctionne uniquement sur les éléments en bibliothèques)
		* ***50*** Positionner les détails additionnels (attributs supplémentaires, informations douanières) avant ou après la description longue des produits / services
		* ***51*** Cacher les dates (de début, de fin, réelles et / ou planifiées) associées aux services dans les documents (devis, commandes, contrats, fiches d'intervention, factures)
		* ***52*** Cacher les durées (totale et ligne par ligne) sur les fiches d'intervention
		* ***53*** Afficher les horaires de début et de fin d'intervention (onglet rapport de la fiche d'intervention. Nécessite le module 'management' - ***Patas-Monkey***)
		* ***54*** Cacher le quantité par ligne dans les documents de la chaîne des ventes (devis, commandes et factures client)
		* ***55*** Cacher le prix unitaire dans les documents de la chaîne des ventes (Devis, commandes et Factures client)
		* ***56*** Cacher la remise par ligne
		* ***57*** Afficher la remise par ligne même pour les lignes optionnelles (sans quantité)
		* ***58*** Afficher une colonne “Prix Unitaire Remisé” dans les documents de vente
		* ***59*** Quand un prix client est défini, afficher le prix par défaut dans la colonne "PU", le prix client dans la colonne "PU remisé" et calculer automatiquement la remise correspondante
		* ***60*** Choisir dans les factures de situation (avancement de travaux) si le total de la ligne (HT et/ou TTC) est calculé par l'avancement total de la ligne (sinon c'est l'avancement de la situation pour cette ligne qui est utilisé)
		* ***61*** Afficher une colonne total TTC (les totaux HT et TTC seront visibles pour chaque ligne - si cette option est décochée seul le total HT sera visible)
		* ***62*** Cacher uniquement la colonne TVA
		* ***63*** Afficher tous les prix en TTC + la TVA en fin de document
		* ***64*** Cacher toutes les informations en rapport avec la TVA (Toutes les valeurs affichées sont TTC)
		* ***65*** Cacher toutes les informations en rapport avec la TVA (Toutes les valeurs affichées sont HT)
		* ***66*** Masquer toutes les colonnes sauf la description produit / service (Devis ou commande client)
		* ***67*** Masquer les colonnes tarifaires (prix unitaires, TVA, remises, totaux, etc...) dans les devis sans totaux en pied de document (InfraSPlus-DST)
		* ***68*** Masquer la colonne des totaux dans les devis sans totaux en pied de document (InfraSPlus-DST)
		* ***69-72*** Afficher les informations de poids, dimensions, volume, surface, la nomenclature douanière (Code SH) et / ou le pays d'origine sur les documents de vente (Devis, commande, Bons de livraison / expéditions ou facture client)
		* ***73*** Quand une facture client est liée à un bon de livraison, afficher les numéros de série des produits présents dans le bon de livraison
		* ***74*** Gérer le positionnement et la largeur de chaque colonne du document (sauf bons de livraison)
		* ***75*** Afficher une colonne 'Code Barre' dans les bons de livraison (InfraSPlus_BL / InfraSPlus_BR) en lieu et place de la référence produit
		* ***76*** Afficher une colonne contenant un attribut supplémentaire issue des Produits (exemple : position dans le stock) dans les bons de livraison (InfraSPlus_BL / InfraSPlus_BR)
		* ***77*** Choisir le code de l'attribut supplémentaire issue des Produits à afficher
		* ***78*** Cacher la quantité commandée dans les bons de livraison (InfraSPlus_BL / InfraSPlus_BR)
		* ***79*** Afficher une colonne 'Reliquat' dans les bons de livraison (InfraSPlus_BL / InfraSPlus_BR)
		* ***80*** Afficher une colonne 'Total HT' dans les bons de livraison (InfraSPlus_BL / InfraSPlus_BR)
		* ***81*** Gérer le positionnement et la largeur de chaque colonne des bons de livraison (InfraSPlus_BL / InfraSPlus_BR)
		* ***82*** Afficher une colonne 'Code Barre' dans les bons de réception (InfraSPlus_RE) en lieu et place de la référence produit
		* ***83*** Afficher une colonne 'Commentaire' dans les bons de réception (InfraSPlus_RE)
		* ***84*** Cacher la quantité commandée dans les bons de réception (InfraSPlus_RE)
		* ***85*** Afficher une colonne 'Reliquat' dans les bons de réception (InfraSPlus_RE)
		* ***86*** Gérer le positionnement et la largeur de chaque colonne des bons de réception (InfraSPlus_RE)
		* ***87*** Afficher les colonnes de valorisation monétaire dans les fiches de stock (InfraSPlus_ST)
		* ***88*** Gérer le positionnement et la largeur de chaque colonne des fiches de stock (InfraSPlus_ST)
		* ***89*** Afficher les numéros de série des produits utilisés dans l'ordre de fabrication (InfraSPlus_MRP)
		* ***90*** Choisir la hauteur du tableau de contrôle (InfraSPlus_MRP)
		* ***91*** Afficher la colonne des dimensions sur les ordres de fabrication (InfraSPlus_MRP)
		* ***92*** Gérer le positionnement et la largeur de chaque colonne des ordres de fabrication (InfraSPlus_MRP)
		* ***93*** Choisir le format à utiliser pour les cartes utilisateur (InfraSPlus_MRP)
		* ***94*** Définir le titre a afficher (InfraSPlus_MRP)
		* ***95*** Afficher la photo de l'utilisateur (InfraSPlus_MRP)
		* ***96*** Afficher le poste / la fonction de l'utilisateur (InfraSPlus_MRP)
		* ***97*** Afficher le n° de téléphone de la société (InfraSPlus_MRP)
		* ***98*** Afficher l'email' de la société (InfraSPlus_MRP)
		* ***99*** Afficher le logo de la société (InfraSPlus_MRP)
	* OPTIONS DU PIED DE DOCUMENT
		* ***1*** Choisir la hauteur de l'espace entre le corps du document (tableau) et les informations de pied de document 
		* ***2*** Choisir la hauteur de l'espace entre le corps du document (tableau) et le total général
		* ***3*** Affichage de la mention rélative au régime du TVA sur les factures
		* ***4*** Afficher les conditions de règlements sur une nouvelle ligne
		* ***5*** Afficher un code de "communication structurée" sur les factures (Belgique)
		* ***6*** Afficher le nombre total d'éléments de type produit dans le document (Devis, commandes et factures client)
		* ***7*** Afficher le total TTC des remises accordées dans le document (Dans la table d'information en bas à gauche)
		* ***8*** intégrer le total HT des remises accordées à la table des totaux (le total HT non remisé sera également affiché)
		* ***9*** afficher le total TTC des remises accordées dans la table des totaux
		* ***10*** Afficher l'encours client en pied de facture
		* ***11*** Inverser la couleur de fond des totaux Ht et TTC (par défaut le total HT n'a pas de couleur de fond mais le total TTC oui)
		* ***12*** Afficher séparément les totaux HT des produits et des services et ventilés par type de TVA (Facture client)
		* ***13*** Présenter les totaux des factures de situation suivant 2 méthodes au choix :
			* le total HT, le total TVA et le total TTC corespondent au cumul des situations, les situations précédentes sont considérées comme des règlements anticipés (sur le TTC) et le TTC de la situation en cours est présenté comme reste à payer.
			* le cumul des situations et les situations précédentes sont affichés HT, puis le total Ht de la situation en cours est présenté avec son total TVA et son total TTC
		* ***14-15*** Pour les société Suisse (CH) non assujettie à la TVA l'utilisation de la TVA forfaitaire est possible dans les factures (Présentation client seule, aucun calcul n'est fait en comptabilité)
		* ***16*** Afficher une ligne de total TTC supplémentaire dans la monnaie locale, pour les documents en devise
		* ***17*** Activer la mention “Arrêté à” + Total TTC en toutes lettres dans les documents de vente. Nécessite le module Number Words actif ! [Téléchargeable ici](https://www.dolistore.com/fr/modules/17-NumberWords.html "page de téléchargement du module Number Words")
		* ***18*** Imprimer un code QR EPC (Conseil européen des paiements) sur les factures (Belgique, Pays-Bas, Allemagne, Autriche et Finlande)
		* ***19*** Sélectionner le mode pour lequel le lien de paiement en ligne est activé (Stripe, Paypal, etc...)
		* ***20*** Afficher le lien de paiement sous forme de QR code
		* ***21*** Afficher le lien vers la création de virement Bridge sur le devis et/ou la facture (Suivant les options choisies dans le module Infras2Bridge) quand le mode de paiement est Virement
		* ***22*** Afficher le QR code vers la création de virement Bridge sur le devis et/ou la facture (Suivant les options choisies dans le module Infras2Bridge) quand le mode de paiement est Virement
		* ***23*** Afficher les acomptes en pied de facture (avant le reste à payer) quelque soit le mode de gestion comptable utilisé
		* ***24-25*** Utiliser des modes de règlements spéciaux (pour ce type de paiement le tableau des détails des règlements sera désactivé et les montants seront séparés du reste des paiements) => gestion de réglements spéciaux type prime tierse (aide gouvernementale, associative, etc...)
		* ***26*** N'afficher QUE l'IBAN et le code BIC du compte proposé pour les règlements (Devis, Commandes, Factures)
		* ***27*** Dans les documents de vente ne jamais afficher le mode de règlement par virement
		* ***28*** Dans les documents de vente afficher le RIB (et/ou IBAN) du compte bancaire lié même pour le mode de règlement de type CB
		* ***29*** Dans les documents de vente afficher le RIB (et/ou IBAN) du compte bancaire lié quelque soit le mode de règlement
		* ***30*** Choisir la hauteur des zones de signature (valeur comprise entre 8 et 48. La largeur est fixe)
		* ***31-33*** Choisir l'épaisseur, le type de ligne et la couleur pour les cadres des zones de signature (choix graphique ou par code RVB, hexa, ou HSV pour la couleur)
		* ***34*** Activer un champ de signature électronique dans les zones de signatures
		* ***35*** Recueillir la signature client (signature PAD) et l'appliquer sur le document dans la zone prévue à cet effet
		* ***36*** Choisir la couleur des signatures clients (choix graphique ou par code RVB, hexa, ou HSV)
		* ***37*** Afficher la zone de signature client sur les devis
		* ***38*** Afficher la zone de signature société émettrice sur les devis
		* ***39*** Afficher la zone de signature client sur les devis sans total en pied
		* ***40*** Afficher le nom complet et la fonction (si disponible) du contact de suivi avant la zone de signature
		* ***41*** Afficher la zone de signature client sur les commandes
		* ***42*** Afficher la zone de signature Tiers (sous-traitant) sur les Ordres de Fabrication (OF créés à partir des commandes) et les Bons de livraison (Expéditions)
		* ***43*** Afficher la zone de signature client sur les contrats
		* ***44*** Afficher la zone de signature client sur les bons de livraison (expédition)
		* ***45*** Afficher la zone de signature client sur les fiches d'intervention
		* ***46*** Afficher la zone de signature société émettrice sur les fiches d'intervention
		* ***47*** Afficher le nom de l'intervenant dans la zone de signature société émettrice sur les fiches d'intervention
	* OPTIONS DU PIED DE PAGE
		* ***1*** Afficher les détails de la société en pied de page (chaque ligne peut être affichée ou masquée indé-pendamment les unes des autres)
			 * Ligne 1 => Adresse du siège social
				* ***2*** Prévoir 2 lignes pour l'affichage de cette adresse (les lignes suivantes sont décalées vers le bas)
			 * ***3*** Ligne 2 => Contacts (téléphone, fax, web et mail)
			 * ***4*** Ligne 3 => Direction
			 * ***5*** Ligne 3 => Forme juridique et capital
			 * ***6*** Ligne 4 => Identifiants professionnels
		* ***7*** Afficher les informations du pied de page en gras
		* ***8*** Ne pas afficher de ligne de séparation au dessus du pied de page
		* ***9*** Créer un pied de page personnalisé à partir d'un fichier PHP
		* ***10*** Remplacer les informations de pied de page par du texte saisie manuellement
		* ***11*** Cacher la numérotation de pages (page x/y) dans les éditions
		* ***12-13*** Définir la position de la numérotation de page sur les éléments concaténés au document (CGV, documentation technique, …)
		* ***14*** Imprimer la LCR avec les factures client quand c'est le moyen de paiement sélectionné
	* GESTION DES CONDITIONS GÉNÉRALES DE VENTE
		* Importer des fichiers PDF comme CGV, CGI ou CGA à utiliser
		* LISTE DES CONDITIONS GÉNÉRALES DE VENTE EXISTANTES
			* Visualiser la liste des fichiers PDF disponibles
		* ***1-2*** Gérer les Conditions Générales de Vente en fonction du type de client (Particulier ou Professionnel)
		* ***3*** Gérer les Conditions Générales de Vente en fonction de la langue
		* ***4*** Insérer des CGV dans les devis, commandes, contrats ou factures
			 * Plusieurs fichiers de CGV différents possible (différentes langues, différentes activités, etc...)
			 * Choix du réglage par défaut (fichier spécifique ou pas de CGV) pour chaque type de document indépendamment (Devis, commande, contrat ou facture)
			 * Choix d'une gestion en fonction de la langue (si l'option multi-langues est activée dans Dolibarr) => le fichier proposé par défaut pour chaque Tiers est en fonction de la langue renseignée pour ce Tiers
		* ***5*** Insérer des CGI dans les fiches d'intervention
			 * Plusieurs fichiers de CGI différents possible (différentes langues, différentes activités, etc...)
			 * Choix d'une gestion en fonction de la langue (si l'option multi-langues est activée dans Dolibarr) => le fichier proposé par défaut pour chaque Tiers est en fonction de la langue renseignée pour ce Tiers
		* ***6*** Insérer des CGA dans les devis ou commandes fournisseurs
			 * Plusieurs fichiers de CGA différents possible (différentes langues, différentes activités, etc...)
			 * Choix du réglage par défaut (fichier spécifique ou pas de CGA) pour chaque type de document indépendemment (Devis ou commande fournisseur)
			 * Choix d'une gestion en fonction de la langue (si l'option multi-langues est activée dans Dolibarr) => le fichier proposé par défaut pour chaque Tiers fournisseur est en fonction de la langue renseignée pour ce Tiers
	* GESTION DES FICHIERS SPÉCIAUX
		* LISTE DES FICHIERS SPÉCIAUX DISPONIBLES (MASQUES PDF)
			* ***1*** Ajouter des fichiers modèles (fichiers PDF associés à des fichiers PHP) => cette option permet de fusionner des PDF en y associant des informatiuons issues de Dolibarr
			* Visualiser la liste des fichiers PDF disponibles
		* LISTE DES FICHIERS SPÉCIAUX DISPONIBLES (TRAITEMENT PDF)
			* ***2*** Ajouter des fichiers modèles (fichiers PHP associés à des fichiers PDF) => cette option permet de fusionner des PDF en y associant des informatiuons issues de Dolibarr
			* Visualiser la liste des fichiers PHP disponibles
* Onglet Images
	* GESTION DES FICHIERS IMAGE UTILISABLES COMME LOGO ET / OU PIED DE PAGE DANS LES DIFFÉRENTES ÉDITIONS DU PACK INFRAS
		* ***1*** Gérer les logos / images de pied de page (téléchargement, nommage, effacement, …)
		* ***2*** Choisir un fichier à afficher par défaut en pied de page (logos partenaires ...)
		* ***3*** Choisir une image à afficher par défaut en filigrane
		* ***4*** Choisir une image à afficher en filigrane sur les cartes utilisateur (InfraSPlus_UST)
		* ***5*** Choisir le Logo secondaire (à afficher sur les en-têtes réduits)
		* ***6*** Choisir une image à inclure dans la zone de signature société émettrice (cachet et / ou signature)
	* LISTE DES LOGOS / PIEDS DE PAGE EXISTANTS
		* Visualiser la liste des fichiers images disponibles
	* OPTIONS CONCERNANT L'UTILISATION D'IMAGES DANS LES ÉDITIONS DU PACK
		* ***1*** Choisir la hauteur du logo dans l’entête principal des éditions (valeur comprise entre 10 et 50. La largeur est calculée proportionnellement mais ne pourra pas être supérieure à 130)
		* ***2*** Utiliser un logo secondaire pour les en-têtes réduits (après la première page)
		* ***3*** Choisir la hauteur du logo pour les en-têtes réduits (après la première page)
		* ***4*** Choisir la largeur maximale de l’image de pied de page (la hauteur est calculée proportionnellement)
		* ***5*** Borner la hauteur maximale de l’image de pied de page (la largeur est calculée proportionnellement)
		* ***6*** Dans les tiers, Enregistrer un des logos de la société émetrice comme fichier à utliser par défaut pour les documents concernant ce tiers
		* ***7*** Dans les documents de la chaine des ventes afficher l'image de chaque produit / service qui contient au moins une image (la première)
		* ***8*** Dans les documents de la chaine des ventes afficher l'image dans la colonne référence (ou numéro de ligne)
		* ***9*** N'afficher que l'image dans la colonne référence (ou numéro de ligne), celle-ci vient en remplacement des valeurs de référence ou de numéro
		* ***10*** Dans les documents de la chaine des ventes n'afficher qu'une fois l'image d'un produit / service utilisé plusieurs fois dans le même document
		* ***11*** Afficher l'image après la description longue des produits / services
		* ***12*** Afficher l'image entre le libellé et la description longue des produits / services
		* ***13*** Choisir la taille de l'intervalle entre l'image du produit / service et le texte de description
		* ***14*** Choisir le texte à afficher comme lien de téléchargement associé à l'image produit / service (renseigné, un lien est créé à coté de l'image du produit avec l'url publique du produit / service)
		* ***15*** Dans les commandes fournisseurs afficher l'image de chaque produit / service contenant au moins une image (la première)
		* ***16*** Largeur maximale des images affichées (la hauteur est calculée proportionnellement)
		* ***17*** Borner la hauteur maximale des images affichées (la largeur est calculée proportionnellement)
		* ***18*** Rechercher les images en utilisant aussi le chemin antérieur à la version 3.7
		* ***19*** Interdire l'utilisation de vignettes (thumb) en lieu et place d'image HQ
		* ***20-21*** Gérer l’opacité des filigranes (du texte utilisé et / ou de l’image de fond indépendamment l’un de l’autre)
		* ***22*** Fixer la largeur de la signature émetteur pour que les dimmensions de l'image correspondent aux dimensions physiques (surtout en cas d'utilisation d'un cachet)
* Onglet Adresses
	* GESTION DES ADRESSES UTILISABLES DANS LES DIFFÉRENTES ÉDITIONS DU PACK INFRAS
		* Gérer la fonction multi-adresses de votre société (création, modification, suppression, …)
		* Choisir une adresse de livraison par défaut (dans la chaîne des achats)
	* LISTE DES ADRESSES ENREGISTRÉES
		* Visualiser la liste des adresses enregistrées pour la société
	* OPTIONS CONCERNANT L'UTILISATION DES ADRESSES DANS LES ÉDITIONS DU PACK
		* ***1*** Lier l'adresse émetteur au pays du destinataire
		* ***2*** Forcer l'affichage du pays dans les adresses (sinon il n'est affiché qu'en cas de différence entre le pays de l'émetteur et celui du destinataire du document)
		* ***3*** Afficher la forme juridique avec le nom de la société dans le cadre d'adresse de l'émetteur
		* ***4-5*** Afficher tous les détails disponibles pour l'émetteur (minimum => adresse, avec cette option vous choisissez d'ajouter le téléphones, le fax, l'email, ou le site web, indépendamment les uns des autres)
		* ***6-11*** Afficher les identifiants professionnels dans l'adresse de l'émetteur (TVA intracommunautaire, Id. prof. 1 “SIREN”, Id. prof. 2 “SIRET”, Id. prof. 3 “NAF – APE”, Id. prof. 4 “RCS/RM”, Id. prof. 5 “EORI”)
		* ***12*** Afficher la forme juridique avec le nom de la société dans le cadre d'adresse du destinataire
		* ***13*** Utiliser l'adresse de facturation de la maison mère (si renseignée) en lieu et place de l'adresse client => l'ensemble de la configuration des automatismes s'applique à la société mère
		* ***14*** Utiliser le nom commercial alternatif (alias) en remplacement de la raison sociale si une adresse émetteur secondaire est sélectionnée pour la génératrion du document
		* ***15*** Gérer l'affichage de l'adresse du destinataire (Adresse du tiers, Adresse du contact lié, Adresse du tiers et affichage du contact ou Adresse du contact et affichage du tiers)
		* ***16*** Automatiser l’utilisation d’une adresse de facturation client spécifique en choisissant le ‘label’ caractérisant cette adresse
		* ***17*** Toujours afficher une adresse de livraison (même si l'adresse de facturation automatique est inactive)
		* ***18*** Afficher automatiquement (par défaut) une adresse de livraison quand l'adresse de facturation automatique est active
		* ***19*** Afficher l'adresse de livraison sur les documents fournisseurs (devis, commandes)
		* ***20*** Afficher l'adresse de livraison sur les documents client (devis, commandes, factures)
		* ***21-22*** Afficher tous les détails disponibles des coordonnées de livraison (par défaut Adresse seule, option activée = + tél. + Fax + Email + Web)
		* ***23*** Utiliser la gestion native des adresses de livraison (par les contacts de suivi livraison)
		* ***24*** Saisir manuellement l'adresse de livraison => utilisation d'un attribut supplémentaire dédié
		* ***25*** Demander la livraison directement à une adresse client (principale ou secondaire) si l'adresse de livraison sur les documents fournisseurs (devis, commandes) est activée, => Liste de choix avec auto-complétions au 2ème caractère entrée
		* ***26*** Dans le cadre de l'utilisation de la gestion native des adresses de livraison, remplacer les informations du destinataire par celles du contact de livraison sélectionné pour les les commandes (clients ou fournisseurs)
		* ***27*** Utiliser le contact de facturation Dolibarr (type externe 'BILLING') comme adresse destinataire
		* ***28-29*** Afficher des détails supplémentaires disponibles pour le destinataire (minimum => adresse, avec cette option vous choisissez d'ajouter le téléphones, le fax, l'email, ou le site web, indépendamment les uns des autres)
		* ***30-31*** Afficher l'adresse d'un contact externe d'un client comme adresse de sous-traitant dans les Ordres de Fabrication (OF créés à partir des commandes) => demande le module CustomLink des Patas-Monkey
* Onglet Attributs supplémentaires
	* OPTIONS CONCERNANT L'UTILISATION DES ATTRIBUTS SUPPLÉMENTAIRES DANS LES ÉDITIONS DU PACK
		* ***1*** Choisir la couleur du texte appliquée aux valeurs des attributs supplémentaires des documents (choix graphique ou par code RVB, hexa, ou HSV)
		* ***2*** Sélectionner les attributs supplémentaires liés à un règlement spécial (Ces attributs doivent contenir une valeur numérique ou monétaire ; ils ne seront pas affichés dans les notes mais intégrés au calcul des totaux comme déductions spéciales)
			* Si la liste saisie ne correspond pas aux attributs supplémentaires 
		* ***3*** Sélectionner l'attribut supplémentaire utilisé pour la gestion des acomptes (deposit)(Cet attribut doit contenir une valeur numérique ; il ne sera pas affiché dans les notes mais mentionné après le total TTC comme pourcentage d'acompte demandé)
		* ***4*** Sélectionner l'attribut supplémentaire utilisé pour la gestion des écotaxes(Cet attribut doit contenir une valeur numérique ou monétaire ; il sera mentionné comme écotaxe incluse dans la description du produit, totalisé dans le document et affiché après le total TTC)
		* ***5*** Sélectionner l'attribut supplémentaire utilisé pour la gestion des devis provisoires (Cet attribut doit contenir une date ; si cette date n'est pas renseignée le devis sera considéré comme provisoire et la mention associée sera affichée en filigrane du document) => exemple : date de prévisite
		* ***6*** afficher les attributs supplémentaires de document sous forme de liste à puces (sinon un simple saut de ligne séparera les différents attributs)
		* ***7-17*** Afficher les attributs supplémentaires des devis, commandes, contrats, fiches d’intervention, expéditions, factures, Ordres de fabrication, Nomenclatures, demandes de prix, commandes et / ou factures fournisseur dans les notes de ces documents (l’activation de cet affichage pour chaque type de document est indépendant)
		* ***18*** Choisir la couleur du texte appliquée aux valeurs des attributs supplémentaires des lignes de document (choix graphique ou par code RVB, hexa, ou HSV)
		* ***19-28*** Afficher les attributs supplémentaires des lignes de devis, commandes, contrats, fiches d’intervention, expéditions, factures, Ordres de fabrication, Nomenclatures, demandes de prix, commandes et / ou factures fournisseur (l’activation de cet affichage pour chaque type de document est indépendant)
* Onglet Mentions complémentaires
	* Ajouter des mentions complémentaires sur les fiches produits (actif dès l'activation du module)
	* GESTION DES MENTIONS COMPLÉMENTAIRES SUPPLÉMENTAIRES DANS LES ÉDITIONS DU PACK
		* Créer plusieurs types de mentions complémentaire distincts pour chaque type de document
		* Gérer les mentions complémentaires des différents types de documents (devis, commandes, contrats, expéditions, réceptions, fiches d’intervention, factures, Ordres de fabrication, Nomenclatures, demandes de prix, commandes fournisseur, fiche produit et notes de frais) d'une même page de paramètres
	* OPTIONS CONCERNANT L'UTILISATION DES MENTIONS COMPLÉMENTAIRES DANS LES ÉDITIONS DU PACK
		* ***1-11*** Intégrer systématiquement les mentions complémentaires de base (le choix se fait pour chaque type de document indépendemment les uns des autres)
		* ***12*** Afficher les mentions complémentaires en dernier et sur la largeur de la page
		* ***13-14*** Automatiser l'utilisation d'une mention liée à une banque (Factor)
		* ***15*** Gérer automatiquement les mentions obligatoires relatives à la TVA (franchise en base de TVA, autoliquidation, export)
		* ***16-21*** Enregistrer la mention obligatoire à utiliser pour les différentes situations possibles (Micro-entreprise, autoliquidation, exonération, Sous-traitance BTP)
* Onglet Notes publiques
	* Ajouter des notes publiques standards sur les fiches produits (actif dès l'activation du module)
	* GESTION DES NOTES PUBLIQUES STANDARDS DANS LES ÉDITIONS DU PACK
		* Créer plusieurs types de notes publiques distincts pour chaque type de document
		* Gérer les notes publiques standards des différents types de documents (devis, commandes, contrats, expéditions, réceptions, fiches d’intervention, factures, Ordres de fabrication, Nomenclatures, demandes de prix, commandes fournisseur, produits, projets, notes de frais) d'une même page de paramètres
	* OPTIONS CONCERNANT L'UTILISATION DES NOTES PUBLIQUES STANDARDS DANS LES ÉDITIONS DU PACK
		* ***1*** Utiliser un type de note publique pour créer une page de garde dans les documents clients
		* ***2-13*** Intégrer systématiquement les notes publiques standards de base (le choix se fait pour chaque type de document indépendemment les uns des autres)
* Onglet Options avant génération
	* ***1-30*** Pour chaque option disponible avant la génération du document choisir le type d'enregistrement du réglage (par utilisateur, par document (référence), par type (devis, commande, ...), par client ou non enregistré) 



## Réglages disponibles avant génération du document

* ***Gérer les droits d'accès aux réglages avant génération du document*** (Sans droits, seule la collecte de la signature client est disponible si elle est activée)
* Choisir le logo, l’adresse expéditeur et / ou l’image en pied de page (marques commerciales, partenaires, communication, …)
* Choisir l'affichage de l'adresse du destinataire (Adresse du tiers, Adresse du contact lié, Adresse du tiers et affichage du contact ou Adresse du contact et affichage du tiers)
* Choisir la / les mention(s) complémentaire(s) disponible(s) pour ce type de document à intégrer au fichier PDF généré
* Choisir la / les note(s) publique(s) standrad(s) disponible(s) pour ce type de document à intégrer au fichier PDF généré
* Choisir une adresse de livraison (dans les documents fournisseurs. Cette adresse peut inclure les adresses société comme celles des clients)
	 * Saisie rapide disponible
* Choisir un sous-traitant dans la liste des contacts externes déclarés via le module customLink et choisir son adresse (si plusieurs adresses sont déclarées pour ce tiers sous-traitant)
	 * Saisie rapide disponible
* ***Gérer les droits d'accès aux choix de CGV, CGA ou CGI avant génération du document*** (Sans droits, le choix est masqué et les paramètres généraux sont imposés à l'utilisateur)
* Choisir les CGV, CGA ou CGI à inclure ou ne pas en inclure du tout
* Choisir un ou plusieurs fichier(s) joint(s) pour le(s) fusionner avec le document généré.
* Inclure les alias dans le nom des tiers
* Fusionner la documentation produit / service avec les devis (si cette option est présente elle n'est jamais mémorisée)
* Afficher une page de garde (le contenu de cette page dépend d'une note publique dédiée)
* Gérer l'affichage de l'adresse du destinataire (Adresse du tiers, Adresse du contact lié, Adresse du tiers et affichage du contact ou Adresse du contact et affichage du tiers)
* Inclure ou exclure les informations douanières (dimensions, poids, volume, surface et / ou code SH) pour chaque ligne, dans les documents de vente (export à l’international)
* Afficher l'image de chaque produit / service qui contient au moins une image (la première)
* Afficher la colonne référence (si l'option est présente dans les paramètres de bases)
* Cacher les durées (total et ligne par ligne) pour les interventions
* Cacher la description longue des produits / services (seul le libellé court sera imprimé) => En fonction des paramètres généraux validés cette option est activée automatiquement pour les commandes client, les bons de livraison (expédition) et les factures
* Cacher la remise par ligne (prix nets)
* Imprimer sur les lignes de détails seulement la description des produits / services (pour les fiches d'intervention associée au module Management des Patas-Monkey l'affichage de la colonne Quantité n'est pas géré par cette option)
* Afficher une colonne 'Total HT' dans les bons de livraison (InfraSPlus_BL / InfraSPlus_BR)
* Afficher le total des remises accordées en pied de document
* Afficher / masquer le mode de règlement par virement
* Afficher / masquer l'utilisation des modes de règlements spéciaux (devis / proposition commerciales)
* Afficher ou masquer les totaux (HTs, TVAs, TTC) en pied de document (dans les fiches d'intervention associée au module Management des Patas-Monkey)
* Désactiver l'adresse de facturation client automatique
* Recueillir la signature client (signature PAD) et l'appliquer sur le document dans la zone prévue à cet effet
	 * Saisie rapide disponible
* Enregistrer automatiquement les réglages utilisateur (par utilisateur pour chaque type de document)
* Enregistrer automatiquement les réglages documents (par type de document : devis, commande,...)
* Enregistrer automatiquement les réglages client (par client)
* Enregistrer automatiquement les réglages documents (par référence)



## Ce qui est nouveau

Voir fichier ChangeLog.



## Documentation

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/index.php?title=InfraSPackPlus "wiki InfraS").


