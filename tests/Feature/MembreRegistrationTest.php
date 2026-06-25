<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Membre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MembreRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed base configurations needed for registration
        AppSetting::set('age_majorite', 18, 'integer', 'majority age threshold', 'general');
        AppSetting::set('entreprise_pays', 'Burkina Faso', 'string', 'default country', 'general');
        
        // Mock SmsGateway and OtpService dependencies or ensure they fall back safely
        // In MembreAuthController: if no SMS gateway is active, it redirects to login
        // Let's ensure active SMS gateway is null during testing so it redirects to login and activates the account automatically.

        // Bypass captcha validation
        Validator::extend('captcha', function () {
            return true;
        });
    }

    public function test_membre_cannot_register_if_minor()
    {
        // 17 years old today
        $dateNaissance = now()->subYears(17)->format('Y-m-d');

        $response = $this->post('/membre/register', [
            'nom' => 'Somda',
            'prenom' => 'Modeste',
            'date_naissance' => $dateNaissance,
            'sexe' => 'M',
            'email' => 'modeste@example.com',
            'country_code' => 'BF',
            'telephone' => '70123456',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha' => 'default_bypass',
        ]);

        $response->assertSessionHasErrors('date_naissance');
        $this->assertDatabaseMissing('membres', [
            'email' => 'modeste@example.com',
        ]);
    }

    public function test_membre_can_register_if_major()
    {
        // 20 years old today
        $dateNaissance = now()->subYears(20)->format('Y-m-d');

        $response = $this->post('/membre/register', [
            'nom' => 'Somda',
            'prenom' => 'Modeste',
            'date_naissance' => $dateNaissance,
            'sexe' => 'M',
            'email' => 'modeste@example.com',
            'country_code' => 'BF',
            'telephone' => '70123456',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha' => 'default_bypass',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membres', [
            'email' => 'modeste@example.com',
        ]);
        $membre = Membre::where('email', 'modeste@example.com')->first();
        $this->assertEquals($dateNaissance, $membre->date_naissance->format('Y-m-d'));
    }

    public function test_majority_threshold_can_be_customized()
    {
        // Set majority age to 21
        AppSetting::set('age_majorite', 21, 'integer', 'majority age threshold', 'general');

        // 20 years old today (major under 18 rules, but minor under 21 rules)
        $dateNaissance = now()->subYears(20)->format('Y-m-d');

        $response = $this->post('/membre/register', [
            'nom' => 'Somda',
            'prenom' => 'Modeste',
            'date_naissance' => $dateNaissance,
            'sexe' => 'M',
            'email' => 'modeste@example.com',
            'country_code' => 'BF',
            'telephone' => '70123456',
            'password' => 'password',
            'password_confirmation' => 'password',
            'captcha' => 'default_bypass',
        ]);

        $response->assertSessionHasErrors('date_naissance');
        $this->assertDatabaseMissing('membres', [
            'email' => 'modeste@example.com',
        ]);
    }
}
