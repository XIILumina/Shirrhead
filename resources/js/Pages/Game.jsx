import React, { useState, useEffect } from "react";
import axios from "axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { motion, AnimatePresence } from "framer-motion";

const Game = ({ game }) => {
  const [hand, setHand] = useState([]);
  const [visibleCards, setVisibleCards] = useState([]);
  const [hiddenCards, setHiddenCards] = useState([]);
  const [pile, setPile] = useState([]);
  const [deck, setDeck] = useState([]);
  const [playerTurn, setPlayerTurn] = useState(false);
  const [enemies, setEnemies] = useState([]);
  const [error, setError] = useState(null);
  const [pickedUpCard, setPickedUpCard] = useState(null);

  const cardVariants = {
    initial: { y: 50, opacity: 0, rotate: -5 },
    animate: { y: 0, opacity: 1, rotate: 0, transition: { type: "spring", stiffness: 300, damping: 20 } },
    exit: { y: -50, opacity: 0, rotate: 5, transition: { duration: 0.3 } },
    play: { x: 0, y: -150, scale: 1.1, rotate: 10, transition: { duration: 1 } },
    pile: { x: 0, y: 0, rotate: 3, transition: { duration: 1 } },
    pickup: { x: 0, y: 200, scale: 1, rotate: 0, transition: { duration: 1, ease: "easeInOut" } },
  };

  useEffect(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
      axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    } else {
      console.warn("CSRF token not found in meta tag");
    }

    const echo = new Echo({
      broadcaster: "pusher",
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      encrypted: true,
    });

    echo.channel(`game.${game.id}`).listen(".card.played", (e) => {
      console.log("Pusher event received:", e); // Debug Pusher data
      fetchGameState();
    });

    fetchGameState();
    return () => echo.leave(`game.${game.id}`);
  }, [game.id]);

  const fetchGameState = async () => {
    try {
      const response = await axios.get(`/game/${game.id}/state`);
      setHand(response.data.hand || []);
      setVisibleCards(response.data.visible_cards || []);
      setHiddenCards(response.data.hidden_cards || []);
      setPile(response.data.pile || []);
      setDeck(response.data.deck || []);
      setPlayerTurn(response.data.turn);
      setEnemies(response.data.enemies || []);
      setError(null);
    } catch (err) {
      console.error("Error fetching game state:", err.response?.data || err);
      setError(err.response?.data?.message || "Failed to load game state");
    }
  };

  const playCard = async (card) => {
    if (!playerTurn) return;
    try {
      const response = await axios.post(`/game/${game.id}/play-card`, { card_id: card.id });
      console.log("Play card response:", response.data);
      fetchGameState();
      setError(null);
    } catch (err) {
      console.error("Error playing card:", err.response?.data || err);
      setError(err.response?.data?.message || "Failed to play card");
    }
  };

  const pickUpPile = async () => {
    if (!playerTurn) return;
    try {
      const topCard = pile[pile.length - 1];
      setPickedUpCard(topCard);
      setTimeout(async () => {
        setPickedUpCard(null);
        const response = await axios.post(`/game/${game.id}/pick-up-cards`);
        console.log("Pick up pile response:", response.data);
        fetchGameState();
        setError(null);
      }, 1000);
    } catch (err) {
      console.error("Error picking up pile:", err.response?.data || err);
      setError(err.response?.data?.message || "Failed to pick up pile");
    }
  };

  const drawCard = async () => {
    if (!playerTurn) return;
    try {
      const response = await axios.post(`/game/${game.id}/draw-card`);
      console.log("Draw card response:", response.data);
      fetchGameState();
      setError(null);
    } catch (err) {
      console.error("Error drawing card:", err.response?.data || err);
      setError(err.response?.data?.message || "Failed to draw card");
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-900 to-green-700 text-white flex flex-col justify-between p-4">
      <AnimatePresence>
        {error && (
          <motion.div
            className="fixed top-4 left-1/2 transform -translate-x-1/2 bg-red-600 text-white p-4 rounded shadow-lg z-50"
            initial={{ opacity: 0, y: -50 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -50 }}
          >
            {error}
            <button
              onClick={() => setError(null)}
              className="ml-4 bg-white text-red-600 px-2 py-1 rounded"
            >
              Close
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      <div className="relative flex-1 flex items-center justify-center">
        {/* Enemies */}
        {enemies.length > 0 && (
          <motion.div className="absolute top-0 left-1/2 transform -translate-x-1/2" initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
            <h3 className="text-lg mb-2 text-center">{enemies[0].name}</h3>
            <div className="flex space-x-2 justify-center">
              {enemies[0].visible_cards.map((card) => (
                <motion.img
                  key={card.id}
                  src={`/images/cards/${card.value}_of_${card.suit}.png`}
                  alt={`${card.value} of ${card.suit}`}
                  className="w-16 h-24"
                  variants={cardVariants}
                  initial="initial"
                  animate="animate"
                />
              ))}
            </div>
          </motion.div>
        )}
        {enemies.length > 1 && (
          <motion.div className="absolute left-0 top-1/2 transform -translate-y-1/2 rotate-90" initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
            <h3 className="text-lg mb-2 text-center">{enemies[1].name}</h3>
            <div className="flex space-x-2 justify-center">
              {enemies[1].visible_cards.map((card) => (
                <motion.img
                  key={card.id}
                  src={`/images/cards/${card.value}_of_${card.suit}.png`}
                  alt={`${card.value} of ${card.suit}`}
                  className="w-16 h-24"
                  variants={cardVariants}
                  initial="initial"
                  animate="animate"
                />
              ))}
            </div>
          </motion.div>
        )}
        {enemies.length > 2 && (
          <motion.div className="absolute right-0 top-1/2 transform -translate-y-1/2 -rotate-90" initial={{ opacity: 0 }} animate={{ opacity: 1 }}>
            <h3 className="text-lg mb-2 text-center">{enemies[2].name}</h3>
            <div className="flex space-x-2 justify-center">
              {enemies[2].visible_cards.map((card) => (
                <motion.img
                  key={card.id}
                  src={`/images/cards/${card.value}_of_${card.suit}.png`}
                  alt={`${card.value} of ${card.suit}`}
                  className="w-16 h-24"
                  variants={cardVariants}
                  initial="initial"
                  animate="animate"
                />
              ))}
            </div>
          </motion.div>
        )}

        {/* Table */}
        <div className="flex space-x-8">
          <motion.div className="relative" onClick={drawCard} whileHover={{ scale: 1.05 }}>
            {deck.length > 0 && (
              <motion.img
                src="/images/cards/back.png"
                alt="Deck"
                className="w-24 h-36"
                initial={{ y: 0 }}
                animate={{ y: [0, -5, 0] }}
                transition={{ repeat: Infinity, duration: 2 }}
              />
            )}
            <span className="absolute top-0 left-0 bg-black bg-opacity-50 px-2 py-1 rounded">{deck.length}</span>
          </motion.div>

          <div className="relative w-24 h-36">
            <AnimatePresence>
              {pile.map((card, index) => (
                <motion.img
                  key={card.id}
                  src={`/images/cards/${card.value}_of_${card.suit}.png`}
                  alt={`${card.value} of ${card.suit}`}
                  className="w-24 h-36 absolute"
                  variants={cardVariants}
                  initial="play"
                  animate={pickedUpCard && pickedUpCard.id === card.id ? "pickup" : "pile"}
                  style={{ zIndex: index }}
                />
              ))}
            </AnimatePresence>
            {pile.length > 0 && (
              <button
                onClick={pickUpPile}
                className="absolute bottom-0 left-0 bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded z-10"
              >
                Pick Up
              </button>
            )}
          </div>
        </div>
      </div>

      <div className="flex flex-col items-center pb-4">
        <h2 className="text-xl mb-4">Your Cards {playerTurn ? "(Your Turn)" : ""}</h2>
        <div className="flex space-x-3">
          <AnimatePresence>
            {hand.map((card) => (
              <motion.button
                key={card.id}
                onClick={() => playCard(card)}
                variants={cardVariants}
                initial="initial"
                animate="animate"
                exit="exit"
                whileHover={{ scale: 1.1, zIndex: 10 }}
                disabled={!playerTurn}
              >
                <img
                  src={`/images/cards/${card.value}_of_${card.suit}.png`}
                  alt={`${card.value} of ${card.suit}`}
                  className="w-24 h-36"
                />
              </motion.button>
            ))}
          </AnimatePresence>
        </div>

        {/* Hidden and Visible Cards Below Hand */}
        <div className="mt-8 relative flex justify-center items-center">
          <div className="relative flex space-x-2">
            {hiddenCards.map((card, index) => (
              <motion.img
                key={card.id}
                src={`/images/cards/${card.value}_of_${card.suit}.png`}
                alt={`${card.value} of ${card.suit}`}
                className="w-24 h-36 absolute"
                variants={cardVariants}
                initial="initial"
                animate="animate"
                style={{ left: index * 36, zIndex: index }}
              />
            ))}
            {visibleCards.map((card, index) => (
              <motion.img
                key={card.id}
                src={`/images/cards/${card.value}_of_${card.suit}.png`}
                alt={`${card.value} of ${card.suit}`}
                className="w-24 h-36 absolute"
                variants={cardVariants}
                initial="initial"
                animate="animate"
                style={{ left: (hiddenCards.length + index) * 36, zIndex: hiddenCards.length + index }}
              />
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Game;