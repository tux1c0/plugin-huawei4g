Description 
===

Plugin permettant la supervision de NAS QNAP.
Les informations supervisées
-   CPU : charge, température
-   RAM : charge, valeur totale, valeur utilisée
-   Disque dur : SMART, température, pourcentage d'utilisation, valeur totale, valeur utilisée
-   NAS : version, modèle, CPU, température, uptime, statut
-   Actions d'arrêt et de redémarrage

Installationn 
===

-   Le plugin, une fois installé, doit être activé. Il installera les dépéndances nécessaires au bon fonctionnement.
-   Le plugin est compatible Debian 9/10, Jeedom v3/v4.
-   Sur Debian 8, des problèmes de dépendances sont bloquants pour le bon fonctionnement du plugin (erreur 500). Il n'y a pas de solution à date. 


Configuration
===

### QNAP 

Le NAS doit avoir le SNMP et SSH activé afin de pouvoir récupérer des informations.

Pour activer le SNMP, il faut aller dans la page d'administration SNMP, puis :

-   Activer le service

-   Choisir la version v1 ou v2 du protocole

-   Définir une communauté SNMP

-   Sauvegarder la configuration

Pour activer le SSH, il faut aller dans la page d'administration Telnet/SSH, puis :

-   Permettre la connexion SSH

-   Choisir le port (généralement le 22)

-   Sauvegarder la configuration

### Plugin

Tous les éléments suivants sont obligatoires pour avoir le plugin fonctionnel

-   IP : adresse IP du NAS

-   SSH (login, mot de passe et port de connexion SSH du NAS définit au-dessus)

-   SNMP : communauté et version SNMP du NAS définie précédemment

-   Utiliser uniquement le SNMP (désactive le SSH) : cocher la case désactive le protocole SSH. Certaines fonctions ne seront plus disponibles (redémarrer, arrêt, build QNAP, version d'OS, CPU)

Sauvegarder la configuration. Le module va commencer à poller toutes les 15 minutes le NAS.


Mise à jour
===
Pour les mises, pensez à réinstaller les dépendances afin d'avoir les derniers paquet à jour et information SNMP.
Dans certains cas, il faut supprimer l'équipement et le recréer pour que les changements soient pris en compte, version de janvier 2020 particulièrement.

Changelog
===

-   25/08/2019 : Ajout du changelog, passage v4, bugfixes, nouvelle branche pour la v3
-   14/01/2020 : Bugfix, correction cron, passage au widget v4, évolution de données en numerique, revue mise à jour des tuiles, compatibilité Debian 10