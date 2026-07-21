<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestService;
use App\Services\RequestMatchingService;

class RentalRequestController extends Controller
{
    private $rentalRequestService;
    private $matchingService;

    public function __construct(
        RentalRequestService $rentalRequestService,
        RequestMatchingService $matchingService
    ) {
        $this->rentalRequestService = $rentalRequestService;
        $this->matchingService = $matchingService;
    }

    /**
     * Единая страница заявок, адаптирующаяся под роль пользователя.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRole = 'guest';

        if ($user && $user->company) {
            if ($user->company->is_lessor) {
                $userRole = 'lessor';
            } elseif ($user->company->is_lessee) {
                $userRole = 'lessee';
            }
        }

        // Админов редиректим на админку
        if ($user && $user->isAdmin()) {
            return redirect()->route('admin.rental-requests.index');
        }

        // Для всех ролей загружаем фильтры
        $categories = Category::where('is_active', true)->get();
        $locations = Location::all();

        return view('rental-requests.index', compact(
            'user',
            'userRole',
            'categories',
            'locations'
        ));
    }
}
