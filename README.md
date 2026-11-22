# Kamer Taste : site e-commerce de restauration

Site web de commande de repas en ligne pour un restaurant : le client parcourt la carte, remplit son panier et commande en livraison ou à emporter. Le restaurant gère sa carte et traite les commandes depuis un espace d'administration.

Projet réalisé par Wendy, en **PHP (architecture MVC)** et **MySQL**.

## Fonctionnalités

**Côté client**
- Page d'accueil, carte par catégories, fiche produit avec options (piment, supplément...)
- Panier (ajouter, modifier la quantité, retirer, vider)
- Inscription et connexion
- Commande en **livraison** ou **à emporter**, adresses enregistrées, code promo
- Historique « Mes commandes » avec barre de suivi (en attente, confirmée, en préparation, prête, en livraison, livrée)

**Côté administration** (`/admin`)
- Tableau de bord (commandes en attente, commandes et chiffre d'affaires du jour)
- Suivi des commandes et changement de statut (une annulation remet les produits en stock)
- Gestion des produits : photo, prix, stock, disponibilité, options
- Gestion des catégories

**Sécurité**
- Mots de passe hachés (`password_hash`)
- Requêtes préparées PDO (contre l'injection SQL) et échappement des sorties (contre le XSS)
- Jeton CSRF sur tous les formulaires
- Limite des tentatives de connexion (5 échecs en 15 minutes)
- Vérification du vrai type des images envoyées, prix toujours recalculés côté serveur

## Technologies

PHP 8 · MySQL / MariaDB · PDO · HTML · CSS · JavaScript · Apache (XAMPP en local)

## Installation en local

**Prérequis** : XAMPP (ou équivalent) avec PHP 8.0 minimum, Apache et MySQL démarrés.

1. **Récupérer le projet** dans le dossier `htdocs` de XAMPP :
   ```
   git clone https://github.com/wendyetouman-stack/Kamer_Taste.git restaurant-ecommerce
   ```
2. **Créer la base de données** : dans phpMyAdmin, onglet **SQL**, colle le contenu de `database/schema.sql` et exécute (le script crée la base `restaurant_db` et toutes les tables).
3. **Données de démonstration** (facultatif) : exécute ensuite `database/seed.sql`.
4. **Configurer la connexion à la base** : copie `app/config/database.example.php` en `app/config/database.php`, puis adapte les valeurs si besoin.
   ```
   copy app\config\database.example.php app\config\database.php
   ```
5. **Régler l'adresse du site** dans `app/config/config.php` : modifie `BASE_URL` selon ton port, par exemple `http://localhost:8080/restaurant-ecommerce/public`. Garde `APP_DEBUG` à `true` en local.
6. **Ouvrir le site** à cette même adresse.
7. **Créer l'administrateur** : ouvre `/admin/auth/installation` et crée le compte (mot de passe de 10 caractères minimum). Cette page se ferme d'elle-même une fois le compte créé.

## Structure du projet

```
restaurant-ecommerce/
├── public/            Seul dossier accessible depuis le navigateur (index.php, CSS, images)
├── app/
│   ├── config/        Configuration (adresse du site, base de données)
│   ├── core/          Noyau MVC : routeur, contrôleur et modèle de base, panier
│   ├── controllers/   Contrôleurs (boutique) et controllers/admin (administration)
│   ├── models/        Un modèle par entité de la base
│   ├── views/         Vues (boutique, admin, mises en page)
│   └── helpers/       Fonctions utilitaires (prix, CSRF, session)
├── database/          schema.sql (structure) et seed.sql (données de test)
└── docs/              Documents du projet (MCD, MPD...)
```

Les URL suivent le format `/controleur/methode/parametres`, par exemple `/produit/show/3` ou `/admin/commande`.

## Paiement Fapshi en sandbox

Pour tester Fapshi, l'application locale doit rester distincte de ses URL publiques HTTPS. Les identifiants doivent être sandbox, conservés dans les variables d'environnement et non intégrés au code.

La procédure complète, notamment l'utilisation obligatoire d'une adresse HTTPS publique pour le callback et le webhook, est décrite dans [`docs/fapshi-sandbox.md`](docs/fapshi-sandbox.md).

## Mise en production

- Faire pointer le domaine vers le dossier `public/`
- Passer `APP_DEBUG` à `false` dans `config.php`
- Utiliser un utilisateur MySQL dédié avec un mot de passe solide (pas `root`)
- Renseigner la vraie adresse dans `BASE_URL`, en HTTPS
- Supprimer les comptes et commandes de test

## Pistes d'évolution

- Paiement Mobile Money en ligne (actuellement enregistré avec le statut « en attente »)
- Notifications par SMS ou email pour le suivi des commandes
- Gestion des menus et formules dans l'administration
- Statistiques de ventes
