<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::with('tenant')->orderBy('created_at', 'desc')->get();
        return response()->json($tickets);
    }

    public function show($id)
    {
        $ticket = SupportTicket::with(['tenant', 'messages.user'])->findOrFail($id);
        return response()->json($ticket);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        
        $message = $ticket->messages()->create([
            'user_id' => $request->user() ? $request->user()->id : null,
            'message' => $request->message,
            'is_admin_reply' => true
        ]);

        return response()->json($message, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,pending,closed',
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return response()->json($ticket);
    }
}
