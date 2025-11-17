<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Visit;
use App\Models\Pet;
use App\Models\Owner;
use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GroomingServiceValidationTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected $user;
    protected $visit;
    protected $groomingService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create([
            'user_role' => 'veterinarian',
            'branch_id' => 1,
        ]);

        // Create owner and pet
        $owner = Owner::factory()->create(['user_id' => $this->user->user_id]);
        $pet = Pet::factory()->create([
            'own_id' => $owner->own_id,
            'user_id' => $this->user->user_id,
        ]);

        // Create a visit
        $this->visit = Visit::factory()->create([
            'pet_id' => $pet->pet_id,
            'user_id' => $this->user->user_id,
            'visit_date' => now(),
        ]);

        // Create at least one grooming service
        $this->groomingService = Service::factory()->create([
            'serv_name' => 'Basic Grooming',
            'serv_type' => 'grooming',
            'serv_price' => 500.00,
            'branch_id' => 1,
        ]);
    }

    /** @test */
    public function it_requires_grooming_packages_to_be_selected()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('medical.visits.grooming.save', $this->visit->visit_id), [
            'visit_id' => $this->visit->visit_id,
            'pet_id' => $this->visit->pet_id,
            'weight' => $this->visit->weight ?? 10,
            'assigned_groomer' => 'Test Groomer',
            'coat_condition' => 'good',
            'grooming_type' => [], // Empty array - no services selected
            'instructions' => 'Test notes',
        ]);

        $response->assertSessionHasErrors('grooming_type');
    }

    /** @test */
    public function it_accepts_valid_grooming_service_submission()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('medical.visits.grooming.save', $this->visit->visit_id), [
            'visit_id' => $this->visit->visit_id,
            'pet_id' => $this->visit->pet_id,
            'weight' => $this->visit->weight ?? 10,
            'assigned_groomer' => 'Test Groomer',
            'coat_condition' => 'good',
            'grooming_type' => [$this->groomingService->serv_name],
            'instructions' => 'Test grooming submission',
            'start_time' => now()->format('Y-m-d\TH:i'),
            'end_time' => now()->addHours(2)->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }

    /** @test */
    public function it_shows_friendly_validation_message_for_missing_services()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('medical.visits.grooming.save', $this->visit->visit_id), [
            'visit_id' => $this->visit->visit_id,
            'pet_id' => $this->visit->pet_id,
            'weight' => $this->visit->weight ?? 10,
            'assigned_groomer' => 'Test Groomer',
            'coat_condition' => 'good',
            'grooming_type' => [],
            'instructions' => 'Test notes',
        ]);

        $response->assertSessionHasErrors('grooming_type');
        
        // Check that the error message is user-friendly
        $errors = session('errors');
        if ($errors) {
            $groomingErrors = $errors->get('grooming_type');
            $this->assertNotEmpty($groomingErrors);
            // The message should mention "select" or "required"
            $this->assertStringContainsStringIgnoringCase('select', $groomingErrors[0]);
        }
    }

    /** @test */
    public function it_accepts_multiple_grooming_services()
    {
        $this->actingAs($this->user);

        // Create additional grooming services
        $service2 = Service::factory()->create([
            'serv_name' => 'Deluxe Grooming',
            'serv_type' => 'grooming',
            'serv_price' => 800.00,
            'branch_id' => 1,
        ]);

        $response = $this->post(route('medical.visits.grooming.save', $this->visit->visit_id), [
            'visit_id' => $this->visit->visit_id,
            'pet_id' => $this->visit->pet_id,
            'weight' => $this->visit->weight ?? 10,
            'assigned_groomer' => 'Test Groomer',
            'coat_condition' => 'good',
            'grooming_type' => [
                $this->groomingService->serv_name,
                $service2->serv_name,
            ],
            'instructions' => 'Multiple services test',
            'start_time' => now()->format('Y-m-d\TH:i'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }

    /** @test */
    public function it_validates_coat_condition_when_provided()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('medical.visits.grooming.save', $this->visit->visit_id), [
            'visit_id' => $this->visit->visit_id,
            'pet_id' => $this->visit->pet_id,
            'weight' => $this->visit->weight ?? 10,
            'assigned_groomer' => 'Test Groomer',
            'grooming_type' => [$this->groomingService->serv_name],
            'coat_condition' => 'invalid_value', // Invalid coat condition
        ]);

        // Should fail if coat_condition validation is strict
        $response->assertSessionHasErrors('coat_condition');
    }

    /** @test */
    public function unauthenticated_users_cannot_submit_grooming_services()
    {
        $response = $this->post(route('medical.visits.grooming.save', $this->visit->visit_id), [
            'visit_id' => $this->visit->visit_id,
            'pet_id' => $this->visit->pet_id,
            'weight' => 10,
            'assigned_groomer' => 'Test Groomer',
            'coat_condition' => 'good',
            'grooming_type' => [$this->groomingService->serv_name],
        ]);

        $response->assertRedirect();
    }
}
