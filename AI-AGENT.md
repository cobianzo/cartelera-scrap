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


# Sequence of the code flowing when executing the task

Sequence
===== 1. Scrapping to extract the sentences for dates

perform_scrap()
	$all_shows = Scraper_Cartelera::scrap_all_shows_in_cartelera();
	update_shows_queue_option( $all_shows );
	batch count = 0
	wp_schedule_single_event ONE TIME OFF batch


Cron ONE TIME OFF => cartelera_process_one_batch()
	self::cartelera_process_one_single_show();
	if  batch count still not finisihed
		recursivelly call myself again cartelera_process_one_batch()
	if finished, wp_schedule_single_event ONE TIME OFF batch

	cartelera_process_one_single_show()

			get_first_queued_show
			scrap_one_tickermaster_show
			scrap_one_cartelera_show
			save_show_result


// Results look like
/*
	Array (
	[title] => Las cuatro estaciones de Vivaldi
	[cartelera] => Array
	(
		[url] => unknown | https://carteleradeteatro...
		[scraped_dates_text] => 22 de junio de 2025.
		[scraped_time_text] => Domingo 12:00 horas.
	)

	[ticketmaster] => Array
	(
		[url] => unknown | https://ticketmaster.mx/search..
		[dates] => Array
			(
				[0] => Array
					(
						[printed_date] => jun22
						[time_12h] => 12:00 p.m.
						[date] => 2025-06-22
						[time] => 12:00
					)
			)
	)
	)
	*/

===== 2 With the results process the dates

for each $result in $results  (process text like `22 de junio de 2025` etc... ))
	first_acceptance_of_date_text( $result[cartelera][scraped_dates_text] )
		$array_of_sentences = separate_dates_sentences( ... same param ... )  <-- separates by year, or full period.

		// returns '22-junio-2025'.
		$array_of_sentences = sanitize_dates_sentence( $array_of_sentences )

		first_acceptance_of_times_text()


TO APPLY WHEN SAVING A RESULT
===
add these values to the result
For Cartelera:

$a = Text_Parser::first_acceptance_of_date_text( $result['cartelera']['scraped_dates_text'] )
$b = Text_Parser::first_acceptance_of_times_text( $result['cartelera']['scraped_time_text'] )

foreach $a as $sentence dates

	definitive_dates_and_times( $a, $b ) >>> [ 'yyyy-dd-mm H:i', 'yyyy-dd-mm H:i' ... ]
		$array_of_YYYYMMDD = Text_Parser::identify_dates_sentence_daterange_or_singledays($sentence_a)
		foreach $array_of_YYYYMMDD as  $weekday
			$weekday = self::get_weekday( $date );

			$times   = self::get_times_for_weekday(
				$weekday,
				sentence_b
			);
