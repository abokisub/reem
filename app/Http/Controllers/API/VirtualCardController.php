<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VirtualCardController extends Controller
{
    public function __construct()
    {
    // Provider removed
    }

    private function unavailable()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Virtual card services are temporarily unavailable due to system upgrades.'
        ], 503);
    }

    public function createNgnCard(Request $request)
    {
        return $this->unavailable();
    }

    public function createUsdCard(Request $request)
    {
        return $this->unavailable();
    }

    public function fundCard(Request $request, $id)
    {
        return $this->unavailable();
    }

    public function withdrawCard(Request $request, $id)
    {
        return $this->unavailable();
    }

    public function changeStatus(Request $request, $id)
    {
        return $this->unavailable();
    }

    public function getCards(Request $request)
    {
        return $this->unavailable();
    }

    public function getCardTransactions(Request $request, $id)
    {
        return $this->unavailable();
    }

    public function getCardDetails(Request $request, $id)
    {
        return $this->unavailable();
    }

    public function AdminTerminateCard(Request $request)
    {
        return $this->unavailable();
    }

    public function AdminDebitCard(Request $request)
    {
        return $this->unavailable();
    }

    public function AdminDeleteCard(Request $request)
    {
        return $this->unavailable();
    }

    public function AdminCardCustomerInfo(Request $request, $cardId)
    {
        return $this->unavailable();
    }
}