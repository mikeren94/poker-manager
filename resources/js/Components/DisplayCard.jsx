import '../../css/DisplayCard.css';

function DisplayCard({card}) {
    return (
        <div className="display-card">{card.rank}{card.suit}</div>
    )
}

export default DisplayCard;