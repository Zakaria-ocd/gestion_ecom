<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Choice;
use App\Models\ChoiceValue;
use App\Models\Order;
use App\Models\Product;
use App\Models\Type;
use App\Models\TypeValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_authentication_and_api_json_auth_errors(): void
    {
        $buyer = User::create([
            'username' => 'Demo Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);

        $this->get('/api/cart')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.'])
            ->assertHeaderMissing('Location');

        $buyerToken = $this->postJson('/api/login', [
            'email' => 'buyer@example.com',
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('user.role', 'buyer')->json('token');

        $this->withToken($buyerToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $buyer->id);

        $this->postJson('/api/login', [
            'email' => 'buyer@example.com',
            'password' => 'incorrect',
        ])->assertUnauthorized()->assertJsonStructure(['message']);

        $this->withToken($buyerToken)
            ->getJson('/api/admin/dashboard/stats')
            ->assertForbidden();
        $this->withToken($buyerToken)->getJson('/api/orders')->assertForbidden();
    }

    public function test_admin_login_returns_a_token_allowed_to_access_admin_api(): void
    {
        $buyer = User::create([
            'username' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);
        User::create([
            'username' => 'Demo Admin',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin',
        ]);

        $adminToken = $this->postJson('/api/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ])->assertOk()->json('token');
        $this->withToken($adminToken)
            ->getJson('/api/admin/dashboard/stats')
            ->assertOk();
        $this->withToken($adminToken)->getJson('/api/orders')->assertOk();

        $order = Order::create([
            'user_id' => $buyer->id,
            'total_amount' => 19.99,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
            'address' => '1 Demo Road',
            'city' => 'Amman',
            'phone' => '0700000000',
        ]);
        $this->withToken($adminToken)
            ->getJson('/api/admin/orders/5')
            ->assertOk()
            ->assertJsonPath('orders.0.total_price', 19.99);
        $this->withToken($adminToken)
            ->putJson("/api/orders/{$order->id}", ['status' => 'shipped'])
            ->assertOk()
            ->assertJsonPath('order.status', 'shipped');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'delivery_status' => 'shipped',
        ]);
    }

    public function test_buyer_cannot_update_order_status(): void
    {
        $buyer = User::create([
            'username' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);
        $order = Order::create([
            'user_id' => $buyer->id,
            'total_amount' => 19.99,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'delivery_status' => 'pending',
            'address' => '1 Demo Road',
            'city' => 'Amman',
            'phone' => '0700000000',
        ]);
        $buyerToken = $this->postJson('/api/login', [
            'email' => 'buyer@example.com',
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($buyerToken)
            ->putJson("/api/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'delivery_status' => 'pending',
        ]);
    }

    public function test_admin_can_fetch_user_details_and_missing_user_images_return_not_found(): void
    {
        $admin = User::create([
            'username' => 'Demo Admin',
            'email' => 'admin@example.com',
            'password' => 'admin123',
            'role' => 'admin',
        ]);
        $seller = User::create([
            'username' => 'Demo Seller',
            'email' => 'seller@example.com',
            'password' => 'seller123',
            'role' => 'seller',
        ]);
        $token = $admin->createToken('admin-test')->plainTextToken;

        $this->getJson("/api/users/{$seller->id}")
            ->assertUnauthorized();

        $this->withToken($token)
            ->getJson("/api/users/{$seller->id}")
            ->assertOk()
            ->assertJsonPath('username', 'Demo Seller')
            ->assertJsonPath('email', 'seller@example.com')
            ->assertJsonPath('role', 'seller')
            ->assertJsonMissingPath('password');

        $this->getJson("/api/users/imageById/{$seller->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Image not found');
    }

    public function test_order_creation_validates_and_persists_delivery_contact_fields(): void
    {
        $buyer = User::create([
            'username' => 'Order Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);
        $token = $buyer->createToken('buyer-test')->plainTextToken;

        $this->withToken($token)->postJson('/api/orders', [
            'recipient_name' => 'Demo Buyer',
            'email' => 'not-an-email',
            'address' => '12 Market Street',
            'city' => 'Amman',
            'state' => 'Amman Governorate',
            'postal_code' => '01118',
            'phone' => 'not-a-phone',
            'payment_method' => 'cash_on_delivery',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone']);

        $category = Category::create(['name' => 'Order Test']);
        $seller = User::create([
            'username' => 'Order Seller',
            'email' => 'seller@example.com',
            'password' => 'password123',
            'role' => 'seller',
        ]);
        $product = Product::create([
            'name' => 'Order Product',
            'description' => 'Test item',
            'category_id' => $category->id,
            'seller_id' => $seller->id,
        ]);
        $choiceValue = ChoiceValue::create(['price' => 25, 'quantity' => 3]);
        $cart = Cart::create(['user_id' => $buyer->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'choice_value_id' => $choiceValue->id,
            'quantity' => 2,
            'price' => 25,
        ]);

        $this->withToken($token)->postJson('/api/orders', [
            'recipient_name' => 'Demo Buyer',
            'email' => 'buyer@example.com',
            'address' => '12 Market Street',
            'city' => 'Amman',
            'state' => 'Amman Governorate',
            'postal_code' => '01118',
            'phone' => '+962 7 9000 0000',
            'payment_method' => 'cash_on_delivery',
            'total_price' => 0.01,
        ])->assertCreated()
            ->assertJsonPath('order.recipient_name', 'Demo Buyer')
            ->assertJsonPath('order.email', 'buyer@example.com')
            ->assertJsonPath('order.address', '12 Market Street')
            ->assertJsonPath('order.city', 'Amman')
            ->assertJsonPath('order.state', 'Amman Governorate')
            ->assertJsonPath('order.postal_code', '01118')
            ->assertJsonPath('order.total_amount', 50);
    }

    public function test_catalog_filters_cart_and_order_work_with_migrated_schema(): void
    {
        $buyer = User::create([
            'username' => 'Shopper',
            'email' => 'shopper@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);
        $seller = User::create([
            'username' => 'Seller',
            'email' => 'seller@example.com',
            'password' => 'password123',
            'role' => 'seller',
        ]);
        $category = Category::create(['name' => 'T-Shirts', 'description' => 'Everyday shirts']);
        $product = Product::create([
            'name' => 'Cotton Tee',
            'description' => 'A demo shirt',
            'category_id' => $category->id,
            'seller_id' => $seller->id,
        ]);
        $choiceValue = ChoiceValue::create(['price' => 24.50, 'quantity' => 8]);
        Choice::create(['product_id' => $product->id, 'choice_values_id' => $choiceValue->id]);
        $colorType = Type::create(['name' => 'color']);
        $sizeType = Type::create(['name' => 'size']);
        $color = TypeValue::create(['type_id' => $colorType->id, 'value' => 'Black', 'colorCode' => '#000000']);
        $size = TypeValue::create(['type_id' => $sizeType->id, 'value' => 'M', 'colorCode' => '']);
        $choiceValue->typeValues()->attach([$color->id, $size->id]);
        $choiceValue->typeValues()->attach($color->id);

        $this->getJson('/api/products')->assertOk()->assertJsonPath('data.0.id', $product->id);
        $this->getJson("/api/products/{$product->id}")->assertOk()->assertJsonPath('data.name', 'Cotton Tee');
        $this->getJson("/api/products/{$product->id}/choices")
            ->assertOk()
            ->assertJsonCount(2, 'data.0.typeValuePairs')
            ->assertJsonPath('data.0.typeValuePairs.0.colorCode', '#000000');
        $this->getJson("/api/categories/{$category->id}/products")
            ->assertOk()
            ->assertJsonPath('products.0.id', $product->id);
        $this->getJson('/api/filter-products?colors='.$color->id.'&sizes='.$size->id.'&sort=popular')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);
        $this->getJson('/api/filter-products?colors='.$color->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);
        $this->getJson('/api/filter-products?sizes='.$size->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);
        $this->getJson('/api/filter-products?categories='.$category->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id);

        $token = $this->postJson('/api/login', [
            'email' => 'shopper@example.com',
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'choice_value_id' => $choiceValue->id,
            'quantity' => 2,
        ])->assertCreated()->assertJsonPath('cart_item.price', 24.5);

        $this->withToken($token)->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('total_price', 49)
            ->assertJsonCount(1, 'cart_items');

        $response = $this->withToken($token)->postJson('/api/orders', [
            'recipient_name' => 'Shopper',
            'email' => 'shopper@example.com',
            'address' => '12 Market Street',
            'city' => 'Amman',
            'state' => 'Amman Governorate',
            'postal_code' => '11118',
            'phone' => '+962700000000',
            'payment_method' => 'cash_on_delivery',
            'total_price' => 0.01,
        ])->assertCreated()
            ->assertJsonPath('order.total_amount', 49)
            ->assertJsonPath('order.total_price', 49)
            ->assertJsonPath('order.status', 'pending');

        $orderId = $response->json('order.id');
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'total_amount' => 49,
            'delivery_status' => 'pending',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'choice_value_id' => $choiceValue->id,
            'quantity' => 2,
            'price' => 24.5,
            'total' => 49,
        ]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('carts', ['user_id' => $buyer->id]);

        $this->withToken($token)->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('items.0.total', 49);
        $this->withToken($token)->getJson('/api/orders/100/limit')
            ->assertOk()
            ->assertJsonCount(1);

        $otherBuyer = User::create([
            'username' => 'Other',
            'email' => 'other@example.com',
            'password' => 'password123',
            'role' => 'buyer',
        ]);
        $this->actingAs($otherBuyer, 'sanctum')
            ->getJson("/api/orders/{$orderId}")
            ->assertForbidden();
    }
}
