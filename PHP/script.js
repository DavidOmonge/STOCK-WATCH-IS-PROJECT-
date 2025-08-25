// Render a bar chart using data from the database API
(() => {
  const canvas = document.getElementById('myChart');
  if (!canvas) return; // Safeguard for pages without the chart

  async function loadAndRender() {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);

    try {
      const res = await fetch('database.php?action=supplier_quantities', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
        signal: controller.signal
      });

      if (!res.ok) throw new Error('HTTP ' + res.status);
      const payload = await res.json();

      const rows = Array.isArray(payload?.data) ? payload.data : [];
      const safeRows = rows.filter(r => typeof r?.supplier === 'string' && Number.isFinite(Number(r?.total_qty)));
      if (safeRows.length === 0) throw new Error('No valid data received');

      const labels = safeRows.map(r => r.supplier || 'Unknown');
      const values = safeRows.map(r => Number(r.total_qty));

      renderBarChart(canvas, labels, values);
    } catch (err) {
      console.error('Fetch/render error:', err);
    } finally {
      clearTimeout(timeout);
    }
  }

  function renderBarChart(el, labels, values) {
    const palette = [
      '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
      '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#22c55e'
    ];

    new Chart(el, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Units by Supplier',
          data: values,
          backgroundColor: labels.map((_, i) => palette[i % palette.length]),
          borderColor: '#1f2937',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        parsing: false,
        plugins: {
          legend: { display: false },
          title: { display: true, text: 'Stock by Supplier' },
          tooltip: { mode: 'index', intersect: false }
        },
        scales: {
          x: {
            ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 }
          },
          y: {
            beginAtZero: true,
            ticks: { precision: 0 }
          }
        }
      }
    });
  }

  loadAndRender();
})();
