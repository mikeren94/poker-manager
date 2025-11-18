import { router } from '@inertiajs/react';

function FeedbackItem({ feedback, userId }) {
  return (
    <tr 
        className="border-b cursor-pointer hover:bg-gray-100 transition"
        onClick={() => router.visit(`/feedback/${userId}/${feedback.id}`)}   
    >
      <td className="max-w-xs truncate" title={feedback.message}>
        {feedback.message}
      </td>
      <td>{new Date(feedback.created_at).toLocaleString()}</td>
    </tr>
  );
}

export default FeedbackItem;