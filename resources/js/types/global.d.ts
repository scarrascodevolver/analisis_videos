/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

// Inertia page props shared from Laravel
interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string;
        };
    };
    organization: {
        id: number;
        name: string;
        slug: string;
        logo_path: string | null;
    } | null;
    flash: {
        success?: string;
        error?: string;
        warning?: string;
        info?: string;
    };
}
