<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';

const page = usePage();

const auth = computed(() => page.props.auth as any);
const user = computed(() => auth.value?.user);
const organization = computed(() => page.props.organization as any);
const flash = computed(() => page.props.flash as any);
const notifications = computed(() => page.props.notifications as any);

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
                <!-- Organization display -->
                <li v-if="organization" class="nav-item">
                    <span class="nav-link text-light">
                        <i class="fas fa-football-ball mr-1"></i>
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
                                <i class="fas fa-at mr-2 text-primary"></i>
                                <strong>{{ notification.data.mentioned_by_name }}</strong> te mencionó
                                <span class="float-right text-muted text-sm">
                                    {{ notification.created_at }}
                                </span>
                                <p class="text-sm text-muted mb-0 mt-1">
                                    {{ notification.data.comment_text.substring(0, 50) }}{{ notification.data.comment_text.length > 50 ? '...' : '' }}
                                </p>
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
            <a href="/videos" class="brand-link d-flex justify-content-center py-2">
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

            <!-- Flash messages -->
            <div v-if="flash?.success || flash?.error || flash?.warning" class="container-fluid">
                <div v-if="flash.success" class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ flash.error }}
                </div>
                <div v-if="flash.warning" class="alert alert-warning alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ flash.warning }}
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
    </div>
</template>
