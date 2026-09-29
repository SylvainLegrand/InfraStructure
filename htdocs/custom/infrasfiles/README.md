![](img/object_infrasfiles.png)



## ***InfraSFiles***
#### Développé par ***InfraS*** - Membre du programme officiel ![](img/Dolibarr_preferred_partner_small.png), gage de qualité et d’expertise.
* ***InfraSFiles*** apporte aux objets Dolibarr qui n'en disposent pas nativement les trois fonctions habituelles des documents :
	* La génération de documents PDF (section « Fichiers joints » en bas de la fiche, avec choix du modèle et de la langue)
	* L'onglet « Fichiers joints » en haut de la fiche (fichiers générés ou déposés à la main, fichiers liés, compteur sur l'onglet)
	* L'envoi par email depuis la fiche (dernier PDF pré-attaché, modèles de mails, événement dans l'agenda)



## LICENCE

***InfraSFiles*** est distribué sous les termes de la licence GNU General Public License v3+ ou supérieure. ![](img/gplv3.png)

Copyright (C) 2026 Lucky Ranasolonirina - InfraS

voir le fichier LICENSE pour plus d'informations

## Autres Licences

Utilise PHP Markdown de Michel Fortin sous licence BSD pour afficher ce fichier README



## Ce qu'est ***InfraSFiles***

***InfraSFiles*** est un module optionnel de Dolibarr ERP & CRM (versions 18 à 24). C'est un **socle générique** : chaque objet pris en charge est une simple déclaration dans un registre, accompagnée d'un modèle PDF. Les tables natives ne sont jamais modifiées : l'état documentaire (dernier modèle, dernier fichier) vit dans une table du module.

Objets pris en charge :
* **Bons de prélèvement et de virement** (module Prélèvement / Virement) — modèle « bordereau » : créancier ou donneur d'ordre, débiteur ou bénéficiaire (tiers ou sa maison mère, IBAN, BIC, RUM), factures ou salaires concernés avec date, échéance, tiers facturé et avoirs appliqués, statut de chaque ligne (en attente, débité / crédité, rejeté), total et montant rejeté. Avec le module InfraSPackPlus, un second modèle « InfraSPlus_Bon » (même contenu, mise en page et options InfraSPackPlus) est proposé
* **Inventaires** (module Stock) — modèle « comptage » : feuille de comptage avec les informations de l'inventaire en en-tête (logo, référence, libellé, entrepôt, filtres, date, statut), références regroupées par zone de stockage dans l'ordre des zones, case vide de relevé, colonne lot / série si besoin, quantité théorique masquée par défaut, zone « Compté par / Date / Signature ». Avec le module InfraSPackPlus, un second modèle « InfraSPlus_INV » (même contenu, mise en page et options InfraSPackPlus) est proposé

Les modules tiers peuvent déclarer leurs propres objets via le hook `infrasFilesRegisterObjects`.



## Fonctionnalités (toutes optionnelles, par document)

* Activer ou non chaque document pris en charge
* Activer la génération PDF et l'onglet « Fichiers joints »
* Activer l'envoi par email — destinataires proposés : contacts des tiers du bon (ou leur adresse en saisie libre), utilisateurs, saisie libre
* Choisir les modèles de documents proposés, le modèle par défaut, et les prévisualiser (spécimen)
* Texte libre en pied de page et filigrane sur les documents brouillon
* Bons de prélèvement : découpage des PDF => un PDF par tiers (regroupant ses factures) ou un PDF par maison mère (regroupant les tiers rattachés à elle qui ont coché l'attribut « Adresser les bordereaux à la maison mère » sur leur fiche ; les autres tiers gardent leur propre PDF)
* Inventaires : affichage ou non de la quantité théorique. La zone de stockage suit la colonne « Zone » du module InfraSWorkflow (section « Gestion des inventaires » : attribut supplémentaire du produit, dans l'ordre des valeurs de la liste, ou sous-catégories d'une catégorie de localisation, dans leur ordre de création)
* Modèles de mails dédiés (types « Bons de prélèvement et de virement » et « Inventaires » dans Configuration → Emails → Modèles), un modèle par défaut créé à l'activation
* Événements agenda automatiques « envoyé par email », désactivables dans la configuration de l'agenda
* Téléchargement des fichiers soumis à la permission native de chaque objet
* Sauvegarder / restaurer l'ensemble des paramètres du module



## CE QUI EST NOUVEAU

Voir fichier ChangeLog.



## DOCUMENTATION

La documentation est disponible sur le site [wiki.infras.fr](https://wiki.infras.fr/books/infrasfiles "wiki InfraS").
