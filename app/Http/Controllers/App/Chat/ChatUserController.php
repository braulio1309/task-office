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
     * Listado unificado de Chats (Usuarios + Grupos)
     */
    public function index()
    {
        $currentUserId = auth()->id();

        // 1. Obtener Usuarios (Chat 1 a 1) - Tu código original optimizado
        $users = User::with(['profilePicture', 'messages' => function($query){
                        $query->orderByDesc('created_at'); // Solo para obtener el último mensaje si quisieras
                    }])
                    ->where('id', '!=', $currentUserId)
                    ->select('id', 'first_name', 'last_name', 'email') // Asegúrate que first_name y last_name existan
                    ->get()
                    ->map(function ($user) {
                        $user->type = 'user'; // Marcamos que es usuario
                        // Concatenar nombre completo si no tienes atributo full_name en BD
                        $user->full_name = $user->first_name . ' ' . $user->last_name; 
                        return $user;
                    });

        // 2. Obtener Grupos donde el usuario actual es miembro
        $groups = ChatGroup::whereHas('members', function ($q) use ($currentUserId) {
                        $q->where('user_id', $currentUserId);
                    })
                    ->with(['members.profilePicture', 'messages' => function($query){
                        $query->orderByDesc('created_at');
                    }])
                    ->get()
                    ->map(function ($group) {
                        // AQUÍ DISFRAZAMOS EL GRUPO PARA QUE EL FRONTEND LO ENTIENDA
                        return [
                            'id' => $group->id,
                            'full_name' => $group->name, // El nombre del grupo actúa como full_name
                            'email' => null,
                            'type' => 'group', // Importante para tu v-if en Vue
                            'profile_picture' => null, // O una imagen por defecto de grupo
                            'groupMembers' => $group->members->pluck('id'), // IDs para lógica de avatares
                            'messages' => $group->messages,
                            'members_data' => $group->members // Data completa para mostrar avatares
                        ];
                    });

        // 3. Unir ambas colecciones
        $unifiedList = $groups->merge($users);

        // 4. (Opcional) Ordenar por el mensaje más reciente globalmente
        // Esto requiere lógica extra en 'messages', lo dejaremos simple por ahora.

        return response()->json($unifiedList);
    }

    /**
     * Crear un nuevo grupo
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'members' => 'required|array|min:1', // Al menos 1 miembro extra
            'members.*' => 'exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            // 1. Crear el grupo
            $group = ChatGroup::create([
                'name' => $request->name,
                'created_by' => auth()->id()
            ]);

            // 2. Preparar miembros (incluyendo al creador)
            $members = $request->members;
            $members[] = auth()->id(); // Agregarse a sí mismo
            
            // 3. Insertar en tabla pivote
            $group->members()->sync($members);

            DB::commit();
            return response()->json(['message' => 'Grupo creado', 'group' => $group]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Método Store Message (Actualizado para soportar grupos)
     */
    public function store(Request $request)
    {
        // Validar si es grupo o usuario
        $isGroup = $request->boolean('is_group');

        $data = [
            'message' => $request->message,
            'sender_id' => auth()->id(),
        ];

        if ($isGroup) {
            $data['chat_group_id'] = $request->receiver_id; // En grupos, el receiver_id es el ID del grupo
            $data['receiver_id'] = null;
        } else {
            $data['receiver_id'] = $request->receiver_id;
            $data['chat_group_id'] = null;
        }

        // ... Lógica de subida de archivos si existe ...
        if ($request->hasFile('file_upload')) {
            // Tu lógica de subida aquí
            // $data['path'] = ...
        }

        $message = Message::create($data);

        // Disparar Evento de Broadcast (Pusher/Reverb)
        // broadcast(new ChatEvent($message))->toOthers();

        return response()->json(['status' => 'success', 'message' => $message]);
    }

    /**
     * Obtener mensajes (Actualizado)
     * Necesitas modificar tu ruta user-messages/{id} para que soporte esto
     */
    public function getUserMessages(Request $request, $id)
{
    $myId = auth()->id();
    // Verificamos si el frontend nos dice que es un grupo (string 'true' o booleano)
    $isGroup = filter_var($request->query('is_group'), FILTER_VALIDATE_BOOLEAN);

    if ($isGroup) {
        // --- LÓGICA DE GRUPO ---
        // Traer mensajes que pertenezcan a este grupo ESPECÍFICO.
        $messages = Message::with('user') // Traemos datos del que envía
                    ->where('chat_group_id', $id) 
                    ->orderBy('created_at', 'asc')
                    ->get();
    } else {
        // --- LÓGICA DE USUARIO (PRIVADO) ---
        // Traer mensajes entre YO y EL OTRO USUARIO
        // ¡IMPORTANTE!: Agregamos whereNull('chat_group_id') para excluir mensajes de grupo
        $messages = Message::with('user')
                    ->whereNull('chat_group_id') // <--- ESTO ES LA CLAVE DEL AISLAMIENTO
                    ->where(function($q) use ($id, $myId) {
                        $q->where(function($inner) use ($id, $myId) {
                            $inner->where('sender_id', $myId)
                                  ->where('receiver_id', $id);
                        })
                        ->orWhere(function($inner) use ($id, $myId) {
                            $inner->where('sender_id', $id)
                                  ->where('receiver_id', $myId);
                        });
                    })
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    return response()->json($messages);
}
}
