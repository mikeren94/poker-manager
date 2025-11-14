import { flexRender } from '@tanstack/react-table';

function TableHeader({table}) {
    return (
        <thead>
            {table.getHeaderGroups().map(headerGroup => (
                <tr key={headerGroup.id}>
                    {headerGroup.headers.map(header => (
                        <th
                        key={header.id}
                        onClick={header.column.getToggleSortingHandler()}
                        style={{ cursor: header.column.getCanSort() ? 'pointer' : 'default' }}
                        >
                        {flexRender(header.column.columnDef.header, header.getContext())}
                        {header.column.getIsSorted() === 'asc' ? ' ↑' :
                        header.column.getIsSorted() === 'desc' ? ' ↓' : ''}
                        </th>
                    ))}
                </tr>
            ))}
        </thead>
    )
}

export default TableHeader;