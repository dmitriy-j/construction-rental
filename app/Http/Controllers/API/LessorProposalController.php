<?php
// app/Http/Controllers/API/LessorProposalController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RentalRequestResponse;
use Illuminate\Http\Request;

class LessorProposalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company.verified', 'company.lessor']);
    }

    public function index(Request $request)
    {
        $proposals = RentalRequestResponse::with(['rentalRequest', 'equipment'])
            ->where('lessor_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $proposals
        ]);
    }
}
