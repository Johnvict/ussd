<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

trait APICaller {
    public static $requestUrl, $urlSet;


	/**
	 * ? WE DEFINE some variables that can change if the 3rd Party Service decides to change them in future
	 * ? so that we can get them changed much easily from here
	 * !! NOTE THAT: This function is called from the App\Providers\AppServiceProvider boot function
	 * !! That means, it is called every time a request hits this microservice.. We can't afford to use updated values
	 */
    public static function init() {
        APICaller::$urlSet = [
			"VARIATION" => [
                "check" => env("SERVICE_VARIATION_URL") . "/variations/check"
            ],
			"PAYMENT" => [
				"verify" => env("PAYMENT_SERVICE_URL") . "/payment/verify"
            ],
			"AIRTIME" => [
				"vend" => env("AIRTIME_SERVICE_URL") . "/airtime/vend-callback"
            ],
			"POWER" => [
				"vend" => env("POWER_SERVICE_URL") . "/power/vend-callback"
            ],
			"DATA" => [
				"vend" => env("DATA_SERVICE_URL") . "/data/vend-callback"
            ],
			"TV" => [
				"vend" => env("TV_SERVICE_URL") . "/tv/vend-callback"
            ],
			"NOTIFICATION" => [
				"send" => env("NOTIFICATION_SERVICE_URL") . "/notify/create"
            ],
        ];

    }


	/**
	 * ? The URLs we need to use are mapped from the array in init() method above
	 * @param Array $urlKeys - This is the indices of the $urlSet of how we can get the needed URL
	 * e.g. To get https://payments.baxipay.com.ng/api/baxipay/superagent/account/balance $urlKeys will be ["GENERAL", "balance"]
	 */
    public static function getUrl(array $urlKeys) {
        APICaller::$requestUrl = APICaller::$urlSet;
        $count = count($urlKeys);
        for ($i = 0; $i < $count; $i++) {
            APICaller::$requestUrl = APICaller::$requestUrl[$urlKeys[$i]];
        }

        return APICaller::$requestUrl;
    }

    public static function get(array $urlStack) {
		APICaller::getUrl($urlStack);
		// dd(APICaller::$requestUrl);

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => APICaller::$requestUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Accept: application/json",
			"baxi-date: " .date(env('CAPRICON_TIME_FORMAT'))
          ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            // return $error;
        } else {
            // success
            return json_decode($response);
        }
    }
    public static function post($urlStack, $data, $isTextPlain = false) {
        APICaller::$requestUrl = gettype($urlStack) == "array" ? APICaller::getUrl($urlStack) : $urlStack;

        Log::info("POST REQUEST URL: ". APICaller::$requestUrl);
        Log::info($data);

        $contentType = $isTextPlain == true ? "Content-Type: text/plain" : "Content-Type: application/json";

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => APICaller::$requestUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $isTextPlain == true ? $data : json_encode($data),
          CURLOPT_HTTPHEADER => array(
            $contentType,
            "Accept: application/json",
			"Connection: keep-alive",
			"Cache-Control: no-cache",
          ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            Log::info("SOME ERROR OCCURED WHILE MAKING <POST> REQUEST");
            Log::error($error);
            return $error;
        } else {
            // success
            Log::info(json_encode($response));
            return json_decode($response);
        }
    }

    public static function getPlain($urlStack, $id = null) {
		APICaller::getUrl($urlStack);

		if ($id != null) APICaller::$requestUrl = APICaller::$requestUrl . '/' . $id;
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => APICaller::$requestUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
			"Accept: application/json",
			"baxi-date: " .date(env('CAPRICON_TIME_FORMAT'))
          ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            // return $error;
        } else {
            // success
            return json_decode($response);
        }
    }

}

