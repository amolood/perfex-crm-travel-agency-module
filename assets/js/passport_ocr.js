/**
 * Client-side passport MRZ auto-fill: when a staff member selects a passport scan image, OCR
 * runs entirely in the browser (Tesseract.js, self-hosted - no data ever leaves the browser for
 * this step) to read the machine-readable zone and pre-fill the traveler's passport detail
 * fields. Staff review/correct the results before saving - nothing is submitted automatically.
 *
 * Deliberately isolated from passport_scan upload/save, which still happens the normal way via
 * upload_group_member_file(); this only assists filling the form faster.
 */
(function () {
    'use strict';

    var VENDOR_BASE = typeof travel_agency_assets_base !== 'undefined'
        ? travel_agency_assets_base + 'js/vendor/tesseract/'
        : null;

    var worker = null;
    var workerPromise = null;

    function getWorker() {
        if (workerPromise) {
            return workerPromise;
        }

        if (!VENDOR_BASE || typeof Tesseract === 'undefined') {
            return Promise.reject(new Error('Tesseract.js not loaded'));
        }

        workerPromise = Tesseract.createWorker('eng', 1, {
            workerPath: VENDOR_BASE + 'worker.min.js',
            // Passed as a directory (not a specific .js file) so Tesseract.js can pick
            // tesseract-core-simd-lstm.wasm.js or fall back to tesseract-core-lstm.wasm.js
            // itself based on the browser's actual WASM SIMD support - both are bundled here.
            corePath: VENDOR_BASE,
            langPath: VENDOR_BASE,
            gzip: true,
            cacheMethod: 'none',
        }).then(function (createdWorker) {
            worker = createdWorker;

            // MRZ text is fixed-width uppercase A-Z, digits, and '<' only - restricting the
            // recognized character set measurably improves accuracy over general-purpose OCR.
            return worker.setParameters({
                tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            }).then(function () {
                return worker;
            });
        });

        return workerPromise;
    }

    /**
     * @param {File} file
     * @return {Promise<object|null>}  resolved MRZ fields, or null if no MRZ could be read
     */
    function scanPassportFile(file) {
        return getWorker().then(function (readyWorker) {
            return readyWorker.recognize(file);
        }).then(function (result) {
            var text = (result && result.data && result.data.text) || '';

            return window.TravelAgencyMrz.findAndParseMrz(text);
        });
    }

    window.TravelAgencyPassportOcr = {
        scanPassportFile: scanPassportFile,
    };
})();
