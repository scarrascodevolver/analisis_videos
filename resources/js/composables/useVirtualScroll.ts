import { ref, computed, type Ref } from 'vue';

interface VirtualScrollOptions {
    items: Ref<any[]>;
    itemHeight: number;
    containerHeight: number;
    bufferSize?: number;
}

export function useVirtualScroll(options: VirtualScrollOptions) {
    const { items, itemHeight, containerHeight, bufferSize = 5 } = options;

    const scrollTop = ref(0);

    const totalHeight = computed(() => items.value.length * itemHeight);

    const startIndex = computed(() => {
        const index = Math.floor(scrollTop.value / itemHeight);
        return Math.max(0, index - bufferSize);
    });

    const endIndex = computed(() => {
        const visibleItemCount = Math.ceil(containerHeight / itemHeight);
        const index = startIndex.value + visibleItemCount;
        return Math.min(items.value.length, index + bufferSize);
    });

    const visibleItems = computed(() => {
        return items.value.slice(startIndex.value, endIndex.value).map((item, i) => ({
            item,
            index: startIndex.value + i,
        }));
    });

    const offsetY = computed(() => startIndex.value * itemHeight);

    const containerStyle = computed(() => ({
        height: `${totalHeight.value}px`,
        position: 'relative' as const,
    }));

    const contentStyle = computed(() => ({
        transform: `translateY(${offsetY.value}px)`,
        position: 'absolute' as const,
        top: 0,
        left: 0,
        right: 0,
    }));

    function onScroll(event: Event) {
        const target = event.target as HTMLElement;
        scrollTop.value = target.scrollTop;
    }

    function scrollToIndex(index: number) {
        const targetScroll = index * itemHeight;
        scrollTop.value = Math.max(0, Math.min(targetScroll, totalHeight.value - containerHeight));
    }

    return {
        visibleItems,
        containerStyle,
        contentStyle,
        scrollToIndex,
        onScroll,
        totalHeight,
        startIndex,
        endIndex,
    };
}
