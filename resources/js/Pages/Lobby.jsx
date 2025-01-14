import React, { useEffect, useState } from "react";
import axios from "axios";

const Lobby = ({ inviteCode }) => {
  const [lobby, setLobby] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchLobby = async () => {
      const response = await axios.get(`/lobby/${inviteCode}`);
      setLobby(response.data.lobby);
      setLoading(false);
    };
    fetchLobby();
  }, [inviteCode]);

  const markReady = async () => {
    await axios.post(`/lobby/${inviteCode}/ready`);
    const response = await axios.get(`/lobby/${inviteCode}`);
    setLobby(response.data.lobby);
  };

  const startGame = async () => {
    await axios.post(`/lobby/${inviteCode}/start`);
    window.location.href = `/game/${inviteCode}`;
  };

  if (loading) return <p>Loading...</p>;

  return (
    <div className="lobby-container">
      <h1 className="text-white">Lobby: {inviteCode}</h1>
      
      {/* Players List */}
      <div className="players">
        {lobby.players.map((player, index) => (
          <div key={index} className="player">
            <span>Player {player.id} - {player.ready ? "Ready" : "Not Ready"}</span>
          </div>
        ))}
      </div>
      
      {/* Empty Slots */}
      {[...Array(4 - lobby.players.length)].map((_, index) => (
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
