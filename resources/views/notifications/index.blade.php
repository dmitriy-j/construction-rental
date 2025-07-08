@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Уведомления</h1>
        <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-check-circle"></i> Прочитать все
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            @forelse($notifications as $notification)
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="{{ $notification->data['url'] }}" 
                           class="{{ $notification->unread() ? 'fw-bold' : '' }}">
                            {{ $notification->data['message'] }}
                        </a>
                        <div class="text-muted small">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                    @if($notification->unread())
                    <form action="{{ route('notifications.markAsRead', $notification) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-link text-success" title="Отметить как прочитанное">
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <p class="text-muted">Нет уведомлений</p>
            </div>
            @endforelse
        </div>
        @if($notifications->hasPages())
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection