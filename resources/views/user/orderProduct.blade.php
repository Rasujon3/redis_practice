@extends('user.layout.master')
@section('content')
    <section class="py-5 mb-5">
        <div class="px-4 px-lg-5">
            @foreach($products as $product)
                @php
                    $isOrdered = in_array($product->id, $orderedProductIds);
                @endphp

                <div class="p-4 bg-secondary-subtle rounded-2 mb-3">
                    <h6 class="fw-semibold">{{ $product->name ?? '' }}</h6>
                    <hr />
                    <div>
                        <img src="{{ $product->file ? asset($product->file) : '' }}" style="max-height: 100px; max-width: 100px;" alt="{{ $product->name ?? '' }}" />
                        <p><small>Price: ৳ {{ $product->price ?? '' }}</small></p>
                        <p><small>Commission: ৳ {{ $product->commission ?? '' }}</small></p>
                        <p><small>Time: {{ $product->created_at ? $product->created_at->toDayDateTimeString() : '' }}</small></p>
                    </div>

                    <form method="POST" action="{{ route('order.product') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" class="btn w-100 btn-dark" {{ $isOrdered ? 'disabled' : '' }}>
                            {{ $isOrdered ? 'Already Ordered' : 'Place Order' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
@endsection
