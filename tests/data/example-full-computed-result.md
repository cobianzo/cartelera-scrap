# Data for the full computed result

1. We have scrapped the data from cartelera and from ticketmaster
2. We ran Parse_Text_Into_Dates::computed_data_cartelera_result( $result )
	To get all the intermediate values and the definitive dates for both platforms.
3. We have ran the extra layer for comparison only the dates inside of the range of comparable
	Parse_Text_Into_Dates::computed_is_comparison_successful( $result )

> NOTE: eveything in [computed] has been calculated with `computed_data_cartelera_result`,
> and everyting inside [computed][comparison][<timestamp>][extra] has been calculated with `computed_is_comparison_successful`


# The data

Array
(
    [title] => Perdida de la memoria
    [cartelera] => Array
        (
            [url] => https://carteleradeteatro.mx/2025/perdida-de-la-memoria/
            [scraped_dates_text] => Del 16 abril al 18 mayo de 2025.
            [scraped_time_text] => Jueves y viernes 20:00 horas, sábado 19:00 horas y domingo 18:00 horas.
Duración aproximada: 45 minutos
Clasificación:
Boletos: Entrada general $204. Descuento del 50%, limitado a estudiantes de nivel básico, maestros, personas con discapacidad, trabajadores de gobierno e INAPAM con credencial vigente. Aplican restricciones. De venta en taquilla y Ticketmaster.
        )

    [ticketmaster] => Array
        (
            [url] => https://www.ticketmaster.com.mx/search?q=Perdida%20de%20la%C2%A0memoria
            [search_results] => 0
            [single_page_url] =>
            [tm_title] =>
            [tm_titles_list] => Array
                (
                    [0] => PÉRDIDA DE LA MEMORIA de Bárbara Alvarado
                )

            [dates] => Array
                (
                    [0] => Array
                        (
                            [printed_date] => may08
                            [time_12h] => 8:00 p.m.
                            [date] => 2025-05-08
                            [time] => 20:00
                        )

                    [1] => Array
                        (
                            [printed_date] => may09
                            [time_12h] => 8:00 p.m.
                            [date] => 2025-05-09
                            [time] => 20:00
                        )

                    [2] => Array
                        (
                            [printed_date] => may10
                            [time_12h] => 7:00 p.m.
                            [date] => 2025-05-10
                            [time] => 19:00
                        )

                    [3] => Array
                        (
                            [printed_date] => may11
                            [time_12h] => 6:00 p.m.
                            [date] => 2025-05-11
                            [time] => 18:00
                        )

                )

        )

    [computed] => Array
        (
            [cartelera] => Array
                (
                    [first_aceptance_dates] => Array
                        (
                            [output] => Array
                                (
                                    [0] => del-16-abril-al-18-mayo-2025
                                )

                            [sentences] => Array
                                (
                                    [0] => Del 16 abril al 18 mayo de 2025
                                )

                            [sanitized] => Array
                                (
                                    [0] => del-16-abril-al-18-mayo-2025
                                )

                        )

                    [first_aceptance_times] => Array
                        (
                            [sentences] => Array
                                (
                                    [0] => Jueves y viernes 20:00 horas
                                    [1] => sábado 19:00 horas
                                    [2] => domingo 18:00 horas
                                )

                            [sanitized] => Array
                                (
                                    [0] => jueves-y-viernes-20:00-horas
                                    [1] => sabado-19:00-horas
                                    [2] => domingo-18:00-horas
                                )

                            [output] => Array
                                (
                                    [0] => thursday-friday-20:00
                                    [1] => saturday-19:00
                                    [2] => sunday-18:00
                                )

                        )

                    [definitive_datetimes] => Array
                        (
                            [removing_dates] => Array
                                (
                                )

                            [dates_per_sentence] => Array
                                (
                                    [del-16-abril-al-18-mayo-2025] => Array
                                        (
                                            [0] => 2025-04-16
                                            [1] => 2025-04-17
                                            [2] => 2025-04-18
                                            [3] => 2025-04-19
                                            [4] => 2025-04-20
                                            [5] => 2025-04-21
                                            [6] => 2025-04-22
                                            [7] => 2025-04-23
                                            [8] => 2025-04-24
                                            [9] => 2025-04-25
                                            [10] => 2025-04-26
                                            [11] => 2025-04-27
                                            [12] => 2025-04-28
                                            [13] => 2025-04-29
                                            [14] => 2025-04-30
                                            [15] => 2025-05-01
                                            [16] => 2025-05-02
                                            [17] => 2025-05-03
                                            [18] => 2025-05-04
                                            [19] => 2025-05-05
                                            [20] => 2025-05-06
                                            [21] => 2025-05-07
                                            [22] => 2025-05-08
                                            [23] => 2025-05-09
                                            [24] => 2025-05-10
                                            [25] => 2025-05-11
                                            [26] => 2025-05-12
                                            [27] => 2025-05-13
                                            [28] => 2025-05-14
                                            [29] => 2025-05-15
                                            [30] => 2025-05-16
                                            [31] => 2025-05-17
                                            [32] => 2025-05-18
                                        )

                                )

                            [times] => Array
                                (
                                    [wednesday] => Array
                                        (
                                        )

                                    [thursday] => Array
                                        (
                                            [0] => 20:00
                                        )

                                    [friday] => Array
                                        (
                                            [0] => 20:00
                                        )

                                    [saturday] => Array
                                        (
                                            [0] => 19:00
                                        )

                                    [sunday] => Array
                                        (
                                            [0] => 18:00
                                        )

                                    [monday] => Array
                                        (
                                        )

                                    [tuesday] => Array
                                        (
                                        )

                                )

                            [output] => Array
                                (
                                    [0] => 2025-04-17 20:00
                                    [1] => 2025-04-18 20:00
                                    [2] => 2025-04-19 19:00
                                    [3] => 2025-04-20 18:00
                                    [4] => 2025-04-24 20:00
                                    [5] => 2025-04-25 20:00
                                    [6] => 2025-04-26 19:00
                                    [7] => 2025-04-27 18:00
                                    [8] => 2025-05-01 20:00
                                    [9] => 2025-05-02 20:00
                                    [10] => 2025-05-03 19:00
                                    [11] => 2025-05-04 18:00
                                    [12] => 2025-05-08 20:00
                                    [13] => 2025-05-09 20:00
                                    [14] => 2025-05-10 19:00
                                    [15] => 2025-05-11 18:00
                                    [16] => 2025-05-15 20:00
                                    [17] => 2025-05-16 20:00
                                    [18] => 2025-05-17 19:00
                                    [19] => 2025-05-18 18:00
                                )

                        )

                )

            [ticketmaster] => Array
                (
                    [definitive_datetimes] => Array
                        (
                            [output] => Array
                                (
                                    [0] => 2025-05-08 20:00
                                    [1] => 2025-05-09 20:00
                                    [2] => 2025-05-10 19:00
                                    [3] => 2025-05-11 18:00
                                )

                        )

                )

            [comparison] => Array
                (
                    [1744920000] => Array
                        (
                            [datetime] => 2025-04-17 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745006400] => Array
                        (
                            [datetime] => 2025-04-18 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745089200] => Array
                        (
                            [datetime] => 2025-04-19 19:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745172000] => Array
                        (
                            [datetime] => 2025-04-20 18:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745524800] => Array
                        (
                            [datetime] => 2025-04-24 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745611200] => Array
                        (
                            [datetime] => 2025-04-25 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745694000] => Array
                        (
                            [datetime] => 2025-04-26 19:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1745776800] => Array
                        (
                            [datetime] => 2025-04-27 18:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1746129600] => Array
                        (
                            [datetime] => 2025-05-01 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1746216000] => Array
                        (
                            [datetime] => 2025-05-02 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1746298800] => Array
                        (
                            [datetime] => 2025-05-03 19:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1746381600] => Array
                        (
                            [datetime] => 2025-05-04 18:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] => date already passed
                                    [success-icon] => 😵 (not evaluable)
                                )

                        )

                    [1746734400] => Array
                        (
                            [datetime] => 2025-05-08 20:00
                            [cartelera] => 1
                            [ticketmaster] => 1
                            [success] => 1
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 1
                                    [count-in-ticketmaster] => 1
                                    [success-icon] => ✅
                                )

                        )

                    [1746820800] => Array
                        (
                            [datetime] => 2025-05-09 20:00
                            [cartelera] => 1
                            [ticketmaster] => 1
                            [success] => 1
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 2
                                    [count-in-ticketmaster] => 2
                                    [success-icon] => ✅
                                )

                        )

                    [1746903600] => Array
                        (
                            [datetime] => 2025-05-10 19:00
                            [cartelera] => 1
                            [ticketmaster] => 1
                            [success] => 1
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 3
                                    [count-in-ticketmaster] => 3
                                    [success-icon] => ✅
                                )

                        )

                    [1746986400] => Array
                        (
                            [datetime] => 2025-05-11 18:00
                            [cartelera] => 1
                            [ticketmaster] => 1
                            [success] => 1
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 4
                                    [count-in-ticketmaster] => 4
                                    [success-icon] => ✅
                                )

                        )

                    [1747339200] => Array
                        (
                            [datetime] => 2025-05-15 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 5
                                    [success-icon] => ❌
                                )

                        )

                    [1747425600] => Array
                        (
                            [datetime] => 2025-05-16 20:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 6
                                    [success-icon] => ❌
                                )

                        )

                    [1747508400] => Array
                        (
                            [datetime] => 2025-05-17 19:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 7
                                    [success-icon] => ❌
                                )

                        )

                    [1747591200] => Array
                        (
                            [datetime] => 2025-05-18 18:00
                            [cartelera] => 1
                            [ticketmaster] =>
                            [success] =>
                            [extra] => Array
                                (
                                    [invalid-for-comparison] =>
                                    [count-in-cartelera] => 8
                                    [success-icon] => ❌
                                )

                        )

                )

        )

)
