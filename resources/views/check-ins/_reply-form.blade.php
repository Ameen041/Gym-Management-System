<form method="POST" action="{{ route('coach.check-ins.reply', $checkIn) }}" class="ci-reply-form">
  @csrf
  <textarea name="coach_reply" rows="2" maxlength="2000" required dir="auto" placeholder="{{ __('Your reply: what went well, what to change next week') }}" aria-label="{{ __('Your reply') }}">{{ $checkIn->coach_reply }}</textarea>
  <div>
    <button type="submit" class="btn btn-sm {{ $checkIn->replied_at ? '' : 'btn-primary' }}">
      <i class="fas fa-paper-plane"></i> {{ $checkIn->replied_at ? __('Update reply') : __('Send reply') }}
    </button>
  </div>
</form>
