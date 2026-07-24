<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class DocumentController extends Controller
{
    private const DOCUMENT_VIEW_LEVELS = ['view', 'download', 'edit', 'delete'];
    private const DOCUMENT_DOWNLOAD_LEVELS = ['download', 'edit', 'delete'];
    private const DOCUMENT_UPDATE_LEVELS = ['edit', 'delete'];
    private const DOCUMENT_DELETE_LEVELS = ['delete'];

    private const FOLDER_VIEW_LEVELS = ['view', 'upload', 'edit', 'delete'];
    private const FOLDER_CREATE_LEVELS = ['upload', 'edit', 'delete'];
    private const FOLDER_DELETE_LEVELS = ['delete'];

    /**
     * Listar contenido de una carpeta
     */
    public function list(Request $request)
    {
        $this->abortUnlessAnyPermission(['view_documents', 'view_document_folders']);

        $folderId = $request->get('folder_id') === 'null' ? null : $request->get('folder_id');
        $currentFolder = null;

        if ($folderId) {
            $currentFolder = Folder::with(['parent', 'permissions', 'visibilityUsers'])->findOrFail($folderId);
            abort_unless($this->canViewFolder($currentFolder), 403, 'No autorizado para ver esta carpeta');
        }

        $folders = Folder::with(['permissions', 'visibilityUsers'])
            ->where('parent_id', $folderId)
            ->orderBy('name')
            ->get()
            ->filter(function (Folder $folder) {
                return $this->canViewFolder($folder);
            })
            ->values()
            ->map(function (Folder $folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id,
                    'created_by' => $folder->created_by,
                    'can_delete' => $this->canDeleteFolder($folder),
                ];
            });

        $files = Document::with(['permissions', 'visibilityUsers'])
            ->where('folder_id', $folderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function (Document $document) {
                return $this->canViewDocument($document);
            })
            ->values()
            ->map(function (Document $document) {
                return [
                    'id' => $document->id,
                    'folder_id' => $document->folder_id,
                    'name' => $document->name,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                    'created_by' => $document->created_by,
                    'created_at_formatted' => $document->created_at_formatted,
                    'readable_size' => $document->readable_size,
                    'preview_url' => $document->preview_url,
                    'download_url' => $document->download_url,
                    'can_preview' => $this->canPreviewDocument($document),
                    'can_download' => $this->canDownloadDocument($document),
                    'can_rename' => $this->canRenameDocument($document),
                    'can_delete' => $this->canDeleteDocument($document),
                ];
            });

        return response()->json([
            'folders' => $folders,
            'files' => $files,
            'current_folder' => $currentFolder ? [
                'id' => $currentFolder->id,
                'name' => $currentFolder->name,
                'parent_id' => $currentFolder->parent_id,
            ] : null,
            'can' => [
                'create_documents' => $this->hasAnyPermission(['create_documents']),
                'create_document_folders' => $this->hasAnyPermission(['create_document_folders', 'create_documents']),
            ],
        ]);
    }

    /**
     * Subir nuevo archivo
     */
    public function upload(Request $request)
    {
        $this->abortUnlessAnyPermission(['create_documents']);

        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
            'folder_id' => 'nullable',
            'visible_user_ids' => 'nullable|array',
            'visible_user_ids.*' => 'integer|exists:users,id',
        ]);

        $uploadedFile = $request->file('file');
        $folderId = $request->input('folder_id') == 'null' ? null : $request->input('folder_id');

        if ($folderId) {
            $folder = Folder::with(['permissions', 'visibilityUsers'])->findOrFail($folderId);
            abort_unless($this->canCreateInFolder($folder), 403, 'No autorizado para subir en esta carpeta');
        }

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

        $visibleUserIds = collect($request->input('visible_user_ids', []))
            ->filter(function ($id) {
                return (int) $id !== (int) Auth::id();
            })
            ->unique()
            ->values()
            ->all();

        if (!empty($visibleUserIds)) {
            $document->visibilityUsers()->sync($visibleUserIds);
        }

        return response()->json($document);
    }

    /**
     * Crear nueva carpeta
     */
    public function createFolder(Request $request)
    {
        $this->abortUnlessAnyPermission(['create_document_folders', 'create_documents']);

        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable',
            'visible_user_ids' => 'nullable|array',
            'visible_user_ids.*' => 'integer|exists:users,id',
        ]);

        $parentId = $request->input('parent_id') == 'null' ? null : $request->input('parent_id');

        if ($parentId) {
            $parentFolder = Folder::with(['permissions', 'visibilityUsers'])->findOrFail($parentId);
            abort_unless($this->canCreateInFolder($parentFolder), 403, 'No autorizado para crear carpetas aqui');
        }

        $folder = Folder::create([
            'name' => $request->name,
            'parent_id' => $parentId,
            'created_by' => Auth::id()
        ]);

        $visibleUserIds = collect($request->input('visible_user_ids', []))
            ->filter(function ($id) {
                return (int) $id !== (int) Auth::id();
            })
            ->unique()
            ->values()
            ->all();

        if (!empty($visibleUserIds)) {
            $folder->visibilityUsers()->sync($visibleUserIds);
        }

        return response()->json($folder);
    }

    /**
     * Ver/Previsualizar un documento
     */
    public function view($id)
    {
        $this->abortUnlessAnyPermission(['preview_documents', 'view_documents']);

        $document = Document::with(['permissions', 'visibilityUsers'])->findOrFail($id);

        abort_unless($this->canPreviewDocument($document), 403, 'No autorizado para previsualizar este archivo');
        
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
     * Descargar un documento
     */
    public function download($id)
    {
        $this->abortUnlessAnyPermission(['download_documents', 'view_documents']);

        $document = Document::with(['permissions', 'visibilityUsers'])->findOrFail($id);
        abort_unless($this->canDownloadDocument($document), 403, 'No autorizado para descargar este archivo');

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'Archivo no encontrado'], 404);
        }

        $safeName = preg_replace('/[^\w\s\-_\.]/', '', $document->name);
        if (empty($safeName)) {
            $safeName = 'document';
        }

        return Storage::disk('public')->download($document->file_path, $safeName);
    }

    /**
     * Renombrar un documento
     */
    public function rename(Request $request, $id)
    {
        $this->abortUnlessAnyPermission(['update_documents']);

        $request->validate(['name' => 'required|string|max:255']);

        $document = Document::with(['permissions', 'visibilityUsers'])->findOrFail($id);
        abort_unless($this->canRenameDocument($document), 403, 'No autorizado para renombrar este archivo');

        $document->name = $request->name;
        $document->save();

        return response()->json($document);
    }

    /**
     * Eliminar un documento
     */
    public function deleteFile($id)
    {
        $this->abortUnlessAnyPermission(['delete_documents']);

        $document = Document::with(['permissions', 'visibilityUsers'])->findOrFail($id);
        abort_unless($this->canDeleteDocument($document), 403, 'No autorizado para eliminar este archivo');
        
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
        $this->abortUnlessAnyPermission(['delete_document_folders', 'delete_documents']);

        $folder = Folder::with(['permissions', 'visibilityUsers'])->findOrFail($id);
        abort_unless($this->canDeleteFolder($folder), 403, 'No autorizado para eliminar esta carpeta');

        $folderIds = $this->collectFolderIds($folder);

        // Eliminar todos los archivos físicos dentro del arbol de carpetas
        $documents = Document::whereIn('folder_id', $folderIds)->get();
        foreach ($documents as $document) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
        }

        // La eliminacion en cascada borra subcarpetas y documentos de BD
        $folder->delete();

        return response()->json(['message' => 'Carpeta eliminada correctamente']);
    }

    private function abortUnlessAnyPermission(array $permissions): void
    {
        abort_unless($this->hasAnyPermission($permissions), 403, 'No autorizado');
    }

    private function isAppAdmin(): bool
    {
        $user = Auth::user();

        return $user && $user->isAppAdmin();
    }

    private function hasAnyPermission(array $permissions): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isAppAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function roleIds(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if (!$user->relationLoaded('roles')) {
            $user->load('roles:id');
        }

        return $user->roles->pluck('id')->all();
    }

    private function hasRoleAccess(Model $entity, array $levels): bool
    {
        $roleIds = $this->roleIds();

        if (empty($roleIds)) {
            return false;
        }

        $permissions = $entity->relationLoaded('permissions')
            ? $entity->permissions
            : $entity->permissions()->get();

        return $permissions
            ->whereIn('role_id', $roleIds)
            ->whereIn('access_level', $levels)
            ->isNotEmpty();
    }

    private function isManuallyVisibleToAuthUser(Model $entity): bool
    {
        if ((int) $entity->created_by === (int) Auth::id()) {
            return true;
        }

        $visibilityUsers = $entity->relationLoaded('visibilityUsers')
            ? $entity->visibilityUsers
            : $entity->visibilityUsers()->get();

        return $visibilityUsers->contains('id', Auth::id());
    }

    private function hasManualVisibilityRules(Model $entity): bool
    {
        $visibilityUsers = $entity->relationLoaded('visibilityUsers')
            ? $entity->visibilityUsers
            : $entity->visibilityUsers()->get();

        return $visibilityUsers->isNotEmpty();
    }

    private function hasEntityRules(Model $entity): bool
    {
        $permissions = $entity->relationLoaded('permissions')
            ? $entity->permissions
            : $entity->permissions()->get();

        return $permissions->isNotEmpty();
    }

    private function canViewDocument(Document $document): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['view_documents'])) {
            return false;
        }

        if ($this->isManuallyVisibleToAuthUser($document)) {
            return true;
        }

        if ($this->hasRoleAccess($document, self::DOCUMENT_VIEW_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($document) && !$this->hasEntityRules($document);
    }

    private function canPreviewDocument(Document $document): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['preview_documents', 'view_documents'])) {
            return false;
        }

        return $this->canViewDocument($document);
    }

    private function canDownloadDocument(Document $document): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['download_documents'])) {
            return false;
        }

        if ($this->isManuallyVisibleToAuthUser($document)) {
            return true;
        }

        if ($this->hasRoleAccess($document, self::DOCUMENT_DOWNLOAD_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($document) && !$this->hasEntityRules($document);
    }

    private function canRenameDocument(Document $document): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['update_documents'])) {
            return false;
        }

        if ((int) $document->created_by === (int) Auth::id()) {
            return true;
        }

        if ($this->hasRoleAccess($document, self::DOCUMENT_UPDATE_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($document) && !$this->hasEntityRules($document);
    }

    private function canDeleteDocument(Document $document): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['delete_documents'])) {
            return false;
        }

        if ((int) $document->created_by === (int) Auth::id()) {
            return true;
        }

        if ($this->hasRoleAccess($document, self::DOCUMENT_DELETE_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($document) && !$this->hasEntityRules($document);
    }

    private function canViewFolder(Folder $folder): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['view_document_folders', 'view_documents'])) {
            return false;
        }

        if ($this->isManuallyVisibleToAuthUser($folder)) {
            return true;
        }

        if ($this->hasRoleAccess($folder, self::FOLDER_VIEW_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($folder) && !$this->hasEntityRules($folder);
    }

    private function canCreateInFolder(Folder $folder): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['create_document_folders', 'create_documents'])) {
            return false;
        }

        if ((int) $folder->created_by === (int) Auth::id()) {
            return true;
        }

        if ($this->hasRoleAccess($folder, self::FOLDER_CREATE_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($folder) && !$this->hasEntityRules($folder);
    }

    private function canDeleteFolder(Folder $folder): bool
    {
        if ($this->isAppAdmin()) {
            return true;
        }

        if (!$this->hasAnyPermission(['delete_document_folders', 'delete_documents'])) {
            return false;
        }

        if ((int) $folder->created_by === (int) Auth::id()) {
            return true;
        }

        if ($this->hasRoleAccess($folder, self::FOLDER_DELETE_LEVELS)) {
            return true;
        }

        return !$this->hasManualVisibilityRules($folder) && !$this->hasEntityRules($folder);
    }

    private function collectFolderIds(Folder $folder): array
    {
        $allFolders = Folder::select(['id', 'parent_id'])->get()->groupBy('parent_id');

        $ids = [];
        $stack = [$folder->id];

        while (!empty($stack)) {
            $currentId = array_pop($stack);
            $ids[] = $currentId;

            /** @var Collection $children */
            $children = $allFolders->get($currentId, collect());
            foreach ($children as $child) {
                $stack[] = $child->id;
            }
        }

        return array_unique($ids);
    }
}