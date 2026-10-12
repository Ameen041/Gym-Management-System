@extends('layouts.admin')
@section('title', __('Add Exercise'))
@section('page-title', __('Add New Exercise'))

@section('content')
<div class="card">
  <form method="POST" action="{{ route('admin.exercises.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.exercises._form')

    <div class="form-actions">
      <a href="{{ route('admin.exercises.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('Save Exercise') }}</button>
    </div>
  </form>
</div>
@endsection
