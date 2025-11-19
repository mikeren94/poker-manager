import { getChartOptions } from './chartConfig';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { Line } from 'react-chartjs-2';
import React from 'react';
import { ThemeContext } from '@/ThemeContext';


function ProfitChart() {
  const isDark = React.useContext(ThemeContext);
  const [chartData, setChartData] = useState(null);
  const [chartOptions, setChartOptions] = useState(null);

  // Fetch chart data once on mount
  useEffect(() => {
    const buildChart = async () => {
    const res = await axios.get('/charts/profit-chart-data');

    const labels = res.data.map(d => d.date);
    const profits = res.data.map(d => d.profit);
    const wonAtShowdown = res.data.map(d => d.won_at_showdown);

    const lastProfit = profits[profits.length - 1];
    const profitColor = lastProfit >= 0 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)';
    const profitFill = lastProfit >= 0 ? 'rgba(34, 197, 94, 0.2)' : 'rgba(239, 68, 68, 0.2)';

    setChartData({
      labels,
      datasets: [
        {
          label: 'Profit Over Time',
          data: profits,
          borderColor: profitColor,
          backgroundColor: profitFill,
          tension: 0.4,
          fill: true,
          pointRadius: 0
        },
        {
          label: 'Won at Showdown',
          data: wonAtShowdown,
          borderColor: 'rgb(59, 130, 246)', // blue-500
          backgroundColor: 'rgba(59, 130, 246, 0.1)', // subtle fill
          tension: 0.4,
          fill: false,
          pointRadius: 0
        }
      ]
    });
  };

  buildChart();
  }, []);

  // Build chart options when data or theme changes
  useEffect(() => {
    if (!chartData) return;

    const baseOptions = getChartOptions(isDark);
    const mergedOptions = {
      ...baseOptions,
      scales: {
        ...baseOptions.scales,
        x: {
          ...baseOptions.scales.x,
          type: 'category',
          ticks: {
            ...baseOptions.scales.x.ticks,
            display: false
          }
        }
      },
      plugins: {
        ...baseOptions.plugins,
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.dataset.label || '';
              const value = context.formattedValue;
              return `${label}: ${value}`;
            }
          }
        },
        legend: {
          display: true,
          onClick: (e, legendItem, legend) => {
            const index = legendItem.datasetIndex;
            const ci = legend.chart;
            const meta = ci.getDatasetMeta(index);
            meta.hidden = meta.hidden === null ? !ci.data.datasets[index].hidden : null;
            ci.update();
          }
        }
      }
    };

    setChartOptions(mergedOptions);
  }, [isDark, chartData]);

  return (
    <div className="bg-white shadow rounded-lg p-4 w-full">
      {chartData && chartOptions ? (
        <Line data={chartData} options={chartOptions} />
      ) : (
        <p>Loading chart...</p>
      )}
    </div>
  );
}

export default ProfitChart;