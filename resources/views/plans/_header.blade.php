{{-- Shared header of the trainee plan pages: $plan --}}
<div class="plan-head">
  <img src="{{ $plan->trainer?->photo_url }}" alt="" class="avatar-sm">
  <div class="grow">
    <h1>{{ $plan->title }}</h1>
    <span class="muted small">{{ $plan->trainer?->name }} · {{ __('Updated :date', ['date' => $plan->updated_at->translatedFormat('j M')]) }}</span>
  </div>
  <div class="plan-actions">
    @if($plan->trainer?->whatsapp_url && $plan->subscription?->isRunning())
      <a href="{{ $plan->trainer->whatsapp_url }}" target="_blank" rel="noopener" class="icon-btn icon-wa" title="{{ __('Ask your coach') }}" aria-label="{{ __('Ask your coach') }}"><i class="fab fa-whatsapp"></i></a>
    @endif
    <button type="button" class="icon-btn" data-print title="{{ __('Print Plan') }}" aria-label="{{ __('Print Plan') }}"><i class="fas fa-print"></i></button>
  </div>
</div>
