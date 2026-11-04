<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';

const form = useForm({ email: '', password: '', remember: true });
const submit = () => form.post('/admin/giris', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Yönetim girişi" />
    <div class="grid min-h-screen bg-[#f6f4ef] font-sans font-normal text-ink lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-night lg:block">
            <img src="/media/galeri/10.jpg" alt="" class="absolute inset-0 size-full object-cover opacity-60" />
            <div class="absolute inset-0 bg-linear-to-t from-night via-night/40 to-transparent"></div>
            <div class="absolute bottom-12 left-12 text-pearl">
                <BrandLogo class="h-14" />
                <p class="mt-4 text-pearl/70">Yönetim paneli</p>
            </div>
        </div>
        <div class="grid place-items-center px-6 py-16">
            <form class="w-full max-w-sm" @submit.prevent="submit">
                <BrandLogo class="mb-10 h-12 lg:hidden" />
                <h1 class="font-serif text-4xl">Giriş yap</h1>
                <p class="mt-2 text-[14px] text-muted">Site içeriğini yönetmek için hesabınızla giriş yapın.</p>

                <label class="mt-8 block">
                    <span class="adm-label">E-posta</span>
                    <input v-model="form.email" type="email" autocomplete="username" class="adm-input" required autofocus />
                    <span v-if="form.errors.email" class="adm-error">{{ form.errors.email }}</span>
                </label>
                <label class="mt-4 block">
                    <span class="adm-label">Şifre</span>
                    <input v-model="form.password" type="password" autocomplete="current-password" class="adm-input" required />
                    <span v-if="form.errors.password" class="adm-error">{{ form.errors.password }}</span>
                </label>
                <label class="mt-4 flex items-center gap-2 text-[13px] text-muted">
                    <input v-model="form.remember" type="checkbox" class="size-4 accent-gold" /> Beni hatırla
                </label>
                <button type="submit" class="adm-btn adm-btn-primary mt-6 w-full py-3" :disabled="form.processing">
                    {{ form.processing ? 'Giriş yapılıyor…' : 'Giriş yap' }}
                </button>
                <a href="/" class="mt-6 block text-center text-[13px] text-muted hover:text-gold-dark">← Siteye dön</a>
            </form>
        </div>
    </div>
</template>
