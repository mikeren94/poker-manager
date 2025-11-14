function RakeTile({amount}) {
  return (
    <div className="bg-white shadow rounded-lg p-4 w-full">
      <h3 className="text-sm font-medium text-gray-500 mb-1">Rake paid</h3>
      <p className="text-2xl font-semibold text-red-600">${amount}</p>
    </div>
  );
}

export default RakeTile;