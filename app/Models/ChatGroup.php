<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Core\Auth\User;
use App\Models\App\Chat\Message;

class ChatGroup extends Model
{
    protected $fillable = ['name', 'created_by'];

    // Relación con usuarios (miembros)
    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_group_users', 'chat_group_id', 'user_id');
    }

    // Relación con mensajes
    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_group_id');
    }
}