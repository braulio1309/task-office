<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    /**
     * Listar contenido de una carpeta
     */
    public function list(Request $request)
    {
        $folderId = $request->get('folder_id');
        
        // Validar acceso si usas permisos (pendiente de tu lógica anterior)
        
        $folders = Folder::where('parent_id', $folderId)
                        ->orderBy('name')
                        ->get();
                        
        $files = Document::where('folder_id', $folderId)
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function($file) {
                            $file->download_url = Storage::url($file->file_path);
                            $file->readable_size = $file->readable_size; 
                            return $file;
                        });

                        
        $currentFolder = $folderId ? Folder::with('parent')->find($folderId) : null;

        return response()->json([
            'folders' => $folders,
            'files' => $files,
            'current_folder' => $currentFolder
        ]);
    }

    /**
     * Subir nuevo archivo
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $uploadedFile = $request->file('file');
        $folderId = $request->input('folder_id') == 'null' ? null : $request->input('folder_id');

        // Guardar en Storage/app/public/documents/año
        $path = $uploadedFile->store('documents/' . date('Y'), 'public');

        $document = Document::create([
            'folder_id' => $folderId,
            'name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
            'created_by' => Auth::id() // Asumiendo autenticación
        ]);

        return response()->json($document);
    }

    /**
     * Crear nueva carpeta
     */
    public function createFolder(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $folder = Folder::create([
            'name' => $request->name,
            'parent_id' => $request->input('parent_id') == 'null' ? null : $request->input('parent_id'),
            'created_by' => Auth::id()
        ]);

        return response()->json($folder);
    }
}