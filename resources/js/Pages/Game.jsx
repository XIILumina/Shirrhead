import React, { useState, useEffect } from "react";
import axios from "axios";

const Game = ({ gameData, userId }) => {
  const [playerHand, setPlayerHand] = useState([]);
  const [faceUpPile, setFaceUpPile] = useState([]);
  const [faceDownPile, setFaceDownPile] = useState([]);
  const [turnInfo, setTurnInfo] = useState({ currentPlayer: null, action: "" });
  const [selectedCards, setSelectedCards] = useState([]);

  useEffect(() => {
    if (gameData) {
      console.log("Game data:", gameData);
      setPlayerHand(gameData.playerHand);
      setFaceUpPile(gameData.faceUpPile);
      setFaceDownPile(gameData.faceDownPile); // Ensure this is populated correctly
      setTurnInfo({
        currentPlayer: gameData.currentPlayer,
        action: gameData.action,
      });
    }
  }, [gameData]);

  
  const handleCardSelect = (card) => {
    if (selectedCards.includes(card)) {
      setSelectedCards(selectedCards.filter((c) => c !== card));
    } else {
      setSelectedCards([...selectedCards, card]);
    }
  };

  const playCards = () => {
    if (selectedCards.length > 0) {
      axios.post("/play-cards", {
        cards: selectedCards,
        userId,
      });
      setSelectedCards([]);
    }
  };

  const drawCard = () => {
    axios.post("/draw-card", { userId }); // Ensure Inertia is defined
  };

  return (
    <div className="bg-gray-800 text-white h-screen flex flex-col items-center">
      <h1 className="text-3xl font-bold my-4">Shithead</h1>

      <div className="mb-6">
        <p>Current Player: {turnInfo.currentPlayer}</p>
        <p>Action: {turnInfo.action}</p>
      </div>

      <div className="flex items-center justify-center mb-8">
        <div className="p-4">
          <h2 className="text-xl font-semibold">Face-Down Pile</h2>
          <div className="bg-gray-600 h-24 w-16 rounded flex items-center justify-center">
            <p>{faceDownPile ? faceDownPile.length : 0} Cards</p>
          </div>
        </div>
        <div className="p-4">
          <h2 className="text-xl font-semibold">Face-Up Pile</h2>
          <div className="flex gap-2">
            {faceUpPile.map((card, index) => (
              <div
                key={index}
                className="bg-gray-600 h-24 w-16 rounded flex items-center justify-center"
              >
                <p>{card.value} {card.suit}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="mb-4">
        <h2 className="text-xl font-semibold">Your Hand</h2>
        <div className="flex gap-2">
          {playerHand.map((card, index) => (
            <div
              key={index}
              onClick={() => handleCardSelect(card)}
              className={`h-24 w-16 rounded flex items-center justify-center cursor-pointer transition-all border-2 ${
                selectedCards.includes(card)
                  ? "border-green-500"
                  : "border-transparent"
              } bg-gray-700`}
            >
              <p>{card.value} {card.suit}</p>
            </div>
          ))}
        </div>
      </div>

      <div className="flex gap-4">
        <button
          onClick={playCards}
          className="bg-green-500 hover:bg-green-700 px-4 py-2 rounded"
        >
          Play Selected
        </button>
        <button
          onClick={drawCard}
          className="bg-blue-500 hover:bg-blue-700 px-4 py-2 rounded"
        >
          Draw Card
        </button>
      </div>
    </div>
  );
};

export default Game;
