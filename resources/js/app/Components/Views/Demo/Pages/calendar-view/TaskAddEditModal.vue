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
                <label class="mb-0">{{ $t('stage') || 'Etapa' }}</label>
                <app-input type="search-select"
                           :placeholder="'Seleccione Etapa'"
                           :list="stageList"
                           list-value-field="name"
                           :required="true"
                           v-model="formData.stage_id"/>
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
                           v-model="formData.supervisor"/>
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
import {STAGES} from "../../../../../Config/ApiUrl";

export default {
    name: "TaskAddEditModal",
    mixins: [FormMixin],
    extends: AppLibrary,
    props: ['taskData', 'selectedUrl'],
    data() {
        return {
            preloader: false,
            deletedCheckbox: false,
            userList: [],
            stageList: [],
            statusList: [
                {id: 'pending', value: 'Pendiente'},
                {id: 'completed', value: 'Completado'},
                {id: 'overdue', value: 'Atrasado'}
            ],
            formData: {
                title: '',
                stage_id: null,
                assigned_to: null,
                supervisor: null,
                status: 'pending',
                end_date: '',
                owner_name: ''
            },
        }
    },
    created() {
        this.getUsers();
        this.getStages();
    },
    watch: {
        taskData: function (value) {
            this.setCustomModalPos(value);
        }
    },
    methods: {
        getUsers() {
            this.axiosGet('/admin/auth/users')
                .then(response => {
                    this.userList = response.data.data; 
                })
                .catch(error => {
                    console.error(error);
                    this.$toastr.e(this.$t('error_loading_users') || 'Error loading users');
                });
        },
        getStages() {
            this.axiosGet(STAGES)
                .then(response => {
                    this.stageList = response.data; 
                })
                .catch(error => {
                    console.error(error);
                    this.$toastr.e(this.$t('error_loading_stages') || 'Error loading stages');
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
            // Validate required fields
            if (!this.formData.stage_id) {
                this.$toastr.e(this.$t('please_select_stage') || 'Please select a stage');
                return;
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
            
            // Edit mode
            if (this.selectedUrl) {
                this.formData.title = arg.event._def.title;
                
                const props = arg.event._def.extendedProps;
                
                this.formData.stage_id = props.stage_id;
                this.formData.assigned_to = props.assigned_to;
                this.formData.supervisor = props.supervisor_id;
                this.formData.status = props.status || 'pending';
                this.formData.end_date = props.end_date;
            } else {
                // Create mode
                this.formData.title = '';
                this.formData.stage_id = null;
                this.formData.assigned_to = null;
                this.formData.supervisor = null;
                this.formData.status = 'pending';
                
                // Set end_date from selected date
                if (arg.start) {
                    this.formData.end_date = this.getDateFormatForBackend(arg.start);
                }
            }

            // Modal positioning
            let customModalDom = document.getElementsByClassName("custom-modal")[0];
            let posLimit = arg.jsEvent.view.innerWidth / 2;
            customModalDom.classList.remove("d-none");
            
            let w = 510, h = 450;

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