<?php
if (defined("IN_APPS") === false) exit("Access Dead");

use App\Middleware\Route;

$app->get('/auction/index', Route::requireLogin(), function() use ($app) {
	$app->render('auction/index.html');
})->name('auction.index');
