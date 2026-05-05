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
        $isGroup = $request->boolean('is_group');

        $data = [
            'message'       => $request->input('message'),
            'sender_id'     => auth()->id(),
            'receiver_id'   => $isGroup ? null : $request->input('receiver_id'),
            'chat_group_id' => $isGroup ? $request->input('receiver_id') : null,
        ];

        $message = Message::create($data);

        if ($request->hasFile('file_upload')) {
            $file = $request->file('file_upload');
            $file_path = $this->uploadImage($file, 'chat');

            $originalFilename = basename($file->getClientOriginalName());
            $originalFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalFilename);

            $message->attachments()->create([
                'message_id'        => $message->id,
                'path'              => $file_path,
                'original_filename' => $originalFilename,
            ]);
        }

        broadcast(new ChatEvent($message));

        return created_responses('send', ['message' => $message->load('attachments')]);
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
