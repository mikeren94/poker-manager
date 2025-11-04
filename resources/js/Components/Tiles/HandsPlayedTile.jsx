function HandsPlayedTile({amount, total}) {
  return (
    <div className="bg-white shadow rounded-lg p-4 w-full">
      <h3 className="text-sm font-medium text-gray-500 mb-1">Hands played</h3>
      <p className="text-2xl font-semibold text-gray-600">{amount}</p>
      <p className="text-sm text-gray-400 mt-1">Out of {total}</p>
    </div>
  );
}

export default HandsPlayedTile;