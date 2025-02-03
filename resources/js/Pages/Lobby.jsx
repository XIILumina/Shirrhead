import React, { useEffect, useState } from "react";
import axios from "axios";

const Lobby = ({ lobby, players, inviteCode }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Ensure lobby.players is always an array
  const lobbyPlayers = lobby?.players ? JSON.parse(lobby.players) : players || [];

  const markReady = async () => {
    try {
      await axios.post(`/lobby/${inviteCode}/ready`);
      const response = await axios.get(`/lobby/${inviteCode}`);
      setLobby(response.data.lobby);
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
    <div className="lobby-container">
      <h1 className="text-white">Lobby: {inviteCode}</h1>

      {/* Players List */}
      <div className="players">
        {lobbyPlayers.map((player, index) => (
          <div key={index} className="player">
            <span>
              Player {player.id} - {player.ready ? "Ready" : "Not Ready"}
            </span>
          </div>
        ))}
      </div>

      {/* Empty Slots */}
      {[...Array(4 - lobbyPlayers.length)].map((_, index) => (
        <div key={index} className="empty-slot">
          Empty Slot
        </div>
      ))}

      <button onClick={markReady} className="btn btn-ready">
        Mark Ready
      </button>

      {lobby.status === "ready" && (
        <button onClick={startGame} className="btn btn-start">
          Start Game
        </button>
      )}
    </div>
  );
};

export default Lobby;