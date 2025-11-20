-- Migration: Ajout du champ type pour les employés
-- Types: Cuisine, Bar, Autre

-- Pour Firebase, on ajoute juste la documentation
-- Le champ sera géré directement dans Firebase

-- Pour MySQL (si utilisé en parallèle)
ALTER TABLE employees 
ADD COLUMN employee_type ENUM('Cuisine', 'Bar', 'Autre') DEFAULT 'Autre' AFTER phone;

-- Ajouter index pour les recherches par type
CREATE INDEX idx_employee_type ON employees(employee_type);

