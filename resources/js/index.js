export default ({ parentId }) => ({
    parentId,
    sortable: null,

    init() {
        this.sortable = new Sortable(this.$el, {
            group: 'nested',
            draggable: '[data-sortable-item]',
            handle: '[data-sortable-handle]',
            animation: 300,
            ghostClass: 'fi-sortable-ghost',
            dataIdAttr: 'data-sortable-item',
            onSort: () => {
                this.$wire.reorder(
                    this.sortable.toArray(),
                    this.parentId === 0 ? null : this.parentId,
                )
            },
        })
    },
})
