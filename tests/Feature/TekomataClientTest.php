<?php

namespace Tests\Feature;

use App\Services\Tekomata\Exceptions\ApiUnavailableException;
use App\Services\Tekomata\Exceptions\TekomataApiException;
use App\Services\Tekomata\Exceptions\UnauthorizedException;
use App\Services\Tekomata\Exceptions\ValidationException;
use App\Services\Tekomata\TekomataClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TekomataClientTest extends TestCase
{
    private function client(): TekomataClient
    {
        // Zero backoff keeps the retry test fast.
        return new TekomataClient([
            'base_url' => 'http://api.test',
            'timeout' => 5,
            'connect_timeout' => 5,
            'retries' => 2,
            'retry_sleep_ms' => 0,
        ]);
    }

    public function test_successful_response_is_decoded(): void
    {
        Http::fake(['http://api.test/*' => Http::response(['ok' => true], 200)]);

        $this->assertSame(['ok' => true], $this->client()->get('/health'));
    }

    public function test_bearer_token_is_attached(): void
    {
        Http::fake(['http://api.test/*' => Http::response([], 200)]);

        $this->client()->get('/auth/me', token: 'jwt-123');

        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer jwt-123'));
    }

    public function test_401_maps_to_unauthorized(): void
    {
        Http::fake(['http://api.test/*' => Http::response(['message' => 'nope'], 401)]);

        $this->expectException(UnauthorizedException::class);
        $this->client()->get('/secure');
    }

    public function test_422_maps_to_validation_with_localized_field_errors(): void
    {
        // New error envelope: error.fields[] carry a per-field code (an i18n key)
        // + optional params; the FE renders from the catalog, not the raw message.
        Http::fake(['http://api.test/*' => Http::response([
            'error' => [
                'code' => 'validation_failed',
                'message' => 'one or more fields are invalid',
                'fields' => [
                    ['field' => 'email', 'code' => 'validation.email', 'message' => 'must be a valid email'],
                    ['field' => 'password', 'code' => 'validation.too_short', 'params' => ['min' => 8], 'message' => 'too short'],
                ],
            ],
        ], 422)]);

        try {
            $this->client()->post('/auth/register', []);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame([
                'email' => [__('errors.validation.email')],
                'password' => [__('errors.validation.too_short', ['min' => 8])],
            ], $e->errors());
        }
    }

    public function test_unknown_field_code_falls_back_to_upstream_message(): void
    {
        Http::fake(['http://api.test/*' => Http::response([
            'error' => [
                'code' => 'validation_failed',
                'fields' => [
                    ['field' => 'email', 'code' => 'validation.unmapped', 'message' => 'upstream fallback'],
                ],
            ],
        ], 422)]);

        try {
            $this->client()->post('/auth/register', []);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['upstream fallback']], $e->errors());
        }
    }

    public function test_error_code_is_exposed_for_rendering(): void
    {
        Http::fake(['http://api.test/*' => Http::response([
            'error' => ['code' => 'rate_limited', 'message' => 'too many requests'],
        ], 429)]);

        try {
            $this->client()->post('/auth/register', []);
            $this->fail('Expected TekomataApiException');
        } catch (TekomataApiException $e) {
            $this->assertSame('rate_limited', $e->errorCode());
            $this->assertSame(__('errors.rate_limited'), $e->localizedMessage());
        }
    }

    public function test_5xx_is_retried_then_throws_unavailable(): void
    {
        Http::fake(['http://api.test/*' => Http::response('boom', 500)]);

        try {
            $this->client()->get('/flaky');
            $this->fail('Expected ApiUnavailableException');
        } catch (ApiUnavailableException $e) {
            $this->assertSame(500, $e->status);
        }

        // retries=2 → 3 attempts total.
        Http::assertSentCount(3);
    }

    public function test_4xx_is_not_retried(): void
    {
        Http::fake(['http://api.test/*' => Http::response('bad', 400)]);

        try {
            $this->client()->get('/nope');
        } catch (TekomataApiException) {
            // expected
        }

        Http::assertSentCount(1);
    }
}
