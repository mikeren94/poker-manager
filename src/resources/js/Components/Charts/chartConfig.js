// chartConfig.js
import {
  Chart as ChartJS,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  TimeScale,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import 'chartjs-adapter-date-fns';

ChartJS.register(
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  TimeScale,
  Title,
  Tooltip,
  Legend,
  Filler
);

export const getChartOptions = (isDark = false) => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
      labels: {
        color: isDark ? '#f3f4f6' : '#333', // gray-100 vs gray-800
        font: { size: 12 }
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: context => `Profit: $${context.raw.toFixed(2)}`
      }
    }
  },
  scales: {
    x: {
      type: 'time',
      time: {
        unit: 'day',
        tooltipFormat: 'MMM d, yyyy',
        displayFormats: {
          day: 'MMM d',
          week: 'MMM d',
          month: 'MMM yyyy'
        }
      },
      ticks: {
        autoSkip: true,
        maxTicksLimit: 10,
        color: isDark ? '#f3f4f6' : '#666' // gray-100 vs gray-600
      },
      grid: {
        display: false
      }
    },
    y: {
      beginAtZero: true,
      ticks: {
        callback: value => `$${Number(value).toFixed(2)}`,
        color: isDark ? '#f3f4f6' : '#666'
      },
      grid: {
        color: isDark ? 'rgba(75, 85, 99, 0.2)' : '#eee' // gray-600 vs light
      }
    }
  }
});