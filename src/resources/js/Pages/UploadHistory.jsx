import HandHistoryUpload from '@/Components/HandHistoryUpload';
import Notice from '@/Components/Notice';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { DisplayText } from '@/Constants/displayText';
export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Upload Hand History
                </h2>
            }
        >
            <Head title="Upload Hand History" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <Notice message={DisplayText.handHistoryNotice} />
                            <HandHistoryUpload />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
