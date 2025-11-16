# 🔥 Guide d'installation Firebase pour GRAFIK

## Étape 1 : Installation de Composer (si nécessaire)

Si Composer n'est pas installé sur votre serveur, installez-le :

```bash
cd /var/www/grafik
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

Ou si Composer est déjà installé globalement :

```bash
cd /var/www/grafik
composer install
```

## Étape 2 : Récupérer les clés Firebase

1. Allez sur [Firebase Console](https://console.firebase.google.com/)
2. Sélectionnez votre projet
3. Cliquez sur l'icône ⚙️ (Paramètres du projet)
4. Allez dans l'onglet **"Comptes de service"**
5. Cliquez sur **"Générer une nouvelle clé privée"**
6. Un fichier JSON sera téléchargé

## Étape 3 : Configuration du projet

1. Renommez le fichier JSON téléchargé en `firebase-config.json`
2. Placez-le dans le dossier `/var/www/grafik/`
3. **IMPORTANT** : Pour la sécurité, ce fichier ne doit PAS être accessible publiquement

### Protection du fichier de configuration

Ajoutez dans votre `.htaccess` ou configuration Apache/Nginx :

**Apache (.htaccess)** :
```apache
<Files "firebase-config.json">
    Order Allow,Deny
    Deny from all
</Files>
```

**Nginx** :
```nginx
location ~* firebase-config\.json$ {
    deny all;
}
```

## Étape 4 : Structure Firebase

Le système utilisera Firebase Realtime Database avec la structure suivante :

```
grafik/
├── employees/
│   ├── {employee_id}/
│   │   ├── pin: "1234"
│   │   ├── first_name: "Jean"
│   │   ├── last_name: "Dupont"
│   │   ├── phone: "+371..."
│   │   ├── qr_code: "unique_code"
│   │   ├── is_active: true
│   │   └── created_at: "2025-11-16T..."
│   └── ...
├── punches/
│   ├── {employee_id}/
│   │   ├── {punch_id}/
│   │   │   ├── type: "in" ou "out"
│   │   │   ├── datetime: "2025-11-16T09:00:00"
│   │   │   ├── device_id: "..."
│   │   │   ├── location: {lat: ..., lng: ...}
│   │   │   └── verified: true
│   │   └── ...
│   └── ...
└── devices/
    ├── {employee_id}/
    │   ├── {device_id}/
    │   │   ├── name: "iPhone de Jean"
    │   │   ├── first_registered: "2025-11-16T..."
    │   │   ├── last_used: "2025-11-16T..."
    │   │   └── is_allowed: true
    │   └── ...
    └── ...
```

## Étape 5 : Activer Firebase Realtime Database

1. Dans Firebase Console, allez dans **"Realtime Database"**
2. Cliquez sur **"Créer une base de données"**
3. Choisissez l'emplacement (ex: europe-west1)
4. Commencez en **mode test** (vous configurerez les règles après)

## Étape 6 : Règles de sécurité Firebase (à configurer après installation)

```json
{
  "rules": {
    "grafik": {
      "employees": {
        ".read": "auth != null",
        ".write": "auth != null"
      },
      "punches": {
        "$employee_id": {
          ".read": "auth != null",
          ".write": "auth != null"
        }
      },
      "devices": {
        "$employee_id": {
          ".read": "auth != null",
          ".write": "auth != null"
        }
      }
    }
  }
}
```

## Étape 7 : Test de connexion

Après installation, testez la connexion en allant sur :
`https://grafik.napopizza.lv/admin/firebase-test.php`

Cette page vérifiera :
- ✅ Composer installé
- ✅ Firebase SDK chargé
- ✅ Connexion à Firebase réussie
- ✅ Lecture/Écriture de test

## Étape 8 : Migration des données existantes

Un script de migration automatique sera fourni : `/admin/migrate-to-firebase.php`

⚠️ **IMPORTANT** : Faites une sauvegarde complète de votre base de données avant la migration !

## Structure des fichiers après installation

```
grafik/
├── composer.json              (créé)
├── composer.lock              (créé par composer)
├── vendor/                    (créé par composer)
├── firebase-config.json       (à créer par vous avec vos clés)
├── firebase-config.example.json (exemple)
├── classes/
│   ├── Firebase.php          (nouvelle classe)
│   └── ...
└── ...
```

## Support

En cas de problème :
1. Vérifiez que Composer est bien installé : `composer --version`
2. Vérifiez les permissions du fichier `firebase-config.json`
3. Vérifiez les logs PHP : `/var/log/apache2/error.log` ou `/var/log/php-fpm/error.log`
4. Contactez-moi avec les messages d'erreur exacts

