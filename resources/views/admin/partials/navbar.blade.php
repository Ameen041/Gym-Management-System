<nav class="admin-nav">
  <div class="brand">Ameen Gym • Admin</div>
  <ul class="menu">
    <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard')?'active':'' }}">Dashboard</a></li>
    <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Users</a></li>
    <li><a href="{{ route('admin.requests.index') }}">Requests</a></li>
    <li><a href="{{ route('admin.templates.index') }}">Templates</a></li>
    <li><a href="{{ route('admin.payments.dashboard') }}">Payments</a></li>
<li><a href="{{ route('admin.subscriptions.expired') }}">Expired Subscriptions</a></li>
  </ul>
  <form method="POST" action="{{ url('/logout') }}">
    @csrf
    <button class="btn btn-danger">Logout</button>
  </form>
</nav>