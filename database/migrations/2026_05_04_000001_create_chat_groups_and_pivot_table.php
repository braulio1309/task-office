<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Runs SECOND: patches the messages table to support group messages
class CreateChatGroupsAndPivotTable extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            // Make receiver_id nullable so group messages don't need a direct receiver
            $table->foreignId('receiver_id')->nullable()->change();

            // Add nullable FK to chat_groups for group messaging
            if (!Schema::hasColumn('messages', 'chat_group_id')) {
                $table->foreignId('chat_group_id')
                    ->nullable()
                    ->after('receiver_id')
                    ->constrained('chat_groups')
                    ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'chat_group_id')) {
                $table->dropForeign(['chat_group_id']);
                $table->dropColumn('chat_group_id');
            }
            $table->foreignId('receiver_id')->nullable(false)->change();
        });
    }
}
