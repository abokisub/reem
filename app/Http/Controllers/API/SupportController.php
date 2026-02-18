<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\MailController;
use Carbon\Carbon;

class SupportController extends Controller
{
    /**
     * Aboki AI Chat Interface
     */
    public function chatAboki(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized or Session Expired'], 401);
        }

        $messageText = $request->message;
        $message = strtolower($messageText);

        // 1. Get or Create Ticket (One user = One active ticket)
        $ticket = $this->getOrCreateTicket($user->id);

        // 2. Logic: If Agent is handling, AI stays quiet or informs
        if ($ticket->current_handler == 'agent' && !str_contains($message, 'exit') && !str_contains($message, 'close')) {
            // Silently return or inform user they are in human support
            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => null, // Frontend should handle wait
                'handler' => 'agent',
                'conversation_id' => $ticket->id
            ]);
        }

        // 3. Logic: Intent Detection
        $botResponse = $this->processAIIntent($message, $user->id, $ticket->id);

        // 4. Save User Message
        $this->saveMessage($ticket->id, 'user', $user->id, $messageText);

        // 5. Save Bot Response (if any)
        if (isset($botResponse['message'])) {
            $this->saveMessage($ticket->id, 'bot', null, $botResponse['message']);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => $botResponse['message'] ?? '',
            'conversation_id' => $ticket->id,
            'handler' => $ticket->current_handler,
            'requires_pin' => $botResponse['requires_pin'] ?? false,
            'escalate_to_agent' => $botResponse['escalate_to_agent'] ?? false,
            'actions' => $botResponse['actions'] ?? []
        ]);
    }
    public function createTicket(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        // Prevent Duplicate Open Tickets
        $existing = DB::table('support_tickets')->where('user_id', $user->id)->where('status', '!=', 'closed')->first();
        if ($existing) {
            return response()->json([
                'status' => 'success',
                'success' => true,
                'ticket' => $existing,
                'message' => 'You already have an active support session.'
            ]);
        }

        $ticketCode = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $id = DB::table('support_tickets')->insertGetId([
            'ticket_code' => $ticketCode,
            'user_id' => $user->id,
            'subject' => $request->subject ?? 'Direct Support Request',
            'status' => 'open',
            'priority' => $request->priority ?? 'medium',
            'type' => 'human',
            'current_handler' => 'agent',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if ($request->message) {
            $this->saveMessage($id, 'user', $user->id, $request->message);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'ticket' => DB::table('support_tickets')->where('id', $id)->first()
        ]);
    }

    /**
     * Specialized intent processor for Aboki
     */
    private function processAIIntent($message, $userId, $ticketId)
    {
        $user = DB::table('users')->where('id', $userId)->first();

        // 1. Human Agent Escalation Triggers (Explicit)
        $escalationKeywords = ['agent', 'human', 'speak to someone', 'talk to agent', 'customer care', 'admin'];
        foreach ($escalationKeywords as $kw) {
            if (str_contains($message, $kw)) {
                return $this->escalateToAgent($user, $ticketId);
            }
        }

        // 2. Greetings
        if (str_contains($message, 'hi') || str_contains($message, 'hello') || str_contains($message, 'hey') || str_contains($message, 'good morning') || str_contains($message, 'good afternoon')) {
            return [
                'message' => "Hi {$user->username} 👋 I’m Aboki, your virtual assistant.\nI can help you check transactions, KYC status, spending, and more. How can I help today? 😊",
                'actions' => [
                    ['label' => 'Check Balance', 'action' => 'check_balance'],
                    ['label' => 'My Spending', 'action' => 'spending_analytics']
                ]
            ];
        }

        // 3. Dynamic Keyword Learning
        $learned = DB::table('ai_learning_keywords')
            ->where('is_active', true)
            ->get();

        foreach ($learned as $item) {
            if (str_contains($message, strtolower($item->keyword))) {
                $response = [
                    'message' => $item->response,
                ];
                if ($item->action) {
                    $response['actions'] = [['label' => ucwords(str_replace('_', ' ', $item->action)), 'action' => $item->action]];
                }
                return $response;
            }
        }

        // 4. Specific Automated info
        if (str_contains($message, 'balance')) {
            $formattedBal = number_format($user->balance, 2);
            return [
                'message' => "Your current wallet balance is ₦$formattedBal. Would you like to see your recent transactions instead?",
                'actions' => [['label' => 'Recent Transactions', 'action' => 'recent_tx']]
            ];
        }

        if (str_contains($message, 'track') || str_contains($message, 'status') || str_contains($message, 'transaction')) {
            return [
                'message' => "To track a transaction, please provide the Reference ID. You can also view your transaction history in the app.",
                'actions' => [['label' => 'History', 'action' => 'list_recent']]
            ];
        }

        // 5. Default: Fallback with escalation count
        // Check how many times bot has failed in this ticket
        $botFails = DB::table('support_messages')
            ->where('ticket_id', $ticketId)
            ->where('sender_type', 'bot')
            ->where('message', 'like', "I’m still learning%")
            ->count();

        if ($botFails >= 2) {
            return $this->escalateToAgent($user, $ticketId, "I'm having a bit of trouble understanding, so I'm connecting you to a human expert who can help better! 👨💼");
        }

        return [
            'message' => "I’m still learning and I want to help you better 😊\nCould you clarify your request, or would you like to speak with a human agent?",
            'actions' => [
                ['label' => 'Talk to Agent', 'action' => 'speak_human'],
                ['label' => 'Main Menu', 'action' => 'help']
            ]
        ];
    }

    private function escalateToAgent($user, $ticketId, $customMessage = null)
    {
        DB::table('support_tickets')->where('id', $ticketId)->update([
            'type' => 'human',
            'current_handler' => 'agent',
            'status' => 'open',
            'updated_at' => now()
        ]);

        $this->notifyAdminOfEscalation($user, $ticketId);

        return [
            'message' => $customMessage ?? "I’ve connected you to a human support agent 👨💼\nPlease hold on while they review your request. I'll stay here if you need anything else later!",
            'escalate_to_agent' => true
        ];
    }

    private function notifyAdminOfEscalation($user, $ticketId)
    {
        try {
            $general = DB::table('general')->first();
            $admins = DB::table('users')->where('type', 'ADMIN')->get();
            foreach ($admins as $admin) {
                // Send Email
                $email_data = [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'username' => $user->username,
                    'title' => 'SUPPORT ESCALATION',
                    'sender_mail' => $general->app_email ?? 'support@kobopoint.com',
                    'user_email' => $user->email,
                    'app_name' => $general->app_name ?? 'App',
                    'website' => '',
                    'date' => now(),
                    'transid' => $ticketId,
                    'app_phone' => $general->app_phone ?? ''
                ];
                // Assuming MailController::send_mail exists
                MailController::send_mail($email_data, 'email.support_escalation');

                // Dashboard Notification (DB request table or similar)
                DB::table('request')->insert([
                    'username' => $user->username,
                    'message' => "User {$user->username} requested human agent (Ticket #$ticketId)",
                    'date' => now(),
                    'transid' => "TKT-$ticketId",
                    'status' => 'pending',
                    'title' => 'SUPPORT TICKET'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error("Escalation Notification Failed: " . $e->getMessage());
        }
    }

    /**
     * Messaging logic for User <-> Human Agent (Post-Escalation)
     */
    public function sendUserMessage(Request $request, $ticketId)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $this->saveMessage($ticketId, 'user', $user->id, $request->message);

        // Update ticket last activity
        DB::table('support_tickets')->where('id', $ticketId)->update([
            'last_message' => $request->message,
            'last_message_at' => now(),
            'status' => 'open' // Reopen if closed
        ]);

        return response()->json(['status' => 'success']);
    }

    public function getChatMessages(Request $request, $ticketId)
    {
        $messages = DB::table('support_messages')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get();

        $ticket = DB::table('support_tickets')->where('id', $ticketId)->first();

        return response()->json([
            'status' => 'success',
            'messages' => $messages,
            'ticket_status' => $ticket->status ?? 'unknown',
            'current_handler' => $ticket->current_handler ?? 'ai'
        ]);
    }

    public function getTickets(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $tickets = DB::table('support_tickets')
            ->where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'tickets' => $tickets
        ]);
    }

    // Helper: Internal save
    private function saveMessage($ticketId, $senderType, $senderId, $message, $isSystem = false)
    {
        DB::table('support_messages')->insert([
            'ticket_id' => $ticketId,
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $message,
            'system_message' => $isSystem,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    // Helper: Ticket persistence
    private function getOrCreateTicket($userId)
    {
        // One user = One active ticket logic
        $ticket = DB::table('support_tickets')
            ->where('user_id', $userId)
            ->where('status', '!=', 'closed')
            ->first();

        if ($ticket) {
            return $ticket;
        }

        $ticketCode = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $id = DB::table('support_tickets')->insertGetId([
            'ticket_code' => $ticketCode,
            'user_id' => $userId,
            'subject' => 'AI Support Chat',
            'status' => 'open',
            'type' => 'ai',
            'current_handler' => 'ai',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return DB::table('support_tickets')->where('id', $id)->first();
    }

    /**
     * ADMIN FUNCTIONS
     */

    public function adminGetOpenTickets(Request $request, $id = null)
    {
        // Many admin routes in this app use the {id}/secure as a manual token check
        // If $id is provided, we can optionally verify it, though Sanctum is also active.
        $userId = $this->verifytoken($id);
        $user = $request->user() ?: ($userId ? DB::table('users')->where('id', $userId)->first() : null);

        if (!$user || trim(strtoupper($user->type)) !== 'ADMIN') {
            return response()->json(['status' => 'error', 'message' => 'Admin only'], 403);
        }

        $tickets = DB::table('support_tickets')
            ->join('users', 'support_tickets.user_id', '=', 'users.id')
            ->select('support_tickets.*', 'users.username', 'users.name as user_fullname')
            ->where('support_tickets.status', '!=', 'closed')
            ->orderBy('support_tickets.updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'tickets' => $tickets
        ]);
    }

    public function adminReply(Request $request, $ticketId)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'ADMIN')
            return response()->json(['status' => 'error', 'message' => 'Admin only'], 403);

        $this->saveMessage($ticketId, 'agent', $user->id, $request->message);

        DB::table('support_tickets')->where('id', $ticketId)->update([
            'last_message' => $request->message,
            'last_message_at' => now(),
            'status' => 'pending', // Waiting for user
            'current_handler' => 'agent' // Agent takes full control
        ]);

        return response()->json(['status' => 'success']);
    }

    public function adminCloseTicket(Request $request, $ticketId)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'ADMIN')
            return response()->json(['status' => 'error', 'message' => 'Admin only'], 403);

        $ticket = DB::table('support_tickets')->where('id', $ticketId)->first();
        if (!$ticket)
            return response()->json(['status' => 'error', 'message' => 'Ticket not found'], 404);

        // Notify User via System Message
        $this->saveMessage($ticketId, 'agent', $user->id, "👋 Support session ended. Agent {$user->name} has closed this ticket. Aboki AI is back to help you!", true);

        DB::table('support_tickets')->where('id', $ticketId)->update([
            'status' => 'closed',
            'current_handler' => 'ai',
            'closed_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['status' => 'success']);
    }
}
