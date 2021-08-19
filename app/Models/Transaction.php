<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

    protected $fillable = [
        'transaction_id',
        'trace_id',
        'ussd_code',
        'payment_reference',
        'phone_number',
        'amount',
        'remarks',
        'service_name',
        'request_time',
        'response_time',
        'status',
        'platform',
        'category',
        'api_request',
        'api_response',
        'client_request',
        'client_response',
        'service_request',
        'service_response',
        'api_callback',
        'date',
    ];

    public function getClientRequestAttribute()
    {
        return json_decode($this->attributes['client_request']);
    }

    public function getApiRequestAttribute()
    {
        return json_decode($this->attributes['api_request']);
    }
    public function getApiVendRequestAttribute()
    {
        return json_decode($this->attributes['api_vend_request']);
    }
    public function getApiVendResponseAttribute()
    {
        return json_decode($this->attributes['api_vend_response']);
    }
    public function getClientResponseAttribute()
    {
        return json_decode($this->attributes['client_response']);
    }
    public function getApiResponseAttribute()
    {
        return json_decode($this->attributes['api_response']);
    }
    public function getClientVendRequestAttribute()
    {
        return json_decode($this->attributes['client_vend_request']);
    }
    public function getClientVendResponseAttribute()
    {
        return json_decode($this->attributes['client_vend_response']);
    }
}
