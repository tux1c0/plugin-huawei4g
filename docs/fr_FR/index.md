Description 
===

Plugin permettant la supervision de routeur 4G Huawei.
Les informations supervisées
-	Volumétrie de données
-	Opérateur
-	SMS

Installationn 
===

-	Le plugin, une fois installé, doit être activé. Il installera les dépéndances nécessaires au bon fonctionnement.
-	Le plugin est compatible Debian 9/10, Jeedom v3/v4.



Configuration
===

### Routeur 

Le routeur doit être accessible via le réseau privé afin de pouvoir récupérer des informations.

Un login et mot de passe doit être défini sur le routeur.

Le plugin est compatible avec le modèle suivant
-	Huawei B715s-23c
-	Huawei B528s-23a
-	Huawei B612s-25d

Le plugin devrait être compatible avec les modèles suivants (non testés)
-	Huawei B310s-22
-	Huawei B525s-23a
-	Huawei B525s-65a
-	Huawei E3131
-	Huawei E5186s-22a
-	Autres modèles LTE non listés

Le plugin n'est pas compatible avec le modèle suivant
-	Huawei B2368-22

### Plugin

Tous les éléments suivants sont obligatoires pour avoir le plugin fonctionnel

-   IP : adresse IP du routeur

-   API (login et mot de passe de connexion du routeur définit au-dessus)


Sauvegarder la configuration. Le module va commencer à poller toutes les 15 minutes le routeur.



Changelog
===

-	18/01/2020 : Init du plugin
-	08/02/2020 : 1ere version beta fonctionnelle
-	12/03/2020 : Passage en prod
-	15/03/2020 : Ajout nombre SMS, redémarrage, correction bug quand aucune info remonte
-	19/03/2020 : Ajout lecture et envoie SMS
