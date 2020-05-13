@extends('layouts.app')
@push('css_lib')
<!-- iCheck -->
<link rel="stylesheet" href="{{asset('public/plugins/iCheck/flat/blue.css')}}">
<!-- select2 -->
<link rel="stylesheet" href="{{asset('public/plugins/select2/select2.min.css')}}">
<!-- bootstrap wysihtml5 - text editor -->
<link rel="stylesheet" href="{{asset('public/plugins/summernote/summernote-bs4.css')}}">
{{--dropzone--}}
<link rel="stylesheet" href="{{asset('public/plugins/dropzone/bootstrap.min.css')}}">
@endpush
@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">{{trans('lang.banners_plural')}} <small>{{trans('lang.banners_desc')}}</small></h1>
      </div><!-- /.col -->
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{url('/dashboard')}}"><i class="fa fa-dashboard"></i> {{trans('lang.dashboard')}}</a></li>
          <li class="breadcrumb-item"><a href="{!! route('banners.index') !!}">{{trans('lang.banners_plural')}}</a>
          </li>
          <li class="breadcrumb-item active">{{trans('lang.banners_edit')}}</li>
        </ol>
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->
<div class="content">
  <div class="clearfix"></div>
  @include('flash::message')
  @include('adminlte-templates::common.errors')
  <div class="clearfix"></div>
  <div class="card">
    <div class="card-header">
      <ul class="nav nav-tabs align-items-end card-header-tabs w-100">
        @can('banners.index')
        <li class="nav-item">
          <a class="nav-link" href="{!! route('banners.index') !!}"><i class="fa fa-list mr-2"></i>{{trans('lang.banners_table')}}</a>
        </li>
        @endcan
        @can('banners.create')
        <li class="nav-item">
          <a class="nav-link" href="{!! route('banners.create') !!}"><i class="fa fa-plus mr-2"></i>{{trans('lang.bannres_create')}}</a>
        </li>
        @endcan
        <li class="nav-item">
          <a class="nav-link active" href="{!! url()->current() !!}"><i class="fa fa-pencil mr-2"></i>{{trans('lang.banners_edit')}}</a>
        </li>
      </ul>
    </div>
    <div class="card-body">
      {!! Form::model($banners, ['route' => ['banners.update', $banners->id], 'method' => 'patch']) !!}
      <div class="row">
        @include('banners.fields')
      </div>
      {!! Form::close() !!}
      <div class="clearfix"></div>
    </div>
  </div>
</div>
@include('layouts.media_modal')
@endsection
@push('scripts_lib')
<!-- iCheck -->
<script src="{{asset('public/plugins/iCheck/icheck.min.js')}}"></script>
<!-- select2 -->
<script src="{{asset('public/plugins/select2/select2.min.js')}}"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="{{asset('public/plugins/summernote/summernote-bs4.min.js')}}"></script>
{{--dropzone--}}
<script src="{{asset('public/plugins/dropzone/dropzone.js')}}"></script>
<script type="text/javascript">
    Dropzone.autoDiscover = false;
    var dropzoneFields = [];
</script>
@endpush