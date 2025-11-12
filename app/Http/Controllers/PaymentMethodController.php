<?php

namespace App\Http\Controllers;

use App\Events\PaymentMethodUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentMethodController extends Controller
{
    /**
     * Get all payment methods for authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // For now, return mock data. In production, fetch from database
        // Assuming payment methods are stored in JSON or related table
        $paymentMethods = $user->payment_methods ?? [];
        
        return response()->json([
            'payment_methods' => $paymentMethods
        ]);
    }

    /**
     * Add a new payment method
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_type' => 'required|string|in:GCash,PayPal,Credit Card,Debit Card,Bank Transfer',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        // Extract last 4 digits for display
        $last4Digits = substr($data['account_number'], -4);

        // In production, save to database
        // For now, we'll just trigger the event
        
        // Dispatch event to send email
        event(new PaymentMethodUpdated(
            $user,
            'added',
            $data['payment_type'],
            $last4Digits
        ));

        Log::info('Payment method added', [
            'user_id' => $user->id,
            'payment_type' => $data['payment_type'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment method added successfully. Confirmation email sent.',
            'payment_method' => [
                'type' => $data['payment_type'],
                'last4' => $last4Digits,
                'name' => $data['account_name'],
                'is_default' => $data['is_default'] ?? false,
            ]
        ], 201);
    }

    /**
     * Update an existing payment method
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'payment_type' => 'sometimes|string|in:GCash,PayPal,Credit Card,Debit Card,Bank Transfer',
            'account_number' => 'sometimes|string',
            'account_name' => 'sometimes|string',
            'is_default' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        // Extract last 4 digits if account number provided
        $last4Digits = isset($data['account_number']) 
            ? substr($data['account_number'], -4) 
            : null;

        // In production, update database record
        
        // Dispatch event to send email
        event(new PaymentMethodUpdated(
            $user,
            'changed',
            $data['payment_type'] ?? 'Credit Card',
            $last4Digits
        ));

        Log::info('Payment method updated', [
            'user_id' => $user->id,
            'payment_method_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated successfully. Confirmation email sent.',
        ]);
    }

    /**
     * Remove a payment method
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // In production, fetch payment method details before deletion
        $paymentType = 'Credit Card'; // Mock data
        $last4Digits = '1234'; // Mock data

        // Dispatch event to send email
        event(new PaymentMethodUpdated(
            $user,
            'removed',
            $paymentType,
            $last4Digits
        ));

        Log::info('Payment method removed', [
            'user_id' => $user->id,
            'payment_method_id' => $id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment method removed successfully. Confirmation email sent.',
        ]);
    }
}
