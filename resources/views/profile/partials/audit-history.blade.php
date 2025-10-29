{{-- resources/views/profile/partials/audit-history.blade.php --}}
@if($auditHistory->count())
<div class="list-group list-group-flush">
    @foreach($auditHistory as $audit)
    <div class="list-group-item px-0">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h6 class="mb-1">{{ $audit->changedBy->name }}</h6>
                <small class="text-muted">
                    {{ $audit->created_at->translatedFormat('d F Y в H:i') }}
                </small>
            </div>
            <span class="badge bg-primary rounded-pill">Изменение</span>
        </div>

        @if($audit->old_values)
            <div class="mt-2">
                @foreach($audit->new_values as $key => $newValue)
                    @php
                        $oldValue = $audit->old_values[$key] ?? '';
                        $fieldNames = [
                            'bank_name' => 'Наименование банка',
                            'bank_account' => 'Расчетный счет',
                            'bik' => 'БИК',
                            'correspondent_account' => 'Корреспондентский счет'
                        ];
                        $fieldName = $fieldNames[$key] ?? $key;
                    @endphp
                    @if($oldValue != $newValue)
                        <div class="mb-1">
                            <small class="text-muted">{{ $fieldName }}:</small>
                            <div class="d-flex align-items-center">
                                <del class="text-danger me-2">{{ $oldValue }}</del>
                                <i class="bi bi-arrow-right text-muted me-2"></i>
                                <strong class="text-success">{{ $newValue }}</strong>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <small class="text-muted">Добавлены первоначальные реквизиты</small>
        @endif

        <div class="mt-2">
            <small class="text-muted">IP: {{ $audit->ip_address }}</small>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="text-center text-muted py-3">
    <i class="bi bi-clock-history display-6 mb-3"></i>
    <p>История изменений отсутствует</p>
</div>
@endif
