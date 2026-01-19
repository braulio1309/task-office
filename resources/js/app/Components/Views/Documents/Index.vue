<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="'Gestor de Documentos'" :directory="breadcrumbs" :icon="'folder'"/>
        
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <button v-if="currentFolder" @click="navigateUp" class="btn btn-outline-secondary btn-sm mr-2">
                                <i class="fa fa-arrow-up"></i> Atrás
                            </button>
                            <h5 class="m-0 text-muted">{{ currentFolder ? currentFolder.name : 'Carpeta Raíz' }}</h5>
                        </div>
                        <div>
                            <button v-if="can('create_documents')" @click="openFolderModal" class="btn btn-outline-primary btn-sm mr-2">
                                <i class="fa fa-folder-plus"></i> Nueva Carpeta
                            </button>
                            <button v-if="can('create_documents')" @click="openUploadModal" class="btn btn-primary btn-sm">
                                <i class="fa fa-cloud-upload"></i> Subir Archivo
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>

                        <div v-else class="row">
                            <div v-if="folders.length === 0 && files.length === 0" class="col-12 text-center py-5 text-muted">
                                <i class="fa fa-folder-open fa-3x mb-3"></i>
                                <p>Esta carpeta está vacía</p>
                            </div>

                            <div v-for="folder in folders" :key="'f-'+folder.id" class="col-6 col-md-3 col-lg-2 mb-4" @click="navigateTo(folder.id)" style="cursor: pointer;">
                                <div class="card h-100 border-0 bg-light folder-card text-center p-3 hover-shadow position-relative">
                                    <i class="fa fa-folder fa-3x text-warning mb-2"></i>
                                    <h6 class="text-truncate mb-0 font-weight-bold" :title="folder.name">{{ folder.name }}</h6>
                                    
                                    <div v-if="can('delete_documents')" class="position-absolute" style="top:5px; right:5px;">
                                        <button @click.stop="deleteFolder(folder)" class="btn btn-sm btn-link text-danger p-0" title="Eliminar Carpeta">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-for="file in files" :key="'d-'+file.id" class="col-6 col-md-3 col-lg-2 mb-4">
                                <div class="card h-100 border text-center p-3 hover-card position-relative file-card">
                                    
                                    <div class="file-info mb-2">
                                        <i :class="getFileIcon(file.mime_type)" class="fa-3x mb-2"></i>
                                        <h6 class="text-truncate mb-1 text-dark" :title="file.name">{{ file.name }}</h6>
                                        <small class="text-muted d-block">{{ file.readable_size }}</small>
                                        <small class="text-muted text-size-10">{{ file.created_at_formatted }}</small>
                                    </div>

                                    <div class="file-actions d-flex justify-content-center align-items-center">
                                        
                                        <button @click="previewFile(file)" class="btn btn-sm btn-primary mr-1 icon-btn" title="Vista Previa">
                                            <i class="fa fa-eye"></i>
                                        </button>

                                        <a :href="file.download_url" target="_blank" class="btn btn-sm btn-success mr-1 icon-btn" title="Descargar">
                                            <i class="fa fa-download"></i>
                                        </a>

                                        <button v-if="can('update_documents')" @click="openRenameModal(file)" class="btn btn-sm btn-info mr-1 icon-btn text-white" title="Renombrar">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <button v-if="can('delete_documents')" @click="deleteFile(file)" class="btn btn-sm btn-danger icon-btn" title="Eliminar">
                                            <i class="fa fa-trash"></i>
                                        </button>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Subir Documento</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="customFile" @change="handleFileUpload">
                            <label class="custom-file-label" for="customFile">{{ fileName || 'Seleccionar archivo...' }}</label>
                        </div>
                        <div v-if="uploading" class="progress mt-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">Subiendo...</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" @click="uploadFile" :disabled="!fileToUpload || uploading">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="folderModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Carpeta</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" v-model="newFolderName" class="form-control" placeholder="Nombre de la carpeta">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" @click="createFolder">Crear</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="renameModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Renombrar Archivo</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <label>Nuevo nombre:</label>
                        <input type="text" v-model="renameData.newName" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" @click="renameFile">Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Vista Previa de Documentos -->
        <div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vista Previa: {{ previewData.name }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body p-0" style="min-height: 500px; max-height: 80vh; overflow: auto;">
                        <div v-if="previewLoading" class="d-flex justify-content-center align-items-center" style="height: 500px;">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                        
                        <!-- Vista previa para imágenes -->
                        <div v-else-if="isImage(previewData.mime_type)" class="text-center p-3">
                            <img :src="previewData.preview_url" class="img-fluid" :alt="previewData.name" style="max-width: 100%; max-height: 70vh;">
                        </div>
                        
                        <!-- Vista previa para PDFs -->
                        <div v-else-if="isPDF(previewData.mime_type)" style="height: 70vh;">
                            <iframe :src="previewData.preview_url" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                        
                        <!-- Mensaje para otros tipos de archivos -->
                        <div v-else class="text-center p-5">
                            <i :class="getFileIcon(previewData.mime_type)" class="fa-5x mb-3"></i>
                            <h5>{{ previewData.name }}</h5>
                            <p class="text-muted">Tipo: {{ previewData.mime_type }}</p>
                            <p class="text-muted">Tamaño: {{ previewData.readable_size }}</p>
                            <p class="text-muted mt-3">Este tipo de archivo no puede ser previsualizado en el navegador.</p>
                            <a :href="previewData.download_url" target="_blank" class="btn btn-primary mt-3">
                                <i class="fa fa-download mr-2"></i>Descargar Archivo
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a :href="previewData.download_url" target="_blank" class="btn btn-success mr-auto">
                            <i class="fa fa-download mr-2"></i>Descargar
                        </a>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import axios from "axios";
// IMPORTANTE: Verifica que esta ruta sea correcta según tu estructura de carpetas
import CoreLibrary from "../../../../../js/core/helpers/CoreLibrary"; 

export default {
    name: "Documents",
    extends: CoreLibrary, 
    data() {
        return {
            loading: false,
            uploading: false,
            previewLoading: false,
            
            // Estado de Carpetas y Archivos
            currentFolderId: null,
            currentFolder: null,
            folders: [],
            files: [],
            breadcrumbs: ['Documentos'],
            
            // Datos para Formularios
            fileToUpload: null,
            fileName: '',
            newFolderName: '',
            renameData: {
                id: null,
                newName: ''
            },
            previewData: {
                id: null,
                name: '',
                mime_type: '',
                preview_url: '',
                download_url: '',
                readable_size: ''
            }
        };
    },
    async mounted() {
        await this.fetchContent();
    },
    methods: {
        // --- SOLUCIÓN DEL ERROR: FUNCIÓN DE PERMISOS LOCAL ---
        // Si tu Mixin no trae 'can', usamos esta por defecto que devuelve TRUE (todo permitido)
        // para que no de error. Luego puedes ajustar la lógica real.
        can(permissionName) {
            // Si existe una función global de permisos, úsala:
            if (this.$store && this.$store.getters && this.$store.getters.can) {
                return this.$store.getters.can(permissionName);
            }
            // De lo contrario, retorna true para que no se rompa la vista
            return true; 
        },

        // --- 1. OBTENER CONTENIDO (GET) ---
        async fetchContent(folderId = null) {
            this.loading = true;
            let url = 'documents/list';
            if (folderId) url += `?folder_id=${folderId}`;

            try {
                const response = await axios.get(url);
                this.folders = response.data.folders || [];
                this.files = response.data.files || [];
                
                this.currentFolder = response.data.current_folder;
                this.currentFolderId = response.data.current_folder ? response.data.current_folder.id : null;
                
                this.updateBreadcrumbs();
            } catch (error) {
                console.error(error);
                this.$toastr.e("Error al cargar documentos");
            } finally {
                this.loading = false;
            }
        },

        // --- 2. SUBIR ARCHIVO (POST) ---
        async uploadFile() {
            if (!this.fileToUpload) return;
            
            this.uploading = true;
            let formData = new FormData();
            formData.append('file', this.fileToUpload);
            if (this.currentFolderId) {
                formData.append('folder_id', this.currentFolderId);
            }

            try {
                await axios.post('documents/upload', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.$toastr.s("Archivo subido correctamente");
                $('#uploadModal').modal('hide');
                await this.fetchContent(this.currentFolderId);
            } catch (error) {
                let msg = error.response?.data?.message || "Error al subir archivo";
                this.$toastr.e(msg);
            } finally {
                this.uploading = false;
                this.fileToUpload = null;
                this.fileName = '';
            }
        },

        // --- 3. CREAR CARPETA (POST) ---
        async createFolder() {
            if (!this.newFolderName) return;
            try {
                await axios.post('documents/folder', {
                    name: this.newFolderName,
                    parent_id: this.currentFolderId
                });
                this.$toastr.s("Carpeta creada");
                $('#folderModal').modal('hide');
                this.newFolderName = '';
                await this.fetchContent(this.currentFolderId);
            } catch (error) {
                this.$toastr.e(error.response?.data?.message || "Error al crear carpeta");
            }
        },

        // --- 4. RENOMBRAR ARCHIVO (POST/PUT) ---
        openRenameModal(file) {
            this.renameData.id = file.id;
            this.renameData.newName = file.name;
            $('#renameModal').modal('show');
        },
        async renameFile() {
             try {
                await axios.post(`documents/file/${this.renameData.id}/rename`, { 
                    name: this.renameData.newName 
                });
                this.$toastr.s('Nombre actualizado');
                $('#renameModal').modal('hide');
                this.fetchContent(this.currentFolderId);
             } catch (error) {
                this.$toastr.e('Error al renombrar');
             }
        },

        // --- 5. ELIMINAR (DELETE) ---
        async deleteFile(file) {
            if(!confirm('¿Estás seguro de eliminar este archivo?')) return;
            try {
                await axios.delete(`documents/file/${file.id}`);
                this.$toastr.s('Archivo eliminado');
                this.fetchContent(this.currentFolderId);
            } catch (e) {
                this.$toastr.e('Error al eliminar');
            }
        },
        async deleteFolder(folder) {
             if(!confirm('¿Eliminar carpeta y todo su contenido?')) return;
             try {
                await axios.delete(`documents/folder/${folder.id}`);
                this.$toastr.s('Carpeta eliminada');
                this.fetchContent(this.currentFolderId);
             } catch (e) {
                this.$toastr.e('Error al eliminar');
             }
        },

        // --- UTILIDADES DE INTERFAZ ---
        handleFileUpload(event) {
            this.fileToUpload = event.target.files[0];
            this.fileName = this.fileToUpload ? this.fileToUpload.name : '';
        },
        openUploadModal() {
            $('#uploadModal').modal('show');
            this.fileToUpload = null;
            this.fileName = '';
        },
        openFolderModal() {
            this.newFolderName = '';
            $('#folderModal').modal('show');
        },
        navigateUp() {
            if (this.currentFolder && this.currentFolder.parent_id) {
                this.fetchContent(this.currentFolder.parent_id);
            } else {
                this.fetchContent(null);
            }
        },
        navigateTo(folderId) {
            this.fetchContent(folderId);
        },
        updateBreadcrumbs() {
             this.breadcrumbs = ['Documentos'];
             if(this.currentFolder) this.breadcrumbs.push(this.currentFolder.name);
        },
        getFileIcon(mime) {
            if (!mime) return 'fa fa-file text-secondary';
            if (mime.includes('image')) return 'fa fa-file-image text-purple';
            if (mime.includes('pdf')) return 'fa fa-file-pdf text-danger';
            if (mime.includes('spreadsheet') || mime.includes('excel') || mime.includes('sheet')) return 'fa fa-file-excel text-success';
            if (mime.includes('word') || mime.includes('document')) return 'fa fa-file-word text-primary';
            if (mime.includes('video')) return 'fa fa-file-video text-warning';
            if (mime.includes('audio')) return 'fa fa-file-audio text-info';
            if (mime.includes('zip') || mime.includes('compressed')) return 'fa fa-file-archive text-dark';
            return 'fa fa-file text-secondary';
        },

        // --- PREVIEW DE DOCUMENTOS ---
        previewFile(file) {
            this.previewData = {
                id: file.id,
                name: file.name,
                mime_type: file.mime_type,
                preview_url: file.preview_url,
                download_url: file.download_url,
                readable_size: file.readable_size
            };
            $('#previewModal').modal('show');
        },

        isImage(mimeType) {
            return mimeType && mimeType.includes('image');
        },

        isPDF(mimeType) {
            return mimeType && mimeType.includes('pdf');
        }
    }
}
</script>

<style scoped>
/* Estilos Base para Tarjetas */
.folder-card, .file-card {
    transition: all 0.3s ease;
}

/* Efecto Hover en Tarjetas */
.hover-shadow:hover, .file-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-3px);
}

.file-card:hover {
    border-color: var(--brand-color) !important; /* Color primario al borde */
}

/* --- LOGICA DE BOTONERA DESLIZANTE --- */
.file-card {
    overflow: hidden; /* Importante para ocultar la botonera cuando está abajo */
}

.file-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(255, 255, 255, 0.95); /* Fondo blanco semitransparente */
    padding: 10px 5px;
    border-top: 1px solid #eee;
    
    /* Estado inicial: Oculto hacia abajo */
    transform: translateY(100%); 
    transition: transform 0.3s ease-in-out;
    z-index: 10;
}

/* Estado Hover: Se desliza hacia arriba */
.file-card:hover .file-actions {
    transform: translateY(0);
}

/* Estilos de botones pequeños */
.icon-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.text-size-10 { font-size: 10px; }
.text-purple { color: #6f42c1; }
</style>