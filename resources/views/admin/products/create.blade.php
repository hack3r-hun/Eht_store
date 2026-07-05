@extends('layouts.admin')

@section('page-title', 'Add Product')

@section('content')
    @include('admin.products.form', ['product' => new \App\Models\Product()])
@endsection
