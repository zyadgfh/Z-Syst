<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessagingService $messagingService
    ) {
    }

    /**
     * Get user messages with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $userId = $request->user()->id;

        $messages = $this->messagingService->getUserMessages(
            $userId,
            $companyId,
            [
                'type' => $request->query('type'),
                'unread' => $request->query('unread'),
                'per_page' => $request->query('per_page', 25),
            ]
        );

        return MessageResource::collection($messages)->response();
    }

    /**
     * Store a new message.
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');
        $data['sender_id'] = $request->user()->id;

        $message = Message::create($data);

        return (new MessageResource($message->load(['sender:id,name', 'recipient:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a specific message.
     */
    public function show(Request $request, Message $message): JsonResponse
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $userId = $request->user()->id;

        // Check user has access to this message
        if ($message->sender_id !== $userId && $message->recipient_id !== $userId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Auto-mark as read when viewed
        if (!$message->is_read && $message->recipient_id === $userId) {
            $message->markAsRead();
        }

        return (new MessageResource($message->load(['sender:id,name', 'recipient:id,name', 'branch:id,name'])))
            ->response();
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Request $request, Message $message): JsonResponse
    {
        $userId = $request->user()->id;

        $isOwnMessage = $this->messagingService->markAsRead($message->id, $userId);

        if (!$isOwnMessage) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['message' => 'Message marked as read']);
    }

    /**
     * Reply to a message.
     */
    public function reply(Request $request, int $parentId): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string'],
        ]);

        try {
            $reply = $this->messagingService->replyToMessage(
                $parentId,
                $request->user()->id,
                $request->input('content')
            );

            return (new MessageResource($reply->load(['sender:id,name', 'recipient:id,name'])))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get unread message count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $userId = $request->user()->id;

        $count = $this->messagingService->getUnreadCount($userId, $companyId);

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Send stock refill request.
     */
    public function sendStockRefill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $message = $this->messagingService->sendStockRefillRequest(
            $companyId,
            $request->user()->id,
            $validated['recipient_id'],
            $validated['branch_id'],
            $validated['products']
        );

        return (new MessageResource($message->load(['sender:id,name', 'recipient:id,name'])))
            ->response()
            ->setStatusCode(201);
    }
}