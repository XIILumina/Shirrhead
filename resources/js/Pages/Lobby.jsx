import React, { useEffect, useState } from "react";
import axios from "axios";
import Pusher from "pusher-js";
import { motion, AnimatePresence } from "framer-motion";

const Lobby = ({ lobby: initialLobby, players: initialPlayers, inviteCode }) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [lobby, setLobby] = useState(initialLobby || {});
  const [players, setPlayers] = useState(initialPlayers || []);

  // Animation variants
  const containerVariants = {
    hidden: { opacity: 0 },
    visible: { 
      opacity: 1,
      transition: { 
        staggerChildren: 0.1 
      }
    }
  };

  const itemVariants = {
    hidden: { y: 20, opacity: 0 },
    visible: { 
      y: 0, 
      opacity: 1,
      transition: { type: "spring", stiffness: 300, damping: 20 }
    }
  };

  const buttonVariants = {
    hover: { scale: 1.05 },
    tap: { scale: 0.95 }
  };

  useEffect(() => {
    // Initial fetch
    const fetchLobby = async () => {
      try {
        setLoading(true);
        const response = await axios.get(`/lobby/${inviteCode}`);
        setLobby(response.data.lobby || {});
        setPlayers(response.data.players || []);
        setLoading(false);
      } catch (err) {
        console.error("Error fetching lobby:", err);
        setError("Failed to fetch lobby");
        setLoading(false);
      }
    };

    fetchLobby();

    // Setup Pusher for real-time updates
    const pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      encrypted: true,
    });

    const channel = pusher.subscribe(`lobby.${inviteCode}`);
    channel.bind("lobby-updated", (data) => {
      setLobby(data.lobby || {});
      setPlayers(data.players || []);
    });

    return () => {
      channel.unbind_all();
      channel.unsubscribe();
    };
  }, [inviteCode]);

  const markReady = async () => {
    try {
      await axios.post(`/lobby/${inviteCode}/ready`);
    } catch (err) {
      console.error("Error marking ready:", err);
      setError("Failed to mark ready");
    }
  };

  const startGame = async () => {
    try {
      await axios.post(`/lobby/${inviteCode}/start`);
      window.location.href = `/game/${inviteCode}`;
    } catch (err) {
      console.error("Error starting game:", err);
      setError("Failed to start game");
    }
  };

  if (loading) return (
    <div className="flex items-center justify-center h-screen bg-gray-900">
      <motion.div
        animate={{ rotate: 360 }}
        transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
        className="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full"
      />
    </div>
  );
  if (error) return <div className="text-red-500 text-center p-4">{error}</div>;

  return (
    <motion.div 
      className="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 p-8 flex items-center justify-center"
      initial="hidden"
      animate="visible"
      variants={containerVariants}
    >
      <motion.div 
        className="bg-gray-800/80 backdrop-blur-md rounded-xl shadow-2xl p-8 w-full max-w-md"
        variants={itemVariants}
      >
        <motion.h1 
          className="text-3xl font-bold text-white mb-2"
          variants={itemVariants}
        >
          Lobby: {inviteCode}
        </motion.h1>
        <motion.p 
          className="text-gray-400 mb-6"
          variants={itemVariants}
        >
          Invite Code: {inviteCode}
        </motion.p>

        {/* Players List */}
        <motion.div className="mb-6" variants={itemVariants}>
          <AnimatePresence>
            {players.map((player, index) => (
              <motion.div
                key={player.id || index}
                className="bg-gray-700/50 p-4 rounded-lg mb-2 flex items-center justify-between border border-gray-600"
                variants={itemVariants}
                initial="hidden"
                animate="visible"
                exit={{ opacity: 0, x: -50 }}
                whileHover={{ scale: 1.02 }}
              >
                <span className="text-white font-medium">{player.name}</span>
                <span className="text-2xl">
                  {player.ready ? "✅" : "❌"}
                </span>
              </motion.div>
            ))}
          </AnimatePresence>
        </motion.div>

        {/* Empty Slots */}
        <AnimatePresence>
          {[...Array(Math.max(0, 4 - players.length))].map((_, index) => (
            <motion.div
              key={`empty-${index}`}
              className="bg-gray-700/30 p-4 rounded-lg mb-2 border border-dashed border-gray-600"
              variants={itemVariants}
              initial="hidden"
              animate="visible"
            >
              <span className="text-gray-500 italic">Waiting for player...</span>
            </motion.div>
          ))}
        </AnimatePresence>

        {/* Buttons */}
        <motion.div className="flex justify-between mt-6" variants={itemVariants}>
          <motion.button
            onClick={markReady}
            className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
            variants={buttonVariants}
            whileHover="hover"
            whileTap="tap"
          >
            Mark Ready
          </motion.button>

          {lobby.status === "ready" && (
            <motion.button
              onClick={startGame}
              className="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors"
              variants={buttonVariants}
              whileHover="hover"
              whileTap="tap"
            >
              Start Game
            </motion.button>
          )}
        </motion.div>
      </motion.div>
    </motion.div>
  );
};

export default Lobby;