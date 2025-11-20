# 🔐 CONNEXION VPS - CIAO.LV

> ⚠️ **RAPPEL ULTRA IMPORTANT** (14/11/2025)  
> **AUCUNE commande locale bloquante (git diff, scripts multi-lignes, python, powershell, etc.) ne doit être lancée avant ou pendant le déploiement.**  
> Toute action doit exclusivement passer par **`plink`** comme décrit ci-dessous pour éviter de bloquer PowerShell ou Git.  
> Si un doute apparaît, relire ce fichier AVANT d’exécuter quoi que ce soit.

**Date de création** : 05 novembre 2025  
**Site** : https://ciao.lv  
**Statut** : ✅ TESTÉ ET VALIDÉ

---

## 📋 INFORMATIONS DE CONNEXION

### SSH
```
Host:     195.35.56.221
Port:     51970
User:     root
Password: LoranRix70*13
Hostkey:  ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao
```

### Répertoires
```
Site ciao.lv:     /home/ciao.lv/public_html
Application PM2:  ciao-app
Port du site:     3008
```

### Base de données
```
Host:     localhost (depuis le VPS)
User:     ciao_admin
Password: [Voir .env sur le VPS]
Database: ciao_ciaolv_db
```

---

## 🚀 COMMANDES ESSENTIELLES

> 📌 **Rappel hostkey validée (14/11/2025)**  
> `ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY`  
> Toutes les commandes plink ci-dessous utilisent cette empreinte pour éviter les blocages. En cas de changement côté VPS, mettre à jour cette valeur et relancer.

### 1️⃣ VOIR LES LOGS PM2

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 logs ciao-app --lines 50 --nostream"
```

### 2️⃣ STATUT PM2

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 status"
```

### 3️⃣ REDÉMARRER L'APPLICATION

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 restart ciao-app"
```

### 4️⃣ VIDER LES LOGS

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 flush ciao-app"
```

---

## 🔄 DÉPLOIEMENT COMPLET

### Git Pull + Build + Restart

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git pull origin ciao-version && npm install && npm run build && pm2 restart ciao-app"
```

### Déploiement avec nettoyage cache

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git pull origin ciao-version && rm -rf .next && npm run build && pm2 restart ciao-app && pm2 flush ciao-app"
```

---

## 🗄️ COMMANDES BASE DE DONNÉES

### Vérifier les tables

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "mysql -u root -p'9BvgCl9ewttgcc' ciao_ciaolv_db -e 'SHOW TABLES;'"
```

### Compter les annonces

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "mysql -u root -p'9BvgCl9ewttgcc' ciao_ciaolv_db -e 'SELECT COUNT(*) as total FROM ads;'"
```

### Vérifier le compte admin@ciao.lv

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "mysql -u root -p'9BvgCl9ewttgcc' ciao_ciaolv_db -e 'SELECT uid, email, displayName, isAdmin, isVerified FROM users WHERE email=\"admin@ciao.lv\";'"
```

### Activer admin@ciao.lv (si nécessaire)

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "mysql -u root -p'9BvgCl9ewttgcc' ciao_ciaolv_db -e 'UPDATE users SET isAdmin = 1, isVerified = 1 WHERE email=\"admin@ciao.lv\";'"
```

---

## 📁 COMMANDES FICHIERS

### Voir les derniers commits Git

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git log --oneline -5"
```

### Voir la branche Git actuelle

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git branch"
```

### Changer de branche

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git checkout ciao-version"
```

### Voir l'espace disque

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "df -h /home/ciao.lv/public_html"
```

---

## 🔍 VÉRIFICATIONS

### Test de connexion simple

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "echo 'Connexion OK ciao.lv'"
```

### Vérifier que le site répond

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "curl -I http://localhost:3008"
```

### Vérifier les variables d'environnement

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && cat .env.local | grep -v PASSWORD | grep -v SECRET"
```

---

## 📊 MONITORING

### Mémoire et CPU

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "free -h && top -bn1 | grep ciao"
```

### Logs système

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "journalctl -u nginx -n 50 --no-pager"
```

---

## ⚠️ DÉPANNAGE RAPIDE

### L'app ne démarre pas

```powershell
# 1. Supprimer le dossier .next
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && rm -rf .next"

# 2. Rebuild
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && npm run build"

# 3. Restart
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 restart ciao-app"
```

### Git lock bloqué

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && rm -f .git/index.lock"
```

### Changements locaux bloquent Git pull

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git stash && git pull origin ciao-version"
```

---

## 🎯 WORKFLOW COMPLET DE DÉPLOIEMENT

### Étape par étape

```powershell
# 1. Test connexion
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "echo 'Connexion OK'"

# 2. Git pull
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && git pull origin ciao-version"

# 3. npm install
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && npm install"

# 4. npm run build
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "cd /home/ciao.lv/public_html && npm run build"

# 5. pm2 restart
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 restart ciao-app"

# 6. Vérifier les logs
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 logs ciao-app --lines 30 --nostream"

# 7. Statut final
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 status"
```

---

## 📝 NOTES IMPORTANTES

### ⚠️ DIFFÉRENCES AVEC ciao.lv

| Élément | CIAO.LV | ciao.lv |
|---------|---------|--------------|
| **Hostkey** | `8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao` | `pxL/PmJ2daLlY08+dYRNA6hB/SeadVlqYpIEdldICrg` |
| **Chemin** | `/home/ciao.lv/public_html` | `/home/ciao.lv/public_html` |
| **PM2 App** | `ciao-app` | `ciao-app` |
| **Port** | `3008` | `3007` |
| **Branche Git** | `ciao-version` | `main` |
| **Base de données** | `ciao_ciaolv_db` | `zala_ciao_db` |

### 🔑 POURQUOI LA HOSTKEY EST DIFFÉRENTE ?

Chaque site sur le VPS peut avoir sa propre configuration SSH. La hostkey est l'empreinte unique de la clé SSH du serveur pour ciao.lv.

### ✅ COMMENT J'AI TROUVÉ LA BONNE HOSTKEY ?

Lors d'une première connexion sans hostkey, plink affiche :
```
The server's ssh-ed25519 key fingerprint is:
  ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao
```

Cette empreinte doit être utilisée dans le paramètre `-hostkey`.

---

## 🚀 COMMANDE COPIER-COLLER RAPIDE

**Pour voir les logs de ciao.lv en 1 commande :**

```powershell
& "C:\Program Files\PuTTY\plink.exe" -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:8fcid6fzaLjj4nPQxUEgFsbm2sfBmn+Y4tl2u2WXoao" 195.35.56.221 "pm2 logs ciao-app --lines 50 --nostream"
```

---

**Document créé le** : 05 novembre 2025  
**Dernière mise à jour** : 05 novembre 2025  
**Testé et validé** : ✅ OUI

**Site** : https://ciao.lv  
**Admin** : admin@ciao.lv

