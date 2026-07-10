/**
 * Parser for the Machine Readable Zone (MRZ) of TD3-format passports (ICAO Doc 9303 Part 4).
 *
 * A TD3 MRZ is exactly two 44-character lines, e.g.:
 *   P<GBRSMITH<<JOHN<<<<<<<<<<<<<<<<<<<<<<<<<<<<
 *   1234567897GBR8001019M2501011<<<<<<<<<<<<<<08
 *
 * Every parsed field is returned alongside whether its own check digit is valid, so the caller
 * can decide how much to trust OCR output that only partially matches the expected format
 * (common with real-world scans: glare, skew, worn document laminate, etc).
 */
(function (global) {
    'use strict';

    var WEIGHTS = [7, 3, 1];

    /**
     * ICAO check-digit algorithm: each character maps to a value (0-9 as itself, A-Z as 10-35,
     * '<' as 0), multiplied by a repeating 7/3/1 weight pattern, summed, then mod 10.
     * @param {string} input
     * @return {number}
     */
    function checkDigit(input) {
        var sum = 0;

        for (var i = 0; i < input.length; i++) {
            sum += charValue(input.charAt(i)) * WEIGHTS[i % 3];
        }

        return sum % 10;
    }

    function charValue(ch) {
        if (ch === '<') {
            return 0;
        }

        if (ch >= '0' && ch <= '9') {
            return ch.charCodeAt(0) - 48;
        }

        if (ch >= 'A' && ch <= 'Z') {
            return ch.charCodeAt(0) - 55;
        }

        return 0;
    }

    function verifyCheckDigit(field, digit) {
        if (!/^[0-9]$/.test(digit)) {
            return false;
        }

        return checkDigit(field) === parseInt(digit, 10);
    }

    /**
     * MRZ dates are YYMMDD with a 2-digit year - resolved using the same century-pivot
     * convention passport software conventionally uses: a two-digit year greater than the
     * current two-digit year is assumed to be the previous century (relevant for birth dates
     * of older travelers), otherwise the current century.
     * @param {string} yymmdd
     * @param {boolean} isExpiry  expiry dates are always assumed to be this century or later
     * @return {string|null}  ISO YYYY-MM-DD, or null if not a plausible date
     */
    function resolveMrzDate(yymmdd, isExpiry) {
        if (!/^[0-9]{6}$/.test(yymmdd)) {
            return null;
        }

        var yy    = parseInt(yymmdd.substring(0, 2), 10);
        var month = parseInt(yymmdd.substring(2, 4), 10);
        var day   = parseInt(yymmdd.substring(4, 6), 10);

        if (month < 1 || month > 12 || day < 1 || day > 31) {
            return null;
        }

        var currentYY = new Date().getFullYear() % 100;
        var century;

        if (isExpiry) {
            century = yy < currentYY ? 2100 : 2000;
        } else {
            century = yy > currentYY ? 1900 : 2000;
        }

        var year = century + yy;

        return year + '-' + pad(month) + '-' + pad(day);
    }

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    /**
     * Strip filler characters and collapse '<' name separators into a single space.
     * @param {string} field
     * @return {string}
     */
    function cleanName(field) {
        return field.replace(/</g, ' ').trim().replace(/\s+/g, ' ');
    }

    var COUNTRY_TO_GENDER = { M: 'M', F: 'F' };

    /**
     * OCR very commonly miscounts a long run of identical '<' filler characters by one or two -
     * both a human eye and Tesseract can merge or drop repeated identical glyphs, and TD3 lines
     * have long filler runs by design (the personal-number field on line 2, name padding on
     * line 1). A raw line that is off by a couple of characters from the required 44 is, in
     * practice, almost always a filler-run miscount rather than genuinely missing/extra data -
     * every fixed-position field before the trailing filler stays intact either way.
     *
     * This normalizes length by adjusting a trailing run of '<' specifically, rather than
     * blindly padding/truncating from the very end of the string (which would corrupt the final
     * check digit whenever the miscounted run isn't already the last thing on the line).
     *
     * @param  {string} line
     * @return {string}  exactly 44 characters
     */
    function normalizeLineLength(line) {
        if (line.length === 44) {
            return line;
        }

        // Find the last run of '<' characters and the (possible) single trailing non-'<'
        // character after it (line 2's final check digit sits right after the filler run).
        var match = line.match(/(<+)([^<]?)$/);

        if (!match) {
            return line.padEnd(44, '<').substring(0, 44);
        }

        var fillerRun = match[1];
        var trailer    = match[2] || '';
        var beforeFillerLen = line.length - fillerRun.length - trailer.length;
        var neededFillerLen = 44 - beforeFillerLen - trailer.length;

        if (neededFillerLen < 0) {
            return line.padEnd(44, '<').substring(0, 44);
        }

        return line.substring(0, beforeFillerLen) + '<'.repeat(neededFillerLen) + trailer;
    }

    /**
     * Parse two raw MRZ lines (already OCR'd / typed, uppercase, '<' as filler) into structured
     * passport fields.
     *
     * @param {string} line1
     * @param {string} line2
     * @return {object|null}  null if the input isn't shaped like a TD3 MRZ at all; otherwise an
     *                        object with every field plus a `checksValid` breakdown and an
     *                        overall `confidence` ('high'|'low') based on how many of the
     *                        individual check digits actually validated.
     */
    function parseTD3(line1, line2) {
        line1 = normalizeLineLength((line1 || '').toUpperCase().replace(/\s/g, ''));
        line2 = normalizeLineLength((line2 || '').toUpperCase().replace(/\s/g, ''));

        if (line1.charAt(0) !== 'P' || line1.length !== 44 || line2.length !== 44) {
            return null;
        }

        // The document-type field is positions 0-1 (2 characters) per ICAO 9303, not just a
        // single 'P' followed by a filler - many issuers (Sudan among them) use a second letter
        // here (e.g. "PC"), so position 1 is NOT reliably '<' and must not be force-corrected to
        // one; doing so previously shifted the entire rest of line 1 and corrupted the issuing
        // country, names, and nationality fields on exactly this kind of real-world document.
        var namesField      = line1.substring(5, 44);
        var nameParts       = namesField.split('<<');
        var surname         = cleanName(nameParts[0] || '');
        var givenNames      = cleanName(nameParts.slice(1).join(' ') || '');

        var passportNumber       = line2.substring(0, 9);
        var passportNumberCheck  = line2.charAt(9);
        // Nationality is a line 2 field (position 10-13), immediately after the passport
        // number's own check digit - it is NOT on line 1 (line 1's position 10-13 falls inside
        // the names field). Reading it from line1 was a pre-existing bug that returned a
        // fragment of the surname/given-names instead of the actual nationality code.
        var nationality           = line2.substring(10, 13);
        var birthDateRaw         = line2.substring(13, 19);
        var birthDateCheck       = line2.charAt(19);
        var sex                  = line2.charAt(20);
        var expiryDateRaw        = line2.substring(21, 27);
        var expiryDateCheck      = line2.charAt(27);
        var finalCheck           = line2.charAt(43);
        var compositeField       = line2.substring(0, 10) + line2.substring(13, 20) + line2.substring(21, 43);

        var checksValid = {
            passportNumber: verifyCheckDigit(passportNumber, passportNumberCheck),
            birthDate:      verifyCheckDigit(birthDateRaw, birthDateCheck),
            expiryDate:     verifyCheckDigit(expiryDateRaw, expiryDateCheck),
            composite:      verifyCheckDigit(compositeField, finalCheck),
        };

        var validCount = Object.keys(checksValid).filter(function (key) {
            return checksValid[key];
        }).length;

        return {
            documentType:      'P',
            issuingCountry:    line1.substring(2, 5).replace(/</g, ''),
            surname:           surname,
            givenNames:        givenNames,
            nationality:       nationality.replace(/</g, ''),
            passportNumber:    passportNumber.replace(/</g, ''),
            dateOfBirth:       resolveMrzDate(birthDateRaw, false),
            sex:               COUNTRY_TO_GENDER[sex] || '',
            passportExpiry:    resolveMrzDate(expiryDateRaw, true),
            checksValid:       checksValid,
            confidence:        validCount >= 3 ? 'high' : (validCount >= 1 ? 'low' : 'none'),
            // ICAO 9303 has no check digit covering line 1 at all (issuing country, surname,
            // given names, nationality) - only line 2's passport number/birth date/expiry/
            // composite are cryptographically verifiable. A "high" confidence score only means
            // line 2 checked out; it says nothing about whether the name was read correctly, so
            // the name fields must always be flagged for manual verification regardless of the
            // overall confidence level.
            namesVerifiable:   false,
            rawLine1:          line1,
            rawLine2:          line2,
        };
    }

    /**
     * Scan free-form OCR text for two lines that look like a TD3 MRZ (mostly uppercase
     * letters/digits/'<', roughly 44 chars each) and parse them. OCR output is noisy - this
     * tolerates minor length drift and joins wrapped lines.
     *
     * @param {string} ocrText
     * @return {object|null}
     */
    function findAndParseMrz(ocrText) {
        // Only require a plausible MRZ-shaped line (mostly A-Z/0-9/'<', long enough to be a
        // TD3 line) here, rather than also requiring the first character to already be a
        // literal 'P' - a single misread leading character (a stray mark, a '1' from nearby
        // page noise, etc.) would otherwise drop line 1 entirely before parseTD3() ever gets a
        // chance to run its own, more precise checks.
        var candidateLines = (ocrText || '')
            .toUpperCase()
            .split(/\r?\n/)
            .map(function (line) {
                return line.replace(/[^A-Z0-9<]/g, '');
            })
            .filter(function (line) {
                return line.length >= 30 && /^[A-Z0-9<]+$/.test(line);
            });

        // Try every adjacent pair, not just ones where the first line already starts with 'P' -
        // parseTD3() itself is the authority on whether a given pair is actually a valid TD3
        // MRZ (it re-checks the leading character, length, and structure); this loop's only job
        // is to offer it plausible candidates.
        for (var i = 0; i < candidateLines.length - 1; i++) {
            var result = parseTD3(candidateLines[i], candidateLines[i + 1]);

            if (result) {
                return result;
            }
        }

        return null;
    }

    global.TravelAgencyMrz = {
        parseTD3: parseTD3,
        findAndParseMrz: findAndParseMrz,
    };
})(window);
