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

    /**
     * Ver/Previsualizar un documento
     */
    public function view($id)
    {
        $document = Document::findOrFail($id);
        
        // Verificar que el archivo existe
        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }

        // Obtener el contenido del archivo
        $file = Storage::disk('public')->get($document->file_path);
        
        // Lista blanca de MIME types permitidos
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp', 'image/bmp',
            'application/pdf',
            'text/plain', 'text/csv',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-rar-compressed'
        ];
        
        // Validar MIME type contra lista blanca
        $mimeType = $document->mime_type ?: 'application/octet-stream';
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $mimeType = 'application/octet-stream';
        }

        // Sanitizar el nombre del archivo más estrictamente
        $safeName = preg_replace('/[^\w\s\-_\.]/', '', $document->name);
        if (empty($safeName)) {
            $safeName = 'document';
        }
        
        // Retornar el archivo con el tipo MIME correcto
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $safeName . '"')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Renombrar un documento
     */
    public function rename(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $document = Document::findOrFail($id);
        $document->name = $request->name;
        $document->save();

        return response()->json($document);
    }

    /**
     * Eliminar un documento
     */
    public function deleteFile($id)
    {
        $document = Document::findOrFail($id);
        
        // Eliminar el archivo físico
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
        
        $document->delete();

        return response()->json(['message' => 'Documento eliminado correctamente']);
    }

    /**
     * Eliminar una carpeta y todo su contenido
     */
    public function deleteFolder($id)
    {
        $folder = Folder::findOrFail($id);
        
        // Eliminar todos los documentos de la carpeta
        $documents = Document::where('folder_id', $folder->id)->get();
        foreach ($documents as $document) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->delete();
        }
        
        // Eliminar la carpeta (las subcarpetas se eliminan automáticamente por cascade)
        $folder->delete();

        return response()->json(['message' => 'Carpeta eliminada correctamente']);
    }
}