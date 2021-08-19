<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () {
	return redirect()->route('health');
});


$router->group(['prefix' => 'api/v2'], function () use ($router) {
	// ? APP STATUS
	$router->get('/health', [
		'as' => 'health',
		'uses' => 'AdminController@health',
		'middleware' => 'appState'
	]);

	// ? ADMIN ROUTES
	$router->group(['prefix' => 'manager/service'], function () use ($router) {
		$router->get('/enable', 'AdminController@enable');
		$router->get('/disable', 'AdminController@disable');
		$router->get('/balance', 'AdminController@balance');
	});

    // ? HISTORY ENDPOINTS
	$router->group(['prefix' => '/history'], function () use ($router) {
		$router->post('/transactions', 'UssdController@transactionHistory');
		$router->post('/unknown-transactions', 'UssdController@unknownTransactionHistory');
	});
    // ? USSD ENDPOINTS
	$router->group(['prefix' => '/ussd'], function () use ($router) {
		$router->post('/generate', 'UssdController@generateUSSD');
	});


    // ? OLAP/OLTP
	$router->group(['prefix' => '/olap'], function () use ($router) {
		$router->get('/ussd', 'OLAPController@backupUSSD');
		$router->get('/unknown', 'OLAPController@backupUnknown');
	});

    // ? CALLBACK ENDPOINT
	$router->group(['prefix' => '/payment'], function () use ($router) {
		$router->post('/notify', 'UssdController@paymentNotify');
	});



});
