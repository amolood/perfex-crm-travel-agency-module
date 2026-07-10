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

            return worker;
        });

        return workerPromise;
    }

    // MRZ text is fixed-width uppercase A-Z, digits, and '<' only - once Tesseract has
    // correctly isolated the MRZ text block, restricting recognition to this character set
    // measurably improves accuracy (it can no longer confuse an 'O'/'0' or similar with a
    // character that couldn't appear in an MRZ). It is only safe to apply once segmentation is
    // already working, though, since applying it to the layout pass below (whole busy passport
    // photo: face, printed fields, background) instead makes segmentation itself worse.
    var MRZ_CHAR_WHITELIST = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<';

    /**
     * Run a single OCR pass with the given Tesseract page-segmentation mode and character
     * whitelist.
     * @param  {Tesseract.Worker} readyWorker
     * @param  {File}             file
     * @param  {string}           psm       Tesseract PSM value, see tesseract_pageseg_mode
     * @param  {string|null}      whitelist character whitelist, or null to leave unrestricted
     * @return {Promise<string>}  recognized text
     */
    function ocrPass(readyWorker, file, psm, whitelist) {
        return readyWorker.setParameters({
            tessedit_pageseg_mode: psm,
            tessedit_char_whitelist: whitelist || '',
        }).then(function () {
            return readyWorker.recognize(file);
        }).then(function (result) {
            return (result && result.data && result.data.text) || '';
        });
    }

    /**
     * @param {File} file
     * @return {Promise<object|null>}  resolved MRZ fields, or null if no MRZ could be read
     */
    function scanPassportFile(file) {
        return getWorker().then(function (readyWorker) {
            // Pass 1: PSM 6 (treat the image as one uniform block of text) with the MRZ
            // character whitelist applied. This is the standard configuration recommended for
            // MRZ OCR and works well whenever the MRZ dominates the frame (a close, well-lit
            // photo cropped fairly tightly to the passport's data page - the common case here).
            return ocrPass(readyWorker, file, '6', MRZ_CHAR_WHITELIST).then(function (text) {
                var mrz = window.TravelAgencyMrz.findAndParseMrz(text);

                if (mrz) {
                    return mrz;
                }

                // Pass 2 (fallback): PSM 11 (sparse text, no assumed reading order) with no
                // character whitelist. This handles busier/wider photos - visible face, printed
                // data fields, background pattern around the MRZ - where forcing a single
                // uniform text block in pass 1 caused Tesseract's own layout analysis to merge
                // or drop the MRZ region entirely, which reads as "no MRZ found" even though the
                // photo itself is perfectly sharp. Only worth the extra ~same-length OCR pass
                // when pass 1 didn't already succeed.
                return ocrPass(readyWorker, file, '11', null).then(function (text2) {
                    return window.TravelAgencyMrz.findAndParseMrz(text2);
                });
            });
        });
    }

    window.TravelAgencyPassportOcr = {
        scanPassportFile: scanPassportFile,
    };
})();
