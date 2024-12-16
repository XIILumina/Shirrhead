import React, { useState, useEffect } from "react";
import axios from "axios";

const Game = ({ game, players }) => {
  const [gameData, setGameData] = useState(null);
  const [error, setError] = useState(null);
  const [isGameStarted, setIsGameStarted] = useState(false);
  const [currentPlayer, setCurrentPlayer] = useState(null);
  const [copyMessage, setCopyMessage] = useState(""); // State for feedback when copying

  useEffect(() => {
    const fetchGame = async () => {
      try {
        const gameId = game.id;
        if (!gameId) {
          throw new Error("Game ID is missing");
        }
        const response = await axios.get(`/game/${gameId}`);
        setGameData(response.data);
        const userId = response.data.current_user_id;
        const playerData = players.find((p) => p.user_id === userId);
        setCurrentPlayer(playerData);
      } catch (err) {
        setError(`Error fetching game data: ${err.message}`);
      }
    };
    fetchGame();
  }, [game, players]);

  const startGame = async () => {
    try {
      const response = await axios.post(`/game/${game.id}/start`);
      if (response.data.message === "Game started and cards dealt") {
        setIsGameStarted(true);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const renderCards = (cards) => {
    return cards.map((card, index) => (
      <div
        key={index}
        className="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white rounded-lg shadow-lg p-4 w-24 h-36 flex flex-col justify-center items-center m-2"
      >
        <span className="text-lg font-bold">{card.value}</span>
        <span className="text-sm">{card.suit}</span>
      </div>
    ));
  };

  const handleCopy = () => {
    navigator.clipboard.writeText(game.invite_code || "N/A").then(() => {
      setCopyMessage("Copied!");
      setTimeout(() => setCopyMessage(""), 2000); // Clear message after 2 seconds
    });
  };

  return (
    <div className="max-w-5xl mx-auto my-8 p-6 bg-gray-100 rounded-lg shadow-lg">
      {/* Game Header */}
      <header className="flex justify-between items-center bg-blue-600 text-white p-4 rounded-lg shadow-md mb-6">
        <h1 className="text-3xl font-bold">Game: {game.name}</h1>
        <div className="flex items-center space-x-2 bg-gray-900 text-white px-4 py-2 rounded-lg">
          <p className="text-sm font-semibold">Invite Code:</p>
          <span className="text-lg font-bold">{game.invite_code || "N/A"}</span>
          <button
            onClick={handleCopy}
            className="ml-2 px-3 py-1 text-white text-sm rounded transition"
          >
            🔗
          </button>
          {copyMessage && (
            <span className="text-xs text-gray-300 ml-2">{copyMessage}</span>
          )}
        </div>
      </header>

      {/* Players List */}
      <section className="mb-6">
        <h2 className="text-2xl font-semibold mb-4">Players</h2>
        <ul className="grid grid-cols-2 gap-4">
          {players.map((player, index) => (
            <li
              key={index}
              className="p-4 bg-white rounded-lg shadow-md flex items-center space-x-4"
            >
              <div className="bg-gray-200 text-gray-700 font-bold rounded-full h-10 w-10 flex justify-center items-center">
                {player.user_id}
              </div>
              <p className="text-gray-800 font-medium">Player {player.user_id}</p>
            </li>
          ))}
        </ul>
      </section>

      {/* Start Game Button */}
      {players.length >= 2 && !isGameStarted && (
        <div className="text-center mb-6">
          <button
            onClick={startGame}
            className="px-8 py-3 bg-green-500 text-white font-semibold text-lg rounded-lg hover:bg-green-600 transition"
          >
            Start Game
          </button>
        </div>
      )}

      {/* Current Player's Hand */}
      {currentPlayer && (
        <section>
          <h2 className="text-2xl font-semibold mb-4 text-center">
            {isGameStarted
              ? `Your Hand, Player ${currentPlayer.user_id}`
              : "Waiting for game to start..."}
          </h2>
          {isGameStarted && currentPlayer.hand ? (
            <div className="flex flex-wrap justify-center">
              {renderCards(JSON.parse(currentPlayer.hand))}
            </div>
          ) : (
            <p className="text-gray-600 text-center">No cards in hand yet.</p>
          )}
        </section>
      )}

      {/* Remaining Deck */}
      <section className="mt-8 text-center">
        <h3 className="text-lg font-semibold mb-2">Remaining Deck</h3>
        {gameData?.cards?.deck && gameData.cards.deck.length > 0 ? (
          <p className="text-gray-600">
            {gameData.cards.deck.length} cards remaining
          </p>
        ) : (
          <p className="text-gray-500">No cards left in the deck.</p>
        )}
      </section>

      {/* Action Buttons */}
      {isGameStarted && (
        <div className="mt-6 flex justify-center space-x-4">
          <button
            className="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
            onClick={() => console.log("Play cards")}
          >
            Play Cards
          </button>
          <button
            className="px-6 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition"
            onClick={() => console.log("Draw card")}
          >
            Draw Card
          </button>
          <button
            className="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
            onClick={() => console.log("Pick up pile")}
          >
            Pick Up Pile
          </button>
        </div>
      )}
    </div>
  );
};

export default Game;
