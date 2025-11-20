-- Migration 002: Ajouter le champ téléphone aux employés
-- Date: 2025-11-16

ALTER TABLE employees 
ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER last_name;

-- Index pour recherche par téléphone
CREATE INDEX idx_employees_phone ON employees(phone);

