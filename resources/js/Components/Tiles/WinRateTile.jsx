function WinRateTile({amount}) {
    const color = amount >= 0 ? 'text-green-600' : 'text-red-600';
    const prefix = amount >= 0 ? '+' : '';

    return (
        <div className="bg-white shadow rounded-lg p-4 w-full">
            <h3 className="text-sm font-medium text-gray-500 mb-1">Win rate</h3>
            <p className={`text-2xl font-semibold ${color}`}>{prefix}{amount}bb/100</p>
        </div>
    );
}

export default WinRateTile;