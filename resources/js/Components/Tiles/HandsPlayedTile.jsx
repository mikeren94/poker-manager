function HandsPlayedTile({amount}) {
  return (
    <div className="bg-white shadow rounded-lg p-4 w-full">
      <h3 className="text-sm font-medium text-gray-500 mb-1">Hands played</h3>
      <p className="text-2xl font-semibold text-green-600">{amount}</p>
    </div>
  );
}

export default HandsPlayedTile;