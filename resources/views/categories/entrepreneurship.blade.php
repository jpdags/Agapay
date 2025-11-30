@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-center mb-4">Entrepreneurship</h2>
        <div class="row">
            @foreach($businesses as $business)
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $business->name }}</h5>
                            <p class="card-text">{{ $business->description }}</p>
                            <p class="card-text">Category: {{ $business->category }}</p>
                            <p class="card-text">Contact: {{ $business->contact }}</p>
                            @if($business->user)
                                <p class="card-text"><small class="text-muted">Owner: {{ $business->user->name }}</small></p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
