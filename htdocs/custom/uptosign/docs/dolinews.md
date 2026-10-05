---
title: "UptoSign 2.4.16 : améliorations diverses"
summary: "Cette version débloque trois situations où l'utilisateur pouvais retrouver : l'onglet de signature sans retour possible, la fenêtre de signature sans bouton de fermeture, et le lot déjà en cours qu'on pouvait renvoyer. Elle gère aussi la situation où serveur de signature demande une pause."
type: release
version: "2.4.16"
focus: bugfix_major
project: uptosign
maturity: stable
compat_status: declared
dolibarr_min: 15
dolibarr_max: 23
locale: fr_FR
---

## Amélioration ergonomique

Trois écrans pouvaient laisser l'utilisateur sans porte de sortie. C'est
l'essentiel de cette version.

L'onglet de signature d'un tiers perdait les onglets de la fiche : une fois
dedans, plus moyen de revenir à la fiche autrement qu'en revenant en arrière
dans le navigateur. Les onglets sont rétablis, et le même correctif vaut pour
les factures fournisseur, les commandes fournisseur et les comptes bancaires.

La fenêtre de signature n'affichait pas toujours de bouton de fermeture, en
particulier quand une procédure était déjà en cours. Elle en a désormais un
quoi qu'elle affiche, et un clic en dehors la referme.

Un document supprimé ou devenu inaccessible affichait une page vide. Il le dit
maintenant.

## Ralentir le serveur le demande

Quand le serveur de signature répond qu'il reçoit trop de demandes, le module
cessait pourtant de l'interroger... jusqu'au chargement de page suivant. La
pause est désormais retenue et survit d'une page ou d'un webhook à l'autre.

Dans la même veine, la tâche planifiée qui récupère les archives espace ses
téléchargements et limite le nombre de récupérations simultanées. Et une
procédure que le serveur ne connaît plus est marquée expirée plutôt que
redemandée chaque nuit.

## Autres corrections

- la liste groupée ne propose plus d'envoyer un lot déjà en cours d'envoi ;
- une signature ne démarre plus si aucune position de signataire n'a été
  placée : le contrôle existait mais ne se déclenchait jamais ;
- libellés français et anglais manquants, dupliqués ou mal traduits ;
- à l'activation du module, un message signale qu'une version plus récente
  d'UptoSign est disponible.

## Prérequis

Dolibarr 18 ou supérieur, PHP 7.4 ou supérieur.
