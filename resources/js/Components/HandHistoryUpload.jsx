import { useState } from "react";
import ResponseMessage from "./ResponseMessage";
import LoadingSpinner from "./LoadingSpinner";

function HandHistoryUpload() {
    const [file, setFile] = useState(null);
    const [loading, setLoading] = useState(false);
    const [response, setResponse] = useState({
        success: null,
        message: ''
    });
    const handleFileChange = (e) => {
        setFile(e.target.files[0]);
    }

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        const formData = new FormData();
        formData.append('hand_history', file);

        try {
            await axios.post('/hands/upload', formData, {
                headers: {'Content-Type': 'multipart/form-data'},
                withCredentials: true
            });
            setResponse({
                success: true,
                message: 'File succesfully uploaded!'
            })
        } catch (err) {
            if(err.response?.data?.errors?.hand_history) {
                setResponse({
                    success: false,
                    message: err.response?.data?.errors?.hand_history[0]
                });
            } else {
                setResponse({
                    success: false,
                    message: 'Something went wrong. Please try again'
                });
            }
        } finally {
            setLoading(false);
        }
    }
    return (
        <form onSubmit={handleSubmit}>
            <ResponseMessage response={response} />
            <input type="file" accept=".txt" onChange={handleFileChange} />
            <button 
                className="btn btn-blue" 
                type="submit"
                disabled={loading}
            >{loading ? 'Uploading...' : 'Upload'}</button>
            {loading && <LoadingSpinner />}
        </form>
    )
}

export default HandHistoryUpload;