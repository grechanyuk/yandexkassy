<?php

Route::post(config('yandexkassy.events_url'), 'Grechanyuk\YandexKassy\Api\EventsController@responseFromYandexKassy');