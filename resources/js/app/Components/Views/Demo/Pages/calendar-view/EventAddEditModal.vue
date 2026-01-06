<template>
    <div class="custom-modal shadow d-none">
        <form ref="form" class="mb-0 min-height-100"
              :class="{'loading-opacity':preloader}"
              :data-url="selectedUrl?selectedUrl:'calendars'">
            <app-overlay-loader v-if="preloader"/>
            
            <div class="form-group">
                <app-input :placeholder="$t('enter_title')"
                           :required="true"
                           v-model="formData.title"/>
            </div>

            <div class="form-group">
                <app-input :placeholder="$t('enter_description')"
                           type="textarea"
                           v-model="formData.description"/>
            </div>

            <div class="form-group">
                <label class="mb-0">{{ $t('assigned_to') || 'Encargado' }}</label>
                <app-input type="search-select"
                           :placeholder="'Seleccione Encargado'"
                           :list="userList"
                           list-value-field="full_name"
                           v-model="formData.assigned_to"/>
            </div>

            <div class="form-group">
                <label class="mb-0">{{ $t('supervisor') || 'Supervisor' }}</label>
                <app-input type="search-select"
                           :placeholder="'Seleccione Supervisor'"
                           :list="userList"
                           list-value-field="full_name"
                           v-model="formData.supervisor_id"/>
            </div>

            <div class="form-group">
                <label class="mb-0">{{ $t('status') || 'Estado' }}</label>
                <app-input type="select"
                           :list="statusList"
                           v-model="formData.status"/>
            </div>

            <div class="form-group">
                <label class="mb-0">{{ $t('end_date') || 'Fecha de Finalización' }}</label>
                <app-input type="date"
                           v-model="formData.end_date"/>
            </div>

            <div v-if="selectedUrl" class="form-group">
                <app-input type="single-checkbox"
                           :list-value-field="$t('remove')"
                           v-model="deletedCheckbox"/>
            </div>

            <div class="d-flex justify-content-end event-modal-footer">
                <button type="button"
                        class="btn btn-secondary mr-2"
                        data-dismiss="modal"
                        @click.prevent="closeAddEditModal">
                    {{ $t('cancel') }}
                </button>
                <button v-if="deletedCheckbox"
                        type="button"
                        class="btn btn-danger"
                        @click.prevent="doDeleteEvent">
                    {{ $t('remove') }}
                </button>
                <button v-else
                        type="button"
                        class="btn btn-primary"
                        @click.prevent="submitEvents">
                    {{ selectedUrl ? $t('update') : $t('save') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import {FormMixin} from "../../../../../../core/mixins/form/FormMixin";
import AppLibrary from "../../../../../Helpers/AppLibrary";

export default {
    name: "EventAddEditModal",
    mixins: [FormMixin],
    extends: AppLibrary,
    props: ['eventData'],
    data() {
        return {
            preloader: false,
            deletedCheckbox: false,
            userList: [], // Lista para almacenar usuarios
            statusList: [
                {id: 'pending', value: 'Pendiente'},
                {id: 'completed', value: 'Completado'},
                {id: 'overdue', value: 'Atrasado'}
            ],
            formData: {
                title: '',
                description: '',
                assigned_to: null, // ID del usuario encargado
                supervisor_id: null, // ID del supervisor
                status: 'pending', // Estado por defecto
                end_date: '' // Fecha finalización
            },
        }
    },
    created() {
        this.getUsers(); // Cargar usuarios al iniciar
    },
    watch: {
        eventData: function (value) {
            this.setCustomModalPos(value);
        }
    },
    methods: {
        getUsers() {
            // Asumiendo que tienes axios disponible globalmente o via mixin
            // Ajusta 'axiosGet' si tu mixin usa otro nombre, o usa axios.get directamente
            this.axiosGet('/admin/auth/users')
                .then(response => {
                    console.log(response.data)
                    this.userList = response.data.data; 
                })
                .catch(error => {
                    console.error(error);
                    this.$toastr.e('Error cargando usuarios');
                });
        },
        doDeleteEvent() {
            if (this.deletedCheckbox) {
                this.preloader = true;
                this.axiosDelete(this.selectedUrl)
                    .then((response) => {
                        this.deletedCheckbox = false;
                        this.closeAddEditModal();
                        this.$toastr.s(response.data.message);
                        this.$emit('reloadCalendar');
                    }).catch(err => this.$toastr.e(err.data.message))
                    .finally(() => this.preloader = false);
            }
        },
        closeAddEditModal() {
            document.getElementsByClassName("custom-modal")[0].classList.add("d-none");
        },
        beforeSubmit() {
            this.preloader = true;
        },
        submitEvents() {
            if (!this.selectedUrl) {
                // Solo formateamos start/end si es creación nueva basada en selección de calendario
                // Si la API requiere end_date aparte, ya está en formData.end_date
                this.formData.start = this.getDateTimeFormatForBackend(this.eventData.start);
                this.formData.end = this.getDateTimeFormatForBackend(this.eventData.end);
            }
            this.save(this.formData);
        },
        afterSuccess(res) {
            this.$toastr.s(res.data.message);
        },
        afterFinalResponse() {
            this.preloader = false;
            this.closeAddEditModal();
            this.$emit('reloadCalendar');
        },
        afterError(res) {
            this.$toastr.e(res.data.message);
        },
        setCustomModalPos(arg) {
            this.deletedCheckbox = false;
            this.fieldStatus = {
                isSubmit: false
            };
            
            // Lógica para rellenar datos en EDICIÓN
            if (this.selectedUrl) {
                this.formData.title = arg.event._def.title;
                
                // Extraer propiedades extendidas (custom fields)
                const props = arg.event._def.extendedProps;
                
                this.formData.description = props.description;
                this.formData.assigned_to = props.assigned_to; // Asegúrate que el backend devuelva este campo
                this.formData.supervisor_id = props.supervisor_id;
                this.formData.status = props.status || 'pending';
                this.formData.end_date = props.end_date; // Formato YYYY-MM-DD
            } else {
                // Lógica para CREACIÓN
                this.formData.title = '';
                this.formData.description = '';
                this.formData.assigned_to = null;
                this.formData.supervisor_id = null;
                this.formData.status = 'pending';
                this.formData.end_date = '';
            }

            // Posicionamiento del Modal (Código original intacto)
            let customModalDom = document.getElementsByClassName("custom-modal")[0];
            let posLimit = arg.jsEvent.view.innerWidth / 2;
            customModalDom.classList.remove("d-none");
            
            let w = 510, h = 200;
            // Aumentamos h (altura estimada) un poco porque el modal ahora es más alto
            h = 400; 

            if (arg.jsEvent.clientX > posLimit) {
                customModalDom.style.left =
                    (arg.jsEvent.clientX - w) + "px";
            } else {
                customModalDom.style.left = arg.jsEvent.clientX + "px";
            }
            if (arg.jsEvent.clientY > (window.innerHeight - 300)) {
                customModalDom.style.top = (arg.jsEvent.pageY - h) + "px";
            } else {
                customModalDom.style.top = arg.jsEvent.pageY + "px";
            }
        },
    },
}
</script>