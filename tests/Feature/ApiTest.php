<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_login_with_email_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'cashier1@gulberg.com',
            'password' => 'password',
            'device_name' => 'test_device'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user',
                    'branch'
                ]
            ]);
    }

    public function test_login_with_pin(): void
    {
        $response = $this->postJson('/api/auth/login-pin', [
            'pin' => '1234',
            'device_name' => 'test_device'
        ]);

        $response->assertStatus(200);
    }

    public function test_fetch_menu_items(): void
    {
        $user = User::where('email', 'cashier1@gulberg.com')->first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/menu-items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'price', 'category_id']
                ]
            ]);
    }

    public function test_create_order(): void
    {
        $user = User::where('email', 'waiter1@gulberg.com')->first();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'order_type' => 'dine_in',
                'table_id' => 1,
                'items' => [
                    [
                        'menu_item_id' => 1,
                        'quantity' => 2,
                        'notes' => 'Extra spicy'
                    ]
                ]
            ]);

        $response->assertStatus(201);
    }

    public function test_unauthorized_access(): void
    {
        $response = $this->getJson('/api/menu-items');
        $response->assertStatus(401);
    }
}
