@extends('layouts.admin')
@section('page-title', 'Add Category')
@section('content')
    @include('admin.categories.form', ['category' => new \App\Models\Category(), 'parents' => $parents])
@endsection
