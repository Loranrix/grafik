-- GRAFIK - Correction des permissions MySQL
-- À exécuter manuellement si l'utilisateur napo_admin n'a pas les droits

-- Créer l'utilisateur et donner les permissions
CREATE USER IF NOT EXISTS 'napo_admin'@'localhost' IDENTIFIED BY 'Superman13**';
GRANT ALL PRIVILEGES ON napo_grafik.* TO 'napo_admin'@'localhost';
FLUSH PRIVILEGES;

-- Vérifier les droits
SHOW GRANTS FOR 'napo_admin'@'localhost';

