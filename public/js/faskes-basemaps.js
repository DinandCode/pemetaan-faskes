/**
 * Helper Global Basemaps Faskes Kab. Banyumas
 * Menyediakan layer Peta Jalan (OSM), Satelit (Esri World Imagery), dan Satelit + Label.
 * Pilihan basemap terakhir disimpan di localStorage dan dipulihkan otomatis saat halaman dibuka.
 */
(function (window) {
    'use strict';

    var STORAGE_KEY = 'faskes_active_basemap';

    var FaskesBasemaps = {
        /**
         * Memasang basemaps dan layer control ke instance Leaflet map.
         * @param {L.Map} map - Objek Leaflet Map
         * @param {Object} options - Konfigurasi opsional (position, storageKey, collapsed)
         * @returns {Object} { baseMaps, control, activeName, activeLayer }
         */
        add: function (map, options) {
            if (!map || typeof map.addLayer !== 'function') {
                console.error('FaskesBasemaps.add: Objek map Leaflet tidak valid.');
                return null;
            }

            options = options || {};
            var position = options.position || 'topleft';
            var storageKey = options.storageKey || STORAGE_KEY;
            var collapsed = options.collapsed !== false;

            // 1. Layer Peta Jalan (OpenStreetMap)
            var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
            });

            // 2. Layer Satelit (Esri World Imagery)
            var satelitTile = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community'
            });

            // 3. Layer Satelit + Label (Esri Imagery + Boundaries/Places Reference Overlay)
            var satelitLabelTile = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community'
            });

            var labelOverlay = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                maxZoom: 19,
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community'
            });

            var satelitPlusLabel = L.layerGroup([satelitLabelTile, labelOverlay]);

            var baseMaps = {
                'Peta Jalan': osmLayer,
                'Satelit': satelitTile,
                'Satelit + Label': satelitPlusLabel
            };

            // Baca pilihan basemap dari localStorage (dengan fallback try/catch)
            var activeName = 'Peta Jalan';
            try {
                var stored = window.localStorage.getItem(storageKey);
                if (stored && Object.prototype.hasOwnProperty.call(baseMaps, stored)) {
                    activeName = stored;
                }
            } catch (err) {
                console.warn('FaskesBasemaps: localStorage tidak dapat diakses:', err);
            }

            // Tambahkan layer aktif awal ke peta
            var activeLayer = baseMaps[activeName];
            activeLayer.addTo(map);

            // Tambahkan kontrol pilihan layer
            var control = L.control.layers(baseMaps, null, {
                position: position,
                collapsed: collapsed
            }).addTo(map);

            // Simpan perubahan basemap ke localStorage
            map.on('baselayerchange', function (e) {
                if (e && e.name) {
                    try {
                        window.localStorage.setItem(storageKey, e.name);
                    } catch (err) {
                        // ignore storage errors
                    }
                }
            });

            return {
                baseMaps: baseMaps,
                control: control,
                activeName: activeName,
                activeLayer: activeLayer
            };
        }
    };

    window.FaskesBasemaps = FaskesBasemaps;
})(window);
