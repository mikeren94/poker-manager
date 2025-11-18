import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import FeedbackForm from '@/Components/Forms/FeedbackForm';
import {usePage} from '@inertiajs/react';
import FeedbackTable from '@/Components/Tables/FeedbackTable';

export default function Feedback() {
    const {props } = usePage();
    const userId = props.userId;
    const feedbackCount = props.feedbackCount;
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
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <FeedbackForm />
                        </div>
                    </div>
                </div>
                
                {
                    feedbackCount > 0 && (
                        <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                            <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                                <div className="p-6 text-gray-900">
                                    <FeedbackTable userId={userId} />
                                </div>
                            </div>
                        </div>
                    )
                }
            </div>
        </AuthenticatedLayout>
    );
}
