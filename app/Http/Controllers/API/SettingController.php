<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Company;

class SettingController extends Controller
{
    /**
     * Update Business Information
     */
    public function updateBusinessInfo(Request $request, $token)
    {
        $userId = $this->verifytoken($token);
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 403, 'message' => $validator->errors()->first()])->setStatusCode(403);
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = time() . '_' . $logo->getClientOriginalName();
            // Store in public/logos folder
            $logo->move(public_path('logos'), $filename);
            // Create the full URL path
            $logoPath = url('logos/' . $filename);
        }

        // Update User Model
        $user->update([
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'business_category' => $request->business_industry,
            'address' => $request->business_address,
            'website' => $request->business_website,
            'description' => $request->business_description,
        ]);

        // Update Company Model if exists
        $company = Company::where('user_id', $user->id)->first();
        if ($company) {
            $updateData = [
                'name' => $request->business_name,
                'business_type' => $request->business_type,
                'business_category' => $request->business_industry,
                'address' => $request->business_address,
            ];

            if ($logoPath) {
                $updateData['logo'] = $logoPath;
            }

            $company->update($updateData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Business info updated successfully',
            'logo' => $logoPath // Return the new logo URL to update frontend state if needed
        ]);
    }

    /**
     * Update User Preferences
     */
    public function updatePreferences(Request $request, $token)
    {
        $userId = $this->verifytoken($token);
        $user = User::where('id', $userId)->first();
        if (!$user) {
            return response()->json(['status' => 403, 'message' => 'Not Authorised'])->setStatusCode(403);
        }

        $user->update([
            'email_on_payment' => $request->has('email_on_payment') ? $request->email_on_payment : $user->email_on_payment,
            'email_customer_on_success' => $request->has('email_customer_on_success') ? $request->email_customer_on_success : $user->email_customer_on_success,
            'resend_failed_webhook' => $request->has('resend_failed_webhook') ? $request->resend_failed_webhook : $user->resend_failed_webhook,
            'resend_failed_webhook_count' => $request->has('resend_failed_webhook_count') ? $request->resend_failed_webhook_count : $user->resend_failed_webhook_count,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Preferences updated successfully'
        ]);
    }
}
