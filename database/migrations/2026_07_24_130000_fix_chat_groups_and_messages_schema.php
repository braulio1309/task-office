<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('chat_groups')) {
            Schema::create('chat_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chat_group_users')) {
            Schema::create('chat_group_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_group_id')->constrained('chat_groups')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['chat_group_id', 'user_id'], 'chat_group_users_unique_member');
            });
        }

        if (Schema::hasTable('messages')) {
            if (!Schema::hasColumn('messages', 'is_read')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->boolean('is_read')->default(false)->after('message');
                });
            }

            if (!Schema::hasColumn('messages', 'chat_group_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->foreignId('chat_group_id')->nullable()->after('receiver_id')
                        ->constrained('chat_groups')->nullOnDelete();
                });
            }

            // Group messages require receiver_id to be nullable.
            if (Schema::hasColumn('messages', 'receiver_id')) {
                // Drop existing foreign key if present to allow column alteration.
                try {
                    Schema::table('messages', function (Blueprint $table) {
                        $table->dropForeign(['receiver_id']);
                    });
                } catch (\Throwable $exception) {
                    // Ignore if the foreign key does not exist.
                }

                // Use raw SQL for robust nullability update on existing schema.
                if (DB::getDriverName() === 'mysql') {
                    DB::statement('ALTER TABLE messages MODIFY receiver_id BIGINT UNSIGNED NULL');
                } elseif (DB::getDriverName() === 'pgsql') {
                    DB::statement('ALTER TABLE messages ALTER COLUMN receiver_id DROP NOT NULL');
                }

                // Re-attach FK as nullable relation.
                try {
                    Schema::table('messages', function (Blueprint $table) {
                        $table->foreign('receiver_id')
                            ->references('id')
                            ->on('users')
                            ->nullOnDelete();
                    });
                } catch (\Throwable $exception) {
                    // Ignore if key already exists or cannot be re-created.
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('messages')) {
            if (Schema::hasColumn('messages', 'chat_group_id')) {
                try {
                    Schema::table('messages', function (Blueprint $table) {
                        $table->dropForeign(['chat_group_id']);
                    });
                } catch (\Throwable $exception) {
                    // Ignore if foreign key does not exist.
                }

                Schema::table('messages', function (Blueprint $table) {
                    $table->dropColumn('chat_group_id');
                });
            }

            if (Schema::hasColumn('messages', 'is_read')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->dropColumn('is_read');
                });
            }
        }

        if (Schema::hasTable('chat_group_users')) {
            Schema::dropIfExists('chat_group_users');
        }

        if (Schema::hasTable('chat_groups')) {
            Schema::dropIfExists('chat_groups');
        }
    }
};
