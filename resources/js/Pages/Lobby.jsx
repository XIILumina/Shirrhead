import React, { useEffect, useState } from "react";
import axios from "axios";

const Lobby = ({ lobby, players: initialPlayers, inviteCode }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [lobbyState, setLobbyState] = useState(lobby || {});
  const [playersState, setPlayersState] = useState(initialPlayers || []);

  useEffect(() => {
    console.log("Component mounted or updated");

    const fetchLobby = async () => {
      try {
        const response = await axios.get(`/lobby/${inviteCode}`);
        console.log("Fetched lobby data:", response.data); // Debugging log
        setLobbyState(response.data.lobby || {});
        setPlayersState(response.data.players || []);
      } catch (err) {
        console.error("Error fetching lobby:", err);
      }
    };

    fetchLobby();
    const interval = setInterval(fetchLobby, 2000);

    return () => {
      console.log("Component unmounted");
      clearInterval(interval);
    };
  }, [inviteCode]);

  useEffect(() => {
    console.log("Players state updated:", playersState); // Debugging log
  }, [playersState]);

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
        {playersState.map((player, index) => (
          <div key={index} className="player bg-gray-700 p-4 rounded-lg mb-2">
            <span className="text-white">
              {player.name} - {player.ready ? "✅ Ready" : "❌ Not Ready"}
            </span>
          </div>
        ))}
      </div>

      {/* Empty Slots */}
      {[...Array(4 - playersState.length)].map((_, index) => (
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