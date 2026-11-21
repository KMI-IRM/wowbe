<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import MapLibreLocation from '@/components/MapLibreLocation.vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
} from '@/components/ui/combobox';
import { FormItem } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Separator from '@/components/ui/separator/Separator.vue';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/components/ui/toast/use-toast';
import { markdown } from '@/lib/utils';
import { type Media, type Site } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { Check, Search } from 'lucide-vue-next';
import { ref } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    defaultTimezone?: string;
    site?: Site;
    pictures?: Media[];
}>();

const form = useForm({
    longitude: props.site?.longitude || '',
    latitude: props.site?.latitude || '',
    altitude: props.site?.altitude || '',
    name: props.site?.name || '',
    timezone: props.site?.timezone || props.defaultTimezone,
    website: props.site?.website || '',
    brand: props.site?.brand || '',
    software: props.site?.software || '',
    picture_add: null as File | null,
    picture_remove: [] as string[],
    password: '',
    mac_address: '',
});

const pictureInput = ref<HTMLInputElement | null>(null);

const clearFileInput = () => {
    if (pictureInput.value) {
        pictureInput.value.value = ''; // Clear the file input
    }
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.picture_add = target.files[0];
    } else {
        form.picture_add = null;
    }
};

const submit = () => {
    // Update Site
    if (props.site) {
        form.post(route('site.update', { id: props.site.id }), {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: trans('form.success.updated.title'),
                    description: trans('form.success.updated.description', { site: form.name }),
                });

                form.picture_add = null; // Reset picture_add after successful update
                form.picture_remove = []; // Reset picture_remove after successful update

                clearFileInput();
            },
            onError: () => {
                form.picture_add = null; // Reset picture_add after error
                form.picture_remove = []; // Reset picture_remove after error

                clearFileInput();
            },
        });
    }
    // Create Site
    else {
        form.post(route('site.store'), {
            forceFormData: true,
        });
    }
};

const handleLocate = (location: GeoJSON.Position, altitude: number | null = null) => {
    form.longitude = location[0].toFixed(6);
    form.latitude = location[1].toFixed(6);

    // If altitude is provided, set it; otherwise, use OpenElevation API
    if (altitude !== null) {
        form.altitude = altitude.toFixed(0);
    } else {
        fetch(`https://api.open-elevation.com/api/v1/lookup?locations=${form.latitude},${form.longitude}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.results && data.results.length > 0) {
                    form.altitude = data.results[0].elevation.toFixed(0);
                } else {
                    form.altitude = '';
                }
            })
            .catch(() => {
                form.altitude = '';
            });
    }
};

const removeMedia = (media: Media) => {
    if (form.picture_remove.includes(media.uuid)) {
        return;
    }

    if (confirm('Are you sure you want to delete this image?')) {
        form.picture_remove.push(media.uuid);
    }
};

const timezones = Intl.supportedValuesOf('timeZone');
</script>

<template>
    <form class="w-2/3 space-y-6" autocomplete="off" @submit.prevent="submit">
        <FormItem>
            <h3 class="text-lg font-medium">1. {{ $t('form.location.title') }}</h3>
            <p class="text-muted-foreground text-sm">{{ $t('form.location.description') }}</p>
            <p class="text-muted-foreground text-sm">{{ $t('form.public') }}</p>
        </FormItem>

        <div class="flex gap-6">
            <div class="basis-1/3 space-y-6">
                <FormItem>
                    <Label for="longitude">{{ $t('form.location.longitude') }}</Label>
                    <Input id="longitude" type="number" step="0.000001" required v-model="form.longitude" />
                    <InputError :message="form.errors.longitude" />
                </FormItem>

                <FormItem>
                    <Label for="latitude">{{ $t('form.location.latitude') }}</Label>
                    <Input id="latitude" type="number" step="0.000001" required v-model="form.latitude" />
                    <InputError :message="form.errors.latitude" />
                </FormItem>

                <FormItem>
                    <Label for="altitude">{{ $t('form.location.altitude') }}</Label>
                    <Input id="altitude" type="number" required v-model="form.altitude" />
                    <InputError :message="form.errors.altitude" />
                </FormItem>
            </div>
            <div class="basis-2/3">
                <MapLibreLocation @locate="handleLocate" :longitude="form.longitude" :latitude="form.latitude" />
            </div>
        </div>

        <Separator />

        <FormItem>
            <h3 class="text-lg font-medium">2. {{ $t('form.details.title') }}</h3>
            <p class="text-muted-foreground text-sm">{{ $t('form.details.description') }}</p>
        </FormItem>

        <FormItem>
            <Label for="name">{{ $t('form.details.name') }}</Label>
            <Input id="name" type="text" required v-model="form.name" />
            <InputError :message="form.errors.name" />
        </FormItem>

        <FormItem>
            <Label for="timezone">{{ $t('form.details.timezone') }}</Label>
            <Combobox v-model="form.timezone">
                <ComboboxAnchor class="w-full">
                    <div class="relative w-full items-center">
                        <ComboboxInput
                            class="bg-background ring-offset-background h-10 px-3 py-2 pl-9 text-base focus-visible:ring-2 focus-visible:ring-offset-2 md:text-sm"
                            :placeholder="$t('form.details.timezone_select')"
                        />
                        <span class="absolute inset-y-0 start-0 flex items-center justify-center px-3">
                            <Search class="text-muted-foreground size-4" />
                        </span>
                    </div>
                </ComboboxAnchor>
                <ComboboxList>
                    <ComboboxEmpty>{{ $t('form.details.timezone_select_notfound') }}</ComboboxEmpty>
                    <ComboboxGroup>
                        <ComboboxItem v-for="(timezone, index) in timezones" :key="index" :value="timezone">
                            {{ timezone }}
                            <ComboboxItemIndicator>
                                <Check class="ml-auto size-4" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>
                </ComboboxList>
            </Combobox>
            <InputError :message="form.errors.timezone" />
        </FormItem>

        <FormItem>
            <Label for="website">{{ $t('form.details.website') }}</Label>
            <Input id="website" type="url" v-model="form.website" />
            <InputError :message="form.errors.website" />
        </FormItem>

        <FormItem>
            <Label for="brand">{{ $t('form.details.brand') }}</Label>
            <p class="text-muted-foreground text-sm">{{ $t('form.not_public') }}</p>
            <Input id="brand" type="string" v-model="form.brand" />
            <InputError :message="form.errors.brand" />
        </FormItem>

        <FormItem>
            <Label for="software">{{ $t('form.details.software') }}</Label>
            <p class="text-muted-foreground text-sm">{{ $t('form.not_public') }}</p>
            <Input id="software" type="string" v-model="form.software" />
            <InputError :message="form.errors.software" />
        </FormItem>

        <Separator />

        <FormItem>
            <h3 class="text-lg font-medium">3. {{ $t('form.pictures.title') }}</h3>
            <p class="text-muted-foreground text-sm">{{ $t('form.pictures.description') }}<br />{{ $t('form.public') }}</p>
        </FormItem>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <div v-for="media in props.pictures" :key="media.uuid" class="relative">
                <img
                    :src="media.preview_url.length > 0 ? media.preview_url : media.original_url"
                    :alt="media.name"
                    class="h-auto w-full rounded-md border-2"
                />
                <div v-if="form.picture_remove.includes(media.uuid)" class="absolute inset-0 rounded-md bg-red-900/75 transition"></div>
                <button
                    type="button"
                    class="bg-opacity-50 absolute top-0 right-0 rounded-tr-md rounded-bl-md border-2 bg-white px-2 py-1 text-xs text-red-500 hover:text-red-700 dark:bg-black"
                    @click="removeMedia(media)"
                    title="Remove this picture"
                >
                    &times;
                </button>
            </div>
        </div>
        <InputError :message="form.errors.picture_remove" />

        <FormItem>
            <Label for="picture">{{ $t('form.pictures.picture_add') }}</Label>
            <input
                ref="pictureInput"
                id="picture"
                type="file"
                accept="image/jpeg,image/png"
                class="border-input bg-background ring-offset-background file:text-foreground placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-base file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                @change="handleFileChange"
            />
            <InputError :message="form.errors.picture_add" />
        </FormItem>

        <template v-if="!site">
            <Separator />

            <FormItem>
                <h3 class="text-lg font-medium">4. {{ $t('form.authentication.title') }}</h3>
                <p class="text-muted-foreground text-sm">{{ $t('form.authentication.description') }}</p>
            </FormItem>

            <FormItem>
                <Label for="password">{{ $t('form.authentication.pincode') }} / {{ $t('form.authentication.password') }}</Label>
                <p class="text-muted-foreground text-sm">{{ $t('form.authentication.alphanumeric_hint') }}</p>
                <Input id="password" type="string" v-model="form.password" />
                <InputError :message="form.errors.password" />
            </FormItem>

            <FormItem>
                <Label for="mac_address">{{ $t('form.authentication.mac_address') }}</Label>
                <p
                    class="text-muted-foreground text-sm"
                    v-html="markdown($t('form.authentication.mac_address_description')) + '<br />' + $t('form.not_public')"
                ></p>
                <Input id="mac_address" type="string" v-model="form.mac_address" />
                <InputError :message="form.errors.mac_address" />
            </FormItem>
        </template>

        <Button type="submit" :disabled="form.processing">
            {{ site ? $t('form.action.save') : $t('form.action.submit') }}
        </Button>
    </form>

    <Toaster />
</template>
