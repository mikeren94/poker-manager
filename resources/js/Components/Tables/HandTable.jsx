import { useEffect, useState } from "react";
import LoadingSpinner from "../LoadingSpinner";
import HandItem from "./HandItem";
import { handColumns } from './columns.jsx';
import TableHeader from "./TableHeader";
import {
    useReactTable,
    getCoreRowModel,
    getSortedRowModel,
} from '@tanstack/react-table';
import Pagination from "./Pagination";


function HandTable({session}) {
    const [hands, setHands] = useState([]);
    const [loading, setLoading] = useState(true);
    const [sorting, setSorting] = useState([]);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    const table = useReactTable({
        data: hands,
        columns: handColumns,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
    });

    const getHands = async (page = 1, sort = sorting) => {
        console.log('updating table');
        setLoading(true);
        try {
            const sortParam = sort.length > 0
                ? `&sort_by=${sort[0].id}&sort_direction=${sort[0].desc ? 'desc' : 'asc'}`
                : '';
        
            const request = await axios.get(`/sessions/${session.id}/hands?page=${page}${sortParam}`);
            const response = request.data;
            setHands(response.data);
            setCurrentPage(response.current_page);
            setLastPage(response.last_page);
        } catch (error) {
            console.log(error)
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        getHands(currentPage, sorting);
    }, [sorting]);
    return (
        <div>
            {loading ? (
                <LoadingSpinner message="loading..." />
            ) : hands.length > 0 ? (
                <div>
                    <table className="table-auto w-full text-center">
                        <TableHeader table={table} />
                        <tbody>
                        {table.getRowModel().rows.map(row => (
                            <HandItem key={row.id} historyItem={row.original} />
                        ))}
                        </tbody>
                    </table>
                    <Pagination
                    currentPage={currentPage}
                    lastPage={lastPage}
                    updateTable={getHands}
                    sorting={sorting}
                    />         
               </div>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default HandTable;