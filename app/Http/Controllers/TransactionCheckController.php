<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\CheckTransactionResource;
use App\Http\Resources\Api\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionCheckController extends Controller
{
    public function index()
    {
        $transaction = Transaction::with([
            'user',
            'detailTransactions',
            'rentalIncludes'

        ])->get();

        return CheckTransactionResource::collection($transaction);
    }
}
