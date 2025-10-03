<?php

namespace App\Livewire\Shop;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingAddress;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class CheckoutPage extends Component
{
    public $items = [];
    public $subtotal = 0;
    public $tax = 0;
    public $total = 0;
    public $itemCount = 0;

    // Shipping form properties
    public $recipient_name = '';
    public $phone = '';
    public $address = '';
    public $city = '';
    public $postal_code = '';

    // Payment properties
    public $payment_method = 'cod';
    public $stripe_payment_method_id = '';
    public $stripe_payment_intent_id = '';

    protected $rules = [
        'recipient_name' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'address' => 'required|string|max:500',
        'city' => 'required|string|max:100',
        'postal_code' => 'required|string|max:10',
        'payment_method' => 'required|in:cod,card',
    ];

    protected $messages = [
        'recipient_name.required' => 'Recipient name is required.',
        'phone.required' => 'Phone number is required.',
        'address.required' => 'Street address is required.',
        'city.required' => 'City is required.',
        'postal_code.required' => 'Postal code is required.',
    ];

    public function mount()
    {
        $this->loadCartData();
        
        // Redirect to home if cart is empty
        if ($this->itemCount == 0) {
            session()->flash('error', 'Your cart is empty. Please add items before checkout.');
            return redirect()->route('home');
        }

        // Pre-fill shipping details if user has them
        $user = Auth::user();
        if ($user && $user->addresses()->exists()) {
            $address = $user->addresses()->latest()->first();
            $this->recipient_name = $address->recipient_name ?? $user->name ?? '';
            $this->phone = $address->phone ?? '';
            $this->address = $address->address ?? '';
            $this->city = $address->city ?? '';
            $this->postal_code = $address->postal_code ?? '';
        } else {
            $this->recipient_name = $user->name ?? '';
        }
    }

    public function loadCartData()
    {
        if (!Auth::check()) {
            $this->items = collect();
            $this->itemCount = 0;
            $this->subtotal = 0;
            $this->tax = 0;
            $this->total = 0;
            return;
        }

        $this->items = CartItem::with('product')
            ->where('user_id', Auth::id())
            ->get();

        $this->itemCount = $this->items->sum('quantity');
        $this->subtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });

        // Calculate tax (you can adjust this rate as needed)
        $this->tax = $this->subtotal * 0.0; // 0% tax for now, change as needed
        $this->total = $this->subtotal + $this->tax;
    }

    public function processStripePayment($paymentMethodId)
    {
        try {
            \Log::info('ProcessStripePayment called', [
                'payment_method_id' => $paymentMethodId,
                'total' => $this->total,
                'user_id' => Auth::id()
            ]);
            
            // Validate form first
            try {
                $this->validate();
                \Log::info('Form validation passed');
            } catch (\Exception $validationError) {
                \Log::error('Form validation failed: ' . $validationError->getMessage());
                throw $validationError;
            }
            
            // Set Stripe API key
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            \Log::info('Stripe API key set');
            
            // Store payment method ID
            $this->stripe_payment_method_id = $paymentMethodId;
            \Log::info('Payment method ID stored: ' . $paymentMethodId);
            
            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => round($this->total * 100), // Convert to cents
                'currency' => 'lkr', // Sri Lankan Rupees
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => route('home'),
                'metadata' => [
                    'user_id' => Auth::id(),
                    'user_email' => Auth::user()->email,
                    'order_total' => $this->total,
                ]
            ]);
            
            // Store payment intent ID
            $this->stripe_payment_intent_id = $paymentIntent->id;
            
            if ($paymentIntent->status === 'requires_action') {
                // 3D Secure authentication required
                \Log::info('Payment requires 3D Secure authentication');
                $this->dispatch('confirmPayment', [
                    'client_secret' => $paymentIntent->client_secret
                ]);
                return ['success' => false, 'message' => '3D Secure authentication required', 'requires_action' => true];
            } else if ($paymentIntent->status === 'succeeded') {
                // Payment succeeded immediately
                \Log::info('Payment succeeded, completing order');
                $orderResult = $this->completeOrder();
                if ($orderResult) {
                    // Flash success message for redirect
                    $deliveryDate = now()->addDays(4)->format('M j, Y');
                    session()->flash('order_success', "Thank you for your purchase! Your payment has been processed successfully. Order will be delivered by {$deliveryDate}.");
                    $this->dispatch('orderCompleted');
                    return ['success' => true, 'message' => 'Order placed successfully', 'redirect' => route('home')];
                } else {
                    return ['success' => false, 'message' => 'Order creation failed'];
                }
            } else {
                // Payment failed
                \Log::error('Payment failed with status: ' . $paymentIntent->status);
                $this->handlePaymentError('Payment failed. Please try again.');
                return ['success' => false, 'message' => 'Payment failed'];
            }
            
        } catch (\Exception $e) {
            \Log::error('Stripe payment error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->handlePaymentError('Payment processing failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }
    
    public function handlePaymentError($message)
    {
        $this->addError('payment_error', $message);
        
        // Re-enable the submit button via JavaScript
        $this->dispatch('paymentError');
    }
    
    public function completeOrder()
    {
        // Complete the order after successful payment
        return $this->createOrder();
    }
    
    private function createOrder()
    {
        try {
            DB::beginTransaction();

            // Generate unique order ID
            do {
                $orderId = 'ORD-' . strtoupper(uniqid());
            } while (Order::where('order_id', $orderId)->exists());

            // Determine payment status based on payment method
            $paymentStatus = 'pending';
            if ($this->payment_method === 'card' && $this->stripe_payment_intent_id) {
                $paymentStatus = 'paid';
            }

            // Create order
            $order = Order::create([
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'total_amount' => $this->total,
                'status' => 'pending',
                'payment_method' => $this->payment_method,
                'payment_status' => $paymentStatus,
                'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
                'notes' => null,
            ]);

            // Create order items
            foreach ($this->items as $item) {
                OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'size' => $item->size,
                ]);
            }

            // Create shipping address
            ShippingAddress::create([
                'user_id' => Auth::id(),
                'order_id' => $order->order_id,
                'recipient_name' => $this->recipient_name,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
            ]);

            // Clear the cart
            CartItem::where('user_id', Auth::id())->delete();

            DB::commit();

            // Send order confirmation email
            try {
                $googleMailService = new \App\Services\GoogleMailService();
                $user = Auth::user();
                $orderWithItems = $order->load(['items.product']);
                $googleMailService->sendOrderConfirmation($orderWithItems, $user->email);
                \Log::info('Order confirmation email sent successfully', ['order_id' => $order->order_id, 'email' => $user->email]);
            } catch (\Exception $e) {
                \Log::error('Order confirmation email failed: ' . $e->getMessage(), ['order_id' => $order->order_id]);
            }

            // Success message
            $deliveryDate = now()->addDays(4)->format('M j, Y');
            $paymentMessage = $this->payment_method === 'card' ? 'Your payment has been processed successfully.' : '';
            session()->flash('order_success', "Thank you for your purchase! {$paymentMessage} Order #{$order->order_id} will be delivered by {$deliveryDate}. A confirmation email has been sent to {$user->email}.");
            
            // Dispatch event to update cart counter
            $this->dispatch('cartUpdated');

            // Return success indicator instead of redirect for AJAX calls
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'stripe_payment_intent_id' => $this->stripe_payment_intent_id
            ]);
            
            $this->handlePaymentError('Order processing failed. Please contact support.');
            return false;
        }
    }

    public function placeOrder()
    {
        // This method now only handles COD payments
        // Card payments are handled by JavaScript and processStripePayment
        
        if ($this->payment_method === 'card') {
            // Card payments should be handled by JavaScript and processStripePayment
            $this->addError('payment_error', 'Please use the card form above for card payments.');
            return;
        }
        
        $this->validate();

        if ($this->itemCount == 0) {
            session()->flash('error', 'Your cart is empty.');
            return;
        }

        try {
            DB::beginTransaction();

            // Check stock availability first
            foreach ($this->items as $cartItem) {
                if ($cartItem->product->stock < $cartItem->quantity) {
                    throw new \Exception("Insufficient stock for {$cartItem->product->name}. Only {$cartItem->product->stock} available.");
                }
            }

            // Create the order  
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => $this->total,
                'status' => 'pending',
                'payment_method' => $this->payment_method,
                'payment_status' => 'pending',
            ]);

            // Create order items and reduce stock
            foreach ($this->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->order_id, // Use order_id instead of id
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                ]);

                // Reduce product stock
                $cartItem->product->decrement('stock', $cartItem->quantity);
                \Log::info('Stock reduced for product via Livewire checkout', [
                    'product_id' => $cartItem->product_id,
                    'quantity_reduced' => $cartItem->quantity,
                    'remaining_stock' => $cartItem->product->fresh()->stock
                ]);
            }

            // Create shipping address
            ShippingAddress::create([
                'user_id' => Auth::id(),
                'order_id' => $order->order_id, // Use order_id instead of id
                'recipient_name' => $this->recipient_name,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
            ]);

            // Clear the cart
            CartItem::where('user_id', Auth::id())->delete();

            DB::commit();

            // Send order confirmation email via Google Gmail API
            try {
                $googleMailService = new \App\Services\GoogleMailService();
                $user = Auth::user();
                $orderWithItems = $order->load(['items.product']);
                $googleMailService->sendOrderConfirmation($orderWithItems, $user->email);
                \Log::info('Order confirmation email sent successfully', ['order_id' => $order->order_id, 'email' => $user->email]);
            } catch (\Exception $e) {
                // Log email error but don't fail the order
                \Log::error('Order confirmation email failed: ' . $e->getMessage(), ['order_id' => $order->order_id]);
            }

            // Flash success message with order details
            $deliveryDate = now()->addDays(4)->format('M j, Y');
            session()->flash('order_success', "Thank you for your purchase! Order #{$order->order_id} will be delivered by {$deliveryDate}. A confirmation email has been sent to {$user->email}.");
            
            // Dispatch event to update cart counter
            $this->dispatch('cartUpdated');

            // Redirect to home or order confirmation page
            return redirect()->route('home');

        } catch (\Exception $e) {
            DB::rollback();
            
            // Provide more specific error messages
            $errorMessage = 'Something went wrong. Please try again.';
            
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                $errorMessage = $e->getMessage();
            } elseif (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'Integrity constraint violation')) {
                $errorMessage = 'Order processing error. Please try again.';
            } elseif (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'Connection timed out')) {
                $errorMessage = 'Database connection error. Please try again.';
            }
            
            session()->flash('error', $errorMessage);
            \Log::error('Order placement failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'cart_items_count' => count($this->items),
                'total' => $this->total
            ]);
            
            // Don't redirect on error, stay on checkout page
            return;
        }
    }



    public function render()
    {
        return view('livewire.shop.checkout-page');
    }
}