@extends('layouts.admin')
@section('title', 'Add Exercise')
@section('page-title', 'Add New Exercise')

@section('content')
<div class="card">
  <form method="POST" action="{{ route('admin.exercises.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.exercises._form')

    <div class="form-actions">
      <a href="{{ route('admin.exercises.index') }}" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Exercise</button>
    </div>
  </form>
</div>
@endsection
