import DisplayCard from "./DisplayCard";

function HandActionBreakdown({ hand }) {
    const streetLabels = {
        0: "Preflop",
        1: "Flop",
        2: "Turn",
        3: "River",
    };

    // Group actions by street
    const actionsByStreet = hand.hand_actions.reduce((acc, action) => {
        const street = action.street;
        if (!acc[street]) acc[street] = [];
        acc[street].push(action);
        return acc;
    }, {});

    // Sort each street's actions
    Object.keys(actionsByStreet).forEach(street => {
        actionsByStreet[street].sort((a, b) => a.action_order - b.action_order);
    });

    // Group board cards by street
    const boardCards = {
        flop: hand.hand_cards.filter(c => c.context === "flop"),
        turn: hand.hand_cards.filter(c => c.context === "turn"),
        river: hand.hand_cards.filter(c => c.context === "river"),
    };

    // Get player name and hole cards
    const getPlayerInfo = (playerId) => {
        const hp = hand.hand_players.find(p => p.player_id === playerId);
        const cards = hand.hand_cards
        .filter(c => c.context === "hole" && c.player_id === playerId)
        .map(c => c.card);
        return {
        name: hp?.player?.name || "Unknown",
        cards,
        };
    };

    const formatMoney = (value) => {
    const num = parseFloat(value);
    return isNaN(num) ? '0.00' : num.toFixed(2);
    };

    const potSize = formatMoney(hand.pot_size);
    const rake = formatMoney(hand.rake);
    const netPot = (parseFloat(potSize) - parseFloat(rake)).toFixed(2);

    const winner = hand.hand_players.find(p => p.is_winner);
    const winnerName = winner?.player?.name;    

    return (
        <div className="space-y-6">
        {/* Hole cards */}
        <div>
            <h3 className="text-lg font-bold text-gray-800 mb-2">Hole Cards:</h3>
            <ul className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            {hand.hand_players.map(hp => {
                const { name, cards } = getPlayerInfo(hp.player_id);
                return (
                <li key={hp.id} className="text-sm text-gray-700">
                    <span className="font-bold">{name}</span>
                    <div className="flex gap-1 mt-1">
                    {cards.length > 0 ? (
                        cards.map((card, i) => <DisplayCard key={i} card={card} />)
                    ) : (
                        <span className="text-gray-400 italic">Not shown</span>
                    )}
                    </div>
                </li>
                );
            })}
            </ul>
        </div>

        {/* Actions by street */}
        {Object.entries(actionsByStreet).map(([street, actions]) => (
            <div key={street}>
            <h3 className="text-lg font-bold text-gray-800 mb-2">{streetLabels[street]}:</h3>

            {/* Board cards */}
            {street > 0 && (
                <div className="flex gap-1 mb-2">
                {boardCards[streetLabels[street].toLowerCase()].map((hc, i) => (
                    <DisplayCard key={i} card={hc.card} />
                ))}
                </div>
            )}

            {/* Action list */}
            <ul className="space-y-1 pl-4">
                {actions.map(action => {
                const { name } = getPlayerInfo(action.player_id);
                return (
                    <li key={action.id} className="text-sm text-gray-700">
                    <span className="font-mono text-blue-700">{name}</span>{" "}
                    <span className="text-gray-600">{action.action}</span>
                    {action.amount && (
                        <span className="text-green-600 font-semibold"> ${parseFloat(action.amount).toFixed(2)}</span>
                    )}
                    </li>
                );
                })}
            </ul>
            </div>
        ))}

        {/* Winner summary */}
        {winner && (
            <div className="mt-6 text-sm text-gray-800">
                <span className="font-bold text-green-700">{winnerName}</span> won{" "}
                <span className="font-semibold">${netPot}</span> from the pot.{" "}
                <span className="text-gray-500 italic">(Rake paid: ${rake})</span>
            </div>
        )}
        </div>
    );
}

export default HandActionBreakdown;