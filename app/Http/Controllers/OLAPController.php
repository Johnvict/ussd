<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OldTransaction;
use App\Models\OldUnknownTransaction;
use App\Models\Transaction;
use App\Models\UnknownTransaction;
use App\Services\DataHelper;
use App\Services\OLAPService;
use App\Services\ResponseFormat;

class OLAPController extends Controller {

	use DataHelper, ResponseFormat, OLAPService;

	public static $idArray = array();

	public function __construct()
	{
		//
	}

	public function backupUSSD() {
		$response = OLAPService::migrateOldTransactions(new Transaction(), new OldTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}
	public function backupUnknown() {
		$response = OLAPService::migrateOldTransactions(new UnknownTransaction(), new OldUnknownTransaction());
		return $response["status"] == "00" ? self::returnSuccess() : self::returnFailed($response["message"]);
	}
}
