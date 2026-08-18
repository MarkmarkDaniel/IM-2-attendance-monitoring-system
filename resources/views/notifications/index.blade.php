@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-subtitle', $unreadCount . ' unread')

@section('page-actions')
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-brand btn-sm">
                <i class="bi bi-check-all me-1"></i>Mark All as Read
            </button>
        </form>
    @endif
@endsection

@section('content')

{{-- ==================== TABS ==================== --}}
<ul class="nav nav-pills mb-3 no-print">
    <li class="nav-item">
        <a class="nav-link {{ ! $filter ? 'active' : '' }}"
           href="{{ route('notifications.index') }}">All</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'unread' ? 'active' : '' }}"
           href="{{ route('notifications.index', ['filter' => 'unread']) }}">
            Unread
            @if ($unreadCount > 0)
                <span class="badge bg-danger ms-1">{{ $unreadCount }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $filter === 'read' ? 'active' : '' }}"
           href="{{ route('notifications.index', ['filter' => 'read']) }}">Read</a>
    </li>
</ul>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            <div class="list-group-item {{ $notification->is_read ? '' : 'bg-light' }}">
                <div class="d-flex gap-3">

                    <div class="pt-1">
                        <i class="bi {{ $notification->icon }} fs-5 text-{{ $notification->type }}"></i>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-medium">
                                    {{ $notification->title }}
                                    @if (! $notification->is_read)
                                        <span class="badge bg-danger ms-1" style="font-size: .65rem;">NEW</span>
                                    @endif
                                </div>
                                <p class="mb-1 small">{{ $notification->message }}</p>
                                <div class="text-muted" style="font-size: .78rem;">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $notification->created_at->format('M j, Y g:i A') }}
                                    &middot; {{ $notification->created_at->diffForHumans() }}
                                    @if ($notification->is_read && $notification->read_at)
                                        &middot; read {{ $notification->read_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>

                            <div class="no-print d-flex gap-1">
                                @if (! $notification->is_read)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-primary"
                                                title="Mark as read">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('notifications.destroy', $notification) }}"
                                      data-confirm="Delete this notification?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @empty
            <div class="list-group-item text-center text-muted py-5">
                <i class="bi bi-bell-slash d-block fs-1 mb-2"></i>
                No notifications to show.
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="card-footer bg-white no-print">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection
