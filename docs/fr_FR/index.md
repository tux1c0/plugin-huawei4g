Description 
===

Plugin permettant la supervision de routeur 4G Huawei.
Les informations supervisées
-	Volumétrie de données
-	Opérateur
-	Signal 4G
-	SMS (nombre, lecture et envoie)
-	SMS (suppression), donne des réponses aléatoires sur certain modèle. Le routeur renvoie un OK alors que l'action n'est pas réalisée.

Installation
===

-	Le plugin, une fois installé, doit être activé. Il installera les dépéndances nécessaires au bon fonctionnement.
-	Le plugin est compatible Debian 9/10, Jeedom v3/v4.

Configuration
===

### Routeurs compatibles

Le routeur doit être accessible via le réseau privé afin de pouvoir récupérer des informations.

Un login et mot de passe doit être défini sur le routeur.

Le plugin est compatible avec les modèles suivant
-	Huawei B715s-23c
-	Huawei B528s-23a (certains firmware ne fournissent pas le support des SMS)
-	Huawei B612s-25d
-	Huawei B535-232
-	Huawei B525s-65a
-	Huawei E5186s-22a
-	Huawei B525s-23a
-	Huawei E5573Bs-320 (toutes les informations ne sont pas disponibles sur le routeur)

Le plugin devrait être compatible avec les modèles suivants (non testés)
-	Huawei B310s-22
-	Huawei E3131
-	Autres modèles LTE non listés

Le plugin n'est pas compatible avec les modèles suivant
-	Huawei B2368-22
-	Huawei E3372

### Plugin

Tous les éléments suivants sont obligatoires pour avoir le plugin fonctionnel

-   IP : adresse IP du routeur
-   API (login et mot de passe de connexion du routeur définit au-dessus). Les espaces ne sont pas supportés dans le mot de passe.
-	Fréquence : choisir si la récupération des données est toutes les 5 ou 15 minutes
-	SMS en mode texte : cocher la case si les SMS ne fonctionnent pas (principalement à cause des caractères accentués)

Sauvegarder la configuration. 

Upgrade
===
En cas de mise à jour du plugin, pensez à sauvegarder tous vos équipements si les données ne s'affichent pas, ou pour voirs les nouvelles commandes.


Utilisation
===

### Templates

-	Le plugin vient avec son jeu de template dashboard et mobile
-	Si le template ne se configure pas, vous pouvez aller dans le menu de configuration des commandes, onglet affichage et choisir le template dans dashboard ou mobile, section Huawei4G.

### Actions sur les SMS par scénario

-	Pour envoyer des SMS par scénario, choisissez la commande d'action "Envoyer SMS". Dans le champs "titre", remplissez le numéro de téléphone et dans le champs "Message" le texte à envoyer.
-	Utilisez la fonction "Rafraichir SMS" si vous souhaitez mettre à jour les informations liées aux SMS. C'est pratique si vous voulez allez à un polling supérieur aux 5min.
-	De manière générale, lancer les fonctions de refresh trop souvent peuvent bloquer le routeur qui se met en sécurité. Si cela arrive, penser à augmenter votre fréquence.


### Notification Manager

Le plugin notification manager ne demande qu'une commande et il n'est pas possible de paramétrer un numéro et le message à envoyer par SMS.
Dans ce cas, il existe 2 possibilités en utilisant un scénario (voir au-dessus) avec un virtuel ou avec une commande virtuelle sans scenario.
-	Créer un virtuel et activez le
-	Ajouter une commande action, soit qui appelera le scenario (voir la suite) ou directement appeler la commande du routeur
-	Configurer cette commande (onglet configuration)
-	Ajouter le scénario dans la section "Action avant exécution de la commande"
-	Sauvegarder


### Debug

-	En cas de problème d'installation, merci de poster le log de dépendance dans community
-	En cas de problème de récupération d'information, passez le plugin en mode debug, et postez le log dans community. Votre routeur n'est peut être pas compatible.
-	Pensez à préciser votre matériel (routeur, Jeedom, versions, Linux, ...)

Changelog
===

-	18/01/2020 : Init du plugin
-	08/02/2020 : 1ere version beta fonctionnelle
-	12/03/2020 : Passage en prod
-	15/03/2020 : Ajout nombre SMS, redémarrage, correction bug quand aucune info remonte
-	19/03/2020 : Ajout lecture et envoie SMS
-	25/03/2020 : Ajout possibilité de polling à 5 min + bug fix
-	30/03/2020 : Ajout suppression SMS et marge RF
-	14/04/2020 : Ajout des valeurs pour les SMS (non lu, reçu; envoyé, supprimé) et une action pour rafraichir les SMS, ajout mode texte pour les SMS
-	15/04/2020 : Ajout des stats mensuelle, ajout dépendance setuptools
-	17/04/2020 : Ajout switch mobile data et wifi, correction affichage
-	25/06/2020 : Corrections icônes pour Jeedom 4.1.
-	27/06/2020 : Update librairie 1.4.12
-	01/07/2020 : Ajout templates mobile
-	05/08/2020 : bug fix last SMS
