<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;


class PaymentController extends Controller
{
    /**
     * Get all payments with related project data.
     */
    public function getPayments($projectId)
       {
        try {
            // Fetch payments where project_id matches, including project info
            $payments = Payment::with('project')
                ->where('project_id', $projectId)
                ->get();

            if ($payments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payments found for this project',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $payments,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
