<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarkup;
use App\Models\Equipment;
use App\Models\Category;
use App\Models\Company;
use App\Models\Platform;
use App\Models\RentalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class MarkupController extends Controller
{
    /**
     * Доступные типы для "Применить к".
     * '' — общая наценка (null в БД)
     */
    const MARKUPABLE_TYPES = [
        ''                  => 'Общая наценка (для всех контекстов)',
        Equipment::class   => 'Оборудование',
        Category::class    => 'Категория',
        Company::class     => 'Компания',
        RentalRequest::class => 'Заявка на аренду',
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = PlatformMarkup::with([
            'markupable',
            'platform:id,name',
            'audits' => fn($q) => $q->latest()->take(5)->with('user:id,name')
        ]);

        foreach (['markupable_type', 'entity_type', 'type'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->$f);
            }
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $markups = $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc')->paginate(20);

        $markupableTypes = collect(self::MARKUPABLE_TYPES)->filter(fn($v, $k) => $k !== '')->prepend('Общая наценка (для всех контекстов)', '');

        return view('admin.markups.index', [
            'markups'         => $markups,
            'markupableTypes' => self::MARKUPABLE_TYPES,
            'entityTypes'     => ['order' => 'Заказы', 'rental_request' => 'Заявки', 'proposal' => 'Предложения'],
            'markupTypes'     => ['fixed' => 'Фиксированная', 'percent' => 'Процентная', 'tiered' => 'Ступенчатая', 'combined' => 'Комбинированная', 'seasonal' => 'Сезонная'],
            'stats'           => [
                'total'   => PlatformMarkup::count(),
                'active'  => PlatformMarkup::where('is_active', true)->count(),
                'expired' => PlatformMarkup::where('is_active', true)->whereNotNull('valid_to')->where('valid_to', '<', now())->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.markups.form', [
            'platforms'      => Platform::all(),
            'equipment'      => Equipment::where('is_approved', true)->get(),
            'categories'     => Category::all(),
            'companies'      => Company::where(fn($q) => $q->where('is_lessee', true)->orWhere('is_lessor', true))->get(),
            'rentalRequests' => RentalRequest::orderBy('created_at', 'desc')->limit(50)->get(),
            'markupableTypes' => self::MARKUPABLE_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        Log::debug('MarkupController@store raw', $request->except('_token'));

        $validated = $request->validate([
            'platform_id'      => 'required|exists:platforms,id',
            'entity_type'      => 'required|in:order,rental_request,proposal',
            'type'             => 'required|in:fixed,percent,tiered,combined,seasonal',
            'calculation_type' => 'required|in:addition,multiplication,complex',
            'value'            => 'required|numeric|min:0',
            'markupable_type'  => 'nullable|string',
            'markupable_id'    => 'nullable|numeric',
            'rules'            => 'nullable|array',
            'is_active'        => 'sometimes|boolean',
            'valid_from'       => 'nullable|date',
            'valid_to'         => 'nullable|date|after:valid_from',
            'priority'         => 'integer|min:0',
        ]);

        Log::debug('MarkupController@store validated', $validated);

        // Нормализация: пустая строка → null для БД
        if (empty($validated['markupable_type'])) {
            $validated['markupable_type'] = null;
            $validated['markupable_id'] = null;
        } else {
            // Если тип указан, приводим ID к int
            $validated['markupable_id'] = !empty($validated['markupable_id'])
                ? (int) $validated['markupable_id']
                : null;

            if (!$validated['markupable_id']) {
                return back()->withInput()->with('error', 'При выборе типа «Применить к» необходимо выбрать конкретную запись.');
            }
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        DB::beginTransaction();
        try {
            // Проверка на дубликат
            $exists = PlatformMarkup::where('platform_id', $validated['platform_id'])
                ->where('entity_type', $validated['entity_type'])
                ->where(function ($q) use ($validated) {
                    if ($validated['markupable_type'] && $validated['markupable_id']) {
                        $q->where('markupable_type', $validated['markupable_type'])
                          ->where('markupable_id', $validated['markupable_id']);
                    } else {
                        $q->whereNull('markupable_type')->whereNull('markupable_id');
                    }
                })->first();

            if ($exists) {
                DB::rollBack();
                return back()->withInput()->with('error', 'Такая наценка уже существует.');
            }

            $markup = PlatformMarkup::create($validated);
            $markup->logAudit('created', null, $markup->toArray(), 'Создание наценки');

            Cache::flush();
            try { Artisan::call('cache:clear'); } catch (\Exception $e) {}

            DB::commit();
            return redirect()->route('markups.index')->with('success', 'Наценка создана');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating markup: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function edit(PlatformMarkup $markup)
    {
        return view('admin.markups.form', [
            'markup'         => $markup,
            'platforms'      => Platform::all(),
            'equipment'      => Equipment::where('is_approved', true)->get(),
            'categories'     => Category::all(),
            'companies'      => Company::where(fn($q) => $q->where('is_lessee', true)->orWhere('is_lessor', true))->get(),
            'rentalRequests' => RentalRequest::orderBy('created_at', 'desc')->limit(50)->get(),
            'markupableTypes' => self::MARKUPABLE_TYPES,
        ]);
    }

    public function update(Request $request, PlatformMarkup $markup)
    {
        $validated = $request->validate([
            'platform_id'      => 'required|exists:platforms,id',
            'entity_type'      => 'required|in:order,rental_request,proposal',
            'type'             => 'required|in:fixed,percent,tiered,combined,seasonal',
            'calculation_type' => 'required|in:addition,multiplication,complex',
            'value'            => 'required|numeric|min:0',
            'markupable_type'  => 'nullable|string',
            'markupable_id'    => 'nullable|numeric',
            'rules'            => 'nullable|array',
            'is_active'        => 'sometimes|boolean',
            'valid_from'       => 'nullable|date',
            'valid_to'         => 'nullable|date|after:valid_from',
            'priority'         => 'integer|min:0',
        ]);

        if (empty($validated['markupable_type'])) {
            $validated['markupable_type'] = null;
            $validated['markupable_id'] = null;
        } else {
            $validated['markupable_id'] = !empty($validated['markupable_id'])
                ? (int) $validated['markupable_id']
                : null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        DB::beginTransaction();
        try {
            $old = $markup->toArray();
            $markup->update($validated);
            $markup->logAudit('updated', $old, $markup->toArray(), 'Обновление наценки');

            Cache::flush();
            try { Artisan::call('cache:clear'); } catch (\Exception $e) {}

            DB::commit();
            return redirect()->route('markups.index')->with('success', 'Наценка обновлена');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating markup: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }

    public function destroy(PlatformMarkup $markup)
    {
        DB::beginTransaction();
        try {
            $markup->logAudit('deleted', $markup->toArray(), null, 'Удаление наценки');
            $markup->delete();
            Cache::flush();
            try { Artisan::call('cache:clear'); } catch (\Exception $e) {}
            DB::commit();
            return redirect()->route('markups.index')->with('success', 'Наценка удалена');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting markup: ' . $e->getMessage());
            return back()->with('error', 'Ошибка: ' . $e->getMessage());
        }
    }
}
