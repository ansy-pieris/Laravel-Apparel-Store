<div class="pt-24 pb-12 min-h-screen bg-black text-white">
    <div class="max-w-6xl mx-auto px-4">
        <h1 class="text-3xl font-bold mb-8">Checkout</h1>

        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-600 text-white rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <form class="grid grid-cols-1 md:grid-cols-2 gap-8">
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
                           placeholder="10000">
                    @error('postal_code')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method Selection -->
                <div class="space-y-3 pt-4">
                    <h3 class="text-lg font-semibold">Payment Method</h3>
                    
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment_method" value="cod" wire:model="payment_method" class="accent-white">
                            <span>Cash on Delivery</span>
                        </label>

                        <label class="flex items-center space-x-2">
                            <input type="radio" name="payment_method" value="card" wire:model="payment_method" class="accent-white">
                            <span>Card Payment</span>
                        </label>

                        <!-- Stripe Card Elements Section -->
                        <div wire:ignore class="space-y-4 mt-4 hidden" id="card-section">
                            <label class="block font-semibold">Card Details*</label>
                            <p class="text-sm text-gray-400">Use test card: 4242 4242 4242 4242</p>
                            
                            <!-- Stripe Card Element Container -->
                            <div>
                                <div id="card-element" class="w-full p-3 bg-gray-800 border border-gray-600 rounded focus-within:border-blue-500">
                                    <!-- Stripe Elements will create form elements here -->
                                </div>
                            </div>
                            
                            <!-- Stripe Errors -->
                            <div id="card-errors" role="alert" class="text-red-500 text-sm min-h-[20px]"></div>
                            
                            @error('payment_error')
                                <p class="text-red-500 text-sm">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-white bg-opacity-10 backdrop-blur-md p-6 rounded-lg">
                @if($itemCount > 0)
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 mb-4">
                        @foreach($items as $item)
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <p class="font-medium">{{ $item->product->name }}</p>
                                    <p class="text-sm text-gray-400">Size: {{ $item->size }} | Qty: {{ $item->quantity }}</p>
                                </div>
                                <p class="font-medium">Rs {{ number_format($item->product->price * $item->quantity, 2) }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-600 pt-4 space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>Rs {{ number_format($subtotal, 2) }}</span>
                        </div>
                        @if($tax > 0)
                            <div class="flex justify-between">
                                <span>Tax:</span>
                                <span>Rs {{ number_format($tax, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total:</span>
                            <span>Rs {{ number_format($total, 2) }}</span>
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
    // Initialize Stripe
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    const elements = stripe.elements();
    
    // Create card element with custom styling
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#ffffff',
                '::placeholder': {
                    color: '#9ca3af',
                },
                backgroundColor: 'transparent',
            },
            invalid: {
                color: '#ef4444',
            },
        },
    });

    let cardMounted = false;
    const cardSection = document.getElementById('card-section');
    
    // Handle card element errors
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
    
    // Function to show/hide card section and mount Stripe element
    function toggleCardSection() {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
        
        // Update Livewire property
        @this.set('payment_method', paymentMethod);
        
        if (paymentMethod === 'card') {
            // Show card section
            if (cardSection) {
                cardSection.classList.remove('hidden');
                
                // Mount card element if not already mounted
                if (!cardMounted) {
                    cardElement.mount('#card-element');
                    cardMounted = true;
                    console.log('✅ Stripe card element mounted');
                }
            }
        } else {
            // Hide card section
            if (cardSection) {
                cardSection.classList.add('hidden');
            }
        }
    }
    
    // Listen to payment method changes
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleCardSection();
        });
    });
    
    // Initial toggle based on current selection
    setTimeout(function() {
        toggleCardSection();
    }, 100);
    
    // Handle form submission with PaymentIntent flow
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span>Processing...</span>';
            
            try {
                // Update Livewire property
                await @this.set('payment_method', paymentMethod);
                
                if (paymentMethod === 'cod') {
                    // Handle COD normally
                    await @this.placeOrder();
                    return;
                }
                
                if (paymentMethod === 'card') {
                    // Step 1: Create PaymentIntent on server
                    const intentResult = await @this.createPaymentIntent();
                    
                    if (!intentResult.success) {
                        throw new Error(intentResult.message);
                    }
                    
                    // Step 2: Confirm payment with Stripe
                    const {error, paymentIntent} = await stripe.confirmCardPayment(
                        intentResult.client_secret,
                        {
                            payment_method: {
                                card: cardElement,
                                billing_details: {
                                    name: document.querySelector('input[wire\\:model="recipient_name"]')?.value || 'Customer',
                                }
                            }
                        }
                    );
                    
                    if (error) {
                        throw new Error(error.message);
                    }
                    
                    if (paymentIntent.status === 'succeeded') {
                        // Step 3: Place order after successful payment
                        const orderResult = await @this.placeOrderAfterPayment(paymentIntent.id);
                        
                        if (orderResult.success) {
                            document.getElementById('card-errors').innerHTML = 
                                '<span style="color: #10b981;">✅ ' + orderResult.message + '</span>';
                            
                            if (orderResult.redirect) {
                                setTimeout(() => {
                                    window.location.href = orderResult.redirect;
                                }, 2000);
                            }
                        } else {
                            throw new Error(orderResult.message);
                        }
                    }
                }
                
            } catch (err) {
                document.getElementById('card-errors').textContent = err.message;
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        });
    }
    
    console.log('✅ Stripe checkout initialized');
});
</script>