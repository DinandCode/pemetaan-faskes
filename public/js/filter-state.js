/**
 * filter-state.js
 * Modul Utilitas Mandiri Penyimpanan dan Pemulihan State Filter ke/dari URL & SessionStorage
 * Sistem Informasi Geografis Pemetaan Fasilitas Kesehatan - Kab. Banyumas
 * 
 * Prinsip:
 * 1. URL adalah SUMBER KEBENARAN TUNGGAL (Single Source of Truth).
 * 2. Hanya parameter non-default yang ditulis ke URL (URL pendek & bersih).
 * 3. history.replaceState digunakan agar tidak mencemari riwayat navigasi (Back/Forward).
 * 4. sessionStorage digunakan per-tab sebagai cadangan jika URL bersih dibuka kembali.
 * 5. Validasi ketat (whitelist, batas numerik, batas panjang teks <= 100 karakter).
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.FilterStateHelper = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    var MAX_URL_LENGTH = 1800;
    var MAX_TEXT_LENGTH = 100;

    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        var timeout;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Safe wrapper untuk sessionStorage (kebal private mode / cookies blocked)
     */
    var sessionStore = {
        get: function (key) {
            try {
                if (typeof window !== 'undefined' && window.sessionStorage) {
                    var val = window.sessionStorage.getItem(key);
                    return val ? JSON.parse(val) : null;
                }
            } catch (e) {
                /* private mode / quota exceeded */
            }
            return null;
        },
        set: function (key, value) {
            try {
                if (typeof window !== 'undefined' && window.sessionStorage) {
                    window.sessionStorage.setItem(key, JSON.stringify(value));
                    return true;
                }
            } catch (e) {
                /* ignore */
            }
            return false;
        },
        remove: function (key) {
            try {
                if (typeof window !== 'undefined' && window.sessionStorage) {
                    window.sessionStorage.removeItem(key);
                }
            } catch (e) {
                /* ignore */
            }
        }
    };

    /**
     * Membaca dan mem-parse URL search query string saat ini
     */
    function getQueryParamMap() {
        var params = {};
        if (typeof window === 'undefined' || !window.location.search) {
            return params;
        }
        var search = window.location.search.substring(1);
        var pairs = search.split('&');
        for (var i = 0; i < pairs.length; i++) {
            if (!pairs[i]) continue;
            var parts = pairs[i].split('=');
            var rawKey = decodeURIComponent(parts[0]);
            var rawVal = parts.length > 1 ? decodeURIComponent(parts[1].replace(/\+/g, ' ')) : '';

            // Dukungan parameter format array: key[sub]=val
            var bracketMatch = rawKey.match(/^([^\[]+)\[([^\]]*)\]$/);
            if (bracketMatch) {
                var mainKey = bracketMatch[1];
                var subKey = bracketMatch[2];
                if (!params[mainKey] || typeof params[mainKey] !== 'object') {
                    params[mainKey] = {};
                }
                params[mainKey][subKey] = rawVal;
            } else {
                params[rawKey] = rawVal;
            }
        }
        return params;
    }

    /**
     * Sanitasi teks umum (potong <= 100 karakter, buang karakter kontrol)
     */
    function sanitizeText(str) {
        if (typeof str !== 'string') return '';
        var clean = str.trim().replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
        if (clean.length > MAX_TEXT_LENGTH) {
            clean = clean.substring(0, MAX_TEXT_LENGTH);
        }
        return clean;
    }

    /**
     * Memperbarui query string di URL dengan history.replaceState
     * @param {Object} queryParams - Key-value pair yang akan dijadikan query string
     * @param {string} [pathname] - Path tujuan (default: window.location.pathname)
     */
    function replaceState(queryParams, pathname) {
        if (typeof window === 'undefined' || !window.history || !window.history.replaceState) {
            return;
        }

        var path = pathname || window.location.pathname;
        var parts = [];

        for (var key in queryParams) {
            if (!Object.prototype.hasOwnProperty.call(queryParams, key)) continue;
            var val = queryParams[key];
            if (val === null || val === undefined || val === '') continue;

            if (typeof val === 'object' && !Array.isArray(val)) {
                // Objek seperti cf[id]=val
                for (var subKey in val) {
                    if (!Object.prototype.hasOwnProperty.call(val, subKey)) continue;
                    var subVal = val[subKey];
                    if (subVal !== null && subVal !== undefined && subVal !== '') {
                        parts.push(encodeURIComponent(key + '[' + subKey + ']') + '=' + encodeURIComponent(subVal));
                    }
                }
            } else if (Array.isArray(val)) {
                if (val.length > 0) {
                    parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(val.join(',')));
                }
            } else {
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(val));
            }
        }

        var queryString = parts.length > 0 ? '?' + parts.join('&') : '';
        var newUrl = path + queryString + (window.location.hash || '');

        // Pengaman batas panjang URL
        if (newUrl.length > MAX_URL_LENGTH) {
            // Jika melebihi batas, hapus parameter non-esensial atau ringkas
            newUrl = path + (queryString.length > 0 ? queryString.substring(0, MAX_URL_LENGTH - path.length - 10) : '');
        }

        if (window.location.pathname + window.location.search !== path + queryString) {
            window.history.replaceState(null, '', newUrl);
        }
    }

    return {
        MAX_URL_LENGTH: MAX_URL_LENGTH,
        MAX_TEXT_LENGTH: MAX_TEXT_LENGTH,
        debounce: debounce,
        sessionStore: sessionStore,
        getQueryParamMap: getQueryParamMap,
        sanitizeText: sanitizeText,
        replaceState: replaceState
    };
}));
