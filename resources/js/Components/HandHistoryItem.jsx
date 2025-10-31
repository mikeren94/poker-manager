import DisplayCard from "./DisplayCard";

function HandHistoryItem({historyItem}) {
    console.log(historyItem);
    return (
        <div class="row-container mb-1">
            {historyItem.hand_cards.map((hand_card, index) => (
                <DisplayCard key={index} card={hand_card.card} />
            ))}
            {historyItem.hand_players[0].result}
        </div>
    )
}

export default HandHistoryItem;