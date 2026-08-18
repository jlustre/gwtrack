export default function prospectKanbanBoard() {
    return {
        draggingProspectId: null,
        dragOverStageId: null,

        startDrag(event, prospectId) {
            this.draggingProspectId = prospectId;
            event.dataTransfer.setData('text/plain', prospectId);
            event.dataTransfer.setData('application/x-prospect-id', prospectId);
            event.dataTransfer.effectAllowed = 'move';
        },

        endDrag() {
            this.draggingProspectId = null;
            this.dragOverStageId = null;
        },

        dragOverColumn(event, stageId) {
            event.preventDefault();
            this.dragOverStageId = stageId;
        },

        dragLeaveColumn(event, stageId) {
            if (event.currentTarget.contains(event.relatedTarget)) {
                return;
            }

            if (this.dragOverStageId === stageId) {
                this.dragOverStageId = null;
            }
        },

        dropOnColumn(event, stageId) {
            event.preventDefault();

            const prospectId =
                event.dataTransfer.getData('text/plain')
                || event.dataTransfer.getData('application/x-prospect-id')
                || this.draggingProspectId;

            this.dragOverStageId = null;
            this.draggingProspectId = null;

            if (! prospectId || ! stageId) {
                return;
            }

            this.$wire.moveProspect(prospectId, stageId);
        },
    };
}
