<?php

namespace App\Http\Controllers\App\Chat;

use App\Http\Controllers\Controller;
use App\Models\App\Chat\Message;
use App\Models\ChatGroup;
use App\Models\Core\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatUserController extends Controller
{
    /**
     * Unified list of all contacts (users + groups).
     * Each entry carries has_messages and last_message_at so the frontend
     * can split "recent chats" from "all contacts".
     */
    public function index()
    {
        $currentUserId = auth()->id();

        // ── Users ──────────────────────────────────────────────────────────
        $users = User::with('profilePicture')
            ->where('id', '!=', $currentUserId)
            ->select('id', 'first_name', 'last_name', 'email')
            ->get()
            ->map(function (User $user) use ($currentUserId) {
                $lastMsg = Message::whereNull('chat_group_id')
                    ->where(function ($q) use ($user, $currentUserId) {
                        $q->where(function ($inner) use ($user, $currentUserId) {
                            $inner->where('sender_id', $currentUserId)
                                  ->where('receiver_id', $user->id);
                        })->orWhere(function ($inner) use ($user, $currentUserId) {
                            $inner->where('sender_id', $user->id)
                                  ->where('receiver_id', $currentUserId);
                        });
                    })
                    ->orderByDesc('created_at')
                    ->first(['message', 'created_at']);

                return [
                    'id'              => $user->id,
                    'full_name'       => $user->first_name . ' ' . $user->last_name,
                    'email'           => $user->email,
                    'type'            => 'user',
                    'profile_picture' => $user->profilePicture,
                    'has_messages'    => (bool) $lastMsg,
                    'last_message'    => $lastMsg ? $lastMsg->message : null,
                    'last_message_at' => $lastMsg ? $lastMsg->created_at : null,
                ];
            });

        // ── Groups ────────────────────────────────────────────────────────
        $groups = ChatGroup::whereHas('members', function ($q) use ($currentUserId) {
                $q->where('user_id', $currentUserId);
            })
            ->with(['members.profilePicture'])
            ->get()
            ->map(function (ChatGroup $group) {
                $lastMsg = Message::where('chat_group_id', $group->id)
                    ->orderByDesc('created_at')
                    ->first(['message', 'created_at']);

                return [
                    'id'              => $group->id,
                    'full_name'       => $group->name,
                    'email'           => null,
                    'type'            => 'group',
                    'profile_picture' => null,
                    'groupMembers'    => $group->members->pluck('id'),
                    'members_data'    => $group->members,
                    'has_messages'    => (bool) $lastMsg,
                    'last_message'    => $lastMsg ? $lastMsg->message : null,
                    'last_message_at' => $lastMsg ? $lastMsg->created_at : null,
                ];
            });

        return response()->json($users->merge($groups)->values());
    }

    /**
     * Create a new chat group.
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:50',
            'members'   => 'required|array|min:1',
            'members.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $group = ChatGroup::create([
                'name'       => $request->name,
                'created_by' => auth()->id(),
            ]);

            $members   = $request->members;
            $members[] = auth()->id();
            $group->members()->sync(array_unique($members));

            DB::commit();
            return response()->json(['message' => 'Grupo creado', 'group' => $group]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch messages for a direct conversation or a group.
     * Query string: ?is_group=true|false
     */
    public function getUserMessages(Request $request, $id)
    {
        $myId    = auth()->id();
        $isGroup = filter_var($request->query('is_group'), FILTER_VALIDATE_BOOLEAN);

        if ($isGroup) {
            $messages = Message::with('user.profilePicture', 'attachments')
                ->where('chat_group_id', $id)
                ->orderBy('created_at')
                ->get();
        } else {
            $messages = Message::with('user.profilePicture', 'attachments')
                ->whereNull('chat_group_id')
                ->where(function ($q) use ($id, $myId) {
                    $q->where(function ($inner) use ($id, $myId) {
                        $inner->where('sender_id', $myId)->where('receiver_id', $id);
                    })->orWhere(function ($inner) use ($id, $myId) {
                        $inner->where('sender_id', $id)->where('receiver_id', $myId);
                    });
                })
                ->orderBy('created_at')
                ->get();
        }

        return response()->json($messages);
    }
}
