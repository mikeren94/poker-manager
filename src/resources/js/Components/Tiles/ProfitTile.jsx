function VpipTile({amount}) {
    
  return (
    <div className="bg-white shadow rounded-lg p-4 w-full">
        <h3 className="text-sm font-medium text-gray-500 mb-1">Profit</h3>
        <p
        className={`text-2xl font-semibold ${
            amount > 0
            ? 'text-green-600'
            : amount < 0
            ? 'text-red-600'
            : 'text-gray-500'
        }`}
        >
        ${parseFloat(amount).toFixed(2)}
        </p>
    </div>
  );
}

export default VpipTile;