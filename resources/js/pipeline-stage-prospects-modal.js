export default function pipelineStageProspectsModal() {
    return {
        stageModalOpen: false,
        stageModalLoading: false,
        stageModalError: '',
        stageModalLabel: '',
        stageModalProspects: [],
        stageProspectsUrlTemplate: '',

        initStageProspectsModal(urlTemplate) {
            this.stageProspectsUrlTemplate = urlTemplate;
        },

        async openStageProspects(stageId, stageLabel) {
            this.stageModalOpen = true;
            this.stageModalLoading = true;
            this.stageModalError = '';
            this.stageModalLabel = stageLabel;
            this.stageModalProspects = [];

            const url = this.stageProspectsUrlTemplate.replace('__STAGE__', String(stageId));

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    throw new Error('Unable to load prospects for this stage.');
                }

                const payload = await response.json();
                this.stageModalLabel = payload.stage_label ?? stageLabel;
                this.stageModalProspects = payload.prospects ?? [];
            } catch (error) {
                this.stageModalError = error instanceof Error ? error.message : 'Unable to load prospects for this stage.';
            } finally {
                this.stageModalLoading = false;
            }
        },

        closeStageProspects() {
            this.stageModalOpen = false;
            this.stageModalLoading = false;
            this.stageModalError = '';
            this.stageModalProspects = [];
        },
    };
}
