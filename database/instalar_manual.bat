@echo off
echo === Criando Usuario e Banco de Dados MySQL ===
echo.
echo Usuario: getxml
echo Senha: gX7#kLp$2Qz!vN9@
echo Banco: getxml
echo.
echo Para criar o usuario manualmente, use uma das opcoes:
echo.
echo 1. phpMyAdmin: http://localhost/phpmyadmin
echo    - Abra a aba SQL
echo    - Cole e execute o SQL abaixo
echo.
echo 2. MySQL Workbench:
echo    - Conecte como root
echo    - Execute o SQL abaixo
echo.
echo 3. Linha de comando:
echo    mysql -u root -p
echo    - Execute o SQL abaixo
echo.
echo === SQL para executar ===
echo.
echo CREATE DATABASE IF NOT EXISTS getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
echo CREATE USER 'getxml'@'localhost' IDENTIFIED BY 'gX7#kLp$2Qz!vN9@';
echo GRANT ALL PRIVILEGES ON getxml.* TO 'getxml'@'localhost';
echo FLUSH PRIVILEGES;
echo.
echo === Depois de criar o usuario, execute ===
echo php database/install.php
echo.
pause