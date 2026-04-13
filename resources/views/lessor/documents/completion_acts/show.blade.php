@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card modern-card">
        <x-completion-act-details :completionAct="$completionAct" userType="lessor" />

        <!-- Кнопки действий для арендодателя -->
        <div class="card-footer bg-transparent">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('lessor.documents.completion_acts.index') }}"
                   class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Назад к списку
                </a>

                <div class="btn-group">
                    <a href="{{ route('lessor.documents.completion_acts.download', $completionAct) }}"
                       class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Скачать PDF
                    </a>

                    @if($completionAct->waybill)
                    <a href="{{ route('lessor.waybills.show', $completionAct->waybill) }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-file-alt me-2"></i>К путевому листу
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
