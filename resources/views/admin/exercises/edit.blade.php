@extends('layouts.admin')
@section('title', 'Edit Exercise')
@section('page-title', 'Edit Exercise')

@section('content')
<div class="card">
  <form method="POST" action="{{ route('admin.exercises.update', $exercise->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.exercises._form')

    <div class="form-actions">
      <a href="{{ route('admin.exercises.index') }}" class="btn btn-secondary">Cancel</a>
      <a href="{{ route('exercises.show', $exercise) }}" class="btn btn-secondary" target="_blank"><i class="fas fa-eye"></i> Preview</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Exercise</button>
    </div>
  </form>
</div>
@endsection
