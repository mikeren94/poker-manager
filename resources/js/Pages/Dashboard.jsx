import HandHistoryList from '@/Components/HandHistoryList';
import HandHistoryUpload from '@/Components/HandHistoryUpload';
import HandsPlayedTile from '@/Components/Tiles/HandsPlayedTile';
import ProfitTile from '@/Components/Tiles/ProfitTile';
import RakeTile from '@/Components/Tiles/RakeTile';
import ShowdownWinTile from '@/Components/Tiles/ShowdownWinTile';
import VpipTile from '@/Components/Tiles/VpipTile';
import WinRateTile from '@/Components/Tiles/WinRateTile';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({summary}) {
    
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />
            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-2">
                        <ProfitTile amount={summary.profit} />
                        <VpipTile />
                        <RakeTile />
                        <HandsPlayedTile />
                        <WinRateTile />
                        <ShowdownWinTile />
                    </div>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <HandHistoryList />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
