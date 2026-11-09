@extends('layouts.storefront')

@section('title', $page->metaValue('meta_title') ?: 'Shipping, Returns & Care')
@section('meta_description', $page->metaValue('meta_description') ?: 'Delivery times, returns and exchanges, made-to-order lead times and care instructions for handmade crochet pieces.')

@section('content')
    <x-cms.sections :page="$page" />
@endsection
