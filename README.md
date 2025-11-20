# 🎯 GRAFIK - Système de Gestion des Pointages Employés

![Status](https://img.shields.io/badge/Status-Production%20Ready-green)
![Version](https://img.shields.io/badge/Version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![Firebase](https://img.shields.io/badge/Firebase-Enabled-orange)

Système complet de gestion des pointages, planning et consommations pour restaurant avec Firebase, sécurité avancée et exports professionnels.

---

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Sécurité](#sécurité)
- [Documentation](#documentation)
- [Support](#support)

---

## ✨ Fonctionnalités

### 👥 Gestion des Employés
- ✅ Création employé (prénom, nom, téléphone, PIN)
- ✅ QR codes uniques générés automatiquement
- ✅ Téléchargement QR codes en PNG haute résolution
- ✅ Activation/désactivation des comptes
- ✅ Historique complet des connexions

### 📅 Planning Mensuel
- ✅ Vue calendrier mensuelle interactive
- ✅ Ajout/modification des horaires par jour
- ✅ Notes et commentaires par shift
- ✅ Duplication de semaine
- ✅ Statistiques par employé (jours, heures)

### ⏱️ Pointages
- ✅ Scan QR code + saisie PIN sécurisée
- ✅ Enregistrement arrivée/départ automatique
- ✅ Vérification GPS optionnelle (rayon configurable)
- ✅ Validation contre le planning
- ✅ Tolérances configurables (anticipé/retard)
- ✅ Historique complet avec device/IP/GPS

### 🍽️ Consommation Employés
- ✅ Saisie consommations nourriture/boissons
- ✅ Calcul automatique réduction 50%
- ✅ Historique jour/mois
- ✅ Statistiques et économies

### 📊 Dashboard Employé
- ✅ Interface en letton, responsive
- ✅ Planning personnel du mois
- ✅ Historique des pointages
- ✅ Heures travaillées (jour/semaine/mois)
- ✅ Module consommation

### 🔒 Sécurité Avancée
- ✅ Restriction par appareil (optionnelle)
- ✅ Vérification GPS (optionnelle)
- ✅ Verrouillage après tentatives échouées
- ✅ Multi-device contrôlé
- ✅ Tous les paramètres configurables sans code

### 📋 Audit et Logs
- ✅ Logs de toutes les connexions employés
- ✅ Logs de toutes les actions admin
- ✅ Logs des tentatives échouées
- ✅ Statistiques en temps réel
- ✅ Filtres par type et période
- ✅ Export possible

### 📥 Exports Professionnels
- ✅ Export Excel (.xlsx) modifiable
- ✅ Export PDF imprimable
- ✅ Par employé ou tous
- ✅ Période personnalisable
- ✅ Exports rapides prédéfinis
- ✅ Formatage professionnel automatique

### 🔥 Firebase Integration
- ✅ PIN codes sécurisés dans Firebase
- ✅ Pointages persistants
- ✅ Appareils enregistrés
- ✅ Synchronisation automatique
- ✅ Migration depuis MariaDB
- ✅ Protection contre perte de données

---

## 🛠️ Technologies

### Backend
- **PHP 7.4+** - Logique serveur
- **MariaDB/MySQL** - Base de données principale
- **Firebase Realtime Database** - Données critiques (PIN, pointages)
- **Composer** - Gestionnaire de dépendances

### Frontend
- **HTML5 / CSS3** - Interface responsive
- **JavaScript vanilla** - Interactions
- **Mobile-first design** - Optimisé tactile

### Librairies
- **kreait/firebase-php** ^7.0 - SDK Firebase
- **tecnickcom/tcpdf** ^6.6 - Génération PDF
- **phpoffice/phpspreadsheet** ^1.28 - Export Excel
- **QR Server API** - Génération QR codes

---

## 📦 Installation

### Prérequis
- Serveur web (Apache/Nginx)
- PHP 7.4 ou supérieur
- MariaDB/MySQL 5.7+
- Composer
- Compte Firebase (gratuit)

### Étape 1: Cloner le projet
```bash
git clone https://github.com/Loranrix/grafik.git
cd grafik
```

### Étape 2: Installer les dépendances
```bash
composer install
```

### Étape 3: Configuration base de données
```bash
# Créer la base de données
mysql -u root -p
CREATE DATABASE grafik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Exécuter les migrations
mysql -u root -p grafik < database/001_init.sql
mysql -u root -p grafik < database/002_add_phone_to_employees.sql
mysql -u root -p grafik < database/003_create_schedules_table.sql
mysql -u root -p grafik < database/004_create_consumptions_table.sql
mysql -u root -p grafik < database/005_create_security_settings.sql
mysql -u root -p grafik < database/006_create_audit_logs.sql
```

### Étape 4: Configuration
```bash
# Copier et modifier le fichier de configuration
cp includes/config.example.php includes/config.php
# Éditer config.php avec vos paramètres
```

### Étape 5: Configuration Firebase
Voir le guide détaillé : [FIREBASE-SETUP.md](FIREBASE-SETUP.md)

1. Créer un projet sur [Firebase Console](https://console.firebase.google.com/)
2. Télécharger les clés de service (JSON)
3. Renommer en `firebase-config.json` et placer à la racine
4. Tester : `https://votre-domaine.com/admin/firebase-test.php`
5. Migrer : `https://votre-domaine.com/admin/migrate-to-firebase.php`

### Étape 6: Sécurité
```bash
# Protéger le fichier Firebase (Apache)
echo '<Files "firebase-config.json">
    Order Allow,Deny
    Deny from all
</Files>' >> .htaccess

# Permissions
chmod 600 firebase-config.json
```

---

## ⚙️ Configuration

### Paramètres de sécurité
Aller sur `https://votre-domaine.com/admin/security-settings.php`

#### Appareil
- **Restriction par appareil** : Limiter à des appareils spécifiques
- **Multi-device** : Autoriser plusieurs appareils par employé

#### GPS
- **Vérification GPS** : Activer la vérification de localisation
- **Coordonnées** : Latitude/Longitude du restaurant
- **Rayon** : Distance autorisée (mètres)

#### PIN
- **Tentatives max** : Nombre avant verrouillage (défaut: 3)
- **Durée verrouillage** : Minutes de blocage (défaut: 15)

#### Pointage
- **Tolérance anticipée** : Minutes avant heure prévue (défaut: 15)
- **Tolérance retard** : Minutes après heure prévue (défaut: 30)

#### Notifications
- **Activer notifications** : Alertes par email
- **Email admin** : Adresse pour recevoir les alertes

---

## 🎮 Utilisation

### Pour les employés

#### 1. Premier scan
1. Scanner le QR code fourni par l'admin
2. Saisir le code PIN (4 chiffres)
3. Choisir: Ierašanās (Arrivée) ou Aiziešana (Départ)

#### 2. Dashboard
- **Mana statistika** : Voir heures travaillées
- **Mans grafiks** : Consulter le planning
- **Patēriņš** : Saisir consommations

#### 3. Consommation
1. Entrer le nom du produit (ex: "Kafija", "Pizza")
2. Entrer le prix normal
3. La réduction -50% est appliquée automatiquement

### Pour les administrateurs

#### Gestion employés
`Admin > Employés`
- Créer/modifier/désactiver
- Voir historique connexions

#### Planning
`Admin > Planning`
- Sélectionner mois
- Cliquer sur case employé/jour
- Entrer horaires de début/fin
- Ajouter notes si besoin

#### QR Codes
`Admin > QR Codes`
- Voir tous les QR codes
- Télécharger individuellement (PNG)
- Imprimer

#### Pointages
`Admin > Pointages`
- Vue de tous les pointages
- Filtrer par employé/date
- Corriger manuellement si besoin

#### Sécurité
`Admin > Sécurité`
- Configurer tous les paramètres
- Voir tentatives échouées

#### Logs
`Admin > Logs`
- Connexions employés
- Actions admin
- Tentatives échouées
- Filtrer par période

#### Export
`Admin > Export`
- Choisir format (Excel/PDF)
- Sélectionner employé ou tous
- Définir période
- Télécharger

---

## 🔐 Sécurité

### Authentification
- PIN codes hashés et stockés dans Firebase
- Verrouillage automatique après échecs
- Logs complets de toutes les tentatives

### Données sensibles
- `firebase-config.json` : Protégé, non commité
- `includes/config.php` : À ne jamais exposer
- Mots de passe admin : Hashés avec `password_hash()`

### Protection
- HTTPS recommandé (obligatoire pour GPS)
- Firewall Firebase configuré
- Backups réguliers recommandés

### Audit
- Tous les logs conservés
- Traçabilité complète
- Statistiques en temps réel

---

## 📚 Documentation

### Guides complets
- [FIREBASE-SETUP.md](FIREBASE-SETUP.md) - Installation Firebase
- [TRAVAUX-2025-11-16-PARTIE-1-COMPLETE.md](TRAVAUX-2025-11-16-PARTIE-1-COMPLETE.md) - Détails Partie 1
- [TRAVAUX-2025-11-16-PARTIE-2-COMPLETE.md](TRAVAUX-2025-11-16-PARTIE-2-COMPLETE.md) - Détails Partie 2

### Structure du projet
```
grafik/
├── admin/              # Interface administrateur
├── employee/           # Interface employé
├── classes/            # Classes PHP (MVC)
├── css/               # Styles
├── database/          # Migrations SQL
├── includes/          # Configuration
├── vendor/            # Dépendances Composer
├── firebase-config.json  # Config Firebase (à créer)
├── composer.json      # Dépendances
└── README.md          # Ce fichier
```

### Classes principales
- `Database.php` - Connexion MariaDB
- `Firebase.php` - Connexion Firebase
- `Employee.php` - Gestion employés
- `Punch.php` - Gestion pointages
- `Schedule.php` - Gestion planning
- `Consumption.php` - Gestion consommations
- `SecuritySettings.php` - Paramètres sécurité
- `AuditLog.php` - Logs et audit
- `Export.php` - Export PDF/Excel

---

## 🐛 Dépannage

### Firebase ne se connecte pas
1. Vérifier que `firebase-config.json` existe
2. Vérifier les permissions du fichier
3. Tester sur `admin/firebase-test.php`
4. Vérifier que Realtime Database est activé sur Firebase

### Exports ne fonctionnent pas
1. Vérifier que Composer est à jour : `composer update`
2. Vérifier que TCPDF et PhpSpreadsheet sont installés
3. Vérifier permissions dossier temporaire

### GPS ne fonctionne pas
1. Site doit être en HTTPS
2. Navigateur doit autoriser géolocalisation
3. Vérifier coordonnées dans paramètres

### Logs PHP
```bash
# Vérifier les erreurs
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/php-fpm/error.log
```

---

## 🤝 Support

### Contact
- **Développeur** : Cursor AI Assistant
- **Client** : NapoPizza
- **Email** : [votre-email]

### Mises à jour
Les mises à jour sont disponibles sur GitHub :
```bash
git pull origin master
composer update
```

---

## 📜 Licence

Propriétaire - NapoPizza © 2025

---

## 🙏 Remerciements

- **Firebase** - Infrastructure temps réel
- **TCPDF** - Génération PDF
- **PhpSpreadsheet** - Export Excel
- **QR Server** - Génération QR codes

---

## 📊 Statistiques

- **Lignes de code** : ~8 000+
- **Fichiers créés** : 40+
- **Tables BDD** : 10+
- **Classes PHP** : 9
- **Pages admin** : 10
- **Pages employé** : 3
- **Migrations SQL** : 6
- **Temps de développement** : Session complète

---

**Version 2.0 - Novembre 2025**  
Système complet de gestion des pointages avec Firebase, sécurité avancée et exports professionnels.

🚀 **Production Ready !**

