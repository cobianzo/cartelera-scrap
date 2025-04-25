# Esqueleto

Quiero desarrollar un Plugin en wordPress. El plugin se llama carterlera-scrap.
Quier seguir los estándares de coding de WordPress VIP.
creame un esqueleto de un proyecto nuevo, en el entorno de wp-env.
Iniciame el package.json y el composer.json.
Una vez tengamos la estuctura principal

# Setup PHPCS
Following setup as per Alleyss standards (which uses WordPress VIP Go standards) in
https://github.com/alleyinteractive/wp-block-converter/blob/develop/composer.json
and helped by the original composer package at
https://github.com/alleyinteractive/alley-coding-standards/blob/develop/composer.json

Requires a litte update in `phpcs.xml` replacing the keyword `Alley`

# SETUP PHPUNIT

Estoy desarrollando un plugin llamado cartelera-scrap en el entorno wp-env

Este es mi tests/bootstrap.php
```
copy here
```
Estos son mis paquetes en composer:
"yoast/phpunit-polyfills": "^4.0",
    "wp-phpunit/wp-phpunit": "^6.8",
    "phpunit/phpunit": "^9.6"

este es el comando que uso:

wp-env run tests-cli bash -c "cd wp-content/plugins/cartelera-scrap && vendor/bin/phpunit"

Al ejecutar el script llega a entrar en mi tests/bootstrap.php, y
Me da este error:
