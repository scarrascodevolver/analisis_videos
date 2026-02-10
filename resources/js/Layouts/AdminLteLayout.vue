<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';

const page = usePage();

const auth = computed(() => page.props.auth as any);
const user = computed(() => auth.value?.user);
const organization = computed(() => page.props.organization as any);
const flash = computed(() => page.props.flash as any);

const props = defineProps<{
    title?: string;
    breadcrumbs?: Array<{ label: string; href?: string; icon?: string }>;
}>();

// Sidebar collapse state
const sidebarCollapsed = ref(false);

function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    document.body.classList.toggle('sidebar-collapse');
}

onMounted(() => {
    // Initialize AdminLTE body classes
    document.body.classList.add('hold-transition', 'sidebar-mini', 'layout-fixed');
});

function isActive(routeName: string): boolean {
    return window.location.pathname.includes(routeName);
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
                        <i class="fas fa-building mr-1"></i>
                        <span class="d-none d-md-inline">{{ organization.name }}</span>
                        <span class="d-inline d-md-none">{{ organization.name.substring(0, 15) }}</span>
                    </span>
                </li>

                <!-- User Dropdown -->
                <li v-if="user" class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                       data-toggle="dropdown">
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
                    <div class="dropdown-menu dropdown-menu-right">
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
                    src="/logohub.png"
                    alt="RugbyHub"
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
                                    <p>Videos del Equipo</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/my-videos" class="nav-link" :class="{ active: isActive('/my-videos') }">
                                    <i class="nav-icon fas fa-user-circle"></i>
                                    <p>Mis Videos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/coach/users" class="nav-link" :class="{ active: isActive('/coach/users') }">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Jugadores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="/evaluations/dashboard" class="nav-link" :class="{ active: isActive('/evaluations') }">
                                    <i class="nav-icon fas fa-chart-bar"></i>
                                    <p>Resultados de Evaluaciones</p>
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
            <strong>&copy; {{ new Date().getFullYear() }} RugbyHub</strong>
            <div class="float-right d-none d-sm-inline-block">
                <small>Plataforma de Análisis de Video para Rugby</small>
            </div>
        </footer>
    </div>
</template>
