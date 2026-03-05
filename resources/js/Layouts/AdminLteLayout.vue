<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import ToastContainer from '@/Components/video-player/ui/ToastContainer.vue';

const page = usePage();
const toast = useToast();

const auth = computed(() => page.props.auth as any);
const user = computed(() => auth.value?.user);
const organization = computed(() => page.props.organization as any);
const userOrganizations = computed(() => (page.props.user_organizations as any[]) ?? []);
const canSwitchOrg = computed(() =>
    userOrganizations.value.length > 1 || user.value?.is_super_admin || user.value?.is_org_manager
);

const orgDropdownOpen = ref(false);

function switchOrganization(orgId: number) {
    orgDropdownOpen.value = false;
    const selectedOrg = userOrganizations.value.find((org: any) => org.id === orgId);
    const selectedName = selectedOrg?.name ?? 'organizacion';

    if (selectedOrg?.is_current) {
        toast.info(`Ya estas en "${selectedName}"`);
        return;
    }

    router.post(`/set-organization/${orgId}`, {}, {
        onStart: () => toast.info(`Cambiando a "${selectedName}"...`),
    });
}
const flash = computed(() => page.props.flash as any);
const notifications = computed(() => page.props.notifications as any);

watch(() => flash.value?.success, (message) => {
    if (message) toast.success(message);
}, { immediate: true });

watch(() => flash.value?.error, (message) => {
    if (message) toast.error(message);
}, { immediate: true });

watch(() => flash.value?.warning, (message) => {
    if (message) toast.warning(message);
}, { immediate: true });

watch(() => flash.value?.info, (message) => {
    if (message) toast.info(message);
}, { immediate: true });

const props = defineProps<{
    title?: string;
    breadcrumbs?: Array<{ label: string; href?: string; icon?: string }>;
}>();

// Sidebar collapse state
const sidebarCollapsed = ref(false);
const notificationsDropdownOpen = ref(false);
const userDropdownOpen = ref(false);

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    document.body.classList.toggle('sidebar-collapse');
}

function toggleNotificationsDropdown() {
    notificationsDropdownOpen.value = !notificationsDropdownOpen.value;
    if (notificationsDropdownOpen.value) {
        userDropdownOpen.value = false;
    }
}

function toggleUserDropdown() {
    userDropdownOpen.value = !userDropdownOpen.value;
    if (userDropdownOpen.value) {
        notificationsDropdownOpen.value = false;
    }
}

function closeDropdowns() {
    notificationsDropdownOpen.value = false;
    userDropdownOpen.value = false;
    orgDropdownOpen.value = false;
}

onMounted(() => {
    // Initialize AdminLTE body classes
    document.body.classList.add('hold-transition', 'sidebar-mini', 'layout-fixed');

    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('.dropdown')) {
            closeDropdowns();
        }
    });
});

function isActive(routeName: string): boolean {
    return window.location.pathname.includes(routeName);
}

function markNotificationAsRead(notificationId: string) {
    router.post(`/notifications/${notificationId}/mark-read`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

function markAllNotificationsAsRead() {
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#" role="button" @click.prevent="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="/videos" class="nav-link">Videos</a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <!-- Organization switcher dropdown -->
                <li v-if="organization && canSwitchOrg" class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       @click.prevent="orgDropdownOpen = !orgDropdownOpen; notificationsDropdownOpen = false; userDropdownOpen = false;">
                        <i class="fas fa-building mr-1"></i>
                        <span class="d-none d-md-inline">{{ organization.name.substring(0, 15) }}</span>
                        <span class="d-inline d-md-none">{{ organization.name.substring(0, 10) }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" :class="{ show: orgDropdownOpen }"
                         style="max-height:350px;overflow-y:auto;min-width:200px;">
                        <span v-if="user?.is_super_admin" class="dropdown-header text-danger">
                            <i class="fas fa-shield-alt mr-1"></i> Super Admin
                        </span>
                        <div v-if="user?.is_super_admin" class="dropdown-divider"></div>
                        <button v-for="org in userOrganizations" :key="org.id"
                                type="button"
                                class="dropdown-item"
                                :class="{ 'active bg-success': org.is_current }"
                                @click="switchOrganization(org.id)">
                            <i :class="org.is_current ? 'fas fa-check mr-2' : 'fas fa-building mr-2'"></i>
                            {{ org.name }}
                        </button>
                    </div>
                </li>
                <!-- Solo nombre si no puede cambiar -->
                <li v-else-if="organization" class="nav-item">
                    <span class="nav-link text-light">
                        <i class="fas fa-building mr-1"></i>
                        <span class="d-none d-md-inline">{{ organization.name }}</span>
                        <span class="d-inline d-md-none">{{ organization.name.substring(0, 15) }}</span>
                    </span>
                </li>

                <!-- Notifications Dropdown -->
                <li v-if="notifications" class="nav-item dropdown">
                    <a class="nav-link" href="#" @click.prevent="toggleNotificationsDropdown">
                        <i class="far fa-bell"></i>
                        <span v-if="notifications.unread_count > 0" class="badge badge-danger navbar-badge">
                            {{ notifications.unread_count }}
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" :class="{ show: notificationsDropdownOpen }">
                        <span class="dropdown-item dropdown-header">
                            {{ notifications.unread_count }} Notificación{{ notifications.unread_count !== 1 ? 'es' : '' }}
                        </span>
                        <div class="dropdown-divider"></div>

                        <template v-if="notifications.unread.length > 0">
                            <a
                                v-for="notification in notifications.unread"
                                :key="notification.id"
                                :href="`/videos/${notification.data.video_id}`"
                                @click="markNotificationAsRead(notification.id)"
                                class="dropdown-item"
                            >
                                <!-- Video compartido por asociación -->
                                <template v-if="notification.data.type === 'video_shared'">
                                    <i class="fas fa-share-alt mr-2 text-success"></i>
                                    <strong>{{ notification.data.source_org_name }}</strong> compartió un video
                                    <span class="float-right text-muted text-sm">
                                        {{ notification.created_at }}
                                    </span>
                                    <p class="text-sm text-muted mb-0 mt-1">
                                        {{ notification.data.video_title }}
                                    </p>
                                </template>
                                <!-- Mención en comentario (y cualquier otro tipo) -->
                                <template v-else>
                                    <i class="fas fa-at mr-2 text-primary"></i>
                                    <strong>{{ notification.data.mentioned_by_name }}</strong> te mencionó
                                    <span class="float-right text-muted text-sm">
                                        {{ notification.created_at }}
                                    </span>
                                    <p class="text-sm text-muted mb-0 mt-1">
                                        {{ (notification.data.comment_text || '').substring(0, 50) }}{{ (notification.data.comment_text || '').length > 50 ? '...' : '' }}
                                    </p>
                                </template>
                            </a>
                        </template>
                        <template v-else>
                            <span class="dropdown-item text-center text-muted">
                                No tienes notificaciones
                            </span>
                        </template>

                        <div class="dropdown-divider"></div>
                        <a
                            v-if="notifications.unread_count > 0"
                            href="#"
                            @click.prevent="markAllNotificationsAsRead"
                            class="dropdown-item dropdown-footer"
                        >
                            Marcar todas como leídas
                        </a>
                    </div>
                </li>

                <!-- User Dropdown -->
                <li v-if="user" class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                       @click.prevent="toggleUserDropdown">
                        <img
                            v-if="user.avatar"
                            :src="user.avatar"
                            alt="Avatar"
                            class="img-circle"
                            style="width: 28px; height: 28px; object-fit: cover;"
                        >
                        <i v-else class="fas fa-user"></i>
                        <span class="d-none d-md-inline ml-2">{{ user.name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" :class="{ show: userDropdownOpen }">
                        <a class="dropdown-item" href="/profile">
                            <i class="fas fa-user"></i> Perfil
                        </a>
                        <a class="dropdown-item" href="/profile/password">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </a>
                        <div class="dropdown-divider"></div>
                        <form action="/logout" method="POST">
                            <input type="hidden" name="_token" :value="page.props._token || ''">
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Main Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="/videos" class="brand-link d-flex justify-content-center align-items-center py-2">
                <!-- Logo completo — visible cuando el sidebar está expandido -->
                <img
                    v-if="organization?.logo_path"
                    :src="organization.logo_path"
                    :alt="organization.name"
                    class="brand-logo-full"
                    style="width: 120px; height: auto; object-fit: contain;"
                >
                <img
                    v-else
                    src="/logo.png"
                    alt="RugbyKP"
                    class="brand-logo-full"
                    style="width: 120px; height: auto; object-fit: contain;"
                >
                <!-- Logo miniatura — visible solo cuando el sidebar está colapsado -->
                <img
                    :src="organization?.logo_path || '/logo.png'"
                    :alt="organization?.name || 'RugbyKP'"
                    class="brand-logo-mini"
                >
            </a>

            <div class="sidebar">
                <!-- User panel (hidden for analysts) -->
                <div v-if="user && user.role !== 'analista'" class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img
                            v-if="user.avatar"
                            :src="user.avatar"
                            alt="Avatar"
                            class="img-circle elevation-2"
                            style="width: 34px; height: 34px; object-fit: cover;"
                        >
                        <i v-else class="fas fa-user-circle fa-2x text-light"></i>
                    </div>
                    <div class="info">
                        <a href="/profile" class="text-light text-decoration-none">
                            {{ user.name }}
                        </a>
                        <div>
                            <small class="text-muted">{{ user.role.charAt(0).toUpperCase() + user.role.slice(1) }}</small>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <!-- Analyst/Coach items -->
                        <template v-if="user && ['analista', 'entrenador'].includes(user.role)">
                            <li class="nav-item">
                                <a href="/videos/create" class="nav-link" :class="{ active: isActive('/videos/create') }">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Subir Video</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/videos" class="nav-link" :class="{ active: isActive('/videos') && !isActive('/videos/create') }">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>{{ organization?.type === 'club' ? 'Videos del Equipo' : 'Videos' }}</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/coach/users" class="nav-link" :class="{ active: isActive('/coach/users') }">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Jugadores</p>
                                </a>
                            </li>
                            <!-- Torneos: solo para asociaciones -->
                            <li v-if="organization?.type === 'asociacion'" class="nav-item">
                                <a href="/tournaments" class="nav-link" :class="{ active: isActive('/tournaments') }">
                                    <i class="nav-icon fas fa-trophy"></i>
                                    <p>Torneos</p>
                                </a>
                            </li>
                            <!-- Torneos disponibles: solo para clubs -->
                            <li v-if="organization?.type === 'club'" class="nav-item">
                                <a href="/tournaments/explore" class="nav-link" :class="{ active: isActive('/tournaments/explore') }">
                                    <i class="nav-icon fas fa-globe"></i>
                                    <p>Torneos Disponibles</p>
                                </a>
                            </li>
                            <li class="nav-header">ADMINISTRACIÓN</li>
                            <li class="nav-item">
                                <a href="/admin" class="nav-link" :class="{ active: isActive('/admin') && !isActive('/admin/organization') }">
                                    <i class="nav-icon fas fa-tools"></i>
                                    <p>Mantenedor</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/admin/organization" class="nav-link" :class="{ active: isActive('/admin/organization') }">
                                    <i class="nav-icon fas fa-user-plus"></i>
                                    <p>Invitar Jugadores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/jugadas" class="nav-link" :class="{ active: isActive('/jugadas') }">
                                    <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                    <p>
                                        Crear Jugadas <span class="badge badge-info" style="font-size: 0.55rem; padding: 1px 4px; margin-left: 2px; vertical-align: middle;">β</span>
                                    </p>
                                </a>
                            </li>
                        </template>

                        <!-- Analyst only items -->
                        <template v-if="user && user.role === 'analista'">
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature">
                                    <i class="nav-icon fas fa-credit-card"></i>
                                    <p>Gestión de Pagos</p>
                                </a>
                            </li>
                        </template>

                        <!-- Player items -->
                        <template v-if="user && user.role === 'jugador'">
                            <li class="nav-item">
                                <a href="/my-videos" class="nav-link" :class="{ active: isActive('/my-videos') }">
                                    <i class="nav-icon fas fa-video"></i>
                                    <p>Mis Videos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/videos" class="nav-link" :class="{ active: isActive('/videos') && !isActive('/my-videos') }">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/evaluacion" class="nav-link" :class="{ active: isActive('/evaluacion') && !isActive('/evaluacion/resultados') }">
                                    <i class="nav-icon fas fa-clipboard-check"></i>
                                    <p>Evaluación de Jugadores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/evaluacion/resultados" class="nav-link" :class="{ active: isActive('/evaluacion/resultados') }">
                                    <i class="nav-icon fas fa-chart-line"></i>
                                    <p>Mis Resultados</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/jugadas" class="nav-link" :class="{ active: isActive('/jugadas') }">
                                    <i class="nav-icon fas fa-football-ball"></i>
                                    <p>Jugadas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link upcoming-feature">
                                    <i class="nav-icon fas fa-money-bill-wave"></i>
                                    <p>Cuota Club</p>
                                </a>
                            </li>
                        </template>

                        <!-- Super Admin -->
                        <template v-if="user && user.is_super_admin">
                            <li class="nav-header text-danger">SUPER ADMIN</li>
                            <li class="nav-item">
                                <a href="/super-admin" class="nav-link" :class="{ active: isActive('/super-admin') }">
                                    <i class="nav-icon fas fa-shield-alt text-danger"></i>
                                    <p>Panel Super Admin</p>
                                </a>
                            </li>
                        </template>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header" v-if="title || breadcrumbs">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 v-if="title" class="m-0">{{ title }}</h1>
                        </div>
                        <div v-if="breadcrumbs" class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li
                                    v-for="(crumb, i) in breadcrumbs"
                                    :key="i"
                                    class="breadcrumb-item"
                                    :class="{ active: i === breadcrumbs.length - 1 }"
                                >
                                    <a v-if="crumb.href" :href="crumb.href">
                                        <i v-if="crumb.icon" :class="crumb.icon"></i>
                                        {{ crumb.label }}
                                    </a>
                                    <span v-else>{{ crumb.label }}</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <slot />
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>&copy; {{ new Date().getFullYear() }} RugbyKP</strong>
            <div class="float-right d-none d-sm-inline-block">
                <small>Plataforma de Análisis de Video para Rugby</small>
            </div>
        </footer>
        <ToastContainer />
    </div>
</template>

<style>
/* ── Sidebar nav links — mismo estilo que app.blade.php ─────────────────── */
.nav-sidebar .nav-link,
.sidebar-dark-primary .nav-sidebar .nav-link,
.sidebar-dark-primary .nav-sidebar .nav-treeview .nav-link {
    color: #00B7B5 !important;
    font-size: 0.78rem;
    padding: 0.4rem 0.7rem;
}
.nav-sidebar .nav-link p,
.nav-sidebar .nav-link > p,
.sidebar-dark-primary .nav-sidebar .nav-link p,
.sidebar-dark-primary .nav-sidebar .nav-treeview .nav-link p {
    color: #00B7B5 !important;
}
.nav-sidebar .nav-link.active p,
.nav-sidebar .nav-link.active > p,
.sidebar-dark-primary .nav-sidebar .nav-link.active p {
    color: #fff !important;
}
.nav-sidebar .nav-link:hover,
.sidebar-dark-primary .nav-sidebar .nav-link:hover {
    background-color: #4A6274;
    color: #00d4d2 !important;
}
.nav-sidebar .nav-link:hover p,
.sidebar-dark-primary .nav-sidebar .nav-link:hover p {
    color: #00d4d2 !important;
}
.nav-sidebar .nav-link.active,
.sidebar-dark-primary .nav-sidebar .nav-link.active {
    background-color: #005461 !important;
    color: white !important;
}
.nav-sidebar .nav-icon {
    font-size: 0.85rem;
    margin-right: 0.5rem;
    color: #00B7B5 !important;
}

/* Logo miniatura: oculto por defecto, visible cuando sidebar está colapsado */
.brand-logo-mini {
    display: none;
    width: 42px;
    height: 42px;
    object-fit: contain;
    border-radius: 6px;
}

.sidebar-collapse .brand-logo-full {
    display: none !important;
}

.sidebar-collapse .brand-logo-mini {
    display: block !important;
}

/* Centrar el link del brand cuando está colapsado */
.sidebar-collapse .brand-link {
    padding: 0.6rem 0 !important;
    justify-content: center !important;
}
</style>
