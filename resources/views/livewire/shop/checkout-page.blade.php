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
                            <input type="radio" name="payment_method" value="cod" class="accent-white" checked>
                            <span>Cash on Delivery</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment_method" value="card" class="accent-white">
                            <span>Card Payment</span>
                        </label>

                        <!-- Stripe Card Details Section -->
                        <div wire:ignore class="space-y-4 mt-4 hidden" id="card-section">
                            <label class="block font-semibold">Card Details*</label>
                            <p class="text-sm text-gray-400">Use test card: 4242 4242 4242 4242</p>
                            
                            <!-- Single Card Element -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Card Information</label>
                                <div id="card-element" class="p-3 bg-gray-800 border border-gray-600 rounded min-h-[40px]">
                                    <!-- Stripe card element will be mounted here -->
                                </div>
                            </div>
                            
                            <!-- Stripe Errors -->
                            <div id="card-errors" role="alert" class="text-red-500 text-sm min-h-[20px]"></div>
                            
                            @error('payment_error')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>
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

<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM loaded, checking Stripe availability...');
    
    // Check if Stripe is loaded
    if (typeof Stripe === 'undefined') {
        console.error('❌ Stripe is not loaded! Check CSP settings.');
        const errorElement = document.getElementById('card-errors');
        if (errorElement) {
            errorElement.textContent = 'Payment system unavailable. Please refresh the page.';
        }
        return;
    }
    
    console.log('✅ Stripe is available, initializing...');
    // Initialize Stripe
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    const elements = stripe.elements();
    
    // Create card element with proper styling
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
    const cardSection = document.getElementById('card-section');
    
    // Function to show/hide card section and mount Stripe element
    function toggleCardSection() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
        console.log('Payment method selected:', paymentMethod);
        
        // Update Livewire property
        @this.set('payment_method', paymentMethod);
        
        if (paymentMethod === 'card') {
            // Show card section
            if (cardSection) {
                cardSection.classList.remove('hidden');
                console.log('✅ Card section shown, classList:', cardSection.classList.toString());
                
                // Mount Stripe element only once
                if (!cardMounted) {
                    try {
                        cardElement.mount('#card-element');
                        cardMounted = true;
                        console.log('✅ Stripe card element mounted successfully');
                        
                        // Focus the card element
                        setTimeout(() => cardElement.focus(), 100);
                    } catch (error) {
                        console.error('❌ Error mounting Stripe element:', error);
                    }
                }
            }
        } else {
            // Hide card section
            if (cardSection) {
                cardSection.classList.add('hidden');
                console.log('✅ Card section hidden, classList:', cardSection.classList.toString());
            }
        }
    }
    
    // Listen to payment method changes
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Radio button changed to:', this.value);
            toggleCardSection();
        });
    });
    
    // Initial toggle based on current selection
    setTimeout(function() {
        console.log('Running initial toggle...');
        toggleCardSection();
    }, 100);
    
    // Handle card validation
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    cardElement.on('ready', function() {
        console.log('✅ Stripe card element is ready for input!');
    });
    
    // Handle form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', async function(event) {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
            console.log('Form submission - payment method:', paymentMethod);
            
            // Update Livewire property before submission
            @this.set('payment_method', paymentMethod);
            
            if (paymentMethod !== 'card') {
                return; // Let Livewire handle COD
            }
            
            event.preventDefault();
            
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span>Processing Payment...</span>';
            
            try {
                const {error, paymentMethod: stripePaymentMethod} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: document.querySelector('input[wire\\:model="recipient_name"]').value,
                    }
                });
                
                if (error) {
                    document.getElementById('card-errors').textContent = error.message;
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                    return;
                }
                
                // Send to Livewire backend
                @this.processStripePayment(stripePaymentMethod.id);
                
            } catch (err) {
                console.error('Payment error:', err);
                document.getElementById('card-errors').textContent = 'Payment failed. Please try again.';
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }
    
    console.log('🎉 Setup complete - card field should work now!');
});
</script>