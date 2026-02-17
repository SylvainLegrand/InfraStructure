Module Osden
==================
Module spécifique pour Osden.


Utilisation du script
---------------------

Le module Osden contient un script qui permet de charger des constantes depuis un fichier CSV.

Il s'utilise en ligne de commande, dans ce cas les constantes seront chargées dans l'entité 0 (toutes les entités):

`php custom/osden/scripts/load_parameters.php path/to/constants_file.csv`

Ou depuis l'interface admin de Osden, dans ce cas les constantes seront appliquées à l'entité courante.

Voici un exemple de fichier CSV (la première ligne peut être conservée. "type" est le plus souvent "chaine"):
```
name,value,type,visible,note
OBLYON_COLOR_TOPMENU_BCKGRD,#eb4c42,chaine,1,"test color"
```

Il existe une constante cachée OSDEN_ONLY_SHOW_PUBLIC_TICKET_EF qui permet d'afficher les extrafields dans l'interface publique des tickets.
