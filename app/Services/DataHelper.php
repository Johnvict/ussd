<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ResponseFormat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait DataHelper
{
	/**
	 * ? These static values are calidation rules for all POST requests into our microservice
	 * ? They are used statically from various providers needing them
	 */
	public static $errorArray;
	public static $GenerateUssdValidationRule = [
		"trace_id"          => "required|string",
        "transaction_id"    => "required|string",
		"phone_number"             => "required|string",
		"amount"            => "required|integer",
		"service_name"      => "required|string",
        "platform"          => "required|string",
        "category"          => "required|string"
	];
	public static $PaymentCallbackValidationRule = [
        "trace_id"          => "required|string",
        "transaction_id"    => "required|string",
        "ussd_code"         => "required|string",
        "payment_reference" => "required|string",
		"amount"            => "required|integer",
		"service_name"      => "required|string",
	];


	public static $TransactionHistoryValidationRule = [
		"trace_id" => "string",
		"phone_number" => "string",
		"last_id" => "integer",
		"start_date" => "date|before_or_equal:today",
		"end_date" => "date|before_or_equal:today",
	];
	public static $UnknownTransactionHistoryValidationRule = [
		"start_date" => "date|before_or_equal:today",
		"end_date" => "date|before_or_equal:today",
	];


	/**
	 * ? To ensure a better object whose keys are the parameter keys as expected and values are the error message
	 * @param Mixed $errorArray - Complex array got from Laravel Validator method
	 * @return Mixed or null - An object is returned if there is an unexpected request body or null if no error
	 */
	public static function formatError($errorArray)
	{
		DataHelper::$errorArray = collect($errorArray);
		$newErrorFormat = DataHelper::$errorArray->map(function ($error) {
			return $error[0];
		});
		return $newErrorFormat;
	}

	/**
	 * ? To validate parameters on incoming requests
	 * ? These validation customizes the validation error
	 * @param Request $requestData - The request body as sent from the client
	 * @return Mixed or null - An object is returned if there is an unexpected request body or null if no error
	 */
	public static function validateRequest(Request $requestData, array $validationRule)
	{
		$validation = Validator::make($requestData->all(), $validationRule);

		// ? Did we get some errors? Okay, restructure the error @here
		if ($validation->fails()) return DataHelper::formatError($validation->errors());
		return false;
	}


			/**
	 * ? To obtain the balance from a file in the the local directory of the microservice
	 * @return Mixed
	 */
	public static function getBalance()
	{
		$check = Cache::has('balance');

        if (!$check) {
            $check = self::setBalance(0.00);
        }

        $accBalance = Cache::get("balance");
		return $accBalance;
	}

			/**
	 * ? To update the balance on the local, by subtracting the amount from the current balance
	 * "@param number $amount - Amount of the just completed transaction
	 */
	public static function resetBalance($amount)
	{
		$currentBalance = self::getBalance();

		$newBalance = $currentBalance - $amount;
		return self::setBalance($newBalance);
	}

			/**
	 * ? To instantiate an account balance, saved on the local directory of our miroservice
	 * @param Mixed $balance - The balance object which comprises of the BALANCE and the UPDATED_AT fields
	 */
	private static function setBalance($balance)
	{
		return Cache::put('balance', $balance);
		return $balance;
	}

	public static function getPackagesFetchReport()
	{
		$file_path = realpath(__DIR__ . '/../../database/packages-fetch-history.json');

		// ? If the file never exists, create it with a default value of 0.00
		if ($file_path == false) return null;

		return json_decode(file_get_contents($file_path), false);
	}

	public static function getPackagesProvidersFetchReport($type)
	{
		$file_path = $type == "packages" ? realpath(__DIR__ . '/../../database/packages-fetch-history.json') : realpath(__DIR__ . '/../../database/providers-fetch-history.json');
		// ? If the file never exists, create it with a default value of 0.00
		if ($file_path == false) return json_decode('{}');
		return json_decode(file_get_contents($file_path), false);
	}

	public static function setPackagesProvidersFetchReport($type, $report)
	{
		if ($type == "packages") {
			return file_put_contents(__DIR__ . '/../../database/packages-fetch-history.json', json_encode($report));
		}
		return file_put_contents(__DIR__ . '/../../database/providers-fetch-history.json', json_encode($report));
	}

}
