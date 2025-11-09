import { createColumnHelper } from '@tanstack/react-table';
import DisplayCard from '@/Components/DisplayCard';

const columnHelper = createColumnHelper();


export const handColumns = [
  columnHelper.display({
    id: 'hole_cards',
    header: 'Your Cards',
    cell: info => {
      const hand = info.row.original;
      const playerCards = hand.hand_cards?.filter(c => c.context === 'hole' && c.player_id)?.sort((a, b) => a.card.id - b.card.id) || [];
      return (
        <div className="flex gap-1">
          {playerCards.map((hc, i) => (
            <DisplayCard key={i} card={hc.card} />
          ))}
        </div>
      );
    },
  }),
  columnHelper.accessor(
    row => row.hand_players?.[0]?.result ?? 0,
    {
      id: 'result',
      header: 'Result',
      enableSorting: true,
      cell: info => {
        const value = info.getValue();
        const color = value > 0 ? 'text-green-600' : 'text-red-500';
        return (
          <span className={`font-bold font-mono text-base ${color}`}>
            {value.toFixed(2)}
          </span>
        );
      },
      sortingFn: (rowA, rowB) => {
        const resultA = rowA.original.hand_players?.[0]?.result ?? 0;
        const resultB = rowB.original.hand_players?.[0]?.result ?? 0;
        return resultA - resultB; // ✅ correct direction
      },
    }
  ),
  columnHelper.display({
    id: 'flop',
    header: 'Flop',
    cell: info => {
      const hand = info.row.original;
      const flopCards = hand.hand_cards?.filter(c => c.context === 'flop') || [];
      return (
        <div className="flex gap-1">
          {flopCards.map((hc, i) => (
            <DisplayCard key={i} card={hc.card} />
          ))}
        </div>
      );
    },
  }),
  columnHelper.display({
    id: 'turn',
    header: 'Turn',
    cell: info => {
      const hand = info.row.original;
      const turnCard = hand.hand_cards?.find(c => c.context === 'turn');
      return turnCard ? <DisplayCard card={turnCard.card} /> : null;
    },
  }),
  columnHelper.display({
    id: 'river',
    header: 'River',
    cell: info => {
      const hand = info.row.original;
      const riverCard = hand.hand_cards?.find(c => c.context === 'river');
      return riverCard ? <DisplayCard card={riverCard.card} /> : null;
    },
  }),
];

export const sessionColumns = [
    columnHelper.accessor('session_id', {
        header: 'Session',
        cell: info => info.getValue(),
    }),
    columnHelper.accessor('stakes', {
        header: 'Stakes',
        cell: info => info.getValue(),
    }),
    columnHelper.accessor('start_time', {
        header: 'Time',
        cell: info => new Date(info.getValue()).toLocaleString(),
    }),
  columnHelper.accessor(
    row => row.result ?? 0,
    {
      id: 'result',
      header: 'Result',
      enableSorting: true,
      cell: info => {
        const value = info.getValue();
        const color = value > 0 ? 'text-green-600' : 'text-red-500';
        return (
          <span className={`font-bold font-mono text-base ${color}`}>
            {value.toFixed(2)}
          </span>
        );
      },
      sortingFn: (rowA, rowB) => {
        const resultA = rowA.original.result ?? 0;
        const resultB = rowB.original.result ?? 0;
        return resultA - resultB; // ✅ correct direction
      },
    }
  ),
];