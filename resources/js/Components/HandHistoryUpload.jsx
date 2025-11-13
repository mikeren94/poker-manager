import { useState, useRef } from "react";
import ResponseMessage from "./ResponseMessage";
import LoadingSpinner from "./LoadingSpinner";
import { DisplayText } from "@/Constants/displayText";
function HandHistoryUpload() {
    const [files, setFiles] = useState([]);
    const fileInputRef = useRef(null);
    const [loading, setLoading] = useState(false);
    const [response, setResponse] = useState({
        success: null,
        message: ''
    });
    const handleFileChange = (e) => {
        setFiles(Array.from(e.target.files));
    }

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        const formData = new FormData();

        files.forEach((file, index) => {
            formData.append(`hand_history[${index}]`, file);
        });

        try {
            await axios.post('/hands/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            withCredentials: true
            });
            setResponse({
            success: true,
            message: 'Files successfully uploaded!'
            });
        } catch (err) {
            const errorMsg = err.response?.data?.errors?.hand_history?.[0] || 'Something went wrong. Please try again';
            setResponse({ success: false, message: errorMsg });
        } finally {
            setLoading(false);
            setFiles([]);
            fileInputRef.current.value = '';
        }
        };
    return (
        <form onSubmit={handleSubmit}>
            <ResponseMessage response={response} />
            <div
                onDragOver={(e) => e.preventDefault()}
                onDrop={(e) => {
                    e.preventDefault();
                    setFiles(Array.from(e.dataTransfer.files));
                    fileInputRef.current.value = ''; // reset input
                }}
                className="border-2 border-dashed border-gray-400 p-4 mb-4 text-center cursor-pointer"
            >
            {DisplayText.uploadInstructions}
            </div>
            <ul className="mb-2">
            {files.map((file, i) => (
                <li key={i}>{file.name}</li>
            ))}
            </ul>
            <input 
                type="file" 
                accept=".txt" 
                onChange={handleFileChange}
                multiple
                ref={fileInputRef} 
            />
            <button 
                className="btn btn-blue" 
                type="submit"
                disabled={loading}
            >{loading ? 'Uploading...' : 'Upload'}</button>
            {loading && <LoadingSpinner message="Uploading..." />}
        </form>
    )
}

export default HandHistoryUpload;