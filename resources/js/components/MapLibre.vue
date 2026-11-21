<style>
@import 'maplibre-gl/dist/maplibre-gl.css';
</style>

<script lang="ts">
import { trans } from 'laravel-vue-i18n';
import { GeolocateControl, GlobeControl, Map, NavigationControl, Popup, ScaleControl, setWorkerUrl } from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import { markRaw, onMounted, onUnmounted, shallowRef } from 'vue';

export default {
    name: 'MapLibre',
    emits: ['feature-click'],
    setup(props, { emit }) {
        const container = shallowRef(null);
        const element = shallowRef(null);

        onMounted(() => {
            const initialState = { lng: 4.4, lat: 50.534, zoom: 8 };

            setWorkerUrl(workerUrl);

            const map = new Map({
                container: container.value,
                // style: 'https://tiles.openfreemap.org/styles/liberty',
                style: {
                    version: 8,
                    sources: {
                        'raster-tiles': {
                            type: 'raster',
                            tiles: ['https://tile.meteo.be/styles/Streets-v10/{z}/{x}/{y}.png'],
                            tileSize: 256,
                            attribution:
                                '<a href="https://www.openmaptiles.org/" target="_blank">&copy; OpenMapTiles</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">&copy; OpenStreetMap contributors</a>',
                        },
                    },
                    layers: [
                        {
                            id: 'simple-tiles',
                            type: 'raster',
                            source: 'raster-tiles',
                            minzoom: 0,
                            maxzoom: 22,
                        },
                    ],
                },
                center: [initialState.lng, initialState.lat],
                zoom: initialState.zoom,
            });

            map.addControl(new NavigationControl(), 'top-left');
            map.addControl(new GeolocateControl({ positionOptions: { enableHighAccuracy: true } }), 'top-left');
            map.addControl(new ScaleControl(), 'bottom-left');
            map.addControl(new GlobeControl());

            map.on('load', async () => {
                map.addSource('wow-live', {
                    type: 'geojson',
                    data: route('api.observation'),
                });
                map.addLayer({
                    id: 'wow-live',
                    type: 'circle',
                    source: 'wow-live',
                    paint: {
                        'circle-radius': 5,
                        'circle-color': [
                            'case',
                            ['==', ['get', 'timestamp'], null],
                            'rgba(80,80,80,0.25)', // Gray if timestamp is null
                            ['interpolate', ['linear'], ['get', 'dt', ['get', 'primary']], 0, 'blue', 50, 'red'],
                        ],
                    },
                });
            });

            const popup = new Popup({
                closeButton: false,
                closeOnClick: false,
            });

            let currentFeatureCoordinates = undefined;
            map.on('mousemove', 'wow-live', (e) => {
                const features = e.features.filter((f) => typeof f.properties.timestamp !== 'undefined');
                if (features.length === 0) {
                    return;
                }

                const featureCoordinates = features[0].geometry.coordinates.toString();
                if (currentFeatureCoordinates !== featureCoordinates) {
                    currentFeatureCoordinates = featureCoordinates;

                    // Change the cursor style as a UI indicator.
                    map.getCanvas().style.cursor = 'pointer';

                    const coordinates = features[0].geometry.coordinates.slice();
                    const datetime = new Date(features[0].properties.timestamp);
                    const name = features[0].properties.name;
                    const reading = JSON.parse(features[0].properties.primary);

                    // Ensure that if the map is zoomed out such that multiple
                    // copies of the feature are visible, the popup appears
                    // over the copy being pointed to.
                    while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
                        coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
                    }

                    // Populate the popup and set its coordinates
                    // based on the feature found.
                    popup
                        .setLngLat(coordinates)
                        .setHTML(
                            `<h1>${name}</h1>` +
                                `<h2>${datetime.toLocaleString()}</h2>` +
                                `<ul>` +
                                (reading.dt !== null ? `<li>${trans('observation.temperature')}: ${reading.dt.toFixed(1)} °C</li>` : '') +
                                (reading.dws !== null ? `<li>${trans('observation.wind_speed')}: ${reading.dws.toFixed(1)} km/h</li>` : '') +
                                (reading.dwd !== null ? `<li>${trans('observation.wind_direction')}: ${reading.dwd.toFixed(0)}°</li>` : '') +
                                (reading.dh !== null ? `<li>${trans('observation.humidity')}: ${reading.dh.toFixed(1)} %</li>` : '') +
                                (reading.dap !== null ? `<li>${trans('observation.pressure')}: ${reading.dap.toFixed(1)} hPa</li>` : '') +
                                `</ul>`,
                        )
                        .addTo(map);
                }
            });
            map.on('mouseleave', 'wow-live', () => {
                currentFeatureCoordinates = undefined;
                map.getCanvas().style.cursor = '';
                popup.remove();
            });

            map.on('click', 'wow-live', (e) => {
                const features = e.features.filter((f) => typeof f.properties.timestamp !== 'undefined');
                if (features.length === 0) {
                    return;
                }

                const feature = features[0];

                map.zoomTo(12, { center: feature.geometry.coordinates });

                emit('feature-click', feature);
            });

            element.value = markRaw(map);
        });

        onUnmounted(() => {
            element.value?.remove();
        });

        return {
            container,
            element,
        };
    },
};
</script>

<template>
    <div class="h-full w-full rounded-lg" ref="container"></div>
</template>
