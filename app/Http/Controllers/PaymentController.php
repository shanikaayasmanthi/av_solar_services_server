<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Support\Facades\Log;


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

//   public function getPaymentsWithProjects(Request $request)
//     {
//         try {
//             $perPage = $request->input('per_page', 10);

//             $projects = Project::with(['customer', 'payments', 'onGrid', 'offGridHybrid'])
//                 ->paginate($perPage);

// $data = $projects->map(function ($project) {
//     // Decide project number based on type
//     $projectNo = null;
//     if ($project->type === 'ongrid' && $project->onGrid) {
//         $projectNo = $project->onGrid->on_grid_project_id;
//     } elseif ($project->type === 'offgrid' && $project->offGridHybrid) {
//         $projectNo = $project->offGridHybrid->off_grid_hybrid_project_id;
//     }

//     // Payment summary (assuming 1 payment per project, otherwise sum it)
//     $totalPayment = $project->payment->sum('total_payment');
//     $paidAmount   = $project->payment->sum('paid_amount');
//     $duePayment   = $project->payment->sum('due_payment');
//     $paymentNotes = $project->payment->pluck('payment_notes')->first();

//     return [
//         'id'            => $project->id,
//         'project_no'    => $projectNo,
//         'project_name'  => $project->project_name,
//         'customer_name' => optional($project->customer)->name,
//         'payment' => [
//             'id' => $project->payment->pluck('id')->first(),
//             'total'   => $totalPayment,
//             'paid'    => $paidAmount,
//             'due'     => $duePayment,
//             'notes'   => $paymentNotes, // <-- added here
//         ],
//     ];
// });


//             return response()->json([
//                 'success' => true,
//                 'data' => $data,
//                 'meta' => [
//                     'current_page' => $projects->currentPage(),
//                     'last_page'    => $projects->lastPage(),
//                     'total'        => $projects->total(),
//                 ]
//             ]);

//         } catch (\Exception $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Failed to fetch payments',
//                 'error'   => $e->getMessage(),
//             ], 500);
//         }
//     }

public function getPaymentsWithProjects(Request $request)
{
    try {
        $perPage = $request->input('per_page', 10);
        $search  = $request->input('search', null);

        // Base query with relationships
        $query = Project::with(['customer', 'payment', 'onGrid', 'offGridHybrid'])
         ->has('payment'); 

        // Add search filtering if search term is provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('onGrid', function ($q3) use ($search) {
                      $q3->where('on_grid_project_id', 'like', "%{$search}%");
                  })
                  ->orWhereHas('offGridHybrid', function ($q4) use ($search) {
                      $q4->where('off_grid_hybrid_project_id', 'like', "%{$search}%");
                  });
            });
        }

        // Paginate
        $projects = $query->paginate($perPage);

        // Map data
        $data = $projects->map(function ($project) {
            $projectNo = null;
            if ($project->type === 'ongrid' && $project->onGrid) {
                $projectNo = $project->onGrid->on_grid_project_id;
            } elseif ($project->type === 'offgrid' && $project->offGridHybrid) {
                $projectNo = $project->offGridHybrid->off_grid_hybrid_project_id;
            }

            // $totalPayment = $project->payment->total_payment??0;
            // $paidAmount   = $project->payment->paid_amount??0;
            // $duePayment   = $project->payment->due_payment??0;
            // $paymentNotes = $project->payment->payment_notes??'';

            return [
                'id'            => $project->id,
                'project_no'    => $projectNo,
                'project_name'  => $project->project_name,
                'customer_name' => optional($project->customer)->name,
                'payment' => [
                                'id'    => optional($project->payment)->id, // Simply access ID safely
                                'total' => $project->payment->total_payment ?? 0,
                                'paid'  => $project->payment->paid_amount ?? 0,
                                'due'   => $project->payment->due_payment ?? 0,
                                'notes' => $project->payment->payment_notes ?? '',
                             ],
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'total'        => $projects->total(),
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch payments',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    // Update a payment record
public function store(Request $request, $projectId)
{
    try {
        $request->validate([
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'due' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        $payment = Payment::create([
            'project_id' => $projectId,
            'total_payment' => $request->total,
            'paid_amount' => $request->paid,
            'due_payment' => $request->due,
            'payment_notes' => $request->notes ?? null,
        ]);

            Log::channel('payments')->info('Payment created', [
            'user_id'    => $request->user()->id(),
            'user_name'  => $request->user()->name ?? 'system',   
            'project_id' => $projectId,
            'payment_id' => $payment->id,
            'data'       => $payment->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $payment,
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create payment',
            'error' => $e->getMessage(),
        ], 500);
    }
}

public function update(Request $request, $id)
{
    try {
        $payment = Payment::findOrFail($id);

        $request->validate([
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'due' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

         $oldData = $payment->toArray();

        $payment->update([
            'total_payment' => $request->total,
            'paid_amount' => $request->paid,
            'due_payment' => $request->due,
            'payment_notes' => $request->notes ?? $payment->payment_notes,
        ]);

                // Log the update
        Log::info('Payment updated', [
            'payment_id' => $payment->id,
            'project_id' => $payment->project_id,
            'updated_by' => $request->user()->id ?? 'system',
            'user_name'  => $request->user()->name ?? 'system',
            'old_values' => $oldData,
            'new_values' => $payment->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => $payment,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update payment',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
