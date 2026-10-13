{{-- Shared header for the trainee plan pages: $plan, $icon, $heading --}}
<div class="plan-header">
    <h1><i class="fas {{ $icon }}"></i> {{ $heading }}</h1>
    <div class="plan-actions">
        @include('partials.lang-switch', ['class' => 'action-btn'])
        <a href="{{ route('trainee.dashboard') }}" class="action-btn back-btn">
            <i class="fas fa-arrow-left"></i> {{ __('Back to Dashboard') }}
        </a>
        @if($plan->trainer?->whatsapp_url && $plan->subscription?->isRunning())
            <a href="{{ $plan->trainer->whatsapp_url }}" target="_blank" rel="noopener" class="action-btn" style="background:#25d366;color:#fff;border-color:transparent">
                <i class="fab fa-whatsapp"></i> {{ __('Ask your coach') }}
            </a>
        @endif
        <button id="print-btn" class="action-btn">
            <i class="fas fa-print"></i> {{ __('Print Plan') }}
        </button>
    </div>
</div>

<div class="plan-content">
    @foreach(['success' => 'check-circle', 'error' => 'exclamation-circle'] as $flash => $flashIcon)
        @if(session($flash))
            <div class="alert alert-{{ $flash === 'error' ? 'danger' : 'success' }}">
                <i class="fas fa-{{ $flashIcon }}"></i> {{ session($flash) }}
            </div>
        @endif
    @endforeach

    <div class="detail-section" style="margin-bottom:14px">
        <h2><i class="fas fa-heading"></i> {{ __('Plan Title') }}</h2>
        <p class="plan-title">{{ $plan->title }}</p>
        <p class="muted" style="margin-top:6px">
            <i class="fas fa-user-tie"></i> {{ __('Coach') }}: <b>{{ $plan->trainer?->name }}</b>
            · {{ __('Updated') }}: {{ $plan->updated_at->translatedFormat('j M Y') }}
        </p>
    </div>
