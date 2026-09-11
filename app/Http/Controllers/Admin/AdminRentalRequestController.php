<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RentalRequest;
use App\Models\RentalRequestItem;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Services\RentalRequestService;
use App\Services\RentalRequestPricingService;
use App\Services\ProposalManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminRentalRequestController extends Controller
{
    public function __construct(
        private RentalRequestService $rentalRequestService,
        private RentalRequestPricingService $pricingService,
        private ProposalManagementService $proposalService,
    ) {}

    public function index(Request $request)
    {
        $query = RentalRequest::with([
            'items.category',
            'location',
            'user.company',
            'responses.lessor.company',
        ])->withCount(['responses', 'items']);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                  ->orWhereHas('company', fn($cq) => $cq->where('legal_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        switch ($request->get('sort', 'newest')) {
            case 'oldest': $query->orderBy('created_at', 'asc'); break;
            case 'budget': $query->orderBy('total_budget', 'desc'); break;
            default:       $query->orderBy('created_at', 'desc');
        }

        $requests = $query->paginate(25)->withQueryString();
        $statuses = [RentalRequest::STATUS_DRAFT, RentalRequest::STATUS_ACTIVE, RentalRequest::STATUS_PAUSED,
                     RentalRequest::STATUS_PROCESSING, RentalRequest::STATUS_COMPLETED, RentalRequest::STATUS_CANCELLED];
        return view('admin.rental-requests.index', compact('requests', 'statuses'));
    }

    public function show($id)
    {
        $rentalRequest = RentalRequest::with([
            'items.category', 'location', 'user.company',
            'responses' => fn($q) => $q->with(['lessor.company', 'equipment.images']),
        ])->withCount(['responses', 'items'])->findOrFail($id);

        $customerBudget = $rentalRequest->total_budget ?? $rentalRequest->calculateBudgetPreview();
        $lessorPricing = $this->pricingService->calculateLessorPrices($rentalRequest);
        $lessorBudget = $lessorPricing['total_lessor_budget'] ?? 0;
        $platformMargin = $customerBudget - $lessorBudget;

        $proposals = $rentalRequest->responses->filter(fn($r) => $r->status !== 'comment');
        $pendingProposals = $proposals->filter(fn($r) => $r->status === 'pending');
        $acceptedProposals = $proposals->filter(fn($r) => $r->status === 'accepted');
        $rejectedProposals = $proposals->filter(fn($r) => $r->status === 'rejected');

        $requestCategoryIds = $rentalRequest->items->pluck('category_id')->toArray();
        $appliedMarkups = \App\Models\PlatformMarkup::where('entity_type', 'rental_request')->active()
            ->where(function($q) use ($rentalRequest, $requestCategoryIds) {
                $q->whereNull('markupable_type')
                  ->orWhere(fn($q2) => $q2->where('markupable_type', \App\Models\RentalRequest::class)->where('markupable_id', $rentalRequest->id))
                  ->orWhere(fn($q2) => $q2->where('markupable_type', \App\Models\Equipment::class)->whereIn('markupable_id', $requestCategoryIds))
                  ->orWhere(fn($q2) => $q2->where('markupable_type', \App\Models\Category::class)->whereIn('markupable_id', $requestCategoryIds))
                  ->orWhere(fn($q2) => $q2->where('markupable_type', \App\Models\Company::class)->where('markupable_id', $rentalRequest->company_id));
            })->orderBy('priority', 'desc')->get();

        $statuses = [RentalRequest::STATUS_DRAFT, RentalRequest::STATUS_ACTIVE, RentalRequest::STATUS_PAUSED,
                     RentalRequest::STATUS_PROCESSING, RentalRequest::STATUS_COMPLETED, RentalRequest::STATUS_CANCELLED];

        return view('admin.rental-requests.show', compact(
            'rentalRequest', 'customerBudget', 'lessorBudget', 'platformMargin',
            'lessorPricing', 'proposals', 'pendingProposals', 'acceptedProposals',
            'rejectedProposals', 'appliedMarkups', 'statuses',
        ));
    }

    public function edit($id)
    {
        $rentalRequest = RentalRequest::with(['items.category', 'location'])->findOrFail($id);
        return view('admin.rental-requests.edit', [
            'rentalRequest' => $rentalRequest,
            'categories'    => Category::with('children')->whereNull('parent_id')->get(),
            'locations'     => Location::all(),
            'companies'     => Company::where(fn($q) => $q->where('is_lessee', true)->orWhere('is_lessor', false))->orderBy('legal_name')->get(),
            'statuses'      => [RentalRequest::STATUS_DRAFT, RentalRequest::STATUS_ACTIVE, RentalRequest::STATUS_PAUSED,
                                RentalRequest::STATUS_PROCESSING, RentalRequest::STATUS_COMPLETED, RentalRequest::STATUS_CANCELLED],
        ]);
    }

    public function update(Request $request, $id)
    {
        $rentalRequest = RentalRequest::with('items')->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:draft,active,paused,processing,completed,cancelled',
            'visibility' => 'sometimes|in:public,private',
            'delivery_required' => 'sometimes|boolean',
            'company_id' => 'sometimes|exists:companies,id',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'rental_period_start' => 'sometimes|date',
            'rental_period_end'   => 'sometimes|date|after_or_equal:rental_period_start',
            'location_id' => 'sometimes|exists:locations,id',
            'items' => 'sometimes|array',
            'items.*.category_id' => 'required_with:items|exists:equipment_categories,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.hourly_rate' => 'sometimes|numeric|min:0',
            'items.*.use_individual_conditions' => 'sometimes|boolean',
            'items.*.individual_conditions' => 'nullable|array',
            'items.*.specifications' => 'sometimes|array',
        ]);

        DB::beginTransaction();
        try {
            // Обновляем основные поля заявки
            $rentalRequest->update($validated);

            // Если переданы позиции — синхронизируем
            if ($request->has('items')) {
                $existingIds = $rentalRequest->items->pluck('id')->toArray();
                $submittedIds = [];

                foreach ($request->items as $i => $itemData) {
                    $specs = $itemData['specifications'] ?? [];
                    $useIndividual = !empty($itemData['use_individual_conditions']);

                    $data = [
                        'rental_request_id' => $rentalRequest->id,
                        'category_id' => $itemData['category_id'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'hourly_rate' => $itemData['hourly_rate'] ?? $rentalRequest->hourly_rate,
                        'use_individual_conditions' => $useIndividual,
                        'individual_conditions' => $itemData['individual_conditions'] ?? null,
                        'specifications' => $specs,
                        'standard_specifications' => $specs,
                    ];

                    // Рассчитываем цену позиции напрямую (без загрузки отношения)
                    $hourlyRate = $itemData['hourly_rate'] ?? $rentalRequest->hourly_rate;
                    $conditions = !empty($itemData['use_individual_conditions']) && !empty($itemData['individual_conditions'])
                        ? $itemData['individual_conditions']
                        : ($rentalRequest->rental_conditions ?? []);
                    $shiftHours = (int) ($conditions['hours_per_shift'] ?? 8);
                    $shiftsPerDay = (int) ($conditions['shifts_per_day'] ?? 1);
                    $start = \Carbon\Carbon::parse($rentalRequest->rental_period_start);
                    $end = \Carbon\Carbon::parse($rentalRequest->rental_period_end);
                    $totalDays = $start->diffInDays($end) + 1;
                    $calculatedPrice = $hourlyRate * $shiftHours * $shiftsPerDay * $totalDays * ($itemData['quantity'] ?? 1);
                    $data['calculated_price'] = $calculatedPrice;

                    // Если есть ID — обновляем, иначе создаём
                    if (!empty($itemData['id'])) {
                        $item = RentalRequestItem::find($itemData['id']);
                        if ($item && $item->rental_request_id == $rentalRequest->id) {
                            $item->update($data);
                            $submittedIds[] = $item->id;
                        }
                    } else {
                        $newItem = RentalRequestItem::create($data);
                        $submittedIds[] = $newItem->id;
                    }
                }

                // Удаляем позиции, которых нет в запросе
                $toDelete = array_diff($existingIds, $submittedIds);
                if (!empty($toDelete)) {
                    RentalRequestItem::whereIn('id', $toDelete)->delete();
                }
            }

            // Пересчитываем бюджет
            $rentalRequest->calculateBudget();
            $this->invalidateMarkupCache();

            DB::commit();
            return redirect()->route('admin.rental-requests.show', $rentalRequest->id)
                ->with('success', 'Заявка обновлена');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating rental request: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $rentalRequest = RentalRequest::findOrFail($id);
        $rentalRequest->items()->delete();
        $rentalRequest->responses()->delete();
        $rentalRequest->delete();
        return redirect()->route('admin.rental-requests.index')->with('success', 'Заявка удалена');
    }

    private function invalidateMarkupCache()
    {
        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Exception $e) {}
    }
}
