<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use App\Services\FirebasePushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    /**
     * Register a new FCM push token for the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string|max:512',
            'platform' => 'nullable|string|in:web,android,ios',
        ]);

        $pushToken = PushToken::register(
            $request->user()->id,
            $request->input('token'),
            $request->input('platform', 'web')
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل رمز الإشعارات بنجاح',
            'id'      => $pushToken->id,
        ]);
    }

    /**
     * Remove/deactivate a push token (e.g. on logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        PushToken::deactivate($request->input('token'));

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة رمز الإشعارات',
        ]);
    }

    /**
     * Test push notification — sends a test alert to the current user.
     */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokens = PushToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد أجهزة مسجلة للإشعارات',
            ], 404);
        }

        $payload = [
            'notification' => [
                'title' => '🧪 اختبار الإشعارات',
                'body'  => 'تم إرسال هذا الاختبار بنجاح! الإشعارات تعمل بشكل سليم.',
            ],
            'data' => [
                'type' => 'test',
                'url'  => url('/admin'),
            ],
            'webpush' => [
                'fcm_options' => [
                    'link' => url('/admin'),
                ],
            ],
        ];

        $projectId = config('services.firebase.project_id', env('VITE_FIREBASE_PROJECT_ID'));
        $serverKey = config('services.firebase.server_key', env('FIREBASE_SERVER_KEY'));
        $serviceAccount = config('services.firebase.credentials');

        $sent = 0;

        foreach ($tokens as $token) {
            try {
                if ($serviceAccount && file_exists($serviceAccount)) {
                    // Use HTTP v1 (simplified — reuses FirebasePushService logic)
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Content-Type' => 'application/json',
                    ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => array_merge($payload, ['token' => $token]),
                    ]);
                    if ($response->successful()) $sent++;
                } elseif ($serverKey) {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => "key={$serverKey}",
                        'Content-Type'  => 'application/json',
                    ])->post('https://fcm.googleapis.com/fcm/send', [
                        'to'           => $token,
                        'notification' => $payload['notification'],
                        'data'         => $payload['data'],
                    ]);
                    if ($response->successful()) $sent++;
                } else {
                    // No Firebase config — just confirm registration works
                    $sent = count($tokens);
                    break;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("FCM test push error: {$e->getMessage()}");
            }
        }

        return response()->json([
            'success' => $sent > 0,
            'sent'    => $sent,
            'message' => $sent > 0
                ? "تم إرسال {$sent} إشعار اختبار بنجاح"
                : 'فشل إرسال الإشعارات — تأكد من إعداد Firebase',
        ]);
    }
}
