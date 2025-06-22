# ==============================================================================
# docker/postgres/init/01-init.sql - Initialisation PostgreSQL
# ==============================================================================
-- Création de la base de test
CREATE DATABASE laravel_test;
-- Création d'un utilisateur de test
CREATE USER laravel_test WITH PASSWORD 'test_password';
-- Permissions
GRANT ALL PRIVILEGES ON DATABASE laravel_test TO laravel_test;
-- Extensions utiles pour les tests
\ c laravel_test;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";