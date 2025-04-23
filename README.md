WHAT IS THIS PROJECT
===

This is a plugin of WordPress.
It's developed in wp-env.
This projects will scrap the list of theater shows in `https://carteleradeteatro.mx/`,
and compare it with the same shows in `http://ticketmaster.com.mx/`.
It will ensure that all timetables are the same.
In case it finds an error, it will display it, or send an email

DEVELOPMENT
===
Dependencies
- v18.20.3
- npm 10.7.0
- composer 2.7.9
- install wp-env globally.

`npm run up`
WordPress development site started at http://localhost:8666
WordPress test site started at http://localhost:8667
MySQL is listening on port 54868
MySQL for automated testing is listening on port 54878

## Use CLI

`wp-env run cli`
`wp-env run cli bash`

### Use CLI for DB connection (MySQL/MariaDB)

The raw connection would be (replacing the port with the one created by wp-env):

`mysql -h127.0.0.1 -P54868 -uroot -p`

Using CLI
`wp-env run cli wp db cli`

### Use WP CLI

`wp-env run cli wp get option siteurl`


PHPCS
===
Installed Alleys PHPCS standards, which uses WordPress VIP.
Installed PHPStan too.
Both work ok, check composer.json scripts to run the phpcs, phpcbf and phpstan commands.
Check AI-AGENT.md for more info.

## commands
```
composer run lint,
composer run format,
composer analyze
npm run cs .
npm run cbf .
```

# TESTS PHP

## Cases to Test
- when the cartelera title is not in ticketmaster
- when the cartelera title has more than one show title occurrence in ticketmaster
- when the cartelera title has a result in ticketmaster
