import React, { useEffect, useState } from "react";
import axios from "axios";

const Lobby = ({ lobby, players, inviteCode }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [lobbyState, setLobbyState] = useState(lobby);

  // Ensure players is always an array
  const lobbyPlayers = lobbyState?.players ? JSON.parse(lobbyState.players) : players || [];

  const markReady = async () => {
    try {
      await axios.post(`/lobby/${inviteCode}/ready`);
      const response = await axios.get(`/lobby/${inviteCode}`);
      setLobbyState(response.data.lobby);
    } catch (err) {
      console.error("Error marking ready:", err);
    }
  };

  const startGame = async () => {
    try {
      await axios.post(`/lobby/${inviteCode}/start`);
      window.location.href = `/game/${inviteCode}`;
    } catch (err) {
      console.error("Error starting game:", err);
    }
  };

  if (loading) return <p>Loading...</p>;
  if (error) return <p>{error}</p>;

  return (
    <div className="lobby-container bg-gray-800 p-8 shadow-md h-screen">
      <h1 className="text-2xl font-bold text-white mb-4">Lobby: {inviteCode}</h1>
      <p className="text-gray-400 mb-6">Invite Code: {inviteCode}</p>

      {/* Players List */}
      <div className="players mb-6">
        {lobbyPlayers.map((player, index) => (
          <div key={index} className="player bg-gray-700 p-4 rounded-lg mb-2">
            <span className="text-white">
              {player.name} - {player.ready ? "✅ Ready" : "❌ Not Ready"}
            </span>
          </div>
        ))}
      </div>

      {/* Empty Slots */}
      {[...Array(4 - lobbyPlayers.length)].map((_, index) => (
        <div key={index} className="empty-slot bg-gray-700 p-4 rounded-lg mb-2">
          <span className="text-gray-400">Empty Slot</span>
        </div>
      ))}

      <button
        onClick={markReady}
        className="btn btn-ready bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg mr-4"
      >
        Mark Ready
      </button>

      {lobbyState.status === "ready" && (
        <button
          onClick={startGame}
          className="btn btn-start bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg"
        >
          Start Game
        </button>
      )}
    </div>
  );
};

export default Lobby;