// Thin Chart.js helpers with a consistent palette across dashboards
(function () {
  'use strict';
  const palette = ['#4f46e5', '#0891b2', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#be185d', '#64748b'];
  Chart.defaults.font.family = "'Inter', -apple-system, sans-serif";
  Chart.defaults.color = '#64748b';
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 8;

  window.mkBarChart = function (canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: datasets.map((d, i) => ({ borderRadius: 6, backgroundColor: d.color || palette[i % palette.length], barThickness: 22, ...d })) },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: datasets.length > 1, position: 'bottom' } },
        scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } },
      },
    });
  };

  window.mkLineChart = function (canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: datasets.map((d, i) => ({
          borderColor: d.color || palette[i % palette.length],
          backgroundColor: (d.color || palette[i % palette.length]) + '22',
          tension: 0.35, fill: true, pointRadius: 3, pointHoverRadius: 5, borderWidth: 2,
          ...d,
        })),
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: datasets.length > 1, position: 'bottom' } },
        scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } },
      },
    });
  };

  window.mkDoughnutChart = function (canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
      type: 'doughnut',
      data: { labels, datasets: [{ data, backgroundColor: colors || palette, borderWidth: 0 }] },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '68%',
        plugins: { legend: { position: 'bottom' } },
      },
    });
  };

  window.chartPalette = palette;
})();
