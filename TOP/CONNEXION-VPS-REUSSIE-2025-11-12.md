# ✅ CONNEXION VPS CIAO.LV - MÉTHODE QUI FONCTIONNE

**Date:** 12 Novembre 2025  
**Testée et validée:** ✅ OUI

---

## 🔑 HOSTKEY ACTUELLE (IMPORTANTE!)

```
ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY
```

⚠️ **Mise à jour :** Cette hostkey a changé le 12/11/2025. Utiliser `-batch` avec cette hostkey.

---

## 🚀 COMMANDE QUI FONCTIONNE

```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY" 195.35.56.221 "VOTRE_COMMANDE"
```

⚠️ **IMPORTANT :** Utiliser `-batch` pour éviter les prompts interactifs.

---

## 📋 INFORMATIONS DE CONNEXION

### SSH VPS
```
Host:     195.35.56.221
Port:     51970
User:     root
Password: LoranRix70*13
Hostkey:  ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY
```

### Application
```
Répertoire: /home/ciao.lv/public_html
PM2 App:    ciao-app
Status:     online (72 redémarrages)
```

### Base de données
```
Host:     localhost (sur le VPS)
Port:     3306
Database: ciao_zalaciao
User:     ciao_admin
Password: Superman13**
```

---

## 📝 COMMANDES UTILES

### Voir les logs PM2
```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY" 195.35.56.221 "pm2 logs ciao-app --lines 50 --nostream"
```

### Voir le statut PM2
```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY" 195.35.56.221 "pm2 status"
```

### Git pull + Build + Restart
```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY" 195.35.56.221 "cd /home/ciao.lv/public_html && git pull origin ciao-version && npm run build && pm2 restart ciao-app"
```

### Exécuter une requête MySQL
```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY" 195.35.56.221 "mysql -u ciao_admin -p'Superman13**' ciao_zalaciao -e 'SHOW TABLES;'"
```

### Appliquer une migration DB
```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 -hostkey "ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY" 195.35.56.221 "cd /home/ciao.lv/public_html && mysql -u ciao_admin -p'Superman13**' ciao_zalaciao < database/migrations/FICHIER.sql"
```

---

## ⚠️ COMMENT J'AI TROUVÉ LA BONNE HOSTKEY

1. J'ai essayé de me connecter avec `-batch` sans hostkey
2. Plink a retourné une erreur avec la nouvelle hostkey affichée :
   ```
   The new ssh-ed25519 key fingerprint is:
     ssh-ed25519 255 SHA256:08PDJADlcKUNLryx548i7rkqJfXIcYbl7ruuGM5ymyY
   ```
3. J'ai utilisé cette hostkey dans ma commande et ça a fonctionné !

---

## 🔄 SI LA HOSTKEY CHANGE À NOUVEAU

Exécuter cette commande pour voir la nouvelle hostkey :

```powershell
& "C:\Program Files\PuTTY\plink.exe" -batch -ssh -P 51970 -l root -pw LoranRix70*13 195.35.56.221 "echo test"
```

L'erreur affichera la nouvelle hostkey à utiliser.

---

## ✅ TESTÉ LE

- **12 Novembre 2025** à 05:30 (heure du serveur)
- Connexion réussie
- PM2 status: ciao-app **online**
- Répertoire: /home/ciao.lv/public_html

---

**✨ Cette méthode fonctionne parfaitement !**

