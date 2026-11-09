@extends('layouts.storefront')

@section('title', $page->metaValue('meta_title') ?: 'About Us')
@section('meta_description', $page->metaValue('meta_description') ?: 'Learn about '.shop_name().' - handcrafted crochet, knitted gifts, and cozy yarn creations made in Karachi.')

@section('content')
    <x-cms.sections :page="$page" />
@endsection
