<?php

namespace App\Http\Controllers\App\Chat;

use App\Events\ChatEvent;
use App\Helpers\Core\Traits\FileHandler;
use App\Http\Controllers\Controller;
use App\Models\App\Chat\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use FileHandler;

    public function __construct()
    {
    }

    public function index()
    {
    }

    public function userMessage($id)
    {
        return Message::with('user.profilePicture', 'attachments')
            ->where(function ($query) use ($id) {
                $query->where('sender_id', auth()->id());
                $query->where('receiver_id', $id);
            })->orWhere(function ($query) use ($id) {
                $query->where('sender_id', $id);
                $query->where('receiver_id', auth()->id());
            })->orderBy('created_at')->get();
    }

    public function markAsRead(Request $request, $id)
    {
        // Mark messages as read for a specific conversation
        Message::where('receiver_id', auth()->id())
            ->where('sender_id', $id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['status' => 'success']);
    }

    public function unreadCount()
    {
        // Get unread message count
        $unreadCount = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->count();
        
        // Get unread count per sender/group
        $unreadBySender = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->selectRaw('sender_id, chat_group_id, COUNT(*) as count')
            ->groupBy('sender_id', 'chat_group_id')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->chat_group_id ?: $item->sender_id,
                    'count' => $item->count,
                    'is_group' => !is_null($item->chat_group_id)
                ];
            });
        
        return response()->json([
            'total' => $unreadCount,
            'by_sender' => $unreadBySender
        ]);
    }

    public function store(Request $request)
    {
        $message = Message::create($request->only('message', 'receiver_id'));
        if ($request->file_upload) {
            $file = request()->file('file_upload');
            $file_path = $this->uploadImage($file, 'chat');
            
            // Sanitize the original filename to prevent path traversal and special character issues
            $originalFilename = $file->getClientOriginalName();
            $originalFilename = basename($originalFilename); // Remove any path components
            $originalFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalFilename); // Replace special chars

            $message->attachments()->updateOrCreate([
                'message_id' => $message->id,
                'path' => $file_path,
                'original_filename' => $originalFilename
            ]);
        }
//        event(new ChatEvent($request->message, $user));

            broadcast(new ChatEvent($message));

        return created_responses('send', ['message' => $message]);
    }


    public function show($id)
    {
        //
    }


    public function update(Request $request, $id)
    {
        //
    }


    public function destroy($id)
    {
        //
    }
}
