@extends('layouts.admin')
@section('title','Payments History')

@section('content')
  <h2 style="margin-bottom:15px;">
    Payments for {{ $user->name }}
  </h2>


  <div class="card" style="margin-bottom:20px;">
    <p><strong>Email:</strong> {{ $user->email }}</p>
    <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
    <p><strong>Status:</strong>
      @if($user->is_active)
        <span style="color:green;">Active (Paid)</span>
      @else
        <span style="color:red;">Suspended / Not paid</span>
      @endif
    </p>
    <a href="{{ route('admin.users.index') }}" class="btn" style="margin-top:10px;">
      ← Back to Users
    </a>
  </div>

  {{-- Add Payment Form --}}
<div class="card" style="margin-bottom:25px; max-width:600px;">
  <h3 style="margin-bottom:10px;">Add New Payment</h3>

  {{-- show validation errors --}}
  @if ($errors->any())
    <div class="alert alert-error" style="margin-bottom:15px;">
      <div>
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.users.payments.store', $user->id) }}">
    @csrf

    <div class="form-group">
      <label class="form-label">Amount</label>
      <input type="number" step="0.01" name="amount" class="form-control"
             required value="{{ old('amount') }}">
    </div>

    <div class="form-group">
      <label class="form-label">Payment Method</label>
      <input type="text" name="method" class="form-control"
             value="{{ old('method','Cash') }}">
    </div>

    <div class="form-group">
      <label class="form-label">Paid At</label>
      <input type="date" name="paid_at" class="form-control date-fix"
             value="{{ old('paid_at', now()->toDateString()) }}" required>
    </div>

    <div class="form-group">
      <label class="form-label">Subscription Period</label>
      <div style="display:flex; gap:10px;">
        <input type="date" name="period_start" class="form-control date-fix"
               value="{{ old('period_start', now()->toDateString()) }}" required>
        <input type="date" name="period_end" class="form-control date-fix"
               value="{{ old('period_end', now()->addMonth()->toDateString()) }}" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        Add Payment
      </button>
    </div>
  </form>
</div>>

  <h3>Payment History</h3>
  @if($payments->isEmpty())
    <p>No payments recorded for this user.</p>
  @else
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Paid At</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Period</th>
          <th>Reference</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
        @foreach($payments as $p)
          <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->paid_at }}</td>
            <td>{{ $p->amount }}</td>
            <td>{{ $p->method }}</td>
            <td>
              @if($p->period_start && $p->period_end)

{{ $p->period_start }} → {{ $p->period_end }}
              @else
                -
              @endif
            </td>
            <td>{{ $p->reference }}</td>
            <td>{{ $p->notes }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
@endsection