function VpipTile({value}) {
  function getVpipStyle(value) {
    if (value <= 15) return { color: 'text-green-600', label: 'Nit' };
    if (value <= 25) return { color: 'text-lime-500', label: 'TAG' };
    if (value <= 35) return { color: 'text-yellow-500', label: 'Semi-loose' };
    if (value <= 45) return { color: 'text-orange-500', label: 'LAG' };
    return { color: 'text-red-600', label: 'Splashy' };
  }
  
  const {color, label} = getVpipStyle(value);

  return (
    <div className="bg-white shadow rounded-lg p-4 w-full">
      <h3 className="text-sm font-medium text-gray-500 mb-1">VPIP</h3>
      <p className={`text-2xl font-semibold ${color}`}>{value}%</p>
      <p className="text-sm text-gray-400 mt-1">{label}</p>
    </div>
  );
}

export default VpipTile;