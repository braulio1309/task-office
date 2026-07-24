<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $appTypeId = DB::table('types')->where('alias', 'app')->value('id');

        if (!$appTypeId) {
            return;
        }

        $permissions = [
            ['name' => 'view_chat', 'group_name' => 'sample_pages'],
            ['name' => 'manage_calendar_view', 'group_name' => 'sample_pages'],
            ['name' => 'manage_kanban_view', 'group_name' => 'sample_pages'],
            ['name' => 'view_documents', 'group_name' => 'documents'],
            ['name' => 'create_documents', 'group_name' => 'documents'],
            ['name' => 'update_documents', 'group_name' => 'documents'],
            ['name' => 'delete_documents', 'group_name' => 'documents'],
            ['name' => 'preview_documents', 'group_name' => 'documents'],
            ['name' => 'download_documents', 'group_name' => 'documents'],
            ['name' => 'view_document_folders', 'group_name' => 'documents'],
            ['name' => 'create_document_folders', 'group_name' => 'documents'],
            ['name' => 'delete_document_folders', 'group_name' => 'documents'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'type_id' => $appTypeId,
                    'group_name' => $permission['group_name'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('permissions')->whereIn('name', [
            'view_chat',
            'manage_calendar_view',
            'manage_kanban_view',
            'view_documents',
            'create_documents',
            'update_documents',
            'delete_documents',
            'preview_documents',
            'download_documents',
            'view_document_folders',
            'create_document_folders',
            'delete_document_folders',
        ])->delete();
    }
};
