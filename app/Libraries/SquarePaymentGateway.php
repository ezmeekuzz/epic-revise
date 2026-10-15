<?php

declare(strict_types=1);

namespace App\Libraries;

use JsonException;
use RuntimeException;

final class SquarePaymentGateway
{
    private const API_VERSION = '2026-09-16';

    /** @return array{enabled: bool, ready: bool, mode: string, currency: string, applicationId: string, locationId: string, sdkUrl: string} */
    public function clientConfiguration(): array
    {
        $settings = new SettingsService();
        $mode = $settings->get('payment_mode', 'sandbox') === 'live' ? 'live' : 'sandbox';
        $applicationId = $settings->get('square_application_id');
        $locationId = $settings->get('square_location_id');
        $accessToken = $settings->get('square_access_token');
        $enabled = $settings->get('payments_enabled', '0') === '1'
            && $settings->get('payment_provider', 'square') === 'square';

        return [
            'enabled' => $enabled,
            'ready' => $enabled && $applicationId !== '' && $locationId !== '' && $accessToken !== '',
            'mode' => $mode,
            'currency' => strtoupper($settings->get('payment_currency', 'USD')),
            'applicationId' => $applicationId,
            'locationId' => $locationId,
            'sdkUrl' => $mode === 'live'
                ? 'https://web.squarecdn.com/v1/square.js'
                : 'https://sandbox.web.squarecdn.com/v1/square.js',
        ];
    }

    /** @return array{success: bool, paymentId?: string, status?: string, receiptUrl?: string, message?: string} */
    public function charge(
        string $sourceId,
        int $amountCents,
        string $currency,
        string $idempotencyKey,
        string $bookingReference,
    ): array {
        $configuration = $this->clientConfiguration();
        $settings = new SettingsService();
        $accessToken = $settings->get('square_access_token');
        if (! $configuration['ready'] || $accessToken === '') {
            throw new RuntimeException('Square payments are not configured.');
        }
        if ($amountCents < 1 || $sourceId === '' || mb_strlen($sourceId) > 500 || mb_strlen($idempotencyKey) > 45) {
            throw new RuntimeException('The Square payment request is invalid.');
        }

        $endpoint = $configuration['mode'] === 'live'
            ? 'https://connect.squareup.com/v2/payments'
            : 'https://connect.squareupsandbox.com/v2/payments';
        try {
            $body = json_encode([
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'amount_money' => ['amount' => $amountCents, 'currency' => strtoupper($currency)],
                'location_id' => $configuration['locationId'],
                'autocomplete' => true,
                'reference_id' => mb_substr($bookingReference, 0, 40),
                'note' => mb_substr('Epic Storage booking ' . $bookingReference, 0, 100),
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The Square payment request could not be prepared.', 0, $exception);
        }

        $handle = curl_init($endpoint);
        if ($handle === false) {
            throw new RuntimeException('The Square payment connection could not be initialized.');
        }
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'Square-Version: ' . self::API_VERSION,
            ],
        ]);

        $responseBody = curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $connectionError = curl_error($handle);
        curl_close($handle);
        if ($responseBody === false || $connectionError !== '') {
            log_message('error', 'Square payment connection failed for booking {reference}.', ['reference' => $bookingReference]);

            return ['success' => false, 'message' => 'Square could not be reached. No payment confirmation was received.'];
        }

        $decoded = json_decode((string) $responseBody, true);
        if ($statusCode >= 200 && $statusCode < 300 && is_array($decoded) && isset($decoded['payment']['id'])) {
            $status = strtoupper((string) ($decoded['payment']['status'] ?? ''));
            if ($status === 'COMPLETED') {
                return [
                    'success' => true,
                    'paymentId' => (string) $decoded['payment']['id'],
                    'status' => $status,
                    'receiptUrl' => (string) ($decoded['payment']['receipt_url'] ?? ''),
                ];
            }

            return ['success' => false, 'status' => $status, 'message' => 'Square did not confirm the payment as completed.'];
        }

        $message = 'Square declined or could not process the payment.';
        if (is_array($decoded) && isset($decoded['errors'][0]['detail'])) {
            $detail = trim((string) $decoded['errors'][0]['detail']);
            if ($detail !== '') {
                $message = mb_substr($detail, 0, 300);
            }
        }
        log_message('warning', 'Square payment was not completed for booking {reference}; HTTP {status}.', [
            'reference' => $bookingReference,
            'status' => $statusCode,
        ]);

        return ['success' => false, 'message' => $message];
    }
}
