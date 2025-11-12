function Pagination({ currentPage, lastPage, updateTable, sorting }) {
    return (
        <div className="flex justify-center gap-2 mt-4">
            <button
                className="btn btn-sm"
                disabled={currentPage === 1}
                onClick={() => updateTable(currentPage - 1, sorting)}
            >
                Prev
            </button>
            <span>Page {currentPage} of {lastPage}</span>
            <button
                className="btn btn-sm"
                disabled={currentPage === lastPage}
                onClick={() => updateTable(currentPage + 1, sorting)}
            >
                Next
            </button>
        </div>
    )
}

export default Pagination;