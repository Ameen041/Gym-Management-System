<nav class="admin-nav">
  <div class="brand">{{ __('Ameen Gym • Admin') }}</div>
  <ul class="menu">
    <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard')?'active':'' }}">{{ __('Dashboard') }}</a></li>
    <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">{{ __('Users') }}</a></li>
    <li><a href="{{ route('admin.requests.index') }}">{{ __('Requests') }}</a></li>
    <li><a href="{{ route('admin.templates.index') }}">{{ __('Templates') }}</a></li>
    <li><a href="{{ route('admin.payments.dashboard') }}">{{ __('Payments') }}</a></li>
<li><a href="{{ route('admin.subscriptions.expired') }}">{{ __('Expired Subscriptions') }}</a></li>
  </ul>
  <form method="POST" action="{{ url('/logout') }}">
    @csrf
    <button class="btn btn-danger">{{ __('Logout') }}</button>
  </form>
</nav>