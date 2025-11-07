import { defaultChartOptions } from './chartConfig';
import { useEffect, useState } from 'react';
import axios from 'axios';
import { Line } from 'react-chartjs-2';

function ProfitChart() {
    const [chartData, setChartData] = useState(null);

    useEffect(() => {
        axios.get('/charts/profit-over-time').then(res => {
            const labels = res.data.map(d => d.date);
            const profits = res.data.map(d => d.profit);

            const lastProfit = profits[profits.length - 1];
            const lineColor = lastProfit >= 0 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)'; // Tailwind green-500 or red-500
            const fillColor = lastProfit >= 0 ? 'rgba(34, 197, 94, 0.2)' : 'rgba(239, 68, 68, 0.2)';
            setChartData({
                labels,
                datasets: [{
                    label: 'Profit Over Time',
                    data: profits,
                    borderColor: lineColor,
                    backgroundColor: fillColor,
                    tension: 0,
                    fill: true,
                    pointRadius: 0
                }],
            })

            defaultChartOptions.scales.x.type = 'category';
            defaultChartOptions.scales.x.ticks.display = false;
        }); 
    }, []);

    return (
        <div className="bg-white shadow rounded-lg p-4 w-full">
            {chartData ? <Line data={chartData} options={defaultChartOptions} /> : <p>Loading chart...</p>}
        </div>
    )
}

export default ProfitChart;