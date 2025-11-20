# 🔥 Activation de Firebase Realtime Database

## Étape actuelle : Activer Realtime Database

1. **Va sur Firebase Console** : https://console.firebase.google.com/

2. **Sélectionne ton projet** : `grafik-napo`

3. **Dans le menu de gauche, clique sur "Realtime Database"**

4. **Clique sur "Créer une base de données"**

5. **Choisis la localisation** :
   - Sélectionne : **europe-west1** (Belgique - le plus proche)

6. **Règles de sécurité** :
   - Sélectionne : **"Commencer en mode test"**
   - (On configurera les vraies règles après)

7. **Clique sur "Activer"**

8. **Une fois créé, note l'URL** (genre : `https://grafik-napo-default-rtdb.europe-west1.firebasedatabase.app`)

---

## Ensuite, configure les règles de sécurité :

Dans l'onglet "Règles" de Realtime Database, remplace par :

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
      },
      "test": {
        ".read": true,
        ".write": true
      }
    }
  }
}
```

**Publie les règles** en cliquant sur "Publier"

---

## ✅ Une fois fait, reviens me dire "ok" et on teste la connexion !

