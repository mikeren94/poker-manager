function DisplayCard({card}) {

    let cardClass = "w-10 h-[55px] bg-white border-2 border-neutral-800 rounded-lg p-2 text-white relative flex items-center justify-center text-[18px] ";
    const suitColors = {
        hearts: 'bg-[#e74c3c]',
        spades: 'bg-[#3a3a3c]',
        diamonds: 'bg-[#3498db]',
        clubs: 'bg-[#27ae60]',
    };
    let suitDisplay = "";
    switch (card.suit) {
        case "d":
            cardClass += suitColors['hearts'];
            suitDisplay = "♦";
            break;
        case "h":
            cardClass += suitColors['hearts'];
            suitDisplay = "♥";
            break;
        case "c":
            cardClass += suitColors['clubs'];
            suitDisplay = "♣";
            break;
        case "s":
            cardClass += suitColors['spades'];
            suitDisplay = "♠";
            break
    }
    return (
        <div className={cardClass}>{card.rank}{suitDisplay}</div>
    )
}

export default DisplayCard;