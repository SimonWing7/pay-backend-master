<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReferralImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ReferralImportController extends Controller
{
    public function __construct(
        protected ReferralImportService $referralImportService
    ) {
    }

    public function show(): View
    {
        return view('admin.referrals.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'export' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $contents = file_get_contents($request->file('export')->getRealPath());
        $stats = $this->referralImportService->import($contents);

        $message = "Processed {$stats['rows']} rows: {$stats['matched']} matched a merchant "
            . "({$stats['earned']} newly earned commission), {$stats['skipped_not_active']} were Free or had no usable date, "
            . "{$stats['skipped_no_match']} had no matching payment, "
            . "{$stats['skipped_invalid']} were skipped (missing user id / email+mobile).";

        return redirect()->route('admin.referrals.index')->with('success', $message);
    }
}
