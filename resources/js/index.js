export default ({ parentId, maxDepth }) => ({
    parentId,
    maxDepth,
    sortable: null,

    init() {
        this.sortable = new Sortable(this.$el, {
            group: 'nested',
            draggable: '[data-sortable-item]',
            handle: '[data-sortable-handle]',
            animation: 300,
            ghostClass: 'fi-sortable-ghost',
            dataIdAttr: 'data-sortable-item',
            onMove: ({ dragged, from, to }) => this.canDrop(dragged, from, to),
            onSort: () => {
                this.$wire.reorder(this.sortable.toArray(), this.parentId)
            },
        })
    },

    // Mirrors MenuHierarchy::canMove() so invalid drops are refused while
    // dragging; the server still validates every move on its own.
    canDrop(item, from, to) {
        if (this.maxDepth === null || from === to) {
            return true
        }

        const depth = Number(to.dataset.sortableDepth) + Number(item.dataset.sortableHeight)

        return depth <= this.maxDepth
    },
})
