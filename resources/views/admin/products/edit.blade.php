@extends('layouts.admin')

@section('page-title', 'Edit Product')

@section('content')
    @include('admin.products.form', ['product' => $product])
@endsection
