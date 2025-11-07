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


function HandTable({session}) {
    const [hands, setHands] = useState([]);
    const [loading, setLoading] = useState(true);
    const [sorting, setSorting] = useState([]);

    const table = useReactTable({
        data: hands,
        columns: handColumns,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    const getHands = async () => {
        try {
            const response = await axios.get(`/sessions/${session.id}/hands`)
            setHands(response.data);
        } catch (error) {
            console.log(error)
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        getHands();
    }, []);
    return (
        <div>
            {loading ? (
                <LoadingSpinner message="loading..." />
            ) : hands.length > 0 ? (
                <table className="table-auto w-full">
                    <TableHeader table={table} />
                    <tbody>
                    {table.getRowModel().rows.map(row => (
                        <HandItem key={row.id} historyItem={row.original} />
                    ))}
                    </tbody>
                </table>
            ) : (
                <p>No sessions found</p>
            )}
        </div>
    )
}

export default HandTable;