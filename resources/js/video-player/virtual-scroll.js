/**
 * Virtual Scroll Manager
 *
 * Efficiently renders large lists (800+ items) by only rendering visible items.
 * Reduces DOM elements from 4,000+ to ~100 for massive performance improvement.
 */

export class VirtualScrollManager {
    constructor(container, items, renderItem, itemHeight = 60) {
        this.container = container;
        this.items = items;
        this.renderItem = renderItem;
        this.itemHeight = itemHeight;

        // Configuration
        this.visibleCount = Math.ceil(container.clientHeight / itemHeight) + 5; // +5 buffer
        this.scrollTop = 0;
        this.startIndex = 0;
        this.endIndex = this.visibleCount;

        // Create virtual scroll structure
        this.viewport = null;
        this.content = null;
        this.spacerTop = null;
        this.spacerBottom = null;

        this.init();
    }

    /**
     * Initialize virtual scroll structure
     */
    init() {
        // Clear container
        this.container.innerHTML = '';

        // Create viewport wrapper
        this.viewport = document.createElement('div');
        this.viewport.style.cssText = `
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        `;

        // Create content wrapper
        this.content = document.createElement('div');
        this.content.style.position = 'relative';

        // Create spacers to maintain scroll height
        this.spacerTop = document.createElement('div');
        this.spacerBottom = document.createElement('div');

        // Assemble structure
        this.content.appendChild(this.spacerTop);
        this.content.appendChild(this.spacerBottom);
        this.viewport.appendChild(this.content);
        this.container.appendChild(this.viewport);

        // Setup scroll listener
        this.viewport.addEventListener('scroll', () => this.onScroll());

        // Initial render
        this.update();
    }

    /**
     * Update items and re-render
     */
    setItems(items) {
        this.items = items;
        this.update();
    }

    /**
     * Handle scroll event
     */
    onScroll() {
        const scrollTop = this.viewport.scrollTop;

        // Only update if scrolled significantly (debounce rendering)
        if (Math.abs(scrollTop - this.scrollTop) < this.itemHeight / 2) {
            return;
        }

        this.scrollTop = scrollTop;
        this.update();
    }

    /**
     * Update visible range and render
     */
    update() {
        if (this.items.length === 0) {
            this.renderEmpty();
            return;
        }

        // Calculate visible range
        const scrollTop = this.viewport.scrollTop;
        this.startIndex = Math.max(0, Math.floor(scrollTop / this.itemHeight) - 2); // -2 buffer
        this.endIndex = Math.min(
            this.items.length,
            this.startIndex + this.visibleCount + 4 // +4 buffer (2 top, 2 bottom)
        );

        // Update spacers
        const topHeight = this.startIndex * this.itemHeight;
        const bottomHeight = (this.items.length - this.endIndex) * this.itemHeight;

        this.spacerTop.style.height = `${topHeight}px`;
        this.spacerBottom.style.height = `${bottomHeight}px`;

        // Render visible items
        this.render();
    }

    /**
     * Render visible items
     */
    render() {
        // Get visible slice
        const visibleItems = this.items.slice(this.startIndex, this.endIndex);

        // Clear previous content (between spacers)
        while (this.content.children.length > 2) {
            this.content.children[1].remove();
        }

        // Render each visible item
        visibleItems.forEach((item, index) => {
            const itemElement = this.renderItem(item, this.startIndex + index);
            this.content.insertBefore(itemElement, this.spacerBottom);
        });

        console.log(`Virtual Scroll: Rendered ${visibleItems.length} items (${this.startIndex}-${this.endIndex} of ${this.items.length})`);
    }

    /**
     * Render empty state
     */
    renderEmpty() {
        this.content.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-video-slash"></i> Sin clips aún
            </div>
        `;
    }

    /**
     * Scroll to specific item index
     */
    scrollToIndex(index) {
        const scrollTop = index * this.itemHeight;
        this.viewport.scrollTop = scrollTop;
    }

    /**
     * Get current visible range
     */
    getVisibleRange() {
        return {
            start: this.startIndex,
            end: this.endIndex,
            total: this.items.length
        };
    }

    /**
     * Destroy and cleanup
     */
    destroy() {
        this.viewport.removeEventListener('scroll', this.onScroll);
        this.container.innerHTML = '';
    }
}
