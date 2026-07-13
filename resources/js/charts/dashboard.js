import ApexCharts from 'apexcharts';

const C = {
    present:   '#10b981',
    absent:    '#f43f5e',
    late:      '#f59e0b',
    excused:   '#8b5cf6',
    completed: '#10b981',
    scheduled: '#38bdf8',
    cancelled: '#f43f5e',
    missed:    '#f97316',
    revenue:   '#f59e0b',
};

function dataset(name) {
    const el = document.querySelector(`[data-chart="${name}"]`);
    if (!el) return null;
    try { return JSON.parse(el.dataset.chartData); } catch { return null; }
}

function base(type, height = 220) {
    return {
        chart: {
            type,
            height,
            background: 'transparent',
            toolbar: { show: false },
            fontFamily: 'Vazirmatn, sans-serif',
            animations: { enabled: true, speed: 500 },
        },
        theme: { mode: 'dark' },
        grid: { borderColor: '#1f2937', strokeDashArray: 4 },
        tooltip: { theme: 'dark' },
    };
}

/** Remove the skeleton overlay after a chart renders. */
function clearSkeleton(el) {
    el?.parentElement?.querySelector('.chart-skeleton')?.remove();
}

function initAttendanceTrend() {
    const data = dataset('attendance-trend');
    const el   = document.getElementById('chart-attendance-trend');
    if (!data || !el) return;

    const make = (key, color, name) => ({ name, data: data.map(d => d[key]), color });

    new ApexCharts(el, {
        ...base('line'),
        stroke:  { curve: 'smooth', width: 2.5 },
        markers: { size: 0 },
        series: [
            make('present', C.present, 'حاضر'),
            make('absent',  C.absent,  'غایب'),
            make('late',    C.late,    'تاخیر'),
            make('excused', C.excused, 'معذور'),
        ],
        xaxis: {
            categories: data.map(d => d.date.slice(5)),
            tickAmount: 6,
            labels: { style: { colors: '#6b7280' }, rotate: 0 },
            axisBorder: { show: false },
            axisTicks:  { show: false },
        },
        yaxis:  { labels: { style: { colors: '#6b7280' } }, min: 0 },
        legend: { position: 'top', labels: { colors: '#d1d5db' } },
    }).render().then(() => clearSkeleton(el));
}

function initTeacherWorkload() {
    const data = dataset('teacher-workload');
    const el   = document.getElementById('chart-teacher-workload');
    if (!data || !el) return;

    new ApexCharts(el, {
        ...base('bar'),
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
        series: [{ name: 'جلسات', data: data.map(d => d.total), color: C.present }],
        xaxis: { categories: data.map(d => d.teacher), labels: { style: { colors: '#6b7280' } } },
        yaxis: { labels: { style: { colors: '#d1d5db' }, maxWidth: 120 } },
        legend: { show: false },
        dataLabels: { enabled: false },
    }).render().then(() => clearSkeleton(el));
}

function initSessionStatus() {
    const data = dataset('session-status');
    const el   = document.getElementById('chart-session-status');
    if (!data || !el) return;

    const total = Object.values(data).reduce((a, b) => a + b, 0);
    if (total === 0) {
        clearSkeleton(el);
        el.innerHTML = '<p class="py-16 text-center text-sm text-gray-500">هنوز داده‌ای ثبت نشده</p>';
        return;
    }

    new ApexCharts(el, {
        ...base('donut'),
        series: [data.completed, data.scheduled, data.cancelled, data.missed],
        labels: ['تکمیل‌شده', 'برنامه‌ریزی‌شده', 'لغو‌شده', 'از دست‌رفته'],
        colors: [C.completed, C.scheduled, C.cancelled, C.missed],
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'کل', color: '#d1d5db' } } } } },
        legend: { position: 'bottom', labels: { colors: '#d1d5db' } },
        dataLabels: { enabled: false },
        stroke: { width: 0 },
    }).render().then(() => clearSkeleton(el));
}

function initLeadSources() {
    const data = dataset('lead-sources');
    const el   = document.getElementById('chart-lead-sources');
    if (!data || !el) return;

    new ApexCharts(el, {
        ...base('bar'),
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
        series: [{ name: 'سرنخ‌ها', data: data.map(d => d.total), color: C.excused }],
        xaxis: { categories: data.map(d => d.source), labels: { style: { colors: '#6b7280' } } },
        yaxis: { labels: { style: { colors: '#d1d5db' }, maxWidth: 120 } },
        legend: { show: false },
        dataLabels: { enabled: false },
    }).render().then(() => clearSkeleton(el));
}

function initMonthlyRevenue() {
    const data = dataset('monthly-revenue');
    const el   = document.getElementById('chart-monthly-revenue');
    if (!data || !el) return;

    new ApexCharts(el, {
        ...base('area'),
        stroke: { curve: 'smooth', width: 2 },
        fill:   { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90] } },
        series: [{ name: 'درآمد (تومان)', data: data.map(d => d.revenue), color: C.revenue }],
        xaxis: {
            categories: data.map(d => d.month),
            labels: { style: { colors: '#6b7280' } },
            axisBorder: { show: false },
            axisTicks:  { show: false },
        },
        yaxis:      { labels: { style: { colors: '#6b7280' } }, min: 0 },
        legend:     { show: false },
        dataLabels: { enabled: false },
    }).render().then(() => clearSkeleton(el));
}

document.addEventListener('DOMContentLoaded', () => {
    initAttendanceTrend();
    initTeacherWorkload();
    initSessionStatus();
    initMonthlyRevenue();
    initLeadSources();
});
