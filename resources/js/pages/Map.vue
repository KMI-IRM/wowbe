<script setup lang="ts">
import MapLibre from '@/components/MapLibre.vue';
import { LineChart } from '@/components/ui/chart-line';
import { Drawer, DrawerContent, DrawerDescription, DrawerHeader, DrawerTitle } from '@/components/ui/drawer';
import { Head, Link } from '@inertiajs/vue3';
import { GeoJSONFeature } from 'maplibre-gl';
import { ref } from 'vue';

const sheetOpen = ref(false);
const sheetTitle = ref('');
const sheetDescription = ref('');
const latestObservation = ref(null);
const data = ref([]);

const handleFeatureClick = (feature: GeoJSONFeature) => {
    sheetTitle.value = feature.properties.name;
    sheetDescription.value = new Date(feature.properties.timestamp).toLocaleString();

    latestObservation.value = feature.properties.primary;

    fetch(route('api.site.graph', { site: feature.properties.site_id }))
        .then((response) => response.json())
        .then((json) => {
            data.value = json.map((d: any) => {
                return {
                    timestamp: d.timestamp,
                    Temperature: d.primary.dt,
                    'Wind speed': d.primary.dws,
                    Rain: d.primary.drr,
                    Pressure: d.primary.dm,
                    Humidity: d.primary.dh,
                };
            });

            sheetOpen.value = true;
        });
};
</script>

<template>
    <Head title="WOW-BE" />
    <div class="flex h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    {{ $t('menu.header.dashboard') }}
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        {{ $t('menu.header.login') }}
                    </Link>
                    <!-- <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        {{ $t('menu.header.register') }}
                    </Link> -->
                </template>
            </nav>
        </header>
        <main class="flex h-full w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <MapLibre @feature-click="handleFeatureClick" />
        </main>
    </div>
    <Drawer :open="sheetOpen" @update:open="sheetOpen = $event">
        <DrawerContent>
            <div class="mx-auto w-full max-w-4xl">
                <DrawerHeader>
                    <DrawerTitle>{{ sheetTitle }}</DrawerTitle>
                    <DrawerDescription>{{ sheetDescription }}</DrawerDescription>
                </DrawerHeader>
                <div class="flex items-center justify-between">
                    <section v-if="latestObservation">
                        <ul>
                            <li v-if="latestObservation.dt">Temperature: {{ latestObservation.dt.toFixed(1) }}°C</li>
                            <li v-if="latestObservation.dws">Wind speed: {{ latestObservation.dws.toFixed(1) }} km/h</li>
                            <li v-if="latestObservation.dwd">Wind direction: {{ latestObservation.dwd.toFixed(0) }}°</li>
                            <li v-if="latestObservation.dh">Humidity: {{ latestObservation.dh.toFixed(1) }} %</li>
                            <li v-if="latestObservation.dap">Pressure: {{ latestObservation.dap.toFixed(1) }} hPa</li>
                        </ul>
                    </section>
                    <LineChart index="timestamp" :data="data" :categories="['Temperature', 'Wind speed', 'Rain', 'Pressure', 'Humidity']" />
                </div>
            </div>
        </DrawerContent>
    </Drawer>
</template>
