<template>
    <modal :modal-id="userAndRoles.users.addUserModalId"
           :title="$t('add_user')"
           :preloader="preloader"
           :modal-scroll="false"
           @submit="submit"
           @close-modal="closeModal">
        <template slot="body">
            <app-overlay-loader v-if="preloader"/>
            <form ref="form" 
                  data-url='admin/auth/users'
                  :class="{'loading-opacity': preloader}">
                <div class="form-group row align-items-center">
                    <label for="firstName" class="col-sm-3 mb-0">
                        {{ $t('first_name') }}
                    </label>
                    <app-input id="firstName"
                               class="col-sm-9"
                               type="text"
                               v-model="user.first_name"
                               :placeholder="$t('enter_first_name')"
                               :required="true"/>
                </div>
                <div class="form-group row align-items-center">
                    <label for="lastName" class="col-sm-3 mb-0">
                        {{ $t('last_name') }}
                    </label>
                    <app-input id="lastName"
                               class="col-sm-9"
                               type="text"
                               v-model="user.last_name"
                               :placeholder="$t('enter_last_name')"/>
                </div>
                <div class="form-group row align-items-center">
                    <label for="userEmail" class="col-sm-3 mb-0">
                        {{ $t('email') }}
                    </label>
                    <app-input id="userEmail"
                               class="col-sm-9"
                               type="email"
                               v-model="user.email"
                               :placeholder="$t('enter_user_email')"/>
                </div>
                <div class="form-group row align-items-center mb-0">
                    <label for="roles" class="col-sm-3 mb-0">
                        {{ $t('role') }}
                    </label>
                    <app-input id="roles"
                               class="col-sm-9"
                               type="multi-select"
                               :list="roleLists"
                               list-value-field="name"
                               :isAnimatedDropdown="true"
                               v-model="user.roles"
                               :required="true"/>
                </div>
            </form>
        </template>
    </modal>
</template>

<script>
    import {FormMixin} from '../../../../../core/mixins/form/FormMixin';
    import {ModalMixin} from '../../../../Mixins/ModalMixin';
    import {UserAndRoleMixin} from '../Mixins/UserAndRoleMixin';
    import * as actions from '../../../../Config/ApiUrl';

    export default {
        name: "AddUserModal",
        mixins: [FormMixin, ModalMixin, UserAndRoleMixin],
        data() {
            return {
                user: {
                    first_name: '',
                    last_name: '',
                    email: '',
                    password: '',
                    roles: []
                },
                roleLists: []
            }
        },
        created(){
            this.getRoles();
        },
        methods: {
            submit() {
                // Generate default password: first name + last name without spaces
                const firstName = (this.user.first_name || '').trim();
                const lastName = (this.user.last_name || '').trim();
                this.user.password = firstName + lastName;
                
                this.save(this.user);
            },

            afterSuccess(res) {
                this.$toastr.s(res.data.message);
                this.reLoadTable();
            },
            
            getRoles(){
                let url = actions.ROLES;

                this.preloader = true;

                this.axiosGet(url).then(response => {
                    this.roleLists = response.data.data;
                }).catch(({response}) => {
                    // Handle error silently
                }).finally(() => {
                    this.preloader = false;
                });
            }
        }
    }
</script>
