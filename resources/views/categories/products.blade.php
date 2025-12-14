@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-center mb-4">Products</h2>
        <div class="row">
            @forelse($allProducts ?? [] as $product)
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            @if($product->brand)
                                <p class="card-text text-muted">Brand: {{ $product->brand }}</p>
                            @endif
                            <p class="card-text">Price: ₱{{ number_format($product->price, 2) }}</p>
                            @if($product->delivery_fee)
                                <p class="card-text text-muted">Delivery Fee: ₱{{ number_format($product->delivery_fee, 2) }}</p>
                            @endif
                            @if($product->user)
                                <p class="card-text"><small class="text-muted">Provider: {{ $product->user->name }}</small></p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <p>No products available at the moment.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
