<style>
@import 'maplibre-gl/dist/maplibre-gl.css';
@import '@maplibre/maplibre-gl-geocoder/dist/maplibre-gl-geocoder.css';
</style>

<script lang="ts">
import MaplibreGeocoder, { CarmenGeojsonFeature, MaplibreGeocoderApiConfig, MaplibreGeocoderFeatureResults } from '@maplibre/maplibre-gl-geocoder';
import * as maplibregl from 'maplibre-gl';
import { GeolocateControl, Map, Marker, setWorkerUrl } from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import { onMounted, shallowRef, watch } from 'vue';

async function forwardGeocode(config: MaplibreGeocoderApiConfig): Promise<MaplibreGeocoderFeatureResults> {
    const features: CarmenGeojsonFeature[] = [];
    try {
        const request = `https://nominatim.openstreetmap.org/search?q=${config.query}&format=geojson&polygon_geojson=1&addressdetails=1`;
        const response = await fetch(request);
        const geojson = await response.json();
        for (const f of geojson.features) {
            const center: GeoJSON.Position = [f.bbox[0] + (f.bbox[2] - f.bbox[0]) / 2, f.bbox[1] + (f.bbox[3] - f.bbox[1]) / 2];
            const feature: CarmenGeojsonFeature = {
                type: 'Feature',
                id: f.properties.osm_id,
                geometry: {
                    type: 'Point',
                    coordinates: center,
                },
                place_name: f.properties.display_name,
                properties: f.properties,
                text: f.properties.display_name,
                place_type: ['place'],
            };
            features.push(feature);
        }
    } catch (e) {
        console.error(`Failed to forwardGeocode with error: ${e}`);
    }

    return { type: 'FeatureCollection', features };
}

export default {
    name: 'MapLibre',
    emits: ['locate'],
    props: {
        longitude: {
            type: [String, Number],
            default: '',
        },
        latitude: {
            type: [String, Number],
            default: '',
        },
    },
    setup(props, { emit }) {
        const container = shallowRef(null);
        const element = shallowRef(null);
        let map: Map | null = null;
        let marker: Marker | null = null;

        onMounted(() => {
            const initialState = { lng: 4.4, lat: 50.534, zoom: 6 };

            setWorkerUrl(workerUrl);

            map = new Map({
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

            // Add geocoder input
            const geocoder = new MaplibreGeocoder(
                {
                    forwardGeocode,
                },
                {
                    maplibregl,
                    enableEventLogging: false,
                    limit: 3,
                    showResultsWhileTyping: true,
                },
            );
            geocoder.on('result', (e) => {
                const feature = e.result as CarmenGeojsonFeature;

                emit('locate', feature.geometry.coordinates);
            });
            map.addControl(geocoder);

            // Add geolocation button
            const geolocate = new GeolocateControl({ positionOptions: { enableHighAccuracy: true } });
            geolocate.on('geolocate', (e) => {
                emit('locate', [e.coords.longitude, e.coords.latitude], e.coords.altitude);
            });
            map.addControl(geolocate, 'top-left');

            map.on('click', (e) => {
                emit('locate', [e.lngLat.lng, e.lngLat.lat], null);
            });

            map.on('load', (e) => {
                if (props.longitude && props.latitude) {
                    const lngNum = Number(props.longitude);
                    const latNum = Number(props.latitude);

                    if (!isNaN(lngNum) && !isNaN(latNum)) {
                        e.target.flyTo({ center: [lngNum, latNum], zoom: Math.max(e.target.getZoom(), 12) });
                        marker = new maplibregl.Marker({ draggable: false }).setLngLat([lngNum, latNum]).addTo(e.target);
                    }
                }
            });
        });

        // Watch for longitude/latitude changes and update marker
        watch(
            () => [props.longitude, props.latitude],
            ([lng, lat]) => {
                if (!map) return;
                if (lng && lat && !isNaN(Number(lng)) && !isNaN(Number(lat))) {
                    const lngNum = Number(lng);
                    const latNum = Number(lat);
                    if (!marker) {
                        marker = new maplibregl.Marker({ draggable: false }).setLngLat([lngNum, latNum]).addTo(map);
                    } else {
                        marker.setLngLat([lngNum, latNum]);
                    }
                    map.flyTo({ center: [lngNum, latNum], zoom: Math.max(map.getZoom(), 12) });
                } else if (marker) {
                    marker.remove();
                    marker = null;
                }
            },
            { immediate: true },
        );

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
