@php($switchTo = app()->isLocale('ar') ? 'en' : 'ar')
<a href="{{ route('lang.switch', $switchTo) }}" class="{{ $class ?? 'btn btn-ghost' }} lang-switch" lang="{{ $switchTo }}" title="{{ $switchTo === 'ar' ? 'التبديل إلى العربية' : 'Switch to English' }}">
  <i class="fas fa-language"></i>
  <span>{{ $switchTo === 'ar' ? 'العربية' : 'English' }}</span>
</a>
