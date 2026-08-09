/* TheFarmConcept — Chart wrappers (Chart.js 4)
   ห่อ Chart.js ไว้ชั้นเดียว เพื่อให้ทุกหน้าที่มีกราฟใช้ค่ามาตรฐานเดียวกัน: ฟอนต์ Kanit, โทนสีเขียวอ่อนของระบบ,
   grid เท่าที่จำเป็น, tooltip ภาษาไทย และ legend ที่แสดงทั้งจำนวนและเปอร์เซ็นต์
   จับคู่กับ CSS .chart-card / .chart-canvas / .chart-legend ใน components.css

   โหลดหลัง Chart.js (CDN) และหลัง mock-data.js (ต้องใช้ TFC.escapeHtml)
   หาก Chart.js โหลดไม่สำเร็จ (เช่นเปิดแบบออฟไลน์) ทุกฟังก์ชันจะ fallback เป็น .chart-placeholder แทน ไม่ throw

   API
   ---
   TFC.charts.donut(canvasId, { labels, values, colors, centerLabel, legendMountId })
   TFC.charts.bar(canvasId, { labels, values, horizontal, color, valueSuffix, maxValue })
   TFC.charts.stackedBar(canvasId, { labels, datasets: [{ label, values, color }] })
   TFC.charts.radar(canvasId, { labels, values, max })
   ทุกฟังก์ชันคืนค่า Chart instance (หรือ null เมื่อ fallback) และ destroy กราฟเดิมของ canvas นั้นให้อัตโนมัติ */
window.TFC = window.TFC || {};

(function () {
  var PALETTE = {
    primary: '#81C060',
    primarySoft: '#A8D69B',
    /* โทน Pastel/Soft สำหรับ Donut — ไล่จากสีระบบไปสีเสริม */
    soft: ['#A8D69B', '#8FC7E8', '#F6C177', '#E79AA6', '#B9A6E0', '#8ED7C8', '#D9CBA0'],
    success: '#A8DDB0',
    warning: '#FBD38D',
    neutral: '#D8DEE4',
    danger: '#F3A6A6',
    info: '#9EC5F5',
    grid: '#EEF1EC',
    text: '#6B7280',
    textStrong: '#1F2937'
  };

  var instances = {};

  function ready() { return typeof window.Chart !== 'undefined'; }

  function canvasOf(canvasId) {
    return typeof canvasId === 'string' ? document.getElementById(canvasId) : canvasId;
  }

  /* เปิดไฟล์แบบไม่มีอินเทอร์เน็ต -> แสดงกล่อง placeholder แทนกราฟ เพื่อไม่ให้หน้าจอพัง */
  function fallback(canvas, message) {
    if (!canvas) return null;
    var wrap = canvas.parentElement;
    if (!wrap || wrap.querySelector('.chart-placeholder')) return null;
    var box = document.createElement('div');
    box.className = 'chart-placeholder';
    box.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
      '<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg>' +
      '<span class="small">' + window.TFC.escapeHtml(message || 'ไม่สามารถโหลดกราฟได้') + '</span>';
    canvas.classList.add('hidden');
    wrap.appendChild(box);
    return null;
  }

  function destroyPrevious(canvas) {
    var key = canvas.id || (canvas.dataset.chartKey = canvas.dataset.chartKey || String(Date.now() + Math.random()));
    if (instances[key]) instances[key].destroy();
    return function (chart) { instances[key] = chart; return chart; };
  }

  function baseFont(size, weight) {
    return { family: 'Kanit, sans-serif', size: size || 12, weight: weight || 400 };
  }

  function tooltipStyle(extra) {
    return Object.assign({
      backgroundColor: 'rgba(31, 41, 55, 0.92)',
      titleFont: baseFont(13, 500),
      bodyFont: baseFont(13),
      padding: 10,
      cornerRadius: 8,
      displayColors: false
    }, extra || {});
  }

  function percent(value, total) {
    return total ? Math.round((value / total) * 100) : 0;
  }

  /* Plugin: ยอดรวมกลางวง Donut */
  var centerTotalPlugin = {
    id: 'tfcCenterTotal',
    afterDraw: function (chart) {
      var opts = chart.options.plugins.tfcCenterTotal;
      if (!opts || !opts.display) return;
      var ctx = chart.ctx;
      var meta = chart.getDatasetMeta(0);
      if (!meta.data.length) return;
      var center = meta.data[0];

      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = PALETTE.textStrong;
      ctx.font = '600 26px Kanit, sans-serif';
      ctx.fillText(String(opts.total), center.x, center.y - 6);
      ctx.fillStyle = PALETTE.text;
      ctx.font = '400 12px Kanit, sans-serif';
      ctx.fillText(opts.label || 'คน', center.x, center.y + 16);
      ctx.restore();
    }
  };

  /* Plugin: ตัวเลขจำนวนบนปลายแท่ง (แทน chartjs-plugin-datalabels เพื่อไม่เพิ่ม dependency) */
  var barValuePlugin = {
    id: 'tfcBarValue',
    afterDatasetsDraw: function (chart) {
      var opts = chart.options.plugins.tfcBarValue;
      if (!opts || !opts.display) return;
      var ctx = chart.ctx;
      var horizontal = chart.options.indexAxis === 'y';

      chart.data.datasets.forEach(function (dataset, datasetIndex) {
        chart.getDatasetMeta(datasetIndex).data.forEach(function (bar, index) {
          var value = dataset.data[index];
          if (!value) return;
          ctx.save();
          ctx.fillStyle = PALETTE.text;
          ctx.font = '500 12px Kanit, sans-serif';
          ctx.textBaseline = 'middle';
          if (horizontal) {
            ctx.textAlign = 'left';
            ctx.fillText(value + (opts.suffix || ''), bar.x + 6, bar.y);
          } else {
            ctx.textAlign = 'center';
            ctx.fillText(value + (opts.suffix || ''), bar.x, bar.y - 10);
          }
          ctx.restore();
        });
      });
    }
  };

  window.TFC.charts = {
    palette: PALETTE,

    /* Donut — ยอดรวมกลางวง + legend แสดงจำนวนและ % (สเปกกำหนดให้ทั้ง 3 การ์ดหน้าตาเหมือนกัน) */
    donut: function (canvasId, opts) {
      var canvas = canvasOf(canvasId);
      if (!canvas) return null;
      opts = opts || {};
      var values = opts.values || [];
      var labels = opts.labels || [];
      var colors = opts.colors || PALETTE.soft.slice(0, values.length);
      var total = values.reduce(function (sum, v) { return sum + v; }, 0);

      if (opts.legendMountId) {
        var legendEl = document.getElementById(opts.legendMountId);
        if (legendEl) {
          legendEl.innerHTML = labels.map(function (label, i) {
            return '<li class="chart-legend-item">' +
              '<span class="chart-legend-dot" style="background:' + colors[i % colors.length] + '"></span>' +
              '<span class="chart-legend-label">' + window.TFC.escapeHtml(label) + '</span>' +
              '<span class="chart-legend-value">' + values[i] + ' คน</span>' +
              '<span class="chart-legend-percent">' + percent(values[i], total) + '%</span>' +
              '</li>';
          }).join('');
        }
      }

      if (!ready()) return fallback(canvas, 'ไม่สามารถโหลดกราฟได้ (ดูข้อมูลจากรายการด้านล่าง)');
      var remember = destroyPrevious(canvas);

      return remember(new window.Chart(canvas, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '68%',
          plugins: {
            legend: { display: false },
            tfcCenterTotal: { display: true, total: total, label: opts.centerLabel || 'คน' },
            tooltip: tooltipStyle({
              callbacks: {
                label: function (ctx) {
                  return ' ' + ctx.label + ' ' + ctx.parsed + ' คน (' + percent(ctx.parsed, total) + '%)';
                }
              }
            })
          }
        },
        plugins: [centerTotalPlugin]
      }));
    },

    /* Bar — vertical (ค่าเริ่มต้น) หรือ horizontal, โทนเขียวอ่อนของระบบ */
    bar: function (canvasId, opts) {
      var canvas = canvasOf(canvasId);
      if (!canvas) return null;
      opts = opts || {};
      if (!ready()) return fallback(canvas);
      var remember = destroyPrevious(canvas);
      var horizontal = !!opts.horizontal;
      var valueAxis = horizontal ? 'x' : 'y';
      var labelAxis = horizontal ? 'y' : 'x';
      var scales = {};

      scales[valueAxis] = {
        beginAtZero: true,
        suggestedMax: opts.maxValue,
        grid: { color: PALETTE.grid, drawTicks: false },
        border: { display: false },
        ticks: { color: PALETTE.text, font: baseFont(12), precision: 0, padding: 6 }
      };
      scales[labelAxis] = {
        grid: { display: false },
        border: { display: false },
        ticks: { color: PALETTE.text, font: baseFont(12), padding: 4 }
      };

      return remember(new window.Chart(canvas, {
        type: 'bar',
        data: {
          labels: opts.labels || [],
          datasets: [{
            data: opts.values || [],
            backgroundColor: opts.color || PALETTE.primarySoft,
            hoverBackgroundColor: PALETTE.primary,
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.7,
            categoryPercentage: 0.75
          }]
        },
        options: {
          indexAxis: horizontal ? 'y' : 'x',
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: { right: horizontal ? 32 : 8, top: horizontal ? 4 : 18 } },
          plugins: {
            legend: { display: false },
            tfcBarValue: { display: true, suffix: opts.valueSuffix || '' },
            tooltip: tooltipStyle({
              callbacks: {
                label: function (ctx) {
                  var value = horizontal ? ctx.parsed.x : ctx.parsed.y;
                  return ' ' + value + (opts.valueSuffix || ' คน');
                }
              }
            })
          },
          scales: scales
        },
        plugins: [barValuePlugin]
      }));
    },

    /* Stacked Bar — ใช้กับการกระจายระดับความพึงพอใจรายหัวข้อ (horizontal, 100% ต่อหัวข้อ) */
    stackedBar: function (canvasId, opts) {
      var canvas = canvasOf(canvasId);
      if (!canvas) return null;
      opts = opts || {};
      if (!ready()) return fallback(canvas);
      var remember = destroyPrevious(canvas);

      return remember(new window.Chart(canvas, {
        type: 'bar',
        data: {
          labels: opts.labels || [],
          datasets: (opts.datasets || []).map(function (dataset) {
            return {
              label: dataset.label,
              data: dataset.values,
              backgroundColor: dataset.color,
              borderRadius: 4,
              borderSkipped: false,
              barPercentage: 0.75,
              categoryPercentage: 0.8
            };
          })
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { color: PALETTE.text, font: baseFont(12), boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle', padding: 16 }
            },
            tooltip: tooltipStyle({
              displayColors: true,
              callbacks: {
                label: function (ctx) { return ' ' + ctx.dataset.label + ': ' + ctx.parsed.x + ' คน'; }
              }
            })
          },
          scales: {
            x: {
              stacked: true,
              beginAtZero: true,
              grid: { color: PALETTE.grid, drawTicks: false },
              border: { display: false },
              ticks: { color: PALETTE.text, font: baseFont(12), precision: 0 }
            },
            y: {
              stacked: true,
              grid: { display: false },
              border: { display: false },
              ticks: { color: PALETTE.text, font: baseFont(12) }
            }
          }
        }
      }));
    },

    /* Radar — คะแนนเฉลี่ยรายด้าน (สเกล 0–5) */
    radar: function (canvasId, opts) {
      var canvas = canvasOf(canvasId);
      if (!canvas) return null;
      opts = opts || {};
      if (!ready()) return fallback(canvas);
      var remember = destroyPrevious(canvas);

      return remember(new window.Chart(canvas, {
        type: 'radar',
        data: {
          labels: opts.labels || [],
          datasets: [{
            data: opts.values || [],
            backgroundColor: 'rgba(129, 192, 96, 0.18)',
            borderColor: PALETTE.primary,
            borderWidth: 2,
            pointBackgroundColor: PALETTE.primary,
            pointRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: tooltipStyle({
              callbacks: { label: function (ctx) { return ' ' + ctx.parsed.r.toFixed(1) + ' คะแนน'; } }
            })
          },
          scales: {
            r: {
              beginAtZero: true,
              max: opts.max || 5,
              ticks: { stepSize: 1, color: PALETTE.text, font: baseFont(11), backdropColor: 'transparent' },
              grid: { color: PALETTE.grid },
              angleLines: { color: PALETTE.grid },
              pointLabels: { color: PALETTE.text, font: baseFont(12) }
            }
          }
        }
      }));
    }
  };
})();
