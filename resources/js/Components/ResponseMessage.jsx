function ResponseMessage({response}) {
    if (!response.message) return null;
    let classStyle = '';
    let heading = 'Error';
    switch (response.success) {
        case true:
            classStyle = 'bg-green-100 border-green-400 text-green-700';
            heading = 'Success';
            break;
        case false:
            classStyle = 'bg-red-100 border-red-400 text-red-700';
            break;
        default:
            classStyle = '';
            break;
    }
    return (
        <div className={`border p-1 mb-2 mt-2 ${classStyle}`}>
            <strong>{heading}:</strong> {response.message}
        </div>
    )
}

export default ResponseMessage;