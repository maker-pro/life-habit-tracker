@extends('layouts.admin')

@section('title', '编辑事项')
@section('page_title', '编辑事项')
@section('page_desc', '调整事项名称、状态和建议时间')

@section('content')
@include('admin.habits.form', ['habit' => $habit, 'action' => route('admin.habits.update', $habit), 'method' => 'PUT'])
@endsection
