@extends('layouts.admin')

@section('title', '新增事项')
@section('page_title', '新增事项')
@section('page_desc', '设置颜色、图标和建议打卡时间')

@section('content')
@include('admin.habits.form', ['habit' => null, 'action' => route('admin.habits.store'), 'method' => 'POST'])
@endsection
