{{-- Page links for a paginated list: {{ $items->links('partials.pager') }} --}}
@if($paginator->hasPages())
  <nav class="pager" aria-label="{{ __('Pages') }}">
    @if($paginator->onFirstPage())
      <span class="btn btn-sm btn-light" aria-disabled="true" disabled>{{ __('Previous') }}</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-sm btn-light" rel="prev">{{ __('Previous') }}</a>
    @endif
    <span class="pager-info">{{ __('Page :page of :pages', ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage()]) }}</span>
    @if($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-sm btn-light" rel="next">{{ __('Next') }}</a>
    @else
      <span class="btn btn-sm btn-light" aria-disabled="true" disabled>{{ __('Next') }}</span>
    @endif
  </nav>
@endif
