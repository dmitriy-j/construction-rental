<div class="tech-card card h-100">
    
    @if($equipment->mainImage)
        <img src="{{ asset('storage/' . $equipment->mainImage->path) }}" class="card-img-top" alt="{{ $equipment->title }}">
    @else
        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
        </div>
    @endif

    <div class="card-body">
        <h5 class="card-title">{{ $equipment->title }}</h5>
        <p class="text-muted">{{ $equipment->category->name }}</p>
        
        <ul class="list-unstyled small">
            <li><i class="bi bi-calendar"></i> Год: {{ $equipment->year }}</li>
            <li><i class="bi bi-speedometer2"></i> Моточасы: {{ $equipment->hours_worked }}</li>
        </ul>
    </div>

    <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <span class="h5 mb-0">
    {{ optional($equipment->rentalTerms->first())->price ?? 'Цена не указана' }} ₽/час
            </span>
            <a href="{{ route('catalog.show', $equipment->id) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-eye"></i> Подробнее
            </a>
        </div>
    </div>
</div>