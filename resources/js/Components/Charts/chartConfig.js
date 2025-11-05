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

export const defaultChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
      labels: {
        color: '#333',
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
        color: '#666'
      },
      grid: {
        display: false
      }
    },
    y: {
      beginAtZero: true,
      ticks: {
        callback: value => `$${value}`,
        color: '#666'
      },
      grid: {
        color: '#eee'
      }
    }
  }
};