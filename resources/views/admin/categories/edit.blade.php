@extends('layouts.admin')
@section('page-title', 'Edit Category')
@section('content')
    @include('admin.categories.form', ['category' => $category, 'parents' => $parents])
@endsection
