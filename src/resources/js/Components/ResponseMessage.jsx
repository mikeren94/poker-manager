function ResponseMessage({ response }) {
    if (!response || !response.message) return null;

    const hasSuccesses = Array.isArray(response.successful) && response.successful.length > 0;
    const hasFailures = Array.isArray(response.failed) && response.failed.length > 0;
    
    console.log(hasSuccesses, hasFailures);
    let bg = 'bg-gray-50';
    let border = 'border-gray-300';
    let text = 'text-gray-800';
    let heading = 'Notice';

    if (hasSuccesses && !hasFailures) {
        bg = 'bg-green-50';
        border = 'border-green-300';
        text = 'text-green-800';
        heading = 'Success';
    } else if (!hasSuccesses && hasFailures) {
        bg = 'bg-red-50';
        border = 'border-red-300';
        text = 'text-red-800';
        heading = 'Upload Failed';
    } else if (hasSuccesses && hasFailures) {
        bg = 'bg-yellow-50';
        border = 'border-yellow-300';
        text = 'text-yellow-800';
        heading = 'Partial Success';
    }

  return (
    <div className={`border rounded-md p-4 mb-4 ${bg} ${border} ${text}`}>
      <h3 className="font-semibold text-lg mb-1">{heading}</h3>
      <p className="mb-2">{response.message}</p>

      {hasSuccesses && (
        <div className="mb-2">
          <p className="font-medium">Uploaded files:</p>
          <ul className="list-disc list-inside text-sm">
            {response.successful.map((file, idx) => (
              <li key={idx}>{file.filename}</li>
            ))}
          </ul>
        </div>
      )}

      {hasFailures && (
        <div>
          <p className="font-medium">Failed files:</p>
          <ul className="list-disc list-inside text-sm">
            {response.failed.map((file, idx) => (
              <li key={idx}>
                <span className="font-mono">{file.filename}</span> — {file.error}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}

export default ResponseMessage;