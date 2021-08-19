<?php

namespace App\Http\Controllers;

use App\Models\OldTransaction;
use App\Models\OldUnknownTransaction;
use App\Models\Transaction;
use App\Models\UnknownTransaction;
use App\Services\APICaller;
use App\Services\DataHelper;
use App\Services\HistoryService;
use App\Services\ResponseFormat;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UssdController extends Controller
{
    use DataHelper, APICaller, ResponseFormat, HistoryService;

    public function generateUSSD(Request $request)
    {

        $isErrored =  self::validateRequest($request, DataHelper::$GenerateUssdValidationRule);
        if ($isErrored) return self::returnFailed($isErrored);

        $requestTime = date('D jS M Y, h:i:sa');

        try {
            $activeService = APICaller::post(["VARIATION", "check"], [
                "name"  => "ussd-payment",
                "type"  => "ussd-payment"
            ]);

            if ($activeService) {
                if (isset($activeService->code)) {
                    if ($activeService->code == "00") {
                        $apiRequest = (array) $request->all();

                        // $apiResponse = APICaller::post("http://localhost:8081/api/v2/ussd/generate", $apiRequest);
                        $apiResponse = APICaller::post($activeService->data->url . "api/v2/ussd/generate", $apiRequest);
                        if ($apiResponse) {
                            if (isset($apiResponse->code)) {
                                if ($apiResponse->code == "00") {

                                    $clientResponse = [
                                        "transaction_id"    => $request->transaction_id,
                                        "trace_id"          => $request->trace_id,
                                        "ussd_code"         => $ussdCode = $apiResponse->data->ussd_code,
                                        "remarks"           => $remarks = "$request->amount NGN $request->service_name",
                                        "date"              => $date = date('D jS M Y, h:i:sa'),
                                    ];

                                    Transaction::create([
                                        "transaction_id"        => $request->transaction_id,
                                        "trace_id"              => $request->trace_id,
                                        "ussd_code"             => $ussdCode,
                                        "phone_number"          => $request->phone_number,
                                        "amount"                => $request->amount,
                                        "remarks"               => $remarks,
                                        "service_name"          => $request->service_name,
                                        "request_time"          => $requestTime,
                                        "response_time"         => $date,
                                        "platform"              => $request->platform,
                                        "category"              => $request->category,
                                        "date"                  => $date,
                                        "api_request"           => json_encode($apiRequest),
                                        "api_response"          => json_encode($apiResponse),
                                        "client_request"        => json_encode($request->all()),
                                        "client_response"       => json_encode($clientResponse),
                                    ]);

                                    return self::returnSuccess($clientResponse);
                                }
                            }
                        }
                    }
                }
            }

            return self::returnFailed("Sorry, the service is currently not available");
        } catch (Exception $err) {
            Log::error("SOMETHING WENT WRONG ON Payment Callback Notification");
            Log::error($err->getMessage());

            return self::returnFailed("Sorry, something went wrong. Don't worry, our engineers are working on it");
        }
    }

    public function paymentNotify(Request $callback)
    {
        Log::info("WE GOT A CALLBACK ON PAYMENT NOTIFICATION FROM 3rd PARTY");
        Log::info(json_encode($callback->all()));

        try {
            $transaction = Transaction::where([
                ["transaction_id", $callback->transaction_id],
                ["service_name", $callback->service_name]
            ])->first();
            if (!$transaction) {
                $transaction = OldTransaction::where([
                    ["transaction_id", $callback->transaction_id],
                    ["service_name", $callback->service_name]
                ])->first();
            }

            Log::info("Transaction being inquired");
            Log::info(json_encode($callback->all()));
            if (!$transaction) {
                Log::error("WE GOT A CALLBACK FROM 3rd party service, but the transaction does not exist");
                return UnknownTransaction::create([
                    "data" => json_encode($callback->all())
                ]);
            }

            $transaction->update([
                "status"            => $callback->responseCode == "00" ? "paid" : "pending",
                "date"              => $date = date('D jS M Y, h:i:sa'),
                "api_callback"      => json_encode($callback->all()),
                "payment_reference" => $callback->payment_reference
            ]);

            return $this->logPayment($transaction);
        } catch (Exception $err) {
            Log::error("SOMETHING WENT WRONG ON Payment Callback Notification");
            Log::error($err->getMessage());
        }
    }

    public function logPayment(Transaction $transaction)
    {
        try {
            $apiRequest = [
                "trace_id"              => $transaction->trace_id,
                "payment_reference"     => $transaction->payment_reference,
                "service_name"          => $transaction->service_name,
                "amount"                => $transaction->amount,
                "platform"              => $transaction->platform,
                "category"              => $transaction->category,
                "payment_type"          => "ussd",
                "phone_number"          => $transaction->phone_number,
                "transaction_id"        => $transaction->transaction_id,
                "remarks"               => $transaction->remarks,
            ];

            Log::info("\n\nMAKING REQUEST TO PAYMENT SERVICE TO LOG PAYMENT RECORD");
            LOG::info(json_encode($apiRequest));

            $apiResponse = APICaller::post(["PAYMENT", "verify"], $apiRequest);

            Log::info("\n\nWE GOT RESPONSE FROM PAYMENT SERVICE AFTER LOGGING PAYMENT RECORD");
            LOG::info(json_encode($apiResponse));

            if ($apiResponse) {
                if (isset($apiResponse->code) == "00") {
                    return $this->vendTransaction($transaction);
                }

                Log::info("\n\nERROR OCCURRED WHILE LOGGING PAYMENT");
                Log::info(json_encode($apiResponse));
            }
        } catch (Exception $err) {
        }
    }

    public function vendTransaction(Transaction $transaction)
    {
        try {
            $apiRequest = [
                "trace_id"              => $transaction->trace_id,
                "transaction_id"        => $transaction->transaction_id,
                "payment_type"          => "ussd",
                "payment_reference"     => $transaction->payment_reference,
                "category"              => $transaction->category
            ];

            Log::info("\n\nMAKING REQUEST TO AIRTIME SERVICE TO VEND TRANSACTION");
            LOG::info(json_encode($apiRequest));

            switch ($transaction->service_name) {
                case "airtime":
                    $apiResponse = $this->vendAirtime($apiRequest);
                    break;
                case "power":
                    $apiResponse = $this->vendPower($apiRequest);
                    break;
                case "tv":
                    $apiResponse = $this->vendTv($apiRequest);
                    break;
                case "data":
                    $apiResponse = $this->vendData($apiRequest);
                    break;
                default:
                    Log::error("\n\nWe've been provided with some unrecognized service name");
                    Log::error("We don't know how to vend this transaction");
                    return self::returnFailed("Invalid Service Name");
            }
            Log::info("\n\nWE GOT RESPONSE FROM $transaction->service_name SERVICE AFTER VEND");
            LOG::info(json_encode($apiResponse));

            if ($apiResponse) {
                if (isset($apiResponse->code) == "00") {
                    $transaction->update([
                        'service_request'   => json_encode($apiRequest),
                        'service_response'  => json_encode($apiResponse)
                    ]);

                    return $this->sendNotification($apiResponse->data, $transaction);
                }

                Log::info("\n\nERROR OCCURRED WHILE VENDING TRANSACTION");
                Log::info(json_encode($apiResponse));
            }
        } catch (Exception $err) {
        }
    }

    /**
     * Vend Airtime Transaction
     */
    public function vendAirtime($apiRequest)
    {
        return APICaller::post(["AIRTIME", "vend"], $apiRequest);
    }

    /**
     * Vend Power Transaction
     */
    public function vendPower($apiRequest)
    {
        return APICaller::post(["POWER", "vend"], $apiRequest);
    }

    /**
     * Vend Tv Transaction
     */
    public function vendTv($apiRequest)
    {
        return APICaller::post(["TV", "vend"], $apiRequest);
    }

    /**
     * Vend Data Transaction
     */
    public function vendData($apiRequest)
    {
        return APICaller::post(["DATA", "vend"], $apiRequest);
    }

    public function sendNotification($data, $transaction)
    {
        try {
            $apiRequest = [
                "trace_id"      => $transaction->trace_id,
                "service_name"  => $transaction->service_name,
                "status"        => $data->status == "fulfilled" ? "successful" : "failed",
                "transaction_id"   =>  $transaction->transaction_id,
                "data"  => (array) $data
            ];

            Log::info("\n\nMAKING REQUEST TO NOTIFICATION TO NOTIFY USER OF TRANSACTION FULFILLMENT");
            LOG::info(json_encode($apiRequest));

            $apiResponse = APICaller::post(["NOTIFICATION", "send"], $apiRequest);

            Log::info("\n\nWE GOT RESPONSE FROM NOTIFICATION SERVICE");
            LOG::info(json_encode($apiResponse));

            if ($apiResponse) {
                if (isset($apiResponse->code) == "00") {
                    Log::info("\n\nTRANSACTION FULFILLMENT CYCLE COMPLETED");
                    Log::info(json_encode($apiResponse));
		    return;
		}

                Log::info("\n\nERROR OCCURRED WHILE SENDING NOTIFICATION");
                Log::info(json_encode($apiResponse));
            }
        } catch (Exception $err) {
            Log::error("\n\nAN ERROR OCCURRED WHILE SENDING NOTIFICATION");
            Log::error($err->getMessage());
        }
    }


    public function transactionHistory(Request $request)
    {
        $isErrored =  self::validateRequest($request, self::$TransactionHistoryValidationRule);
        if ($isErrored) return self::returnFailed($isErrored);
        $historyData = self::fetchHistory(new Transaction(), new OldTransaction(), $request->all(), $request->page);

        return self::returnSuccess($historyData);
    }

    public function unknownTransactionHistory(Request $request)
    {
        $isErrored =  self::validateRequest($request, self::$TransactionHistoryValidationRule);
        if ($isErrored) return self::returnFailed($isErrored);
        $historyData = self::fetchHistory(new UnknownTransaction(), new OldUnknownTransaction(), $request->all(), $request->page);

        return self::returnSuccess($historyData);
    }
}
