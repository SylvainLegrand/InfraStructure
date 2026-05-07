---
title: "FAQ"
weight: 50
description: "Réponses aux questions fréquentes sur ScanInvoices."
---

# Questions fréquentes

## Combien d'analyses sont incluses avec le serveur d'OCR de CAP-REL ?

Le serveur public `https://ocr.cap-rel.fr` offre 5 analyses gratuites par mois. Au-delà, vous pouvez souscrire un abonnement sur https://shop.cap-rel.fr/ ou héberger votre propre serveur d'OCR compatible.

## Le bouton "Vérifier la connexion" affiche une erreur de blacklist, que faire ?

Cela arrive fréquemment sur les serveurs mutualisés où l'adresse IP de sortie est partagée avec d'autres clients. Le message d'erreur contient un lien direct vers `https://bl.cap-rel.fr/check.php` pour demander le retrait de votre adresse IP de la liste noire. L'adresse IP de votre serveur Dolibarr est aussi affichée en haut de la page de configuration.

## La création automatique du fournisseur ne fonctionne pas, pourquoi ?

La création automatique repose sur le numéro de TVA présent sur la facture. Elle ne fonctionne que pour les numéros de TVA français, belges et suisses. Pour les autres pays, ou si la facture ne mentionne pas de numéro de TVA, créez le fournisseur manuellement avant l'import.

## Le numéro de TVA est mal détecté, comment corriger ?

Lancez un import manuel pour ce fournisseur : ajustez la zone d'analyse autour du numéro de TVA et relancez l'OCR. Les zones sont mémorisées sur la fiche tiers et seront réutilisées au prochain import.

## La facture importée n'a qu'une seule ligne globale, comment avoir le détail ?

Vérifiez l'onglet **Import des lignes** de la configuration : l'option **Désactiver l'import automatique des lignes de factures** doit être décochée. Si elle est décochée et que les lignes ne sont toujours pas extraites, cela signifie que l'OCR n'a pas pu reconnaître le tableau. Lancez un import manuel pour ajuster les zones.

## Puis-je importer plusieurs factures à la fois ?

Oui : utilisez l'**Import automatique** par glisser-déposer. Vous pouvez déposer plusieurs PDF d'un coup. Pour de gros lots, utilisez la file **Plus tard** afin que l'analyse se fasse pendant la nuit sans pénaliser les utilisateurs en journée.

## Les factures déposées dans le dossier Nextcloud ne sont pas importées, pourquoi ?

Trois vérifications :

1. La tâche planifiée **ScanInvoices service d'import automatique** est-elle activée dans **Accueil > Configuration > Tâches planifiées** ? Sa fréquence par défaut est de 3 heures.
2. Le bouton **Vérifier la connexion** dans l'onglet **Partage** de la configuration affiche-t-il bien le contenu du dossier ?
3. Les factures sont-elles au format PDF et de taille inférieure à 8 Mo ?

Si l'un de ces points pose problème, consultez le compte rendu d'import envoyé par email.

## Pourquoi le module renomme-t-il mes PDF ?

Pour faciliter le classement, le PDF attaché à la facture fournisseur est renommé selon le paramètre **Référence à utiliser comme nom de pièce jointe** (onglet **Réglages**). Vous pouvez choisir entre la référence interne Dolibarr (`FACF2401-0001.pdf`) et le numéro de facture présent sur le document, et ajouter un préfixe optionnel.

## Une facture s'affiche en double dans la liste des imports, est-ce normal ?

Le module détecte les doublons à l'import (même fournisseur + même numéro de facture + même date) et affiche un message d'erreur sans créer la facture en double. Si vous voyez deux entrées dans la liste **Imports**, l'une est probablement marquée comme **doublon** et la facture associée à l'import original.

## Puis-je modifier la facture après l'import ?

Oui, la facture créée par ScanInvoices est une facture fournisseur Dolibarr standard : tous les outils habituels (modification, validation, paiement) restent disponibles. Le PDF d'origine est attaché à la fiche.

## Les conditions de règlement de la facture importée ne correspondent pas à celles de ma fiche tiers, pourquoi ?

Par défaut, les conditions présentes sur la facture (date d'échéance, mode de paiement) sont prises en priorité. Si vous préférez utiliser celles de votre fiche tiers Dolibarr, cochez l'option **Forcer la date de règlement, le moyen et le compte selon la fiche tiers** dans l'onglet **Réglages** de la configuration.

## Le module est-il compatible avec mon Dolibarr ?

ScanInvoices fonctionne à partir de Dolibarr 11. Pour les versions très récentes, consultez la page DoliStore pour vérifier la compatibilité affichée. Le numéro de version installé est rappelé dans **Accueil > Configuration > Modules/Applications** sur la ligne du module.

## J'ai mis à jour le module mais je ne vois pas les nouveautés, que faire ?

Une mise à jour nécessite de **désactiver puis réactiver** le module pour appliquer les évolutions de la base de données et enregistrer les nouveaux menus. Voir [Installation > Mise à jour](/scaninvoices/installation).

## Mon serveur d'OCR est en cours de mise à jour, mes imports échouent

C'est temporaire : le serveur affiche un message **Mise à jour du serveur en cours**. Patientez quelques minutes et réessayez. Pendant la mise à jour, n'envoyez pas de nouveaux fichiers : ils seraient marqués en erreur. La file **Plus tard** continue de fonctionner et reprendra automatiquement à la prochaine exécution.

## Existe-t-il une application mobile ?

Sur cette branche du module, l'import se fait depuis l'interface web Dolibarr (depuis un navigateur, y compris mobile). Le bouton **Photo...** de l'import manuel permet de prendre une photo directement depuis un téléphone via le navigateur.

## Où trouver de l'aide supplémentaire ?

- Page **À propos** du module dans la configuration : liens vers la documentation, le SAV, le forum, le contact commercial.
- Site éditeur : https://cap-rel.fr
- Demande de support : `commercial+scaninvoices@cap-rel.fr`
