---
title: "FAQ"
weight: 50
description: "Questions fréquentes sur le module UptoSign : compte, signature, erreurs courantes et bonnes pratiques."
---

# FAQ

## Compte et connexion

### Comment créer un compte UptoSign ?

Si vous n'avez pas encore de compte, renseignez vos identifiants dans l'onglet **Serveur** de la configuration et cliquez sur **Tester la connexion**. Le module tente de créer automatiquement votre compte sur le serveur choisi. Vous pouvez également créer un compte manuellement sur [uptosign.com](https://www.uptosign.com/).

### Le test de connexion échoue, que faire ?

Vérifiez que :

1. L'adresse e-mail et le mot de passe sont corrects
2. Vous avez accepté les CGU
3. Le serveur choisi (production ou démonstration) correspond bien à votre compte

Si le problème persiste, utilisez le lien **Réinitialisation du mot de passe** proposé par le module.

### Puis-je utiliser le serveur de démonstration pour tester ?

Oui. Le serveur de démonstration est gratuit et permet de tester toutes les fonctionnalités sans consommer votre quota de signatures. Les documents signés sur ce serveur n'ont pas de valeur légale.

## Signature

### Quelle est la différence entre signature et scellement ?

La **signature** implique un ou plusieurs signataires qui doivent valider leur identité (par SMS ou e-mail). Elle engage juridiquement les parties.

Le **scellement** garantit l'intégrité et l'horodatage du document, sans intervention d'un tiers signataire. Il certifie que le document n'a pas été modifié après une date donnée.

### Le numéro de téléphone du signataire est rejeté, pourquoi ?

Le numéro de téléphone mobile doit être au format international, par exemple `+33612345678`. Vérifiez le numéro dans la fiche du contact ou de l'utilisateur Dolibarr.

### Comment positionner automatiquement les signatures ?

Activez l'option **Utiliser PdfToText** dans les paramètres du module, puis utilisez les [mots-clés magiques](https://doc.cap-rel.fr/projet_uptosign/utiliser_des_mots-cles_magiques) dans vos modèles de documents PDF. Le module détecte automatiquement la position du mot-clé et y place la signature.

La commande `pdftotext` doit être installée sur votre serveur.

### Le signataire ne reçoit pas l'e-mail, que faire ?

Vérifiez que :

1. L'adresse e-mail du contact est correctement renseignée dans Dolibarr
2. Le contact est bien désigné comme signataire (onglet **Contacts/Adresses** de la fiche)
3. L'e-mail n'a pas été filtré par un anti-spam

### Comment envoyer le code par e-mail plutôt que par SMS ?

Dans l'onglet **Paramètres**, section **Zone expérimentale**, modifiez le choix du mode d'envoi du code de double authentification. Vous pouvez également personnaliser ce choix par tiers en activant l'option correspondante.

> **Attention** : l'envoi du code par e-mail affaiblit le niveau de la signature (un seul canal de communication au lieu de deux). La signature par SMS est considérée comme une signature simple renforcée.

## Erreurs courantes

### "Aucun contact n'est désigné pour la signature"

Rendez-vous dans l'onglet **Contacts/Adresses** de la fiche du document et affectez le rôle **Signature électronique** au contact client qui doit signer. Si l'option **Autoriser tout contact lié à signer** est activée dans les paramètres, tout contact lié au document peut signer sans avoir ce rôle spécifique.

### "Il n'y a pas de configuration pour ce modèle de document"

Vous devez créer une configuration dans l'onglet **Modèles de documents** de la configuration du module. Associez le type d'objet et le nom exact du modèle de document PDF que vous utilisez.

### "Aucun fichier PDF n'est associé à cet objet"

Générez le PDF du document avant de lancer le processus de signature. Depuis la fiche du document, utilisez le bouton de génération du PDF.

### "La version du module n'est pas cohérente"

Ce message apparaît lorsque la version du code du module ne correspond pas à la version de la base de données. Désactivez puis réactivez le module depuis **Accueil > Configuration > Modules/Applications**.

### "La configuration sécurité de votre Dolibarr est incomplète"

Votre Dolibarr n'a pas de système de chiffrement correctement configuré. Consultez la [documentation technique de sécurité](https://doc.cap-rel.fr/projet_dolibarr/securite) et configurez les clés de sécurité.

## Documents signés

### Puis-je modifier un document signé ?

Non. Un document signé ne peut être ni modifié ni supprimé. Si vous re-générez le PDF d'un document signé, le module affiche un avertissement et vous demande confirmation car le scellement et/ou la signature seront perdus.

### Où se trouvent les documents signés ?

Les documents signés sont stockés dans les **fichiers joints** de la fiche Dolibarr correspondante. Le fichier signé porte le suffixe configuré dans les paramètres (par défaut : `-signe`). Le dossier de preuves est également disponible.

### Qu'est-ce que la vérification d'intégrité ?

La vérification compare la somme de contrôle (empreinte numérique) du fichier présent sur votre espace de stockage avec celle du fichier original. Si les deux correspondent, le fichier n'a pas été modifié depuis sa signature ou son scellement.

## Tâches planifiées

### Quelles tâches planifiées le module installe-t-il ?

| Tâche | Fréquence | Description |
|-------|-----------|-------------|
| Archivage automatique | Quotidienne | Vérifie que les documents signés disposent d'une copie locale et re-télécharge les fichiers manquants |
| Revendeurs | Quotidienne | Met à jour les données de facturation des clients revendeurs (réservé aux comptes revendeurs) |

Ces tâches sont désactivées par défaut. Activez-les dans **Accueil > Configuration > Tâches planifiées** si nécessaire.
