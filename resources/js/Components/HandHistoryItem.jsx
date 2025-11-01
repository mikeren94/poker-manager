import DisplayCard from "./DisplayCard";

function HandHistoryItem({historyItem}) {
        const playerCards = historyItem.hand_cards.filter(card => card.player_id);
        const flopCards = historyItem.hand_cards.filter(card => card.context === 'flop' && card.player_id === null);
        const turnCard = historyItem.hand_cards.find(card => card.context === 'turn' && card.player_id === null);
        const riverCard = historyItem.hand_cards.find(card => card.context === 'river' && card.player_id === null);    return (
        <div className="flex flex-row items-center gap-3 w-full mb-1">
            {playerCards.map((hand_card, index) => (
                <DisplayCard key={index} card={hand_card.card} />
            ))}
            <div className={
                "font-bold font-mono text-base " + (historyItem.hand_players[0].result > 0 ? "text-green-600" : "text-red-500")
            }>
                {historyItem.hand_players[0].result}
            </div>
            {flopCards.map((hand_card, index) => (
                <DisplayCard key={index} card={hand_card.card} />
            ))}
            <div className="turn-card">
                {turnCard && <DisplayCard card={turnCard.card} />}
            </div>

            <div className="river-card">
                {riverCard && <DisplayCard card={riverCard.card} />}
            </div>
        </div>
    )
}

export default HandHistoryItem;