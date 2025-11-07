import { useEffect, useState } from "react";
import LoadingSpinner from "../LoadingSpinner";
import SessionItem from './SessionItem';
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

    const table = useReactTable({
        data: sessions,
        columns: sessionColumns,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    const getSessions = async () => {
        try {
            const response = await axios.get('/sessions')
            setSessions(response.data);
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
                <table className="table-auto w-full">
                    <TableHeader table={table} />
                    <tbody>
                    {table.getRowModel().rows.map(row => (
                        <SessionItem key={row.id} session={row.original} />
                    ))}
                    </tbody>
                </table>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default SessionTable;