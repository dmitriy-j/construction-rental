<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RentalRequestResource;
use App\Models\RentalRequest;
use App\Models\Category;
use App\Models\Location;
use App\Services\RentalRequestService;
use App\Services\RentalRequestPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminRentalRequestController extends Controller
{
    public function __construct(
        private RentalRequestService $rentalRequestService,
        private RentalRequestPricingService $pricingService
    ) {
    }

    public function index(Request $request)
    {
        $query = RentalRequest::with([
            'items.category',
            'location',
            'user.company',
            'responses.lessor.company',
        ])
        ->withCount(['responses', 'items']);

        // Поиск
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('company', function ($cq) use ($search) {
                      $cq->where('legal_name', 'like', "%{$search}%");
                  });
            });
        }

        // Фильтр по статусу
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Фильтр по видимости
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        // Сортировка
        switch ($request->get('sort', 'newest')) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'budget':
                $query->orderBy('total_budget', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $requests = $query->paginate(25)->withQueryString();

        $statuses = [
            RentalRequest::STATUS_DRAFT,
            RentalRequest::STATUS_ACTIVE,
            RentalRequest::STATUS_PAUSED,
            RentalRequest::STATUS_PROCESSING,
            RentalRequest::STATUS_COMPLETED,
            RentalRequest::STATUS_CANCELLED,
        ];

        return view('admin.rental-requests.index', compact('requests', 'statuses'));
    }

    public function show($id)
    {
        $rentalRequest = RentalRequest::with([
            'items.category',
            'location',
            'user.company',
            'responses' => function ($q) {
                $q->with(['lessor.company', 'equipment.images']);
            },
        ])
        ->withCount(['responses', 'items'])
        ->findOrFail($id);

        $proposalsCount = $rentalRequest->responses
            ->filter(fn($r) => $r->status !== 'comment')
            ->count();

        $commentsCount = $rentalRequest->responses
            ->filter(fn($r) => $r->status === 'comment')
            ->count();

        $statuses = [
            RentalRequest::STATUS_DRAFT,
            RentalRequest::STATUS_ACTIVE,
            RentalRequest::STATUS_PAUSED,
            RentalRequest::STATUS_PROCESSING,
            RentalRequest::STATUS_COMPLETED,
            RentalRequest::STATUS_CANCELLED,
        ];

        return view('admin.rental-requests.show', compact(
            'rentalRequest',
            'proposalsCount',
            'commentsCount',
            'statuses'
        ));
    }

    public function create()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();

        $locations = Location::all();

        return view('admin.rental-requests.create', compact('categories', 'locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'hourly_rate' => 'required|numeric|min:0',
            'rental_period_start' => 'required|date',
            'rental_period_end' => 'required|date|after_or_equal:rental_period_start',
            'location_id' => 'required|exists:locations,id',
            'company_id' => 'required|exists:companies,id',
            'items' => 'required|array|min:1',
            'items.*.category_id' => 'required|exists:equipment_categories,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.hourly_rate' => 'sometimes|numeric|min:0',
            'items.*.specifications' => 'sometimes|array',
            'items.*.custom_specs_metadata' => 'sometimes|array',
            'visibility' => 'sometimes|in:public,private',
        ]);

        $user = \App\Models\User::where('company_id', $validated['company_id'])
            ->whereHas('company', fn($q) => $q->where('is_lessee', true))
            ->first();

        if (!$user) {
            return back()->withErrors(['company_id' => 'У выбранной компании нет пользователя с ролью арендатора']);
        }

        $rentalRequest = $this->rentalRequestService->createRentalRequestFromApi(
            $validated,
            $user
        );

        if (isset($validated['visibility'])) {
            $rentalRequest->update(['visibility' => $validated['visibility']]);
        }

        return redirect()
            ->route('admin.rental-requests.show', $rentalRequest->id)
            ->with('success', 'Заявка создана от имени компании');
    }

    public function edit($id)
    {
        $rentalRequest = RentalRequest::with(['items.category', 'location'])
            ->findOrFail($id);

        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->get();

        $locations = Location::all();

        $statuses = [
            RentalRequest::STATUS_DRAFT,
            RentalRequest::STATUS_ACTIVE,
            RentalRequest::STATUS_PAUSED,
            RentalRequest::STATUS_PROCESSING,
            RentalRequest::STATUS_COMPLETED,
            RentalRequest::STATUS_CANCELLED,
        ];

        return view('admin.rental-requests.edit', compact(
            'rentalRequest',
            'categories',
            'locations',
            'statuses'
        ));
    }

    public function update(Request $request, $id)
    {
        $rentalRequest = RentalRequest::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:draft,active,paused,processing,completed,cancelled',
            'visibility' => 'sometimes|in:public,private',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'rental_period_start' => 'sometimes|date',
            'rental_period_end' => 'sometimes|date|after_or_equal:rental_period_start',
            'location_id' => 'sometimes|exists:locations,id',
        ]);

        $rentalRequest->update($validated);

        return redirect()
            ->route('admin.rental-requests.show', $rentalRequest->id)
            ->with('success', 'Заявка обновлена');
    }

    public function destroy($id)
    {
        $rentalRequest = RentalRequest::findOrFail($id);

        $rentalRequest->items()->delete();
        $rentalRequest->responses()->delete();
        $rentalRequest->delete();

        return redirect()
            ->route('admin.rental-requests.index')
            ->with('success', 'Заявка и все связанные данные удалены');
    }
}
