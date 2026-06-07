@echo off
REM Installation BDD HappyBite — XAMPP Windows
REM Usage : database\install.bat
REM Prérequis : MySQL démarré dans XAMPP

set MYSQL=C:\xampp\mysql\bin\mysql.exe
if not exist "%MYSQL%" (
    echo [ERREUR] mysql.exe introuvable. Verifiez que XAMPP est installe dans C:\xampp
    exit /b 1
)

set ROOT=%~dp0
cd /d "%ROOT%"

echo === HappyBite : installation base de donnees ===

call :run schema.sql
call :run schema_utilisateur_auth.sql
call :run schema_commande_livraison.sql
call :run schema_sante_frigo.sql
call :run communaute_tables_complete.sql
call :run challenge_migration.sql
call :run migration_settings.sql
call :run migration_user_notifications.sql
call :run migration_user_notifications_ref_key.sql
call :run migration_utilisateur_profil_image.sql
call :run migration_commande_id_utilisateur.sql
call :run migration_fix_commande_date.sql
call :run migration_livraison_timeline.sql
call :run migration_livraison_transit.sql
call :run migration_communaute_id_utilisateur.sql
call :run migration_suivi_journalier_happybite.sql
call :run seed_demo.sql

echo.
echo === Termine ! Compte admin : admin@happybite.tn / password ===
exit /b 0

:run
echo -- %1
"%MYSQL%" -u root < "%1"
if errorlevel 1 (
    echo [AVERTISSEMENT] Erreur possible sur %1 — verifiez phpMyAdmin si besoin.
)
exit /b 0
