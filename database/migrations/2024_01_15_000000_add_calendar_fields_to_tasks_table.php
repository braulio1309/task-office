<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add additional fields to tasks table for calendar integration
 * These fields might already exist if they were added manually to the model
 * This migration ensures the database schema matches the model
 */
class AddCalendarFieldsToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Add end_date if it doesn't exist
            if (!Schema::hasColumn('tasks', 'end_date')) {
                $table->date('end_date')->nullable()->after('stage_id');
            }
            
            // Add supervisor if it doesn't exist
            if (!Schema::hasColumn('tasks', 'supervisor')) {
                $table->foreignId('supervisor')->nullable()->constrained('users')->onDelete('set null')->after('end_date');
            }
            
            // Add assigned_to if it doesn't exist
            if (!Schema::hasColumn('tasks', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->after('supervisor');
            }
            
            // Add status if it doesn't exist
            if (!Schema::hasColumn('tasks', 'status')) {
                $table->string('status')->default('pending')->after('assigned_to');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('tasks', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
            if (Schema::hasColumn('tasks', 'supervisor')) {
                $table->dropForeign(['supervisor']);
                $table->dropColumn('supervisor');
            }
            if (Schema::hasColumn('tasks', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
}
