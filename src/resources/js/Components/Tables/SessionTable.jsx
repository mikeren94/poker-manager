import { useEffect, useState } from "react";
import LoadingSpinner from "../LoadingSpinner";
import SessionItem from './SessionItem';
import Pagination from "./Pagination";
import {
    useReactTable,
    getCoreRowModel,
    getSortedRowModel,
} from '@tanstack/react-table';
import { sessionColumns } from './columns.jsx';
import TableHeader from "./TableHeader";

function SessionTable() {
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [sorting, setSorting] = useState([]);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    const table = useReactTable({
        data: sessions,
        columns: sessionColumns,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
    });

    const getSessions = async (page = 1, sort = sorting) => {
        setLoading(true);
        try {
            const sortParam = sort.length > 0
            ? `&sort_by=${sort[0].id}&sort_direction=${sort[0].desc ? 'desc' : 'asc'}`
            : '';

            const request = await axios.get(`/sessions?page=${page}${sortParam}`);
            const response = request.data;

            setCurrentPage(response.current_page);
            setLastPage(response.last_page);
            setSessions(response.data);
        } catch (error) {
            console.log(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        getSessions(1, sorting); // reset to page 1 on sort change
    }, [sorting]);
    
    return (
        <div>
            {loading ? (
                <LoadingSpinner message="loading..." />
            ) : sessions.length > 0 ? (
                <div>
                    <table className="table-auto w-full text-center">
                        <TableHeader table={table} />
                        <tbody>
                        {table.getRowModel().rows.map(row => (
                            <SessionItem key={row.id} session={row.original} />
                        ))}
                        </tbody>
                    </table>
                    <Pagination
                        currentPage={currentPage}
                        lastPage={lastPage}
                        updateTable={(page) => getSessions(page, sorting)}
                        sorting={sorting}
                    />
                </div>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default SessionTable;