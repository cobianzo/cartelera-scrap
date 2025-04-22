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

