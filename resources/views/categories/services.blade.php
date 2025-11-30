@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-center mb-4">Services</h2>
        <div class="row">
            @foreach($services as $service)
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $service->name }}</h5>
                            <p class="card-text">{{ $service->description }}</p>
                            <p class="card-text">Rate: ₱{{ number_format($service->rate, 2) }}</p>
                            @if($service->user)
                                <p class="card-text"><small class="text-muted">Provider: {{ $service->user->name }}</small></p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
