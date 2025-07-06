<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Device;
use App\Services\CardknoxSignatureVerifier;

class CardknoxController extends Controller
{
    protected $signatureVerifier;

    public function __construct(CardknoxSignatureVerifier $signatureVerifier)
    {
        $this->signatureVerifier = $signatureVerifier;
    }

    public function getDevices()
    {
        $devices = Device::where('user_id', auth()->id())->get();
        return view('sale_pos.partials.cardknox_device_modal', compact('devices'));
    }

    public function getDevicesAlt()
    {
        $devices = Device::where('user_id', auth()->id())->get();
        return view('sale_pos.partials.cardknox_device_modal_alt', compact('devices'));
    }

    public function initiateTransaction(Request $request)
    {
        try {
            $deviceId = $request->input('device_id');
            $amount = $request->input('amount');
            $externalRequestId = Str::uuid();

            // Get device details
            $device = Device::where('id', $deviceId)->first();
            if (!$device) {
                Log::error('Device not found', [
                    'device_id' => $deviceId,
                    'user_id' => auth()->id()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device selected'
                ], 400);
            }

            $apiKey = $device->cardknox_api_key;

            $payload = [
                'xPayload' => [
                    'xCommand' => 'cc:sale',
                    'xAmount' => number_format($amount, 2, '.', ''),
                    'xEnableTipPrompt' => true,
                    'xExternalRequestId' => $externalRequestId,
                    'xSoftwareName' => 'YourSoftware',
                    'xSoftwareVersion' => '1.0.0',
                    'xInvoice'=>$externalRequestId
                ],
                'xDeviceId' => $device->device_id,
                'xWebhookId' => 'whk1a614b54d-f497-4aa0-910a-407ef9d619dd',
            ];

            // Log the request payload
            Log::info('Cardknox Request Payload:', $payload);

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://device.cardknox.com/v1/Session/initiate', $payload);

            // Log the response from Cardknox
            Log::info('Cardknox Response:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                Log::error('Cardknox API Error', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error communicating with payment processor'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);

        } catch (\Exception $e) {
            Log::error('Cardknox Transaction Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    public function pollSession(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $device_id = $request->input('device_id');
            
            Log::info('Starting Cardknox session poll', [
                'session_id' => $sessionId,
                'user_id' => auth()->id()
            ]);
            
            if (!$sessionId) {
                Log::warning('Session poll failed - missing session ID');
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID is required'
                ], 400);
            }

            // Get the device associated with the session
            $device = Device::where('id', $device_id)->first();
            if (!$device) {
                Log::error('Session poll failed - device not found', [
                    'user_id' => auth()->id()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found'
                ], 404);
            }

            Log::info('Found device for session poll', [
                'device_id' => $device->id,
                'user_id' => auth()->id()
            ]);

            $apiKey = $device->cardknox_api_key;

            // Poll the CardKnox session status
            Log::info('Sending request to Cardknox API', [
                'url' => "https://device.cardknox.com/v1/Session/{$sessionId}",
                'session_id' => $sessionId
            ]);

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ])->get("https://device.cardknox.com/v1/Session/{$sessionId}");

            Log::info('Received response from Cardknox API', [
                'status_code' => $response->status(),
                'response_body' => $response->json()
            ]);

            if (!$response->successful()) {
                Log::error('Cardknox Session Poll Error', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'session_id' => $sessionId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error checking session status'
                ], 500);
            }

            $sessionData = $response->json();

            // Check if the session is completed
            if ($sessionData['xSessionStatus'] === 'COMPLETED') {
                Log::info('Session completed successfully', [
                    'session_id' => $sessionId,
                    'session_status' => $sessionData['xSessionStatus'],
                    'gateway_status' => $sessionData['xGatewayStatus'] ?? 'unknown'
                ]);
                return response()->json([
                    'success' => true,
                    'data' => $sessionData
                ]);
            }

            // Check if session timed out
            if ($sessionData['xSessionStatus'] === 'TIMEOUT') {
                Log::warning('Session timed out', [
                    'session_id' => $sessionId,
                    'session_status' => $sessionData['xSessionStatus']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment session timed out',
                    'data' => $sessionData
                ]);
            }

            // If session is not completed, return the current status
            Log::info('Session still in progress', [
                'session_id' => $sessionId,
                'session_status' => $sessionData['xSessionStatus']
            ]);

            return response()->json([
                'success' => true,
                'data' => $sessionData,
                'message' => 'Session is still in progress'
            ]);

        } catch (\Exception $e) {
            Log::error('Cardknox Session Poll Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $sessionId ?? 'unknown'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while checking session status'
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        try {
            // Log all request headers for debugging
            Log::info('Cardknox webhook headers:', $request->headers->all());
			Log::info('request all:', $request->all());
            
            // Get the webhook signature from header
            $signature = $request->header('x-cim-signature');
            
            // Testing mode - bypass signature verification if signature is test_key
            $isTestMode = $signature && strpos($signature, 'keyId=test_key') !== false;
            
            if (!$signature && !$isTestMode) {
                Log::error('Cardknox webhook missing signature');
                return response()->json(['error' => 'Missing signature'], 400);
            }

            // Get raw request body
            $rawBody = $request->getContent();

            // Skip signature verification in test mode
            if (!$isTestMode) {
                // Verify signature
                if (!$this->signatureVerifier->verifySignature($rawBody, $signature)) {
                    Log::error('Cardknox webhook signature verification failed');
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            } else {
                Log::info('Running in test mode - signature verification bypassed');
            }

            // Parse JSON data
            $data = $request->all();
            
            // Log the webhook data
            Log::info('Cardknox webhook received', [
                'data' => $data,
                'signature' => $signature,
                'test_mode' => $isTestMode
            ]);

            // Broadcast the webhook data to the frontend
            event(new \App\Events\CardknoxWebhookReceived($data));

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Cardknox webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
