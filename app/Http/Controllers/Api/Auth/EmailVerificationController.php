<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailVerificationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'verified' => $request->user()?->hasVerifiedEmail() ?? false,
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address is already verified.',
            ]);
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            $mailer = (string) config('mail.default');

            Log::error('Verification email resend failed.', [
                'user_id' => $request->user()?->id,
                'mailer' => $mailer,
                'mail_host' => config("mail.mailers.{$mailer}.host"),
                'mail_port' => config("mail.mailers.{$mailer}.port"),
                'exception' => $exception::class,
                'error' => $this->sanitizeExceptionMessage($exception->getMessage()),
            ]);

            return response()->json([
                'message' => 'Verification email could not be sent. Check the server mail configuration and try again later.',
            ], 503);
        }

        return response()->json([
            'message' => 'Verification link sent.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user() ? new UserResource($request->user()) : null,
        ]);
    }

    private function sanitizeExceptionMessage(string $message): string
    {
        $secrets = array_values(array_filter([
            config('mail.mailers.smtp.username'),
            config('mail.mailers.smtp.password'),
            config('services.brevo.api_key'),
        ], static fn (mixed $value): bool => is_string($value) && $value !== ''));

        if ($secrets !== []) {
            $message = str_replace($secrets, '[redacted]', $message);
        }

        $message = preg_replace([
            '/\b(password|passwd|api[-_ ]?key|authorization|bearer|token)(\s*[:=]\s*|\s+)[^\s,;]+/i',
            '~(://)[^/@\s:]+:[^/@\s]+@~',
        ], [
            '$1$2[redacted]',
            '$1[redacted]@',
        ], $message) ?? 'Email delivery failed.';

        return mb_substr((string) preg_replace('/[\r\n\t]+/', ' ', $message), 0, 500);
    }
}
