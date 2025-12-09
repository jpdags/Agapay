@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-center mb-4">Select a Category</h2>
        <div class="row">
            <div class="col-md-6">
                <a href="{{ route('categories.products') }}" class="btn btn-primary w-100">Products</a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('categories.services') }}" class="btn btn-primary w-100">Services</a>
            </div>
        </div>
    </div>
@endsection
