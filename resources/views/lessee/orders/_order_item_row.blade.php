<tr>
    <td>
        <div class="d-flex align-items-center">
            @if($item->equipment->mainImage && $item->equipment->mainImage->path)
            <img src="{{ Storage::url($item->equipment->mainImage->path) }}"
                 alt="{{ $item->equipment->title }}"
                 class="rounded me-3" width="60" height="60">
            @else
            <div class="bg-light border rounded d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                <i class="fas fa-image text-muted"></i>
            </div>
            @endif
            <div>
                <a href="{{ route('catalog.show', $item->equipment) }}" class="fw-bold text-decoration-none">
                    {{ $item->equipment->title }}
                </a>
                <div class="text-muted small">
                    {{ $item->equipment->brand }} {{ $item->equipment->model }}
                </div>
            </div>
        </div>
    </td>
    <td>
        {{ $item->equipment->company->legal_name ?? 'Не указан' }}
    </td>
    <td class="text-center">
        <div class="d-flex flex-column">
            <span>{{ $start_date->format('d.m.Y') }}</span>
            <span class="text-muted small">по</span>
            <span>{{ $end_date->format('d.m.Y') }}</span>
        </div>
    </td>
    <td class="text-center">
        <span class="badge bg-primary rounded-pill px-3 py-2">
            {{ $item->period_count }} ч
        </span>
    </td>
    <td class="text-end fw-bold">
        {{ number_format($item->total_price + ($item->delivery_cost ?? 0), 2) }} ₽
    </td>
    <td class="text-center">
        <span class="badge bg-{{ $item->order->status_color }} py-2">
            {{ $item->order->status_text }}
        </span>
    </td>
</tr>
