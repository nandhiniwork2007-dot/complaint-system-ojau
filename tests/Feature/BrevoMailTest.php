<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class BrevoMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        config([
            'mail.default' => 'brevo',
            'mail.from.address' => 'verified@example.com',
            'mail.from.name' => 'CMS Support',
            'services.brevo.api_key' => 'test-api-key',
            'services.brevo.endpoint' => 'https://api.brevo.com/v3/smtp/email',
            'services.brevo.timeout' => 10,
        ]);
    }

    public function test_brevo_transport_sends_expected_message_and_authentication_header(): void
    {
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'test-message-id'], 201),
        ]);

        $this->sendTestEmail();

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-api-key')
                && $request['sender'] === ['email' => 'verified@example.com', 'name' => 'CMS Support']
                && $request['to'][0]['email'] === 'recipient@example.com'
                && $request['subject'] === 'Test subject'
                && $request['htmlContent'] === '<p>Test email body</p>';
        });
    }

    public function test_smtp_configuration_uses_a_supported_starttls_scheme(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp-relay.brevo.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'smtp-login',
            'mail.mailers.smtp.password' => 'smtp-key',
        ]);

        $this->assertInstanceOf(EsmtpTransport::class, Mail::mailer('smtp')->getSymfonyTransport());
    }

    public function test_brevo_transport_throws_when_api_rejects_or_returns_invalid_success_response(): void
    {
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['message' => 'rejected'], 401),
        ]);

        try {
            $this->sendTestEmail();
            $this->fail('A rejected API request must not be reported as sent.');
        } catch (TransportException $exception) {
            $this->assertSame('Brevo did not accept the email request.', $exception->getMessage());
            $this->assertStringNotContainsString('test-api-key', $exception->getMessage());
        }

        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['unexpected' => 'response'], 201),
        ]);

        $this->expectException(TransportException::class);
        $this->sendTestEmail();
    }

    public function test_brevo_transport_wraps_connection_failures_without_exposing_details(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('timeout while using secret test-api-key');
        });

        try {
            $this->sendTestEmail();
            $this->fail('A connection failure must not be reported as sent.');
        } catch (TransportException $exception) {
            $this->assertSame('Brevo email request failed.', $exception->getMessage());
            $this->assertStringNotContainsString('test-api-key', $exception->getMessage());
        }
    }

    public function test_registration_sends_verification_email_through_brevo(): void
    {
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'verification-id'], 201),
        ]);

        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '1234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()->assertJsonPath('mail_warning', null);

        Http::assertSent(fn ($request): bool => $request['to'][0]['email'] === 'jane@example.com'
            && str_contains($request['htmlContent'], 'email/verify/'));
    }

    public function test_verification_resend_uses_the_brevo_transport(): void
    {
        $user = User::factory()->unverified()->create();
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'verification-resend-id'], 201),
        ]);

        $this->actingAs($user)->postJson('/api/auth/email/verification-notification')
            ->assertOk();

        Http::assertSent(fn ($request): bool => $request['to'][0]['email'] === $user->email
            && str_contains($request['htmlContent'], 'email/verify/'));
    }

    public function test_verification_resend_uses_a_signed_https_production_url(): void
    {
        config(['app.url' => 'https://complaint-system-p1y1.onrender.com']);
        URL::forceRootUrl('https://complaint-system-p1y1.onrender.com');
        URL::forceScheme('https');

        $user = User::factory()->unverified()->create();
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'verification-resend-id'], 201),
        ]);

        $this->actingAs($user)->postJson('/api/auth/email/verification-notification')->assertOk();

        $message = Http::recorded()->first()[0]['htmlContent'];
        preg_match('~https://complaint-system-p1y1\.onrender\.com/email/verify/[^"<>\s]+~', html_entity_decode($message), $matches);

        $this->assertNotEmpty($matches[0] ?? null);
        $this->assertTrue(URL::hasValidSignature(Request::create($matches[0])));
    }

    public function test_failed_verification_delivery_returns_a_safe_service_unavailable_response(): void
    {
        $user = User::factory()->unverified()->create();
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['message' => 'rejected test-api-key'], 401),
        ]);

        $this->actingAs($user)->postJson('/api/auth/email/verification-notification')
            ->assertStatus(503)
            ->assertJson(['message' => 'Verification email could not be sent. Check the server mail configuration and try again later.'])
            ->assertDontSee('test-api-key')
            ->assertDontSee('TransportException');
    }

    public function test_password_reset_email_can_be_requested_and_used_through_brevo(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'reset-id'], 201),
        ]);

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk();

        $request = Http::recorded()->first()[0];
        preg_match('~/reset-password/([A-Za-z0-9]+)~', html_entity_decode($request['htmlContent']), $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'The reset email should contain the existing reset route and token.');

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $matches[1],
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(password_verify('new-password', $user->fresh()->password));
    }

    public function test_failed_password_reset_delivery_returns_generic_error(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);
        Http::fake([
            'api.brevo.com/v3/smtp/email' => Http::response(['message' => 'rejected'], 401),
        ]);

        $this->postJson('/api/auth/forgot-password', ['email' => 'jane@example.com'])
            ->assertStatus(503)
            ->assertJson(['message' => 'Password reset email could not be sent. Try again later.'])
            ->assertDontSee('test-api-key');
    }

    private function sendTestEmail(): void
    {
        $email = (new Email)
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->subject('Test subject')
            ->html('<p>Test email body</p>');

        Mail::mailer('brevo')->getSymfonyTransport()->send($email);
    }
}
