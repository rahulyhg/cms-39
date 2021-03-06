<?php /* @var $content \Gzero\Cms\ViewModels\ContentViewModel */ ?>
@extends('gzero-core::layouts.withRegions')
@section('bodyClass', $content->theme())

@section('metaData')
    @if(isProviderLoaded('Gzero\Social\ServiceProvider') && function_exists('fbOgTags'))
        {!! fbOgTags($content->url(), $content->translation) !!}
    @endif
@stop

@section('title', $content->seoTitle())
@section('seoDescription', $content->seoDescription())
@section('head')
    @parent
    @include('gzero-cms::contents._canonical', ['paginator' => $children])
    @include('gzero-cms::contents._alternateLinks', ['content' => $content])
    @include('gzero-cms::contents._stDataMarkup', ['content' => $content, 'children' => $children])
@stop
@section('breadcrumbs')
    {!! Breadcrumbs::render('category') !!}
@stop
@section('content')
    @include('gzero-cms::contents._notPublishedContentMsg')
    <h1 class="content-title">
        {{ $content->title() }}
    </h1>
    {!! $content->body() !!}
    @if($children)
        @foreach($children as $index => $child)
            @include('gzero-cms::contents._article', ['child' => $child])
        @endforeach
        {!! $children->links('pagination::bootstrap-4') !!}
    @endif
    <div class="w-100 my-4"></div>
@stop
@section('footerScripts')
    @parent
    @if(config('gzero-cms.disqus.enabled') && config('gzero-cms.disqus.domain'))
        <script id="dsq-count-scr" src="//{{config('gzero-cms.disqus.domain')}}.disqus.com/count.js" async></script>
    @endif
@stop
