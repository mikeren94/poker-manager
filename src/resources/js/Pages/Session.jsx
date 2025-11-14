import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import HandTable from '@/Components/Tables/HandTable';
import { Head } from '@inertiajs/react';


export default function Session({session}) {
    console.log(session);
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Viewing session - {session.session_id}
                </h2>
            }
        >
            <Head title="Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <HandTable session={session}/>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    )
}