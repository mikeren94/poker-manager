import { useState } from "react";
import LoadingSpinner from "@/Components/LoadingSpinner";
import ResponseMessage from "./ResponseMessage";
import Notice from "@/Components/Notice";
import { feedbackNotice } from "@/Constants/displayText";
function FeedbackForm() {
  const [feedback, setFeedback] = useState('');
  const [loading, setLoading] = useState(false);
  const [response, setResponse] = useState({
    success: null,
    message: ''
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await axios.post('/feedback', { feedback }, { withCredentials: true })
        .then((response) => {
            setResponse(response.data);
        });
    } catch (err) {
      const errorMsg = err.response?.data?.errors?.feedback?.[0] || 'Something went wrong. Please try again';
      setResponse({ success: false, message: errorMsg });
    } finally {
      setLoading(false);
      setFeedback('');
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <ResponseMessage response={response} />
      <div className="mb-4">
        <Notice message={feedbackNotice} />
        <label htmlFor="feedback" className="block mb-2 font-medium text-gray-700">
          Share your thoughts
        </label>
        <textarea
          id="feedback"
          value={feedback}
          onChange={(e) => setFeedback(e.target.value)}
          rows={4}
          className="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500"
          placeholder="Let us know what you think..."
        />
      </div>
      <button
        className="btn btn-blue ml-2"
        type="submit"
        disabled={loading}
      >
        {loading ? 'Submitting...' : 'Submit'}
      </button>
      {loading && <LoadingSpinner message="Submitting..." />}
    </form>
  );
}

export default FeedbackForm;