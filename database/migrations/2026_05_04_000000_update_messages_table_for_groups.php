<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Runs FIRST: creates chat_groups and chat_group_users (prerequisite for the messages FK)
class UpdateMessagesTableForGroups extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('chat_groups')) {
            Schema::create('chat_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chat_group_users')) {
            Schema::create('chat_group_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_group_id')->constrained('chat_groups')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('chat_group_users');
        Schema::dropIfExists('chat_groups');
    }
}
