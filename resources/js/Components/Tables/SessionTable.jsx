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
        getSortedRowModel: getSortedRowModel(),
    });

    const getSessions = async (page = 1) => {
        setLoading(true);
        try {
            const request = await axios.get(`/sessions?page=${page}`)
            const response = request.data;
            setCurrentPage(response.current_page);
            setLastPage(response.last_page);
            setSessions(response.data)
        } catch (error) {
            console.log(error)
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        getSessions();
    }, []);
    
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
                    <Pagination currentPage={currentPage} lastPage={lastPage} updateTable={getSessions} />
                </div>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default SessionTable;