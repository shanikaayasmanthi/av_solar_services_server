<?php

namespace App\Http\Controllers;

use App\Models\expence;
use Illuminate\Http\Request;

class ExpenceController extends Controller
{
    public function createExpense($invoiceId, $description, $amount){
        return expence::create([
            'invoice_id' => $invoiceId,
            'expense_type' => $description,
            'amount' => $amount,
        ]);
    }
}
