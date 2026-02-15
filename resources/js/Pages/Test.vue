<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const auth = computed(() => page.props.auth as any);
const organization = computed(() => page.props.organization as any);

const counter = ref(0);

const increment = () => counter.value++;
const decrement = () => counter.value--;
</script>

<template>
    <div class="wrapper">
        <div class="content-wrapper" style="margin-left: 0; padding: 40px 20px; background-color: #0f0f0f; min-height: 100vh;">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <h1 style="color: #00B7B5; font-family: 'Bebas Neue', sans-serif; font-size: 3rem;">
                                RugbyHub + Vue 3
                            </h1>
                            <p style="color: #aaa;">Inertia.js + Pinia + TypeScript funcionando correctamente</p>
                        </div>

                        <!-- Status Card -->
                        <div class="card" style="background: #1a1a1a; border: 1px solid #018790;">
                            <div class="card-header" style="background: linear-gradient(135deg, #005461, #00B7B5); border: none;">
                                <h3 class="card-title text-white">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    Infraestructura Vue Activa
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Stack info -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled" style="color: #ccc;">
                                            <li class="mb-2"><i class="fab fa-vuejs text-success mr-2"></i> Vue 3 (Composition API)</li>
                                            <li class="mb-2"><i class="fas fa-exchange-alt mr-2" style="color: #00B7B5;"></i> Inertia.js v2</li>
                                            <li class="mb-2"><i class="fas fa-database mr-2" style="color: #00B7B5;"></i> Pinia (State Management)</li>
                                            <li class="mb-2"><i class="fas fa-code mr-2" style="color: #3178c6;"></i> TypeScript</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled" style="color: #ccc;">
                                            <li class="mb-2"><i class="fab fa-laravel mr-2" style="color: #ff2d20;"></i> Laravel 12</li>
                                            <li class="mb-2"><i class="fas fa-bolt mr-2" style="color: #646cff;"></i> Vite 6</li>
                                            <li class="mb-2"><i class="fas fa-palette mr-2" style="color: #00B7B5;"></i> AdminLTE + Bootstrap 4</li>
                                            <li class="mb-2"><i class="fas fa-building mr-2" style="color: #00B7B5;"></i> Multi-Tenant</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div v-if="auth?.user" class="alert" style="background: #005461; border: none; color: white;">
                                    <i class="fas fa-user mr-2"></i>
                                    <strong>{{ auth.user.name }}</strong> ({{ auth.user.role }})
                                    <span v-if="organization" class="ml-3">
                                        <i class="fas fa-building mr-1"></i> {{ organization.name }}
                                    </span>
                                </div>

                                <!-- Reactivity Test -->
                                <div class="text-center mt-4 p-4" style="background: #111; border-radius: 8px;">
                                    <p style="color: #aaa;" class="mb-3">Test de reactividad Vue:</p>
                                    <div class="d-flex align-items-center justify-content-center gap-3">
                                        <button class="btn btn-outline-danger btn-lg" @click="decrement">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span style="font-size: 3rem; color: #00B7B5; font-family: 'Bebas Neue', sans-serif; min-width: 80px; display: inline-block;">
                                            {{ counter }}
                                        </span>
                                        <button class="btn btn-lg" style="background: #00B7B5; color: white;" @click="increment">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Back link -->
                        <div class="text-center mt-3">
                            <a href="/videos" style="color: #00B7B5;">
                                <i class="fas fa-arrow-left mr-1"></i> Volver a Videos (Blade)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
