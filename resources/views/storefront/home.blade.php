@extends('layouts.storefront')

@section('title', $page->metaValue('meta_title') ?: 'Home')
@section('meta_description', $page->metaValue('meta_description') ?: shop_config('tagline'))

@section('content')
    <x-cms.sections :page="$page" />
@endsection
