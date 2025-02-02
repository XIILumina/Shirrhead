import React, { useState, useEffect } from "react";
import axios from "axios";

const Game = ({ game, players }) => {
  const [hand, setHand] = useState([]);
  const [pile, setPile] = useState([]);
  const [deckCount, setDeckCount] = useState(0);
  const [playerTurn, setPlayerTurn] = useState(false);
  const [gameStatus, setGameStatus] = useState(game.status);
  const [timeElapsed, setTimeElapsed] = useState(0);

  // Fetch the player's current state
  const fetchPlayerState = async () => {
    try {
      const response = await axios.get(`/game/${game.id}/state`);
      const { hand, pile, deck, turn } = response.data;

      setHand(hand);
      setPile(pile);
      setDeckCount(deck.length);
      setPlayerTurn(turn);
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
    try {
      const response = await axios.post(`/game/${game.id}/play-card`, {
        card,
      });
      setHand(response.data.hand);
      setPile(response.data.pile);
      if (response.data.message.includes("won")) {
        alert("You won the game!");
        setGameStatus("completed");
      }
    } catch (err) {
      alert(err.response.data.message);
    }
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
    } catch (err) {
      console.error("Error drawing card:", err);
    }
  };

  return (
    <div>
      <h1>{game.name}</h1>
      <p>Status: {gameStatus}</p>
      <p>Deck cards remaining: {deckCount}</p>
      <p>Time Elapsed: {timeElapsed} seconds</p>

      {gameStatus === "ongoing" && (
        <>
          <div>
            <h2>Your Hand</h2>
            <div>
              {hand.map((card, index) => (
                <button key={index} onClick={() => playCard(card)}>
                  {card.value} of {card.suit}
                </button>
              ))}
            </div>
          </div>

          <div>
            <h2>Pile</h2>
            {pile.length > 0 ? (
              <p>
                Top Card: {pile[pile.length - 1].value} of{" "}
                {pile[pile.length - 1].suit}
              </p>
            ) : (
              <p>Pile is empty</p>
            )}
          </div>

          {playerTurn && (
            <>
              <button onClick={pickUpPile}>Pick Up Pile</button>
              <button onClick={drawCard}>Draw Card</button>
            </>
          )}
        </>
      )}

      {gameStatus === "completed" && <h2>The game has ended!</h2>}
    </div>
  );
};

export default Game;
