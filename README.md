TODO
===
- BUG:When deployed in a server, the main cron calling the recursive onetimeoff job is not wokring ok.
- Send email with report. Add it to the settings options.
- Add the title in tickermaster to the info in the results.
- apply 'suspende' text
- sometiems ticketmaster search returns more than onwe show. Apply extra comparison to find the closest one, and open the link: ie. https://www.ticketmaster.com.mx/search?q=Magic
Check 'get_ticketmaster_url', there we can move to the first link for the first show, and analyze that page, not the search resultt
- correct this error: 22-junio-2025 parses into two dates:
(
    [0] => 2025-06-22
    [1] => 2025-06-27
    [2] => 2025-06-01
)
- Refactor: ideally we loop all shows and save all relevant information in the results option table.
then we render those results in the table. (at the mo it calculates de output results as we render that table html)



WHAT IS THIS PROJECT
===

This is a plugin of WordPress. It's purely managed from a custom settings page (no frontend).
It's developed in wp-env.
This projects will scrap the list of theater shows in `https://carteleradeteatro.mx/`,
and compare it with the same shows in `http://ticketmaster.com.mx/`.
It will ensure that all timetables are the same.
In case it finds an error, it will display it, or send an email

> **Note:** The e2e PHPUnit test is very useful to see the entire sequence of what the plugin does.

HOW IT WORKS
===

First it scan and scrap the carteleradeteatro.mx/todas to grab all the titles for all theathre shows.
In the table options, it saves that list as the queue of shows that need to be scraped
On every iteration, it scrap one by one every show in the list, starting by the first.
Once processed, it saves the result in the table options too, and removes the processed show from the queue.
When this whole process is finished, we have the relevant data for all shows, either from
carteleradeteatro and from ticketmaster. It looks like this.

- The results of scrapping both sites can be exported and saved in a file called wp-content/uploads/cartelera-scrap/cartelera-scrap-results.json.
NOTE: most of shows are only in cartelera, but has no entry in ticketmaster.

Settings:



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

Using DB CLI
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

# PHPUNIT

```
npm run test:php
npm run test:php:watch

it uses wp-env run tests-wordpress ...
```

Following the last PHPUnit installation working version: https://github.com/cobianzo/wp-env-sidebar-related-article/
PHPUnit 9.4.

Important, we need to use php 8.3 in wp-env, so we can run the package
`wp-env run tests-wordpress` which works perfectly out of the box.

The watch version of the phpunit run works like charm!!

If run teh tests outside the container, it's still not tested.

packages:
- phpunit/phpunit: ! important, version 9 max, or it will be incompatible with function inside teh tests.
Then we can access locally o inside the container to wp-content/plugins/cartelera-scrap/vendor/bin/phpunit
- yoast/phpunit-polyfills it must be installed, and `wp-env run tests-wordpress` finds it automatically. When installed, it install phpunit, as it depends on it, but the version 12. We need to install phpunit ourselves, the version 9, so there are no incompatibilites.
- spatie/phpunit-watcher: for the phpUnit watcher, ran with `npm run test:php:watch`.
- ~~wp-phpunit/wp-phpunit~~: not needed, all the bootstrap is handled by `wp-env run tests-wordpress`

# TESTS PHP

Since we use cron jobs, and we can't run then naturally in local, I created a couple of scripts
to run it.
There are two cron jobs in this project:
- 'cartelera-scrap_automated_cron' : runs every day at midnight, if activated in the settings page
- 'cartelera-scrap_process_next_onetimeoff' : recursive one-time-off cron that calls itself until the option 'cartelera-scrap_shows_queue' is emptied

Use

`npm run test:listcron` - to show all cron jobs scheduled
`npm run test:runcron`  - to run the next recursive one time off cron job
`npm run test:options`  - to see the settings about this plugin saved in the DB.

## Cases to Test
- when the cartelera title is not in ticketmaster
- when the cartelera title has more than one show title occurrence in ticketmaster
- when the cartelera title has a result in ticketmaster
