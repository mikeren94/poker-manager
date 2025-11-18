import { Head, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FeedbackReplyForm from '@/Components/Forms/FeedbackReply';

export default function FeedbackThread() {
    const { props } = usePage();
    const { feedback } = props;
    const { userId } = props;
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Feedback
                </h2>
            }
        >
            <Head title="Feedback" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8 mb-2">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg mb-2">
                        <div className="p-6 text-gray-900">
                            {feedback.message && (
                            <div
                                className={`mt-2 flex ${feedback.user.id === userId ? 'justify-end' : 'justify-start'}`}
                            >
                                <div className="max-w-md">
                                <p className="text-sm">
                                    {feedback.user.name}:
                                </p>
                                <p className="mt-1">{feedback.message}</p>
                                </div>
                            </div>
                            )}

                            {feedback.replies.length > 0 &&
                            feedback.replies.map((reply) => (
                                <div
                                key={reply.id}
                                className={`mt-4 flex ${reply.user?.id === userId ? 'justify-end' : 'justify-start'}`}
                                >
                                <div className="max-w-md">
                                    <p className="text-sm">
                                    {reply.user?.name || 'Admin'}:
                                    </p>
                                    <p className="mt-1">{reply.message}</p>
                                </div>
                                </div>
                            ))}
                        </div>
                        </div>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <FeedbackReplyForm feedbackId={feedback.id} />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    )
}