<?php

namespace Tests\Feature\Backend;

use App\Models\Document;
use App\Models\Folder;
use Tests\TestCase;

class DocumentsManagerTest extends TestCase
{
    public function test_admin_can_move_document_to_another_folder()
    {
        $admin = $this->loginAsAdmin();

        $sourceFolder = Folder::create([
            'name' => 'Origen',
            'original_name' => 'Origen',
            'created_by' => $admin->id,
        ]);

        $targetFolder = Folder::create([
            'name' => 'Destino',
            'original_name' => 'Destino',
            'created_by' => $admin->id,
        ]);

        $document = Document::create([
            'folder_id' => $sourceFolder->id,
            'name' => 'archivo.pdf',
            'original_name' => 'archivo.pdf',
            'file_path' => 'documents/2026/archivo.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'created_by' => $admin->id,
        ]);

        $response = $this->postJson("/documents/file/{$document->id}/move", [
            'folder_id' => $targetFolder->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'folder_id' => $targetFolder->id,
        ]);
    }

    public function test_admin_can_move_folder_to_another_parent()
    {
        $admin = $this->loginAsAdmin();

        $parentFolder = Folder::create([
            'name' => 'Padre',
            'original_name' => 'Padre',
            'created_by' => $admin->id,
        ]);

        $targetFolder = Folder::create([
            'name' => 'Destino',
            'original_name' => 'Destino',
            'created_by' => $admin->id,
            'parent_id' => $parentFolder->id,
        ]);

        $folderToMove = Folder::create([
            'name' => 'Mover',
            'original_name' => 'Mover',
            'created_by' => $admin->id,
            'parent_id' => $parentFolder->id,
        ]);

        $response = $this->postJson("/documents/folder/{$folderToMove->id}/move", [
            'parent_id' => $targetFolder->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('folders', [
            'id' => $folderToMove->id,
            'parent_id' => $targetFolder->id,
        ]);
    }
}
