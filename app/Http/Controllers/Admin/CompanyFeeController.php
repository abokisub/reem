<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyFeeSetting;
use App\Services\FeeService;
use Illuminate\Http\Request;

class CompanyFeeController extends Controller
{
    public function __construct(private FeeService $feeService) {}

    /**
     * GET /api/admin/company-fees/{companyId}
     * Get all fee settings for a company (custom + defaults)
     */
    public function show($companyId)
    {
        $company = Company::findOrFail($companyId);
        $overview = $this->feeService->getCompanyFeeOverview($companyId);

        return response()->json([
            'status'  => 'success',
            'company' => ['id' => $company->id, 'name' => $company->name, 'email' => $company->email],
            'fees'    => $overview,
            'types'   => CompanyFeeSetting::TYPES,
        ]);
    }

    /**
     * POST /api/admin/company-fees/{companyId}
     * Set or update a custom fee for a company
     */
    public function update(Request $request, $companyId)
    {
        Company::findOrFail($companyId);

        $request->validate([
            'transaction_type' => 'required|string|in:' . implode(',', array_keys(CompanyFeeSetting::TYPES)),
            'fee_model'        => 'required|string|in:flat,percentage,hybrid',
            'percentage_fee'   => 'nullable|numeric|min:0|max:100',
            'flat_fee'         => 'nullable|numeric|min:0',
            'cap_amount'       => 'nullable|numeric|min:0',
            'minimum_fee'      => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:200',
        ]);

        try {
            $setting = $this->feeService->setCompanyFee($companyId, $request->transaction_type, $request->all());

            return response()->json([
                'status'  => 'success',
                'message' => 'Custom fee saved',
                'data'    => $setting,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/admin/company-fees/{companyId}/{transactionType}
     * Remove custom fee — reverts to system default
     */
    public function destroy($companyId, $transactionType)
    {
        Company::findOrFail($companyId);
        $this->feeService->removeCompanyFee($companyId, $transactionType);

        return response()->json(['status' => 'success', 'message' => 'Custom fee removed, reverted to default']);
    }

    /**
     * POST /api/admin/company-fees/simulate
     * Test fee calculation for any company/amount/type
     */
    public function simulate(Request $request)
    {
        $request->validate([
            'company_id'       => 'required|integer',
            'amount'           => 'required|numeric|min:1',
            'transaction_type' => 'required|string',
        ]);

        $result = $this->feeService->calculateFee(
            $request->company_id,
            $request->amount,
            $request->transaction_type
        );

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * GET /api/admin/companies/search?q=name_or_email
     * Search companies for the fee management UI
     */
    public function searchCompanies(Request $request)
    {
        $q = $request->get('q', '');

        $companies = Company::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->select('id', 'name', 'email', 'status')
            ->limit(20)
            ->get();

        return response()->json(['status' => 'success', 'data' => $companies]);
    }
}
