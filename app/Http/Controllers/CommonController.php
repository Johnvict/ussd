<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommonController extends Controller
{


    public static function encryptPGP($dataToEncrypt)
    {

        // TODO
        /**
         * ENCRYPT DATA USING THE PROCEDURES STATES IN THE DOCS
         */
        $result = $dataToEncrypt;   // DUmmy IMPLEMENTATION FOR NOW

        return $result;
    }


    // to mock realtime ussdResponse
    public static function mockApiResponse(Request $request) {
        return json_decode(json_encode([
            "ResponseHeader" => [
                "ResponseCode" => "00",
                "ResponseMessage" => "Success"
            ],
            "ResponseDetails" => [
                "Reference" => CommonController::generateRandomCode(4),
                "Amount" => $request->amount,
                "TransactionID" => CommonController::generateRandomCode(20),
                "TraceID" => $request->trace_id
            ]
        ]));
    }

    public static function generateRandomCode($length) {
        return substr(str_shuffle("50167283491245190393837"), 0, $length);
    }
}
