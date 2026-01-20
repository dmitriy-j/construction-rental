@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card modern-card">
        <x-completion-act-details :completionAct="$completionAct" userType="lessee" />

        <!-- Кнопки действий для арендатора -->
        <div class="card-footer bg-transparent">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('documents.index', ['type' => 'completion_acts']) }}"
                   class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Назад к списку
                </a>

                <div class="btn-group">
                    <a href="{{ route('documents.completion-acts.download', $completionAct) }}"
                       class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Скачать PDF
                    </a>

                    @if(isset($actData['parent_order_id']))
                    <a href="{{ route('lessee.orders.show', $actData['parent_order_id']) }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-2"></i>К заказу
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
