<script>
/**
 * Alpine.js component for split year/month/day date input.
 *
 * Prevents 5-digit years by using three separate number inputs.
 * Year is restricted to 2010–2099.
 * Computes the Jalali (Shamsi) equivalent client-side.
 *
 * @param {string} fieldName  - used only for identification (not DOM binding)
 * @param {string} initial    - initial ISO date string "YYYY-MM-DD" or ""
 */
function dateForm(fieldName, initial) {
    return {
        year:  '',
        month: '',
        day:   '',
        jalali: '',
        isoValue: '',

        init() {
            if (initial && initial.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const parts = initial.split('-');
                this.year  = parts[0];
                this.month = String(parseInt(parts[1]));
                this.day   = String(parseInt(parts[2]));
                this.onDateChange();
            } else {
                // Default to today
                const today = new Date();
                this.year  = today.getFullYear();
                this.month = today.getMonth() + 1;
                this.day   = today.getDate();
                this.onDateChange();
            }
        },

        padYear() {
            // Clamp year to 4 digits, 2010–2099
            let y = parseInt(this.year) || 0;
            if (y < 2010) y = 2010;
            if (y > 2099) y = 2099;
            this.year = String(y);
            this.onDateChange();
        },

        onDateChange() {
            const y = parseInt(this.year);
            const m = parseInt(this.month);
            const d = parseInt(this.day);

            if (y >= 2010 && y <= 2099 && m >= 1 && m <= 12 && d >= 1 && d <= 31) {
                const mm = String(m).padStart(2, '0');
                const dd = String(d).padStart(2, '0');
                this.isoValue = `${y}-${mm}-${dd}`;
                this.jalali = this.toJalali(y, m, d);
            } else {
                this.isoValue = '';
                this.jalali = '';
            }
        },

        /**
         * Lightweight Gregorian → Jalali conversion.
         * Matches the PHP Jalalian::toJalali algorithm.
         */
        toJalali(gy, gm, gd) {
            const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            const gy2 = gm > 2 ? gy + 1 : gy;
            let days = 355666
                + (365 * gy)
                + Math.floor((gy2 + 3) / 4)
                - Math.floor((gy2 + 99) / 100)
                + Math.floor((gy2 + 399) / 400)
                + gd
                + g_d_m[gm - 1];
            let jy = -1595 + (33 * Math.floor(days / 12053));
            days = days % 12053;
            jy += 4 * Math.floor(days / 1461);
            days %= 1461;
            if (days > 365) {
                jy += Math.floor((days - 1) / 365);
                days = (days - 1) % 365;
            }
            let jm, jd;
            if (days < 186) {
                jm = 1 + Math.floor(days / 31);
                jd = 1 + (days % 31);
            } else {
                jm = 7 + Math.floor((days - 186) / 30);
                jd = 1 + ((days - 186) % 30);
            }
            return `${jy}/${String(jm).padStart(2,'0')}/${String(jd).padStart(2,'0')}`;
        },
    };
}
</script>
