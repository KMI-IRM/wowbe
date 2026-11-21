<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormItem } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PinInput, PinInputGroup, PinInputInput } from '@/components/ui/pin-input';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast, Toaster } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import SiteLayout from '@/layouts/site/Layout.vue';
import { markdown } from '@/lib/utils';
import { type BreadcrumbItem, type Site } from '@/types';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const props = defineProps<{
    site: Site;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: trans('dashboard.title'),
        href: route('dashboard'),
    },
    {
        title: trans('form.header.site', { site: props.site.name }),
        href: route('site.edit', { site: props.site.id }),
    },
    {
        title: trans('form.header.update.title'),
        href: route('site.edit_auth', { site: props.site.id }),
    },
]);

const form = useForm({
    tab: props.site.has_pin_code === true ? 'pincode' : 'password',
    pincode: props.site.has_pin_code === true ? props.site.auth_key.split('') : null,
    password: props.site.has_pin_code !== true ? props.site.auth_key : '',
    mac_address: props.site.mac_address ?? '',
});

const submit = () => {
    form.patch(route('site.update_auth', { id: props.site.id }), {
        onSuccess: () => {
            toast({
                title: trans('form.success.updated.title'),
                description: trans('form.success.updated.description_auth', { site: props.site.name }),
            });
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="trans('form.header.update.title')" />

        <SiteLayout :site="props.site">
            <div class="flex flex-col space-y-6">
                <HeadingSmall :title="trans('form.header.authentication.title')" :description="trans('form.header.authentication.description')" />

                <form class="w-2/3 space-y-6" autocomplete="off" @submit.prevent="submit">
                    <Tabs v-model="form.tab">
                        <TabsList class="grid w-full grid-cols-2">
                            <!-- Adding class for dark mode here shouldn't be needed, but it doesn't seem to work without -->
                            <TabsTrigger value="pincode" class="dark:data-[state=active]:bg-background">{{
                                trans('form.authentication.pincode')
                            }}</TabsTrigger>
                            <TabsTrigger value="password" class="dark:data-[state=active]:bg-background">{{
                                trans('form.authentication.password')
                            }}</TabsTrigger>
                        </TabsList>
                        <TabsContent value="pincode">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{{ trans('form.authentication.pincode') }}</CardTitle>
                                    <CardDescription>{{ trans('form.authentication.pincode_description') }}</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <FormItem>
                                        <Label for="auth_key_pincode">{{ trans('form.menu.authentication') }}</Label>
                                        <PinInput id="auth_key_pincode" required v-model="form.pincode" placeholder="○">
                                            <PinInputGroup>
                                                <PinInputInput v-for="(id, index) in 6" :key="id" :index="index" />
                                            </PinInputGroup>
                                        </PinInput>
                                        <InputError :message="form.errors.pincode" />
                                    </FormItem>
                                </CardContent>
                            </Card>
                        </TabsContent>
                        <TabsContent value="password">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{{ trans('form.authentication.password') }}</CardTitle>
                                    <CardDescription>{{ trans('form.authentication.password_description') }}</CardDescription>
                                </CardHeader>
                                <CardContent class="space-y-2">
                                    <FormItem>
                                        <Label for="auth_key_password">{{ trans('form.menu.authentication') }}</Label>
                                        <Input id="auth_key_password" type="text" required v-model="form.password" />
                                        <InputError :message="form.errors.password" />
                                        <p class="text-muted-foreground text-sm">{{ trans('form.authentication.alphanumeric_hint') }}</p>
                                    </FormItem>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    <FormItem>
                        <Label for="mac_address">{{ $t('form.authentication.mac_address') }}</Label>
                        <p
                            class="text-muted-foreground text-sm"
                            v-html="markdown($t('form.authentication.mac_address_description')) + '<br />' + $t('form.not_public')"
                        ></p>
                        <Input id="mac_address" type="string" v-model="form.mac_address" />
                        <InputError :message="form.errors.mac_address" />
                    </FormItem>

                    <Button type="submit" :disabled="form.processing">
                        {{ $t('form.action.save') }}
                    </Button>
                </form>
            </div>
        </SiteLayout>
    </AppLayout>

    <Toaster />
</template>
