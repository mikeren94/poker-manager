function DisplayCard({ card }) {
  const suitColors = {
    h: 'bg-[#e74c3c]',
    s: 'bg-[#3a3a3c]',
    d: 'bg-[#3498db]',
    c: 'bg-[#27ae60]',
  };

  const suitSymbols = {
    h: '♥',
    s: '♠',
    d: '♦',
    c: '♣',
  };

  const suit = card.suit;
  const suitClass = suitColors[suit] || '';
  const suitDisplay = suitSymbols[suit] || '?';

  return (
    <div className={`w-10 h-[55px] border-2 border-neutral-800 rounded-lg p-2 text-white relative flex items-center justify-center text-[18px] ${suitClass}`}>
      {card.rank}{suitDisplay}
    </div>
  );
}

export default DisplayCard;