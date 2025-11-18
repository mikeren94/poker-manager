import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import FeedbackForm from '@/Components/Forms/FeedbackForm';

export default function Feedback() {

    const [feedback, setFeedback] = useState('');

    const handleSubmit = async (e) => {
        console.log('Submitting feedback:', feedback);
    }
    
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
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <FeedbackForm />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
