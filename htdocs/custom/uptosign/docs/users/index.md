---
title: "UptoSign"
weight: 1
description: "Module Dolibarr de signature électronique et de scellement de documents en ligne, conforme eIDAS, via un cloud 100 % français."
---

# UptoSign

## Présentation

**UptoSign** est un module Dolibarr qui permet de signer et sceller vos documents professionnels de manière électronique, à distance, via un cloud hébergé en France. Il est conforme à la norme eIDAS (signature simple renforcée).

Le module repose sur le service en ligne [uptosign.com](https://www.uptosign.com/) et s'intègre directement dans l'interface de Dolibarr : un onglet **Signature électronique** est ajouté sur chaque type de document pris en charge.

## Fonctionnalités principales

- **Signature électronique** — Envoyez un document à un ou plusieurs signataires. Chaque signataire reçoit un lien par e-mail et valide son identité par SMS ou e-mail (double authentification).
- **Scellement et horodatage** — Scellez un document pour en garantir l'intégrité et l'horodatage, sans intervention d'un tiers signataire.
- **Signature en masse** — Créez une liste de destinataires et lancez la signature d'un même document auprès de plusieurs personnes en une seule opération.
- **Signature locale** — Lancez le processus de signature directement en présence de votre client, sans lui envoyer de lien par e-mail.
- **Page publique de signature** — Vos correspondants signent ou refusent les documents depuis une page web dédiée, sans avoir besoin d'un compte Dolibarr.
- **Vérification d'intégrité** — Contrôlez la somme de contrôle des fichiers signés pour détecter toute modification.
- **Archivage automatique** — Une tâche planifiée vérifie quotidiennement que les documents signés disposent d'une copie locale et re-télécharge les fichiers manquants.
- **Automatisations** — Création automatique de factures à la signature d'un devis, scellement automatique, envoi de factures par e-mail, fermeture de commandes.
- **Dossier de preuves** — Téléchargez le dossier de preuves associé à chaque signature.
- **Pseudo-anonymisation** — Masquage partiel des adresses e-mail et numéros de téléphone sur les documents produits (option payante).

![Onglet de signature électronique sur un devis Dolibarr avec le positionnement du sceau et des signatures](screenshots/onglet-signature-devis.webp)

## Documents pris en charge

UptoSign ajoute un onglet **Signature électronique** sur les fiches suivantes :

| Type de document | Description |
|------------------|-------------|
| Propositions commerciales (devis) | Signature client et/ou interne |
| Commandes clients | Signature et fermeture automatique |
| Commandes fournisseurs | Signature fournisseur |
| Factures | Scellement et signature |
| Contrats | Signature avec activation automatique des services |
| Fiches d'intervention | Signature technicien et/ou client |
| Projets | Signature de documents de projet |
| Tiers | Signature de documents liés au tiers |
| Utilisateurs | Signature de documents liés à l'utilisateur |

## Cas d'usage

### Signature de devis à distance

Vous créez un devis dans Dolibarr, générez le PDF, puis cliquez sur l'onglet **Signature électronique**. Vous positionnez le sceau UptoSign et la zone de signature sur le document, puis envoyez la demande. Votre client reçoit un e-mail avec un lien vers la page de signature. Il accepte et signe en ligne, en validant son identité par un code SMS. Le document signé est automatiquement rapatrié dans Dolibarr.

### Scellement de factures en masse

Depuis la liste des factures, vous sélectionnez plusieurs factures et lancez l'action de masse **Sceller un lot de factures**. Chaque facture est scellée et horodatée individuellement via le service UptoSign.

### Signature de contrats avec activation automatique

Lorsqu'un contrat est signé via UptoSign, le module peut automatiquement activer tous les services du contrat, évitant une intervention manuelle.

## Prérequis

- Dolibarr 15.0 ou supérieur
- PHP 7.0 ou supérieur
- Extensions PHP : `intl`, `fileinfo`, `gd`, `mbstring`
- Module ECM (Gestion Électronique de Documents) activé dans Dolibarr
- Un compte sur [uptosign.com](https://www.uptosign.com/) (un serveur de démonstration gratuit est disponible pour les tests)

## Support

- Documentation technique : [doc.cap-rel.fr/projet_uptosign](https://doc.cap-rel.fr/projet_uptosign/)
- Éditeur : [CAP-REL](https://cap-rel.fr)
- Support disponible via le [formulaire de contact dédié](https://cap-rel.fr/sav-module-dolibarr/)
