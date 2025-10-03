<div class="pt-24 pb-12 min-h-screen bg-black text-white">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="text-3xl font-bold mb-8">Checkout</h1>

        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-600 text-white rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="placeOrder" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Shipping Form -->
            <div class="space-y-4 bg-white bg-opacity-10 backdrop-blur-md p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Shipping Details</h2>

                <div>
                    <label class="block mb-1 font-semibold">Recipient Name*</label>
                    <input type="text" wire:model="recipient_name" required 
                           class="w-full p-2 bg-gray-800 text-white rounded border @error('recipient_name') border-red-500 @else border-gray-600 @enderror" 
                           placeholder="John Doe">
                    @error('recipient_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 font-semibold">Phone Number*</label>
                    <input type="text" wire:model="phone" required 
                           class="w-full p-2 bg-gray-800 text-white rounded border @error('phone') border-red-500 @else border-gray-600 @enderror" 
                           placeholder="+94XXXXXXXXX">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 font-semibold">Street Address*</label>
                    <input type="text" wire:model="address" required 
                           class="w-full p-2 bg-gray-800 text-white rounded border @error('address') border-red-500 @else border-gray-600 @enderror" 
                           placeholder="123 Main Street">
                    @error('address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 font-semibold">City*</label>
                    <input type="text" wire:model="city" required 
                           class="w-full p-2 bg-gray-800 text-white rounded border @error('city') border-red-500 @else border-gray-600 @enderror" 
                           placeholder="Colombo">
                    @error('city')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block mb-1 font-semibold">Postal Code*</label>
                    <input type="text" wire:model="postal_code" required 
                           class="w-full p-2 bg-gray-800 text-white rounded border @error('postal_code') border-red-500 @else border-gray-600 @enderror" 
                           placeholder="00000">
                    @error('postal_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-white bg-opacity-10 backdrop-blur-md p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4">Your Order</h2>
                
                @if($items->count() > 0)
                    <table class="w-full text-left mb-6">
                        <thead>
                            <tr class="text-gray-300">
                                <th class="pb-2">Product</th>
                                <th class="pb-2">Quantity</th>
                                <th class="pb-2">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr class="border-b border-gray-700">
                                    <td class="py-2 flex items-center space-x-3">
                                        @if($item->product && $item->product->image)
                                            <img src="{{ $item->product->image_url }}" 
                                                 class="w-12 h-12 object-cover rounded" 
                                                 alt="{{ $item->product->name }}">
                                        @else
                                            <div class="w-12 h-12 bg-gray-600 rounded flex items-center justify-center">
                                                <span class="text-xs text-gray-300">No Image</span>
                                            </div>
                                        @endif
                                        <span>{{ $item->product->name }}</span>
                                    </td>
                                    <td class="py-2">x{{ $item->quantity }}</td>
                                    <td class="py-2">Rs. {{ number_format($item->product->price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="border-t border-gray-600 pt-4 text-sm space-y-1">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>Rs. {{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Shipping:</span>
                            <span>Free</span>
                        </div>
                        @if($tax > 0)
                            <div class="flex justify-between">
                                <span>Tax:</span>
                                <span>Rs. {{ number_format($tax, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-semibold">
                            <span>Total:</span>
                            <span>Rs. {{ number_format($total, 2) }}</span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-6 space-y-2">
                        <label class="block font-semibold mb-1">Payment Method</label>
                        <label class="flex items-center space-x-2">
                            <input type="radio" wire:model.live="payment_method" value="cod" class="accent-white">
                            <span>Cash on Delivery</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="radio" wire:model.live="payment_method" value="card" class="accent-white">
                            <span>Card Payment</span>
                        </label>

                        <!-- Stripe Card Details Section -->
                        @if($payment_method === 'card')
                            <div class="space-y-4 mt-4">
                                <label class="block font-semibold">Card Details*</label>
                                <p class="text-sm text-gray-400">Enter your card number, expiry date, and CVC</p>
                                
                                <!-- Stripe Elements Container -->
                                <div id="card-element" class="p-3 bg-gray-800 border border-gray-600 rounded min-h-[40px]">
                                    <!-- Stripe Elements will create form elements here -->
                                </div>
                                
                                <!-- Stripe Errors -->
                                <div id="card-errors" role="alert" class="text-red-500 text-sm min-h-[20px]"></div>
                                
                                <div class="text-xs text-gray-400">
                                    <p>✓ Card number must be 16 digits</p>
                                    <p>✓ Expiry date in MM/YY format</p>
                                    <p>✓ CVC must be 3-4 digits</p>
                                    <p>✓ Use test card: 4242 4242 4242 4242</p>
                                </div>
                                
                                @error('payment_error')
                                    <p class="text-red-500 text-sm">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>

                    <button type="submit" 
                            class="mt-6 w-full bg-red-600 text-white py-2 rounded text-lg font-semibold hover:bg-red-700 transition disabled:opacity-50"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove>Place Order</span>
                        <span wire:loading>Processing...</span>
                    </button>
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-300 mb-4">Your cart is empty</p>
                        <a href="{{ route('shop') }}" class="inline-block bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
                            Continue Shopping
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Stripe
    const stripe = Stripe('{{ env('STRIPE_PUBLISHABLE_KEY') }}');
    const elements = stripe.elements({
        appearance: {
            theme: 'night',
            variables: {
                colorPrimary: '#dc2626',
                colorBackground: '#1f2937',
                colorText: '#ffffff',
                colorDanger: '#ef4444',
                fontFamily: 'system-ui, sans-serif',
                borderRadius: '6px',
            }
        }
    });

    // Create card element with proper validation
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#ffffff',
                '::placeholder': {
                    color: '#9ca3af',
                },
            },
            invalid: {
                color: '#ef4444',
            },
        },
    });
    let cardMounted = false;
    let cardComplete = false;
    let cardEmpty = true;

    // Function to mount/unmount card element based on payment method
    function toggleCardElement() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
        const cardContainer = document.getElementById('card-element');
        const submitButton = document.querySelector('button[type="submit"]');
        
        if (paymentMethod === 'card' && !cardMounted) {
            cardElement.mount('#card-element');
            cardMounted = true;
            // Disable submit until card is complete
            submitButton.disabled = !cardComplete || cardEmpty;
        } else if (paymentMethod === 'cod' && cardMounted) {
            cardElement.unmount();
            cardMounted = false;
            // Enable submit for COD
            submitButton.disabled = false;
        }
    }

    // Handle payment method changes
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', toggleCardElement);
    });

    // Initial setup
    toggleCardElement();

    // Handle real-time validation errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        const submitButton = document.querySelector('button[type="submit"]');
        
        // Update card state
        cardComplete = event.complete;
        cardEmpty = event.empty;
        
        if (event.error) {
            displayError.textContent = event.error.message;
            submitButton.disabled = true;
        } else {
            displayError.textContent = '';
            // Only enable if card is complete and valid
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
            if (paymentMethod === 'card') {
                submitButton.disabled = !cardComplete || cardEmpty;
            }
        }
    });

    // Handle form submission for card payments
    const form = document.querySelector('form');
    form.addEventListener('submit', async function(event) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
        
        if (paymentMethod !== 'card') {
            return; // Let Livewire handle COD normally
        }

        event.preventDefault();

        // Check if card is complete and valid before processing
        if (!cardComplete || cardEmpty) {
            document.getElementById('card-errors').textContent = 'Please enter complete and valid card details.';
            return;
        }

        // Disable submit button
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span>Processing Payment...</span>';

        try {
            // Create payment method
            const {error, paymentMethod: stripePaymentMethod} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
                billing_details: {
                    name: document.querySelector('input[wire\\:model="recipient_name"]').value,
                }
            });

            if (error) {
                // Show error to customer
                document.getElementById('card-errors').textContent = error.message;
                submitButton.disabled = false;
                submitButton.innerHTML = '<span>Place Order</span>';
                return;
            }

            // Send payment method ID to Livewire
            @this.processStripePayment(stripePaymentMethod.id);

        } catch (err) {
            console.error('Stripe error:', err);
            document.getElementById('card-errors').textContent = 'An unexpected error occurred.';
            submitButton.disabled = false;
            submitButton.innerHTML = '<span>Place Order</span>';
        }
    });

    // Listen for payment confirmation from server
    Livewire.on('confirmPayment', async (data) => {
        const {error} = await stripe.confirmCardPayment(data.client_secret);
        
        if (error) {
            @this.handlePaymentError(error.message);
        } else {
            @this.completeOrder();
        }
    });
});
</script>
@endpush