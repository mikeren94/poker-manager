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

            setChartData({
                labels,
                datasets: [{
                    label: 'Profit Over Time',
                    data: profits,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.3,
                    fill: true
                }]
            })
        }); 
    }, []);

    return (
        <div className="bg-white shadow rounded-lg p-4 w-full">
            {chartData ? <Line data={chartData} options={defaultChartOptions} /> : <p>Loading chart...</p>}
        </div>
    )
}

export default ProfitChart;