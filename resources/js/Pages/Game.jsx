import React, { useState, useEffect } from "react";
import axios from "axios";
import Pusher from "pusher-js"; // Import Pusher
import Echo from "laravel-echo"; // Import Laravel Echo

const Game = ({ game, players }) => {
  const [hand, setHand] = useState([]);
  const [visibleCards, setVisibleCards] = useState([]);
  const [hiddenCards, setHiddenCards] = useState([]);
  const [pile, setPile] = useState([]);
  const [deckCount, setDeckCount] = useState(0);
  const [playerTurn, setPlayerTurn] = useState(false);
  const [gameStatus, setGameStatus] = useState(game.status);
  const [timeElapsed, setTimeElapsed] = useState(0);
  const [animatedCard, setAnimatedCard] = useState(null);
  const [enemyVisibleCards, setEnemyVisibleCards] = useState([]);

  // Initialize Pusher and Laravel Echo
  useEffect(() => {
    const echo = new Echo({
        broadcaster: "pusher",
        key: import.meta.env.VITE_PUSHER_APP_KEY, // Use Vite environment variables
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        encrypted: true,
    });

    // Listen for the CardPlayed event
    echo.channel(`game.${game.id}`).listen('.card.played', (data) => {
        setEnemyVisibleCards((prev) => [...prev, data.card]);
    });

    // Cleanup on component unmount
    return () => {
        echo.leave(`game.${game.id}`);
    };
}, [game.id]);

  // Fetch the player's current state
  const fetchPlayerState = async () => {
    try {
      const response = await axios.get(`/game/${game.id}/state`);
      const {
        hand,
        pile,
        deck,
        turn,
        visible_cards,
        hidden_cards,
        enemy_visible_cards,
      } = response.data;

      setHand(hand);
      setVisibleCards(visible_cards);
      setHiddenCards(hidden_cards);
      setPile(pile);
      setDeckCount(deck.length);
      setPlayerTurn(turn);
      setEnemyVisibleCards(enemy_visible_cards);
    } catch (err) {
      console.error("Error fetching game state:", err);
    }
  };

  useEffect(() => {
    const timer = setInterval(() => {
      setTimeElapsed((prevTime) => prevTime + 1);
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  useEffect(() => {
    fetchPlayerState();
  }, [game.id]);


const playCard = async (card) => {
  console.log("Card being played:", card); // Debugging
  setAnimatedCard(card);
  setTimeout(async () => {
    try {
      const response = await axios.post(`/game/${game.id}/play-card`, { card });
      setHand(response.data.hand);
      setPile(response.data.pile);
      setAnimatedCard(null);
    } catch (err) {
      alert(err.response.data.message);
    }
  }, 500);
};


  const pickUpPile = async () => {
    try {
      const response = await axios.post(`/game/${game.id}/pick-up`);
      setHand(response.data.hand);
      setPile([]);
    } catch (err) {
      console.error("Error picking up pile:", err);
    }
  };

  const drawCard = async () => {
    try {
        const response = await axios.post(`/game/${game.id}/draw-card`);
        setHand(response.data.hand);
        setDeckCount((prev) => prev - 1);

        // Trigger animation
        setAnimatedCard(response.data.card);
        setTimeout(() => {
            setAnimatedCard(null);
        }, 500); // Match the animation duration
    } catch (err) {
        console.error("Error drawing card:", err);
    }
};

  return (
    <div className="min-h-screen bg-green-900 text-white flex flex-col justify-between p-4">
  {/* Enemy Section */}
<div className="flex flex-col items-center">
  <h2 className="text-xl mb-4">Enemy's Cards</h2>
  {players
    .filter((player) => player.is_bot)
    .map((bot, index) => (
      <div key={index} className="mb-4">
        <h3 className="text-lg mb-2">Bot {index + 1}</h3>
        <div className="flex space-x-2">
          {bot.visible_cards.map((card, cardIndex) => (
            <img
              key={cardIndex}
              src={`/images/cards/${card.value}_of_${card.suit}.png`}
              alt={`${card.value} of ${card.suit}`}
              className="w-24 h-36 transform hover:scale-110 transition-transform"
            />
          ))}
        </div>
      </div>
    ))}
</div>

  {/* Pile Section */}
  <div className="flex justify-center">
    <div className="relative">
      {pile.map((card, index) => (
        <img
          key={index}
          src={`/images/cards/${card.value}_of_${card.suit}.png`}
          alt={`${card.value} of ${card.suit}`}
          className="w-24 h-36 absolute"
          style={{
            transform: `rotate(${index * 10 - (pile.length * 5)}deg) translateY(${index * 5}px)`,
          }}
        />
      ))}
    </div>
  </div>

  <div className="flex justify-center">
  <button
    onClick={drawCard}
    className="px-8 py-4 bg-gray-500/75 hover:bg-gray-600 text-white rounded-lg shadow-lg transform transition-transform hover:scale-110 focus:outline-none focus:ring-4 focus:ring-gray-500"
  >
    Draw Card
  </button>
</div>


  {/* Player Section */}
  <div className="flex flex-col items-center">
  <h2 className="text-xl mb-4">Your Cards</h2>
  <div className="flex space-x-2">
    {hand.map((card, index) => (
      <button
        key={index}
        onClick={() => playCard(card)}
        className={`transform hover:scale-110 transition-transform ${
          animatedCard === card ? "card-animation" : ""
        }`}
      >
        <img
          src={`/images/cards/${card.value}_of_${card.suit}.png`}
          alt={`${card.value} of ${card.suit}`}
          className="w-24 h-36"
        />
      </button>
    ))}
  </div>
  <div className="mt-4">
    <h3>Visible Cards</h3>
    <div className="flex space-x-2 relative">
      {visibleCards.map((card, index) => (
        <img
          key={index}
          src={`/images/cards/${card.value}_of_${card.suit}.png`}
          alt={`${card.value} of ${card.suit}`}
          className="w-24 h-36 absolute"
          style={{
            transform: `translateX(${index * 20}px)`,
            zIndex: index + 1,
          }}
        />
      ))}
    </div>
  </div>
  <div className="mt-4">
    <h3>Hidden Cards</h3>
    <div className="flex space-x-2">
      {hiddenCards.map((_, index) => (
        <img
          key={index}
          src="/images/cards/back.png"
          alt="Hidden Card"
          className="w-36 h-48" // Larger size for hidden cards
        />
      ))}
    </div>
  </div>
</div>
</div>
  );
};

export default Game;