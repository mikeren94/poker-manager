import { useEffect, useState } from "react";
import LoadingSpinner from "../LoadingSpinner";
import Pagination from "./Pagination";
import TableHeader from "./TableHeader";
import FeedbackItem from "./FeedbackItem"; // You’ll define this like SessionItem
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
} from "@tanstack/react-table";
import { feedbackColumns } from "./columns.jsx";

function FeedbackTable({userId}) {
  const [feedback, setFeedback] = useState([]);
  const [loading, setLoading] = useState(true);
  const [sorting, setSorting] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const table = useReactTable({
    data: feedback,
    columns: feedbackColumns,
    state: { sorting },
    onSortingChange: setSorting,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
  });

  const getFeedback = async (page = 1, sort = sorting) => {
    setLoading(true);
    try {
      const sortParam =
        sort.length > 0
          ? `&sort_by=${sort[0].id}&sort_direction=${sort[0].desc ? "desc" : "asc"}`
          : "";

      const request = await axios.get(`/feedback/${userId}/?page=${page}${sortParam}`);
      const response = request.data;

      setCurrentPage(response.current_page);
      setLastPage(response.last_page);
      setFeedback(response.data);
    } catch (error) {
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    getFeedback(1, sorting); // reset to page 1 on sort change
  }, [sorting]);

  return (
    <div>
      {loading ? (
        <LoadingSpinner message="loading..." />
      ) : feedback.length > 0 ? (
        <div>
          <table className="table-auto w-full text-left">
            <TableHeader table={table} />
            <tbody>
              {table.getRowModel().rows.map((row) => (
                <FeedbackItem key={row.id} feedback={row.original} userId={userId} />
              ))}
            </tbody>
          </table>
          <Pagination
            currentPage={currentPage}
            lastPage={lastPage}
            updateTable={(page) => getFeedback(page, sorting)}
            sorting={sorting}
          />
        </div>
      ) : (
        <p>No feedback found</p>
      )}
    </div>
  );
}

export default FeedbackTable;