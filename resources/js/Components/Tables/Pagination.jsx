function Pagination({ currentPage, lastPage, updateTable }) {
    return (
        <div className="flex justify-center gap-2 mt-4">
            <button
                className="btn btn-sm"
                disabled={currentPage === 1}
                onClick={() => updateTable(currentPage - 1)}
            >
                Prev
            </button>
            <span>Page {currentPage} of {lastPage}</span>
            <button
                className="btn btn-sm"
                disabled={currentPage === lastPage}
                onClick={() => updateTable(currentPage + 1)}
            >
                Next
            </button>
        </div>
    )
}

export default Pagination;