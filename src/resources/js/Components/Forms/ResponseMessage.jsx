function ResponseMessage({ response }) {
    if (!response || !response.message) return null;    

    return (
        <div className={`response-container border rounded-md p-4 mb-4 ${response.success ? 'bg-green-50 border-green-300 text-green-800' : 'bg-red-50 border-red-300 text-red-800'}`}>
            <h3 className="font-semibold text-lg mb-1">{response.success ? 'Success' : 'Error'}</h3>
            <p>{response.message}</p>
        </div>
    )
}

export default ResponseMessage;