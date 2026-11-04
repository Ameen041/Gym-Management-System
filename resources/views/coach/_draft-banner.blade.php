{{-- In a plan builder: the changes the coach left without sending came back ($restored = PlanDraft), or the plan starts from the last one ($draftFrom) --}}
@if($restored)
  <div class="alert" role="status">
    <i class="fas fa-clock-rotate-left"></i>
    <div>
      {{ __('Your changes that were not sent yet are back (:time). Send the plan when it is ready.', ['time' => $restored->updated_at->diffForHumans()]) }}
      <button type="submit" form="discard-draft" class="btn btn-sm btn-light" style="margin-top:6px"><i class="fas fa-rotate-left"></i> {{ $plan ? __('Back to the sent plan') : __('Start over') }}</button>
    </div>
  </div>
@elseif($draftFrom)
  <div class="alert" role="status">
    <i class="fas fa-copy"></i>
    <div>{{ __('Started from the last plan (:date). Change what you need, then send.', ['date' => $draftFrom->updated_at->translatedFormat('j M')]) }}</div>
  </div>
@endif
