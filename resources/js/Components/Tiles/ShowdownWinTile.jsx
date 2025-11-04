function ShowdownWinTile({value}) {
    function getShowdownStyle(value) {
        if (value <= 20) return { color: 'text-red-600', label: 'Leaky' };
        if (value <= 40) return { color: 'text-orange-500', label: 'Struggling' };
        if (value <= 60) return { color: 'text-yellow-500', label: 'Holding On' };
        if (value <= 80) return { color: 'text-lime-500', label: 'Solid' };
        return { color: 'text-green-600', label: 'Crusher' };
    }

    
    const {color, label} = getShowdownStyle(value);

    return (
        <div className="bg-white shadow rounded-lg p-4 w-full">
            <h3 className="text-sm font-medium text-gray-500 mb-1">Showdown win</h3>
            <p className={`text-2xl font-semibold ${color}`}>{value}%</p>
            <p className="text-sm text-gray-400 mt-1">{label}</p>
        </div>
    );
}

export default ShowdownWinTile;