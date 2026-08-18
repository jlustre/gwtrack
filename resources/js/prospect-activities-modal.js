export default function prospectActivitiesModal() {

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';



    const toLocalInput = (isoValue) => {

        if (! isoValue) {

            return '';

        }



        const date = new Date(isoValue);



        if (Number.isNaN(date.getTime())) {

            return '';

        }



        const offset = date.getTimezoneOffset();

        const local = new Date(date.getTime() - offset * 60 * 1000);



        return local.toISOString().slice(0, 16);

    };



    const defaultForm = (pipelineStageId = '') => ({

        id: null,

        activity_type: 'phone_call',

        pipeline_stage_id: pipelineStageId !== null && pipelineStageId !== undefined && pipelineStageId !== ''

            ? String(pipelineStageId)

            : '',

        subject: '',

        notes: '',

        occurred_at: toLocalInput(new Date().toISOString()),

        duration_minutes: '',

        outcome: '',

        next_action: '',

        next_follow_up_at: '',

    });



    return {

        open: false,

        loading: false,

        saving: false,

        prospect: null,

        activities: [],

        pipelineStages: [],

        currentPipelineStageId: '',

        formMode: 'create',

        form: defaultForm(),

        error: null,

        activityTypes: {},

        outcomeSuggestions: [],



        init() {

            this.activityTypes = this.$el.dataset.activityTypes

                ? JSON.parse(this.$el.dataset.activityTypes)

                : {};



            this.outcomeSuggestions = this.$el.dataset.outcomeSuggestions

                ? JSON.parse(this.$el.dataset.outcomeSuggestions)

                : [];

        },



        openFor(prospect) {

            this.prospect = prospect;

            this.open = true;

            this.error = null;

            this.pipelineStages = [];

            this.currentPipelineStageId = '';

            this.resetForm();

            this.loadActivities();

        },



        close() {

            this.open = false;

            this.prospect = null;

            this.activities = [];

            this.pipelineStages = [];

            this.currentPipelineStageId = '';

            this.error = null;

            this.resetForm();

        },



        async loadActivities() {

            if (! this.prospect?.id) {

                return;

            }



            this.loading = true;

            this.error = null;



            try {

                const response = await fetch(`/team/prospects/records/${this.prospect.id}/activities`, {

                    headers: {

                        Accept: 'application/json',

                        'X-Requested-With': 'XMLHttpRequest',

                    },

                });



                if (! response.ok) {

                    throw new Error('Failed to load activities.');

                }



                const data = await response.json();

                this.activities = data.activities ?? [];

                this.pipelineStages = data.pipeline_stages ?? [];

                this.currentPipelineStageId = data.current_pipeline_stage_id

                    ? String(data.current_pipeline_stage_id)

                    : '';



                if (data.activity_types && Object.keys(data.activity_types).length > 0) {

                    this.activityTypes = data.activity_types;

                }



                if (Array.isArray(data.outcome_suggestions) && data.outcome_suggestions.length > 0) {

                    this.outcomeSuggestions = data.outcome_suggestions;

                }



                if (this.formMode === 'create') {

                    this.form.pipeline_stage_id = this.currentPipelineStageId;

                }

            } catch (error) {

                this.error = error.message || 'Could not load activities.';

            } finally {

                this.loading = false;

            }

        },



        resetForm() {

            this.formMode = 'create';

            this.form = defaultForm(this.currentPipelineStageId);

        },



        editActivity(activity) {

            this.formMode = 'edit';

            this.form = {

                id: activity.id,

                activity_type: activity.activity_type,

                pipeline_stage_id: activity.pipeline_stage_id

                    ? String(activity.pipeline_stage_id)

                    : this.currentPipelineStageId,

                subject: activity.subject ?? '',

                notes: activity.notes ?? '',

                occurred_at: toLocalInput(activity.occurred_at),

                duration_minutes: activity.duration_minutes ?? '',

                outcome: activity.outcome ?? '',

                next_action: activity.next_action ?? '',

                next_follow_up_at: toLocalInput(activity.next_follow_up_at),

            };

        },



        buildPayload() {

            return {

                ...this.form,

                pipeline_stage_id: this.form.pipeline_stage_id

                    ? Number(this.form.pipeline_stage_id)

                    : null,

                duration_minutes: this.form.duration_minutes

                    ? Number(this.form.duration_minutes)

                    : null,

            };

        },



        async saveActivity() {

            if (! this.prospect?.id) {

                return;

            }



            this.saving = true;

            this.error = null;



            const url = this.formMode === 'edit' && this.form.id

                ? `/team/prospects/records/${this.prospect.id}/activities/${this.form.id}`

                : `/team/prospects/records/${this.prospect.id}/activities`;



            const method = this.formMode === 'edit' && this.form.id ? 'PATCH' : 'POST';



            try {

                const response = await fetch(url, {

                    method,

                    headers: {

                        Accept: 'application/json',

                        'Content-Type': 'application/json',

                        'X-CSRF-TOKEN': csrfToken,

                        'X-Requested-With': 'XMLHttpRequest',

                    },

                    body: JSON.stringify(this.buildPayload()),

                });



                const data = await response.json().catch(() => ({}));



                if (! response.ok) {

                    const validationMessage = data.errors

                        ? Object.values(data.errors).flat()[0]

                        : null;



                    throw new Error(validationMessage || data.message || 'Could not save activity.');

                }



                if (this.formMode === 'edit' && this.form.id) {

                    const index = this.activities.findIndex((item) => item.id === this.form.id);



                    if (index >= 0) {

                        this.activities.splice(index, 1, data.activity);

                    }

                } else {

                    this.activities.unshift(data.activity);

                }



                if (data.current_pipeline_stage_id) {

                    this.currentPipelineStageId = String(data.current_pipeline_stage_id);

                }



                this.resetForm();

            } catch (error) {

                this.error = error.message || 'Could not save activity.';

            } finally {

                this.saving = false;

            }

        },



        async deleteActivity(activity) {

            if (! this.prospect?.id || ! activity?.id) {

                return;

            }



            if (! window.confirm('Delete this activity?')) {

                return;

            }



            this.error = null;



            try {

                const response = await fetch(`/team/prospects/records/${this.prospect.id}/activities/${activity.id}`, {

                    method: 'DELETE',

                    headers: {

                        Accept: 'application/json',

                        'X-CSRF-TOKEN': csrfToken,

                        'X-Requested-With': 'XMLHttpRequest',

                    },

                });



                if (! response.ok) {

                    const data = await response.json();

                    throw new Error(data.message || 'Could not delete activity.');

                }



                this.activities = this.activities.filter((item) => item.id !== activity.id);



                if (this.form.id === activity.id) {

                    this.resetForm();

                }

            } catch (error) {

                this.error = error.message || 'Could not delete activity.';

            }

        },

    };

}

