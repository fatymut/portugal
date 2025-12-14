# Projet Généalogie

Une application web de gestion généalogique permettant de créer, modifier et visualiser des individus et leurs relations familiales.  
Le projet utilise PHP et MongoDB pour la gestion des données, avec Tailwind CSS pour le style et Docker pour simplifier le développement.

---

## 🛠️ Technologies utilisées

- PHP
- MongoDB
- Tailwind CSS
- Docker (optionnel)
- Git

---

## 🚀 Installation

### 1. Cloner le dépôt
```bash
git clone https://github.com/fatymut/portugal.git
cd portugal
````

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configuration Docker (optionnel)

```bash
docker ps
docker stop <container_id>
docker rm <container_id>
```

---

## 📁 Structure du projet

* `/config` : fichiers de configuration
* `/src` : code source PHP
* `/public` : fichiers accessibles depuis le navigateur
* `/vendor` : dépendances Composer

---

## ⚡ Utilisation

1. Configurer la connexion à MongoDB dans `/config/db.php`
2. Lancer le serveur local PHP :

```bash
php -S localhost:8000 -t public
```

3. Ouvrir [http://localhost:8000](http://localhost:8000) dans votre navigateur

---

## 📝 Fonctionnalités

* Ajouter, modifier et supprimer des individus
* Visualiser l’arbre généalogique
* Gestion des relations familiales
* Interface responsive avec Tailwind CSS



