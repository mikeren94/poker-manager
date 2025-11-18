import { useState } from "react";
import LoadingSpinner from "@/Components/LoadingSpinner";
import ResponseMessage from "./ResponseMessage";

function FeedbackReplyForm({ feedbackId }) {
  const [reply, setReply] = useState('');
  const [loading, setLoading] = useState(false);
  const [response, setResponse] = useState({
    success: null,
    message: ''
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await axios.post(`/feedback/${feedbackId}/reply`, { message: reply }, { withCredentials: true });
      setResponse({
        success: true,
        message: 'Reply submitted successfully.'
      });
    } catch (err) {
      const errorMsg = err.response?.data?.errors?.message?.[0] || 'Something went wrong. Please try again';
      setResponse({ success: false, message: errorMsg });
    } finally {
      setLoading(false);
      setReply('');
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <ResponseMessage response={response} />
      <div className="mb-4">
        <label htmlFor="reply" className="block mb-2 font-medium text-gray-700 dark:text-gray-200">
          Add a follow-up or respond to the admin
        </label>
        <textarea
          id="reply"
          value={reply}
          onChange={(e) => setReply(e.target.value)}
          rows={3}
          className="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring focus:border-blue-500 dark:bg-gray-700 dark:text-gray-50 dark:border-gray-600"
          placeholder="Write your reply..."
        />
      </div>
      <button
        className="btn btn-blue ml-2"
        type="submit"
        disabled={loading}
      >
        {loading ? 'Submitting...' : 'Reply'}
      </button>
      {loading && <LoadingSpinner message="Submitting reply..." />}
    </form>
  );
}

export default FeedbackReplyForm;