<?php

namespace App\Http\Controllers;

use App\Models\expence;
use App\Models\invoice;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{

    use HttpResponses;
    public function create(Request $request){
        try{
            $request->validate([
                'invoice_number' => 'required|string|unique:invoices,invoice_number',
                'customer_id' => 'required|integer|exists:customers,user_id',
                'project_id' => 'required|integer|exists:projects,id',
                'discount' => 'nullable|numeric',
                'special_note' => 'nullable|string',
                'expenses' => 'nullable|array',
                'expenses.*.description' => 'required|string',
                'expenses.*.amount' => 'required|numeric',
                'total_amount' => 'required|numeric',

            ]);
            Log::info('Creating invoice with data: '.json_encode($request->all()));

            // Invoice creation logic goes here
            $result = invoice::create(
                [
                    'invoice_number' => $request->input('invoice_number'),
                    'customer_id' => $request->input('customer_id'),
                    'created_by' => $request->user()->id,
                    'project_id' => $request->input('project_id'),
                    'invoice_date' => now(),
                    'amount' => $request->input('total_amount'),
                    'discount' => $request->input('discount', 0),
                    'total' => $request->input('total_amount'),
                    'notes' => $request->input('special_note', ''),
                ]
            );
            if($result){
                $expenceController = new ExpenceController();
                foreach($request->input('expenses', []) as $expense){
                    // Assuming there's a model InvoiceExpense to handle expenses
                    $expenceController->createExpense($result->id, $expense['description'], $expense['amount']);
                }

                //add total to payment table
                $paymentController = new PaymentController();
                $paymentController->updatePaymentTotal($request->input('project_id'), $request->input('total_amount'));

                Log::info('Invoice created successfully with ID: '.$result->id);
                return $this->success([
                    'invoice' => $result->invoice_number
                ], 'Invoice created successfully'); 
            }
            else{
                Log::error('Failed to create invoice with data: '.json_encode($request->all()));
                return $this->error('', 'Failed to create invoice', 500);
            }

        }
        catch(ValidationException $e){
            Log::error('Validation Error in Invoice Creation: '.$e->getMessage());
            return $this->error('', $e->getMessage(), 422);
        }
        catch(Exception $e){
            Log::error('Server Error in Invoice Creation: '.$e->getMessage());
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }
}
