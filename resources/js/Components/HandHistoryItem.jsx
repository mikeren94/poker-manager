import { router } from '@inertiajs/react';
import DisplayCard from "./DisplayCard";

function HandHistoryItem({historyItem}) {
    const playerCards = historyItem.hand_cards.filter(card => card.player_id);
    const flopCards = historyItem.hand_cards.filter(card => card.context === 'flop' && card.player_id === null);
    const turnCard = historyItem.hand_cards.find(card => card.context === 'turn' && card.player_id === null);
    const riverCard = historyItem.hand_cards.find(card => card.context === 'river' && card.player_id === null);    
    return (
        <tr 
            className="border-b cursor-pointer hover:bg-gray-100 transition"
            onClick={() => router.visit(`/hands/${historyItem.id}`)}    
        >
            <td>
                <div className="flex gap-1">
                {playerCards.map((hand_card, index) => (
                    <DisplayCard key={index} card={hand_card.card} />
                ))}
                </div>
            </td>
            <td>
                <span className={`font-bold font-mono text-base ${historyItem.hand_players[0].result > 0 ? 'text-green-600' : 'text-red-500'}`}>
                {historyItem.hand_players[0].result}
                </span>
            </td>
            <td>
                <div className="flex gap-1">
                {flopCards.map((hand_card, index) => (
                    <DisplayCard key={index} card={hand_card.card} />
                ))}
                </div>
            </td>
            <td>
                {turnCard && <DisplayCard card={turnCard.card} />}
            </td>
            <td>
                {riverCard && <DisplayCard card={riverCard.card} />}
            </td>
        </tr>
    )
}

export default HandHistoryItem;